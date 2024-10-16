<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Quote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

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
        $apiUrl = config('services.alpha_vantage.api_url');
        $apiKey = config('services.alpha_vantage.api_key');
        $stocks = config('services.alpha_vantage.stocks');
        $redisCacheDuration = config('services.alpha_vantage.cache_duration') ?? 60;

        $function = 'GLOBAL_QUOTE';
        $allStocks = [];

        $this->info('-------------START JOB PROCESSING-----------------');
        Log::debug('-------------START JOB PROCESSING-----------------');

        // process each stock - do not insert yet
        foreach ($stocks as $stock) {

            $this->info('Fetching stock prices for ' . $stock);
            Log::debug('Fetching stock prices for ' . $stock);

            //return;

            // handle network issues
            try {
                $response = Http::get("{$apiUrl}?function={$function}&symbol={$stock}&apikey={$apiKey}");
            } catch (Exception $ex) {
                Log::error($ex->getMessage());

                // stop command until next scheduled
                return 2;
            }

            if ($response->getStatusCode() == 200) {

                Log::debug('Inside 200');

                $data = json_decode($response->getBody(), true);

                // rate limit handling
                if (isset($data['Information'])) {
                    $this->error($data['Information']);
                    Log::error($data['Information']);

                    return 3;
                }

                Log::debug('Data: ' . json_encode($data));

                // check if data is valid
                if (!isset($data['Global Quote']) || !isset($data['Global Quote']['01. symbol'])) {
                    $this->error('Invalid data received');
                    Log::error('Invalid data received');

                    continue;
                }

                $this->info('Stock price data fetched successfully');
                Log::debug('Stock price data fetched successfully');

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
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $allStocks[$quoteData['symbol']] = $quoteData;

                Cache::store('redis')->put("stock:{$quoteData['symbol']}", $quoteData, $redisCacheDuration);

                $this->info("Data stored in Redis cache");
                Log::debug("Data stored in Redis cache");

            } else {
                $this->error('Api request failed, status code: ' . $response->getStatusCode());
                Log::error('Api request failed, status code: ' . $response->getStatusCode());
                return 1;
            }
        }

        // batch insert data
        if (count($allStocks) > 0) {
            Quote::insert($allStocks);

            $this->info('Storing data in database');
            Log::debug('Storing data in database');
        }

        Log::debug("-------------END JOB PROCESSING-----------------\n\n");

        return 0;
    }
}
