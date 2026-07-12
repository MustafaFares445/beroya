<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('markets')) {
            Schema::create('markets', function (Blueprint $table): void {
                $table->increments('id');
                $table->text('name');
                $table->text('image');
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('markets');
    }
};
