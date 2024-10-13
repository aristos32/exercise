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

                $data = json_decode($response->getBody(), true);

                // rate limit handling
                if (isset($data['Information'])) {
                    $this->error($data['Information']);
                    Log::error($data['Information']);

                    return 3;
                }

                // check if data is valid
                if (!isset($data['Global Quote']) || !isset($data['Global Quote']['01. symbol'])) {
                    $this->error('Failed to fetch stock prices');
                    Log::error('Failed to fetch stock prices');

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

            } else {
                $this->error('Failed to fetch stock prices');
                return 1;
            }
        }

        // batch insert data
        if (count($allStocks) > 0) {
            // insert data in database
            Quote::insert($allStocks);

            $this->info('Storing data in database');
            Log::debug('Storing data in database');

            // Use Redis pipeline to perform batch insert
            Redis::pipeline(function ($pipe) use ($allStocks) {
                foreach ($allStocks as $key => $value) {
                    $pipe->set("stock:{$key}", json_encode($value));
                    $pipe->expire($key, 3600);
                }
            });

            //Cache::put($cacheKey, $quoteData, $redisCacheDuration);

            $this->info("Data stored in Redis cache");
            Log::debug("Data stored in Redis cache");
        }

        Log::debug("-------------END JOB PROCESSING-----------------\n\n");

        return 0;
    }
}
