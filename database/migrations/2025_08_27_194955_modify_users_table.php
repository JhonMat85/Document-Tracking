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
            // Laravel default users table already has:
            // - id (bigint unsigned, auto_increment, primary key)
            // - name (varchar 255)
            // - email (varchar 255, unique)
            // - email_verified_at (timestamp nullable)
            // - password (varchar 255)
            // - remember_token (varchar 100, nullable)
            // - created_at (timestamp nullable)
            // - updated_at (timestamp nullable)
            
            // Add additional fields for the document tracking system
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 50)->default('user')->after('is_active');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department', 100)->nullable()->after('role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'department')) {
                $table->dropColumn('department');
            }
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
