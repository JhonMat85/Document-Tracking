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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number', 20)->unique();
            $table->foreignId('request_id')->constrained('requests');
            $table->foreignId('rfq_id')->nullable()->constrained('rfqs');
            $table->foreignId('destination_contact_id')->nullable()->constrained('client_contacts');
            $table->text('service_description');
            $table->text('pickup_address');
            $table->text('delivery_address');
            $table->dateTime('service_start_date');
            $table->dateTime('service_end_date')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_percentage', 5, 2)->default(18.00);
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('total', 12, 2);
            $table->enum('currency', ['PEN', 'USD'])->default('PEN');
            $table->enum('proposed_payment_method', ['factoring', 'direct_payment', 'cash', 'others'])->default('factoring');
            $table->integer('version')->default(1);
            $table->foreignId('parent_quote_id')->nullable()->constrained('quotes');
            $table->dateTime('generation_date');
            $table->dateTime('sent_date')->nullable();
            $table->dateTime('response_date')->nullable();
            $table->dateTime('expiration_date')->nullable();
            $table->foreignId('state_id')->constrained('process_states');
            $table->string('template_used', 100)->default('standard');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['request_id', 'version']);
            $table->index(['state_id', 'generation_date']);
            $table->index(['generation_date', 'expiration_date']);
            $table->index(['parent_quote_id', 'version']);
            $table->fullText('service_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
