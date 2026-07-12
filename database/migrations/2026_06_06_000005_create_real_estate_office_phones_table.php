<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('real_estate_office_phones')) {
            Schema::create('real_estate_office_phones', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('real_estate_office_id');
                $table->string('phone', 30);

                $table->foreign('real_estate_office_id')
                    ->references('id')
                    ->on('real_estate_offices')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_office_phones');
    }
};
