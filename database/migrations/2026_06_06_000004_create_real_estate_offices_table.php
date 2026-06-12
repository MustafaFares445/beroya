<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_estate_offices', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('province_id');
            $table->string('name', 255);
            $table->text('address');
            $table->boolean('is_active')->default(true);

            $table->foreign('province_id')
                ->references('id')
                ->on('provinces')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_offices');
    }
};
