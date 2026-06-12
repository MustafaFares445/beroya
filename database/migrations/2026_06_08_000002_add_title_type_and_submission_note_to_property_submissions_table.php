<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_submissions', function (Blueprint $table): void {
            $table->string('title_type', 100)->after('property_nature');
            $table->text('submission_note')->nullable()->after('owner_phone');
        });
    }

    public function down(): void
    {
        Schema::table('property_submissions', function (Blueprint $table): void {
            $table->dropColumn(['title_type', 'submission_note']);
        });
    }
};
