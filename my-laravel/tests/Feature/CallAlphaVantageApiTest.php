<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log; // Add this line
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

class CallAlphaVantageApiTest extends TestCase
{
    use RefreshDatabase;

    private $apiUrl = 'https://www.alphavantage.co/query';

    /**
     * Test successful fetching and storage of stock data.
     */
    public function test_fetch_and_store_stock_data()
    {

        Http::fake([
            "{$this->apiUrl}*" => Http::response([
                'Global Quote' => [
                    '01. symbol' => 'AAPL',
                    '02. open' => '150.00',
                    '03. high' => '152.00',
                    '04. low' => '148.00',
                    '05. price' => '151.00',
                    '06. volume' => '1200000',
                    '07. latest trading day' => '2024-10-12',
                    '08. previous close' => '149.00',
                    '09. change' => '2.00',
                    '10. change percent' => '1.34%',
                ]
            ], 200)
        ]);

        Http::fake(function ($request) {
            Log::info('Intercepted URL: ' . $request->url());
            return Http::response([ /* fake response */], 200);
        });

        // Mock the Cache facade
        Cache::shouldReceive('store')
            ->andReturnSelf(); // Return the Cache instance itself

        // Mock the Cache facade for the 'put' method
        // use Mockery to match only part of the data
        Cache::shouldReceive('put')
            ->with(
                'stock:AAPL',
                \Mockery::on(function ($data) {
                    return is_array($data) &&
                        isset($data['symbol']) && $data['symbol'] === 'AAPL' &&
                        isset($data['price']) && $data['price'] == 151;
                }),
                \Mockery::any()  // The TTL can be any value
            );

        // Mock the Cache facade
        Cache::shouldReceive('get')
            ->once()
            ->with('stock:AAPL')
            ->andReturn([
                'symbol' => 'AAPL',
                'price' => 151.00
            ]);


        // Call the command
        $exitCode = Artisan::call('app:call-alpha-vantage-api');

        // Assert that the command ran successfully
        $this->assertEquals(0, $exitCode);

        // Assert that the data was stored in the database
        $this->assertDatabaseHas('quotes', [
            'symbol' => 'AAPL',
            'price' => 151.00
        ]);


        // Assert that the data was cached in Redis
        $cachedData = Cache::store('redis')->get('stock:AAPL');
        $this->assertEquals(151.00, $cachedData['price']);
    }

    /**
     * Test API failure handling.
     */
    public function test_handle_api_failure()
    {
        // Mock a failed API response
        Http::fake([
            "{$this->apiUrl}*" => Http::response([
                null
            ], 500)
        ]);

        // Call the command
        $exitCode = Artisan::call('app:call-alpha-vantage-api');

        // Assert that the command did not complete successfully
        $this->assertEquals(1, $exitCode);

        // Assert that no data was stored in the database
        $this->assertDatabaseMissing('quotes', ['symbol' => 'AAPL']);

        // Assert that no data was cached in Redis
        $this->assertNull(Cache::get('stock:AAPL'));
    }

    /**
     * Test rate limiting message from the API.
     */
    public function test_handle_rate_limiting()
    {
        // Mock the Alpha Vantage API response with a rate-limiting message
        Http::fake([
            "{$this->apiUrl}*" => Http::response([
                'Information' => 'The API call frequency is limited.'
            ], 200)
        ]);

        // Call the command
        $exitCode = Artisan::call('app:call-alpha-vantage-api');

        // Assert that the command ran successfully
        $this->assertEquals(3, $exitCode);

        // Assert no data is stored in the database
        $this->assertDatabaseMissing('quotes', ['symbol' => 'AAPL']);

        // Assert no data is cached in Redis
        $this->assertNull(Cache::get('stock:AAPL'));
    }

    /**
     * Test network issue handling.
     */
    public function test_handle_network_issue()
    {
        // Mock a network issue
        Http::fake([
            "{$this->apiUrl}*" => function () {
                throw new \Exception('Network error');
            }
        ]);

        // Call the command
        $exitCode = Artisan::call('app:call-alpha-vantage-api');

        // Assert that the command did not complete successfully
        $this->assertEquals(2, $exitCode);

        // Assert that no data was stored in the database
        $this->assertDatabaseMissing('quotes', ['symbol' => 'AAPL']);

        // Assert that no data was cached in Redis
        $this->assertNull(Cache::get('stock:AAPL'));
    }

}
