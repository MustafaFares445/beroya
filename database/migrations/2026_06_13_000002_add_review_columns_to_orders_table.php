<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('orders', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('order_state');
                }

                if (! Schema::hasColumn('orders', 'rejected_at')) {
                    $table->timestamp('rejected_at')->nullable()->after('approved_at');
                }

                if (! Schema::hasColumn('orders', 'reviewed_by_user_id')) {
                    $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->after('rejected_at');
                }

                if (! Schema::hasColumn('orders', 'reject_reason')) {
                    $table->text('reject_reason')->nullable()->after('reviewed_by_user_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                foreach (['approved_at', 'rejected_at', 'reviewed_by_user_id', 'reject_reason'] as $column) {
                    if (Schema::hasColumn('orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
