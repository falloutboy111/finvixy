<?php

namespace Tests\Unit\Services;

use App\Services\TextractService;
use Aws\Textract\TextractClient;
use Mockery\MockInterface;
use Tests\TestCase;

class TextractServiceTest extends TestCase
{
    /**
     * Test 1: detectText with valid response
     */
    public function test_detect_text_extracts_lines_from_blocks()
    {
        $mockClient = $this->createMockClient([
            'Blocks' => [
                ['BlockType' => 'PAGE', 'Text' => 'full page'],
                ['BlockType' => 'LINE', 'Text' => 'First Line'],
                ['BlockType' => 'WORD', 'Text' => 'Word'],
                ['BlockType' => 'LINE', 'Text' => 'Second Line'],
            ]
        ]);

        $service = $this->createServiceWithMock($mockClient);
        $result = $service->detectText('fake image bytes');

        $this->assertStringContainsString('First Line', $result);
        $this->assertStringContainsString('Second Line', $result);
        $this->assertStringNotContainsString('full page', $result);
    }

    /**
     * Test 2: detectText with empty blocks
     */
    public function test_detect_text_handles_empty_blocks()
    {
        $mockClient = $this->createMockClient(['Blocks' => []]);
        $service = $this->createServiceWithMock($mockClient);
        $result = $service->detectText('fake image bytes');

        $this->assertEmpty($result);
    }

    /**
     * Test 3: detectText with no LINE blocks
     */
    public function test_detect_text_filters_non_line_blocks()
    {
        $mockClient = $this->createMockClient([
            'Blocks' => [
                ['BlockType' => 'WORD', 'Text' => 'Word'],
                ['BlockType' => 'PAGE', 'Text' => 'Page'],
            ]
        ]);

        $service = $this->createServiceWithMock($mockClient);
        $result = $service->detectText('fake image bytes');

        $this->assertEmpty(trim($result));
    }

    /**
     * Test 4: detectText with multi-line output
     */
    public function test_detect_text_joins_lines_with_newlines()
    {
        $mockClient = $this->createMockClient([
            'Blocks' => [
                ['BlockType' => 'LINE', 'Text' => 'Line 1'],
                ['BlockType' => 'LINE', 'Text' => 'Line 2'],
                ['BlockType' => 'LINE', 'Text' => 'Line 3'],
            ]
        ]);

        $service = $this->createServiceWithMock($mockClient);
        $result = $service->detectText('fake image bytes');

        $lines = explode("\n", $result);
        $this->assertCount(3, $lines);
        $this->assertEquals('Line 1', $lines[0]);
        $this->assertEquals('Line 3', $lines[2]);
    }

    private function createMockClient(array $response): MockInterface
    {
        $mockResult = \Mockery::mock('result');
        $mockResult->shouldReceive('get')->with('Blocks')->andReturn($response['Blocks'] ?? null);

        return \Mockery::mock(TextractClient::class)
            ->shouldReceive('detectDocumentText')
            ->andReturn($mockResult)
            ->getMock();
    }

    private function createServiceWithMock(MockInterface $mockClient): TextractService
    {
        $service = new TextractService();
        \Closure::bind(function() use ($mockClient) {
            $this->client = $mockClient;
        }, $service, TextractService::class)();

        return $service;
    }
}
