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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->dateTime('payment_date');
            $table->decimal('invoiced_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('discount_percentage', 5, 2)->default(0.00);
            $table->decimal('net_received_amount', 12, 2);
            $table->enum('currency', ['PEN', 'USD'])->default('PEN');
            $table->string('payment_method', 100)->nullable();
            $table->string('bank_reference', 255)->nullable();
            $table->string('origin_bank', 100)->nullable();
            $table->integer('delay_days')->nullable();
            $table->foreignId('state_id')->constrained('process_states');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['invoice_id', 'state_id']);
            $table->index('payment_date');
            $table->index('bank_reference');
            $table->index(['state_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
