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
        Schema::table('rfqs', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['request_id']);
            
            // Make the column nullable
            $table->foreignId('request_id')->nullable()->change();
            
            // Recreate the foreign key constraint
            $table->foreign('request_id')->references('id')->on('requests')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['request_id']);
            
            // Make the column not nullable again
            $table->foreignId('request_id')->nullable(false)->change();
            
            // Recreate the foreign key constraint
            $table->foreign('request_id')->references('id')->on('requests');
        });
    }
};
