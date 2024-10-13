<?php

use App\Http\Controllers\StockHandleController;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StockHandleControllerTest extends TestCase
{
    use RefreshDatabase;

    private $apiUrl = 'https://www.alphavantage.co/query';

    /**
     * Test getting stock data from cache.
     */
    public function test_get_stock_from_cache()
    {
        $symbol = 'AAPL';
        $cacheKey = "stock:$symbol";
        $cachedData = [
            'symbol' => 'AAPL',
            'open' => '150.00',
            'high' => '152.00',
            'low' => '148.00',
            'price' => '151.00',
            'volume' => '1200000',
            'latest_trading_day' => '2024-10-12',
            'previous_close' => '149.00',
            'change' => '2.00',
            'change_percent' => '1.34%',
        ];

        // Mock the cache to return the cached data
        Cache::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn($cachedData);

        $response = $this->get("/api/stock/$symbol");


        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => $cachedData,
                'source' => 'cache'
            ]);
    }

    /**
     * Test getting stock data from the database.
     */
    public function test_get_stock_from_database()
    {
        $symbol = 'IBM';
        $cacheKey = "stock:$symbol";
        $quote = Quote::factory()->create([
            'symbol' => $symbol,
            'price' => '151',
            'open' => '189.52',
            'high' => '186.68',
            'low' => '198.01',
            'latest_trading_day' => '2024-10-12',
            'previous_close' => '111.84',
            'change' => '-8.41',
            'change_percent' => '-3.75%',
        ]);

        $response = $this->get("/api/stock/$symbol");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => $quote->toArray(),
                'source' => 'database'
            ]);

        $quoteArray = $quote->toArray();

        // Adjust the actual response data to match the expected format
        $quoteArray['latest_trading_day'] = date('Y-m-d', strtotime($quoteArray['latest_trading_day']));
        // Assert that the data is stored in the cache
        $cachedData = Cache::get($cacheKey);

        $this->assertEquals($quoteArray, $cachedData);
    }


}