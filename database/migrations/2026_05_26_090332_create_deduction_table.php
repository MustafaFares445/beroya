<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('deduction')) {
            Schema::create('deduction', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->integer('amount');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('accountant_id');

                $table->foreign('accountant_id')
                    ->references('id')
                    ->on('accountants')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('deduction');
    }
};
