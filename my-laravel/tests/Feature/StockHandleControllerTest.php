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
    public function test_get_latest_stock_from_cache()
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

        $response = $this->get("/api/stock/get/$symbol");


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
    public function test_get_latest_stock_from_database()
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

        $response = $this->get("/api/stock/get/$symbol");

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


    /**
     * Test getting real-time stock report from cache.
     */
    public function test_get_real_time_stock_report_from_cache()
    {
        $symbol = 'AAPL';
        $cacheKey = "stockRealTime:$symbol";
        $cachedData = [
            'price_current' => 150.00,
            'price_previous' => 149.00,
            'percentage_change' => 1.34,
            'latest_trading_day' => '2024-10-12',
        ];

        // Mock the cache to return the cached data
        Cache::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn($cachedData);

        $response = $this->get("/api/stock/report/$symbol");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'symbol' => $symbol,
                    'price_current' => $cachedData['price_current'],
                    'price_previous' => $cachedData['price_previous'],
                    'percentage_change' => $cachedData['percentage_change'],
                    'latest_trading_day' => $cachedData['latest_trading_day'],
                ]
            ]);
    }

    /**
     * Test getting real-time stock report from the database.
     */
    public function test_get_real_time_stock_report_from_database()
    {
        $symbol = 'AAPL';
        $cacheKey = "stockRealTime:$symbol";
        $quote = Quote::factory()->create([
            'symbol' => $symbol,
            'price' => '150',
            'previous_close' => '149',
            'latest_trading_day' => '2024-10-12',
        ]);

        $response = $this->get("/api/stock/report/$symbol");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'symbol' => $symbol,
                    'price_current' => $quote->price,
                    'price_previous' => $quote->previous_close,
                    'percentage_change' => (($quote->price - $quote->previous_close) / $quote->previous_close) * 100,
                    'latest_trading_day' => $quote->latest_trading_day->toISOString(),
                ]
            ]);

        // Assert that the data is stored in the cache
        $cachedData = Cache::get($cacheKey);

        // Format both dates for consistency
        $formattedLatestTradingDay = date('Y-m-d', strtotime($quote->latest_trading_day));
        $cachedData['latest_trading_day'] = date('Y-m-d', strtotime($cachedData['latest_trading_day']));

        $this->assertEquals($cachedData, [
            'price_current' => $quote->price,
            'price_previous' => $quote->previous_close,
            'percentage_change' => (($quote->price - $quote->previous_close) / $quote->previous_close) * 100,
            'latest_trading_day' => $formattedLatestTradingDay,
        ]);
    }

    /**
     * Test getting real-time stock report for all symbols.
     */
    public function test_get_real_time_stock_report_all()
    {
        $symbol1 = 'AAPL';
        $symbol2 = 'IBM';

        $quote1 = Quote::factory()->create([
            'symbol' => $symbol1,
            'price' => '150',
            'previous_close' => '149',
            'latest_trading_day' => '2024-10-12',
        ]);

        $quote2 = Quote::factory()->create([
            'symbol' => $symbol2,
            'price' => '200',
            'previous_close' => '190',
            'latest_trading_day' => '2024-10-12',
        ]);

        $response = $this->get("/api/stock/report");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    [
                        'status' => 'success',
                        "data" => [
                            'symbol' => $symbol1,
                            'price_current' => $quote1->price,
                            'price_previous' => $quote1->previous_close,
                            'percentage_change' => (($quote1->price - $quote1->previous_close) / $quote1->previous_close) * 100,
                            'latest_trading_day' => $quote1->latest_trading_day->toISOString(),
                        ]
                    ],
                    [
                        'status' => 'success',
                        "data" => [
                            'symbol' => $symbol2,
                            'price_current' => $quote2->price,
                            'price_previous' => $quote2->previous_close,
                            'percentage_change' => (($quote2->price - $quote2->previous_close) / $quote2->previous_close) * 100,
                            'latest_trading_day' => $quote2->latest_trading_day->toISOString(),
                        ]
                    ],
                ]
            ]);
    }

}