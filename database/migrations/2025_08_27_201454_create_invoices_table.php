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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('hes_id')->constrained('hes'); // CRITICAL: NOT NULL - HES REQUIRED
            $table->dateTime('issue_date');
            $table->dateTime('sent_to_client_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('total', 12, 2);
            $table->enum('currency', ['PEN', 'USD'])->default('PEN');
            $table->string('accounting_area', 100)->nullable();
            $table->foreignId('state_id')->constrained('process_states');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['hes_id', 'state_id']);
            $table->index('issue_date');
            $table->index(['due_date', 'state_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
