<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('provinces')) {
            Schema::create('provinces', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name', 255);
                $table->boolean('is_active')->default(true);
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
