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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->string('solped_number', 50)->nullable();
            $table->foreignId('quote_id')->constrained('quotes');
            $table->dateTime('issue_date');
            $table->dateTime('validity_date')->nullable();
            $table->dateTime('scheduled_execution_date')->nullable();
            $table->string('assigned_technician', 255)->nullable();
            $table->string('reception_area', 255)->nullable();
            $table->decimal('authorized_amount', 12, 2);
            $table->enum('currency', ['PEN', 'USD'])->default('PEN');
            $table->text('special_conditions')->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->foreignId('state_id')->constrained('process_states');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['quote_id', 'state_id']);
            $table->index('scheduled_execution_date');
            $table->index(['is_urgent', 'state_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
