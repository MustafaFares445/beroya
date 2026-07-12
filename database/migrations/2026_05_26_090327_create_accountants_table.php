<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accountants')) {
            Schema::create('accountants', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedInteger('user_id');
                $table->string('user_name', 200);
                $table->string('user_position', 100);
                $table->string('user_gallery', 100);
                $table->integer('sales_count');
                $table->integer('sales_amount');
                $table->integer('deduction_amount');
                $table->integer('working_days_count');
                $table->integer('salary');
                $table->unsignedBigInteger('week_id');
                $table->string('year', 20);
                $table->integer('total_amount')->default(0);
                $table->string('received', 1)->default('0');

                $table->index(['user_id', 'week_id']);
                $table->index(['week_id', 'year']);
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('accountants');
    }
};
