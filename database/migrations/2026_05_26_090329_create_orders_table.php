<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('client_name', 50);
            $table->string('client_phone', 20);
            $table->string('car_market', 20);
            $table->string('car_model', 20);
            $table->string('year', 10);
            $table->integer('price_low');
            $table->integer('price_high');
            $table->string('order_state', 30);
            $table->text('order_notes');
            $table->string('user_name', 200);
            $table->string('gallery_name', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
