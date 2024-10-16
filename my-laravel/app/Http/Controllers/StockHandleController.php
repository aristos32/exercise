<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StockHandleController extends Controller
{
    /**
     * get latest stock price as taken from Alpha Vantage API, no processing done
     * @param mixed $symbol
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getLatestStockPrice($symbol)
    {
        // get stock price from cache
        $cacheKey = "stock:$symbol";
        $cachedData = Cache::store('redis')->get($cacheKey);

        if ($cachedData) {
            Log::info("Stock data for {$symbol} found in cache");
            return response()->json([
                'status' => 'success',
                'data' => $cachedData,
                'source' => 'cache'
            ]);
        }

        // if not found in cache, get latest quote from database
        $quote = Quote::select('symbol', 'open', 'high', 'low', 'price', 'volume', 'latest_trading_day', 'previous_close', 'change', 'change_percent')->where('symbol', $symbol)->latest('id')->first();
        if ($quote) {

            // Convert the model to an array with only relevant fields
            $quoteData = $quote->only(['symbol', 'open', 'high', 'low', 'price', 'volume', 'latest_trading_day', 'previous_close', 'change', 'change_percent']);

            // Format the created_at and updated_at fields
            $quoteData['latest_trading_day'] = $quote->latest_trading_day->format('Y-m-d');

            // store data in redis cache
            Cache::store('redis')->put($cacheKey, $quoteData, 60);

            Log::info("Stock data for {$symbol} found in database, and stored in cache");
            return response()->json([
                'status' => 'success',
                'data' => $quote,
                'source' => 'database'
            ]);
        }

        // if not found in cache or database
        return response()->json(['error' => 'Stock not found'], 404);
    }


    /**
     * Get real-time stock price and percentage change.
     *
     * @param string $symbol
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRealTimeStockReportForSymbol($symbol)
    {
        // Try to retrieve stock data from cache - differnt key for real-time data
        $cacheKey = "stockRealTime:{$symbol}";
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            // If data is found in cache, use it
            $priceCurrent = $cachedData['price_current'];
            $pricePrevious = $cachedData['price_previous'];
            $percentageChange = $cachedData['percentage_change'];
            $latestTradingDay = $cachedData['latest_trading_day'];
        } else {

            // If not found in cache, fetch from database
            $quote = Quote::where('symbol', $symbol)->latest('id')->first();

            if (!$quote) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stock data not found'
                ], 404);
            }

            // Calculate percentage change
            $priceCurrent = $quote->price;
            $pricePrevious = $quote->previous_close;

            $percentageChange = $this->calculatePercentageChange($priceCurrent, $pricePrevious);

            $latestTradingDay = $quote->latest_trading_day;

            // Store the data in cache for future requests
            $cachedData = [
                'price_current' => $priceCurrent,
                'price_previous' => $pricePrevious,
                'percentage_change' => $percentageChange,
                'latest_trading_day' => $latestTradingDay
            ];

            // Cache the data for a specific period (e.g., 1 minute)
            Cache::put($cacheKey, $cachedData, 60);  // Cache for 60 seconds

        }

        // Return the stock data along with percentage change
        return response()->json([
            'status' => 'success',
            'data' => [
                'symbol' => $symbol,
                'price_current' => $priceCurrent,
                'price_previous' => $pricePrevious,
                'percentage_change' => $percentageChange,
                'latest_trading_day' => $latestTradingDay,
            ]
        ]);
    }

    /**
     * Get real-time stock price and percentage change for all symbols
     *
     * @param string $symbol
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRealTimeStockReportAll()
    {
        // Get all unique symbols from the database
        $symbols = Quote::select('symbol')->distinct()->pluck('symbol');

        $stockReports = [];

        foreach ($symbols as $symbol) {
            $stockReport = $this->getRealTimeStockReportForSymbol($symbol);
            $stockReports[] = $stockReport->original;
        }

        return response()->json([
            'status' => 'success',
            'data' => $stockReports
        ]);
    }

    /**
     * calculate Percentage Change
     * @param mixed $priceCurrent
     * @param mixed $pricePrevious
     * @return float|int
     */
    private function calculatePercentageChange($priceCurrent, $pricePrevious)
    {
        return ($pricePrevious != 0) ? (($priceCurrent - $pricePrevious) / $pricePrevious) * 100 : 0;
    }
}
