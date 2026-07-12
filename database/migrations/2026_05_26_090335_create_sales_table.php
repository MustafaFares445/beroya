<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->integer('user_comiss');
            $table->text('user_note');
            $table->string('buyer_name', 30);
            $table->string('buyer_phone')->default('0');
            $table->integer('owner_comiss')->nullable();
            $table->integer('owner_comiss_payed');
            $table->integer('buyer_comiss');
            $table->integer('buyer_comiss_payed');
            $table->text('owner_id_image');
            $table->text('buyer_id_image');
            $table->text('contract_image');
            $table->date('date');
            $table->unsignedBigInteger('week_id');
            $table->string('car_brand')->nullable();
            $table->string('car_model')->nullable();
            $table->string('car_name', 100);
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('car_id');
            $table->string('car_number')->nullable();
            $table->integer('price');
            $table->string('employee_name')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('owner_phone', 50)->nullable();
            $table->string('status', 50)->default('hold');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->string('approved', 1)->default('0');

            $table->foreign('week_id')
                ->references('id')
                ->on('weeks')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
