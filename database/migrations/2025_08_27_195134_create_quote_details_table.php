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
        Schema::create('quote_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->onDelete('cascade');
            $table->integer('item_order');
            $table->integer('quantity');
            $table->string('unit_of_measure', 20)->default('UNIT');
            $table->string('item_description', 255);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('item_subtotal', 10, 2)->nullable();
            $table->text('item_notes')->nullable();
            $table->timestamps();
            
            // Constraints
            $table->unique(['quote_id', 'item_order'], 'uk_quote_order');
            
            // Indexes
            $table->fullText('item_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_details');
    }
};
