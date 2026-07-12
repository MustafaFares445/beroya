<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table): void {
                if (! Schema::hasColumn('sales', 'contract_type')) {
                    $table->string('contract_type', 20)->default('cash')->after('approved');
                }

                if (! Schema::hasColumn('sales', 'installment_count')) {
                    $table->unsignedInteger('installment_count')->nullable()->after('contract_type');
                }

                if (! Schema::hasColumn('sales', 'installment_amount')) {
                    $table->integer('installment_amount')->nullable()->after('installment_count');
                }

                if (! Schema::hasColumn('sales', 'installment_start_date')) {
                    $table->date('installment_start_date')->nullable()->after('installment_amount');
                }

                if (! Schema::hasColumn('sales', 'installment_end_date')) {
                    $table->date('installment_end_date')->nullable()->after('installment_start_date');
                }

                if (! Schema::hasColumn('sales', 'installment_note')) {
                    $table->text('installment_note')->nullable()->after('installment_end_date');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table): void {
                foreach (['contract_type', 'installment_count', 'installment_amount', 'installment_start_date', 'installment_end_date', 'installment_note'] as $column) {
                    if (Schema::hasColumn('sales', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
