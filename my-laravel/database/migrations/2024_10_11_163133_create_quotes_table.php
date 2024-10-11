<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('symbol');
            $table->decimal('open', 10, 4);
            $table->decimal('high', 10, 4);
            $table->decimal('low', 10, 4);
            $table->decimal('price', 10, 4);
            $table->bigInteger('volume');
            $table->date('latest_trading_day');
            $table->decimal('previous_close', 10, 4);
            $table->decimal('change', 10, 4);
            $table->string('change_percent');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quotes');
    }
};
