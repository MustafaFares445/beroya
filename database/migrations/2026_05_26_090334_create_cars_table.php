<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cars')) {
            Schema::create('cars', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedInteger('market_id');
                $table->unsignedInteger('model_id');
                $table->string('year', 30);
                $table->string('gasoline', 30);
                $table->string('engine', 30);
                $table->string('transmission', 50);
                $table->string('color', 50);
                $table->string('distance', 100);
                $table->string('imported', 50);
                $table->text('spray');
                $table->string('status', 200);
                $table->text('description');
                $table->string('plateNumber', 50);
                $table->text('notes');
                $table->integer('price');
                $table->text('possession');
                $table->string('owner_name', 50);
                $table->string('owner_phone', 15);
                $table->unsignedInteger('gallery_id');
                $table->text('image_1');
                $table->text('image_2');
                $table->text('image_3');
                $table->text('image_4');
                $table->text('image_5');
                $table->text('image_6');
                $table->integer('car_sale_state')->default(1);

                $table->foreign('market_id')
                    ->references('id')
                    ->on('markets')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->foreign('model_id')
                    ->references('id')
                    ->on('models')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->foreign('gallery_id')
                    ->references('id')
                    ->on('galleries')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
