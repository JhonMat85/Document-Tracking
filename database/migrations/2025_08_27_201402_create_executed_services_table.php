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
        Schema::create('executed_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('main_technician', 255);
            $table->json('work_team')->nullable();
            $table->text('work_description')->nullable();
            $table->text('incidents')->nullable();
            $table->decimal('execution_hours', 5, 2)->nullable();
            $table->foreignId('state_id')->constrained('process_states');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['purchase_order_id', 'state_id']);
            $table->index(['start_date', 'main_technician']);
            $table->index(['state_id', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('executed_services');
    }
};
