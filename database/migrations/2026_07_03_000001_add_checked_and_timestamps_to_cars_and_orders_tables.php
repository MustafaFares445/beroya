<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cars')) {
            Schema::table('cars', function (Blueprint $table): void {
                if (! Schema::hasColumn('cars', 'created_at') && ! Schema::hasColumn('cars', 'updated_at')) {
                    $table->timestamps();
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('orders', 'checked')) {
                    $table->boolean('checked')->default(false);
                }

                if (! Schema::hasColumn('orders', 'created_at') && ! Schema::hasColumn('orders', 'updated_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                if (Schema::hasColumn('orders', 'checked')) {
                    $table->dropColumn('checked');
                }

                if (Schema::hasColumn('orders', 'created_at') || Schema::hasColumn('orders', 'updated_at')) {
                    $table->dropTimestamps();
                }
            });
        }

        if (Schema::hasTable('cars')) {
            Schema::table('cars', function (Blueprint $table): void {
                if (Schema::hasColumn('cars', 'created_at') || Schema::hasColumn('cars', 'updated_at')) {
                    $table->dropTimestamps();
                }
            });
        }
    }
};
