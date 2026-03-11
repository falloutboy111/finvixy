<?php

namespace Tests\Unit\Services;

use App\Services\BedrockAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DateValidationTest extends TestCase
{
    use RefreshDatabase;

    private BedrockAgentService $bedrockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bedrockService = app(BedrockAgentService::class);
    }

    /**
     * Test 1: Future date → converted to today
     */
    public function test_future_date_converted_to_today()
    {
        // Use reflection to call the protected method
        $reflection = new \ReflectionClass($this->bedrockService);
        $method = $reflection->getMethod('validateAndFixDate');
        $method->setAccessible(true);

        $futureDate = Carbon::tomorrow()->toDateString();
        $result = $method->invoke($this->bedrockService, $futureDate);

        $this->assertEquals(today()->toDateString(), $result);
    }

    /**
     * Test 2: Today's date → used as-is
     */
    public function test_today_date_used_as_is()
    {
        $reflection = new \ReflectionClass($this->bedrockService);
        $method = $reflection->getMethod('validateAndFixDate');
        $method->setAccessible(true);

        $todayDate = today()->toDateString();
        $result = $method->invoke($this->bedrockService, $todayDate);

        $this->assertEquals($todayDate, $result);
    }

    /**
     * Test 3: Past date (30 days ago) → used as-is
     */
    public function test_past_date_used_as_is()
    {
        $reflection = new \ReflectionClass($this->bedrockService);
        $method = $reflection->getMethod('validateAndFixDate');
        $method->setAccessible(true);

        $pastDate = Carbon::now()->subDays(30)->toDateString();
        $result = $method->invoke($this->bedrockService, $pastDate);

        $this->assertEquals($pastDate, $result);
    }

    /**
     * Test 4: Invalid/null date → default to today
     */
    public function test_invalid_null_date_defaults_to_today()
    {
        $reflection = new \ReflectionClass($this->bedrockService);
        $method = $reflection->getMethod('validateAndFixDate');
        $method->setAccessible(true);

        // Test null
        $result = $method->invoke($this->bedrockService, null);
        $this->assertEquals(today()->toDateString(), $result);

        // Test empty string
        $result = $method->invoke($this->bedrockService, '');
        $this->assertEquals(today()->toDateString(), $result);

        // Test invalid format
        $result = $method->invoke($this->bedrockService, 'not-a-date');
        $this->assertEquals(now()->toDateString(), $result);
    }
}
