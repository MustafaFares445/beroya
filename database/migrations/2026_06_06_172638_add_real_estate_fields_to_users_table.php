<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('real_estate_office_id')->nullable()->after('phone');
            $table->string('real_estate_role', 255)->nullable()->after('real_estate_office_id');

            $table->foreign('real_estate_office_id')
                ->references('id')
                ->on('real_estate_offices')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['real_estate_office_id']);
            $table->dropColumn(['real_estate_office_id', 'real_estate_role']);
        });
    }
};
