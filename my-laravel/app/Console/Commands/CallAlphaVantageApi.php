<?php

namespace App\Console\Commands;

use App\Models\Quote;
use Cache;
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
        $function = 'GLOBAL_QUOTE';
        $stocks = config('alphaVantage.stocks');

        Log::debug('-------------START JOB PROCESSING-----------------');

        // call api
        $client = new Client();
        foreach ($stocks as $stock) {
            $this->info('Fetching stock prices for ' . $stock);
            Log::debug('Fetching stock prices for ' . $stock);
            $response = $client->get("https://www.alphavantage.co/query?function={$function}&symbol={$stock}&apikey={$apiKey}");

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody(), true);
                $this->info('Stock price data fetched successfully');
                // Process or store the $data
                Log::debug("Data fetched successfully: " . json_encode($data));

                // check if data is valid
                if (!isset($data['Global Quote']) || !isset($data['Global Quote']['01. symbol'])) {
                    $this->error('Failed to fetch stock prices');
                    continue;
                }

                $quoteData = [
                    'symbol' => $data['Global Quote']['01. symbol'],
                    'open' => (float) $data['Global Quote']['02. open'],
                    'high' => (float) $data['Global Quote']['03. high'],
                    'low' => (float) $data['Global Quote']['04. low'],
                    'price' => (float) $data['Global Quote']['05. price'],
                    'volume' => (int) $data['Global Quote']['06. volume'],
                    'latest_trading_day' => $data['Global Quote']['07. latest trading day'],
                    'previous_close' => (float) $data['Global Quote']['08. previous close'],
                    'change' => (float) $data['Global Quote']['09. change'],
                    'change_percent' => $data['Global Quote']['10. change percent'],
                ];

                // store data in database
                Quote::create($quoteData);

                // store data in redis cache
                $cacheKey = 'stock:' . $quoteData['symbol'];
                Cache::put($cacheKey, $quoteData, config('services.alpha_vantage.cache_duration_minutes'));
                Log::debug("Data stored in Redis cache");
                $this->info("Data stored in Redis cache");

            } else {
                $this->error('Failed to fetch stock prices');
            }
        }

        Log::debug("-------------END JOB PROCESSING-----------------\n\n");

    }
}
