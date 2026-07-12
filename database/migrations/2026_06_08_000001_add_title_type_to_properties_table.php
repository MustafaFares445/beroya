<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('properties')) {
            Schema::table('properties', function (Blueprint $table): void {
                if (! Schema::hasColumn('properties', 'title_type')) {
                    $table->string('title_type', 100)->after('property_nature');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('properties')) {
            Schema::table('properties', function (Blueprint $table): void {
                if (Schema::hasColumn('properties', 'title_type')) {
                    $table->dropColumn('title_type');
                }
            });
        }
    }
};
