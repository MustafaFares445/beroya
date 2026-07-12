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
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table): void {
                if (! Schema::hasColumn('sales', 'requested_at')) {
                    $table->timestamp('requested_at')->nullable()->useCurrent()->after('date');
                }

                if (! Schema::hasColumn('sales', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('requested_at');
                }

                if (! Schema::hasColumn('sales', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable()->after('approved_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table): void {
                foreach (['requested_at', 'approved_at', 'completed_at'] as $column) {
                    if (Schema::hasColumn('sales', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
