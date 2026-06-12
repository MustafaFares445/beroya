<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_categories', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_categories');
    }
};
