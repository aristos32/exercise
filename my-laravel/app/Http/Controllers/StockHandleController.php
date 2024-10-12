<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Support\Facades\Cache;

class StockHandleController extends Controller
{
    public function getStock($symbol)
    {
        // get stock price from cache
        $cacheKey = "stock:$symbol";
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            return response()->json([
                'status' => 'success',
                'data' => $cachedData,
                'source' => 'cache'
            ]);
        }

        // if not found in cache, get latest quote from database
        $quote = Quote::where('symbol', $symbol)->latest('id')->first();
        if($quote) {
            // Convert the model to an array with only relevant fields
            $quoteData = $quote->only(['symbol', 'open', 'high', 'low', 'price', 'volume', 'latest_trading_day', 'previous_close', 'change', 'change_percent']);

            // store data in redis cache
            Cache::put($cacheKey, $quoteData, 60);
            return response()->json([
                'status' => 'success',
                'data' => $quote,
                'source' => 'database'
            ]);
        }

        // if not found in cache or database
        return response()->json(['error' => 'Stock not found'], 404);
    }
}
