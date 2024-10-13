<?php

use App\Http\Controllers\StockHandleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/stock/{symbol}', [StockHandleController::class, 'getLatestStockPrice']);

Route::get('/stock/report/all', [StockHandleController::class, 'getRealTimeStockReportAll']);
Route::get('/stock/report/symbol/{symbol}', [StockHandleController::class, 'getRealTimeStockReportForSymbol']);
