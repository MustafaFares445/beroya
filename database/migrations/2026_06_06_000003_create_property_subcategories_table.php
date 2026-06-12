<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_subcategories', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('property_category_id');
            $table->string('name', 255);

            $table->foreign('property_category_id')
                ->references('id')
                ->on('property_categories')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_subcategories');
    }
};
