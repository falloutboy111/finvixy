<?php

namespace Tests\Unit\Services;

use App\Models\ExpenseCategory;
use App\Services\BedrockAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;
use Aws\BedrockAgentRuntime\BedrockAgentRuntimeClient;

class BedrockAgentServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 5: parseExpenseDocument extracts valid JSON response
     */
    public function test_parse_expense_document_with_valid_json()
    {
        $org = \App\Models\Organisation::factory()->create();

        $validJson = json_encode([
            'vendor_name' => 'Woolworths',
            'invoice_number' => 'INV-12345',
            'date' => '2024-03-10',
            'total_amount' => 250.50,
            'currency' => 'ZAR',
            'category' => 'Groceries',
            'tax_amount' => 25.05,
            'line_items' => [
                ['item_name' => 'Bread', 'quantity' => 1, 'unit_price' => 15.99, 'total_price' => 15.99],
            ],
        ]);

        $mockClient = $this->createMockBedrockClient($validJson);
        $service = $this->createServiceWithMock($mockClient);

        $result = $service->parseExpenseDocument('Receipt text here', $org->id);

        $this->assertEquals('Woolworths', $result['vendor_name']);
        $this->assertEquals(250.50, $result['total_amount']);
        $this->assertEquals('ZAR', $result['currency']);
        $this->assertCount(1, $result['line_items']);
    }

    /**
     * Test 6: parseExpenseDocument with markdown-wrapped JSON
     */
    public function test_parse_expense_document_unwraps_markdown()
    {
        $org = \App\Models\Organisation::factory()->create();

        $markdownJson = '```json
        {
            "vendor_name": "Pick n Pay",
            "total_amount": 500,
            "currency": "ZAR",
            "category": null,
            "invoice_number": null,
            "date": null,
            "tax_amount": null,
            "line_items": []
        }
        ```';

        $mockClient = $this->createMockBedrockClient($markdownJson);
        $service = $this->createServiceWithMock($mockClient);

        $result = $service->parseExpenseDocument('Receipt', $org->id);

        $this->assertEquals('Pick n Pay', $result['vendor_name']);
        $this->assertEquals(500, $result['total_amount']);
    }

    /**
     * Test 7: parseExpenseDocument returns fallback on invalid JSON
     */
    public function test_parse_expense_document_fallback_on_invalid_json()
    {
        $org = \App\Models\Organisation::factory()->create();

        $invalidJson = 'This is not JSON at all!';

        $mockClient = $this->createMockBedrockClient($invalidJson);
        $service = $this->createServiceWithMock($mockClient);

        $result = $service->parseExpenseDocument('Receipt', $org->id);

        $this->assertEquals('Unknown Vendor', $result['vendor_name']);
        $this->assertEquals(0, $result['total_amount']);
        $this->assertEmpty($result['line_items']);
    }

    /**
     * Test 8: parseExpenseDocument with missing optional fields
     */
    public function test_parse_expense_document_handles_missing_fields()
    {
        $org = \App\Models\Organisation::factory()->create();

        $minimalJson = json_encode([
            'vendor_name' => 'Shop',
            'total_amount' => 100,
        ]);

        $mockClient = $this->createMockBedrockClient($minimalJson);
        $service = $this->createServiceWithMock($mockClient);

        $result = $service->parseExpenseDocument('Receipt', $org->id);

        $this->assertEquals('Shop', $result['vendor_name']);
        $this->assertNull($result['invoice_number']);
        $this->assertNull($result['date']);
        $this->assertNull($result['tax_amount']);
    }

    /**
     * Test 9: parseExpenseDocument with zero total_amount
     */
    public function test_parse_expense_document_with_zero_amount()
    {
        $org = \App\Models\Organisation::factory()->create();

        $json = json_encode([
            'vendor_name' => 'Store',
            'total_amount' => 0,
            'currency' => 'ZAR',
        ]);

        $mockClient = $this->createMockBedrockClient($json);
        $service = $this->createServiceWithMock($mockClient);

        $result = $service->parseExpenseDocument('Receipt', $org->id);

        $this->assertEquals(0, $result['total_amount']);
    }

    private function createMockBedrockClient(string $response): MockInterface
    {
        $mockResult = \Mockery::mock('result');
        $mockResult->shouldReceive('get')->with('completion')->andReturn([
            ['chunk' => ['bytes' => $response]],
        ]);

        return \Mockery::mock(BedrockAgentRuntimeClient::class)
            ->shouldReceive('invokeAgent')
            ->andReturn($mockResult)
            ->getMock();
    }

    private function createServiceWithMock(MockInterface $mockClient): BedrockAgentService
    {
        $service = new BedrockAgentService();
        \Closure::bind(function() use ($mockClient) {
            $this->client = $mockClient;
        }, $service, BedrockAgentService::class)();

        return $service;
    }
}
