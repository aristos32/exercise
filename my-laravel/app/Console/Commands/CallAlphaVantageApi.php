<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class CallAlphaVantageApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:call-alpha-vantage-api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Call Alpha Vantage API to fetch stock prices and update our system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = config('services.alpha_vantage.api_key');
        Log::debug('Fetching prices...key: ' . $apiKey);

        // call api
        $client = new Client();
        $response = $client->get("https://www.alphavantage.co/query?function=TIME_SERIES_INTRADAY&symbol=IBM&interval=1min&apikey={$apiKey}");

        if ($response->getStatusCode() == 200) {
            $data = json_decode($response->getBody(), true);
            $this->info('Stock price data fetched successfully');
            // Process or store the $data

            // store data in database

            // store date in redis cache

        } else {
            $this->error('Failed to fetch stock prices');
        }

    }
}
