<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_submissions', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('offer_number', 100)->unique();
            $table->unsignedInteger('province_id');
            $table->unsignedInteger('office_id')->nullable();
            $table->unsignedInteger('main_category_id');
            $table->unsignedInteger('subcategory_id');
            $table->string('property_nature', 255);
            $table->string('area', 255);
            $table->string('district', 255);
            $table->text('address');
            $table->string('building', 255);
            $table->string('floor', 50);
            $table->string('direction', 100);
            $table->unsignedInteger('rooms_count');
            $table->unsignedInteger('area_size');
            $table->integer('price');
            $table->string('ownership_type', 100);
            $table->string('offer_type', 100);
            $table->string('rent_duration', 100)->nullable();
            $table->string('owner_name', 255);
            $table->string('owner_phone', 30);
            $table->string('status', 100)->default('pending');
            $table->text('reject_reason')->nullable();
            $table->unsignedInteger('published_property_id')->nullable()->unique();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('province_id')
                ->references('id')
                ->on('provinces')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('office_id')
                ->references('id')
                ->on('real_estate_offices')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('main_category_id')
                ->references('id')
                ->on('property_categories')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('subcategory_id')
                ->references('id')
                ->on('property_subcategories')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('published_property_id')
                ->references('id')
                ->on('properties')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_submissions');
    }
};
