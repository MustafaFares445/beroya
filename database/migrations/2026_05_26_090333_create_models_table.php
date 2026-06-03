<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('models', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name', 30);
            $table->unsignedInteger('market_id');

            $table->foreign('market_id')
                ->references('id')
                ->on('markets')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('models');
    }
};
