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
        Schema::create('hes', function (Blueprint $table) {
            $table->id();
            $table->string('hes_number', 50)->unique();
            $table->foreignId('executed_service_id')->constrained('executed_services');
            $table->string('reference_po_number', 50);
            $table->string('reference_solped_number', 50)->nullable();
            $table->dateTime('approval_date');
            $table->string('approver_name', 255);
            $table->string('approver_position', 150)->nullable();
            $table->decimal('authorized_amount', 12, 2);
            $table->enum('currency', ['PEN', 'USD'])->default('PEN');
            $table->enum('payment_method', ['factoring', 'direct_payment', 'cash', 'others']);
            $table->integer('payment_days')->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->string('imputation_type', 100)->nullable();
            $table->foreignId('state_id')->constrained('process_states');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['executed_service_id', 'state_id']);
            $table->index(['approval_date', 'payment_method']);
            $table->index(['payment_method', 'state_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hes');
    }
};
