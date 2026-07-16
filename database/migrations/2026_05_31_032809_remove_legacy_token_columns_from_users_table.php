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
            if (Schema::hasIndex('users', 'idx_token')) {
                Schema::table('users', function (Blueprint $table): void {
                    $table->dropIndex('idx_token');
                });
            }

            Schema::table('users', function (Blueprint $table): void {
                if (Schema::hasColumn('users', 'token')) {
                    $table->dropColumn('token');
                }

                if (Schema::hasColumn('users', 'token_expiry')) {
                    $table->dropColumn('token_expiry');
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
            Schema::table('users', function (Blueprint $table): void {
                if (! Schema::hasColumn('users', 'token')) {
                    $table->string('token', 500)->nullable()->index('idx_token');
                }

                if (! Schema::hasColumn('users', 'token_expiry')) {
                    $table->dateTime('token_expiry')->nullable();
                }
            });
        }
    }
};
