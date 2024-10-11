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
}
