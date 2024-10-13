<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'open',
        'high',
        'low',
        'price',
        'volume',
        'latest_trading_day',
        'previous_close',
        'change',
        'change_percent',
    ];

    protected $casts = [
        'price' => 'string',
        'open' => 'string',
        'high' => 'string',
        'low' => 'string',
        'volume' => 'integer',
        'latest_trading_day' => 'date',
        'previous_close' => 'string',
        'change' => 'string',
        'change_percent' => 'string',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
