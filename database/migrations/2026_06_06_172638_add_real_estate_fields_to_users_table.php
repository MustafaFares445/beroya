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
        if (Schema::hasTable('users')) {
            $hasRealEstateOfficeId = Schema::hasColumn('users', 'real_estate_office_id');

            Schema::table('users', function (Blueprint $table) use ($hasRealEstateOfficeId) {
                if (! Schema::hasColumn('users', 'real_estate_office_id')) {
                    $table->unsignedInteger('real_estate_office_id')->nullable()->after('phone');
                }

                if (! Schema::hasColumn('users', 'real_estate_role')) {
                    $table->string('real_estate_role', 255)->nullable()->after('real_estate_office_id');
                }

                if (! $hasRealEstateOfficeId) {
                    $table->foreign('real_estate_office_id')
                        ->references('id')
                        ->on('real_estate_offices')
                        ->nullOnDelete()
                        ->cascadeOnUpdate();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'real_estate_office_id')) {
                    $table->dropForeign(['real_estate_office_id']);
                    $table->dropColumn('real_estate_office_id');
                }

                if (Schema::hasColumn('users', 'real_estate_role')) {
                    $table->dropColumn('real_estate_role');
                }
            });
        }
    }
};
