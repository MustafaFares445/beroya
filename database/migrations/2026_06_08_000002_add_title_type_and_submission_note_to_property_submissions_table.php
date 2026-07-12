<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('property_submissions')) {
            Schema::table('property_submissions', function (Blueprint $table): void {
                if (! Schema::hasColumn('property_submissions', 'title_type')) {
                    $table->string('title_type', 100)->after('property_nature');
                }

                if (! Schema::hasColumn('property_submissions', 'submission_note')) {
                    $table->text('submission_note')->nullable()->after('owner_phone');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('property_submissions')) {
            Schema::table('property_submissions', function (Blueprint $table): void {
                foreach (['title_type', 'submission_note'] as $column) {
                    if (Schema::hasColumn('property_submissions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
