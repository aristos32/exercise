<?php

namespace Database\Factories;

use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition()
    {
        return [
            'symbol' => $this->faker->word,
            'price' => $this->faker->randomFloat(2, 100, 200),
            'open' => $this->faker->randomFloat(2, 100, 200),
            'high' => $this->faker->randomFloat(2, 100, 200),
            'low' => $this->faker->randomFloat(2, 100, 200),
            'volume' => $this->faker->numberBetween(1000000, 5000000),
            'latest_trading_day' => $this->faker->date,
            'previous_close' => $this->faker->randomFloat(2, 100, 200),
            'change' => $this->faker->randomFloat(2, -10, 10),
            'change_percent' => $this->faker->randomFloat(2, -5, 5) . '%',
        ];
    }
}


