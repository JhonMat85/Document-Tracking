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
        Schema::create('quote_transport', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->onDelete('cascade');
            $table->string('driver_name', 255)->nullable();
            $table->string('driver_license', 50)->nullable();
            $table->string('vehicle_plate', 20)->nullable();
            $table->string('vehicle_model', 100)->nullable();
            $table->string('vehicle_capacity', 50)->nullable();
            $table->boolean('includes_insurance')->default(false);
            $table->text('transport_notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('vehicle_plate');
            $table->index('driver_license');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_transport');
    }
};
