<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log; // Add this line
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CallAlphaVantageApiTest extends TestCase
{
    use RefreshDatabase;

    
    /**
     * Test successful fetching and storage of stock data.
     */
    public function test_fetch_and_store_stock_data()
    {
        // Mock the Alpha Vantage API response
        Http::fake(function($request){
            Log::info('Intercepted URL: ' . $request->url());
            return Http::response([
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
            ], 200);
        });

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
        $cachedData = Cache::get('stock:AAPL');
        $this->assertEquals(151.00, $cachedData['price']);
    }

    // /**
    //  * Test API failure handling.
    //  */
    // public function test_handle_api_failure()
    // {
    //     // Mock a failed API response
    //     Http::fake([
    //         'https://www.alphavantage.co/query*' => Http::response(null, 500)
    //     ]);

    //     // Call the command
    //     $exitCode = Artisan::call('app:call-alpha-vantage-api');

    //     // Assert that the command did not complete successfully
    //     $this->assertEquals(1, $exitCode);

    //     // Assert that no data was stored in the database
    //     $this->assertDatabaseMissing('quotes', ['symbol' => 'AAPL']);
    // }

    // /**
    //  * Test rate limiting message from the API.
    //  */
    // public function test_handle_rate_limiting()
    // {
    //     // Mock the Alpha Vantage API response with a rate-limiting message
    //     Http::fake([
    //         'https://www.alphavantage.co/query*' => Http::response([
    //             'Information' => 'The API call frequency is limited.'
    //         ], 200)
    //     ]);

    //     // Call the command
    //     $exitCode = Artisan::call('app:call-alpha-vantage-api');

    //     // Assert that the command ran successfully
    //     $this->assertEquals(0, $exitCode);

    //     // Assert no data is stored in the database
    //     $this->assertDatabaseMissing('quotes', ['symbol' => 'AAPL']);
    // }
}
