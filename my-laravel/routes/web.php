<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    return view('test');
});


use Illuminate\Support\Facades\Cache;

Route::get('/redis-test', function () {
    // Store a value in Redis
    Cache::store('redis')->put('key', 'This is a test value 4', 10); // Cache for 10 minutes
    Cache::store('redis')->put('key2', 'This is a test value 4', 10); // Cache for 10 minutes

    // Retrieve the value
    return Cache::store('redis')->get('key');
});