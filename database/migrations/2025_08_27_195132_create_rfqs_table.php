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
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_number', 50)->unique()->comment('Número de RFQ del cliente');
            $table->foreignId('request_id')->constrained('requests')->comment('Referencia a tabla requests');
            $table->dateTime('received_date')->comment('Fecha de recepción del RFQ');
            $table->dateTime('quote_deadline')->comment('Fecha límite para cotizar');
            $table->text('detailed_description')->nullable()->comment('Descripción detallada del servicio');
            $table->foreignId('state_id')->constrained('process_states')->comment('Estado actual del proceso');
            $table->foreignId('created_by')->constrained('users')->comment('Usuario que creó el registro');
            $table->foreignId('updated_by')->nullable()->constrained('users')
                  ->comment('Usuario que actualizó por última vez');
            $table->text('notes')->nullable()->comment('Notas adicionales');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimizar consultas
            $table->index(['request_id', 'state_id'], 'idx_rfq_request_state');
            $table->index('quote_deadline', 'idx_rfq_deadline');
            $table->index(['state_id', 'quote_deadline'], 'idx_rfq_state_deadline');
            $table->index('received_date', 'idx_rfq_received_date');
            $table->index('created_by', 'idx_rfq_created_by');
            
            // Restricción de unicidad compuesta
            $table->unique(['rfq_number', 'request_id'], 'uk_rfq_number_request');
            
            // Índice de texto completo para búsquedas
            $table->fullText('detailed_description', 'ft_rfq_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfqs');
    }
};
