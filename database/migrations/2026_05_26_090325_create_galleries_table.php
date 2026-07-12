<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('galleries')) {
            Schema::create('galleries', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name', 50);
                $table->text('address');
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('galleries');
    }
};
