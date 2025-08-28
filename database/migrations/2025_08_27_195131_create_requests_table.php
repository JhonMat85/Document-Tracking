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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 20)->unique()->comment('Número único de solicitud auto-generado');
            $table->foreignId('client_id')->constrained('clients')->comment('Referencia a tabla clients');
            $table->foreignId('contact_id')->nullable()->constrained('client_contacts')
                  ->comment('Referencia a tabla client_contacts');
            $table->string('requesting_department', 150)->nullable()->comment('Departamento que hace la solicitud');
            $table->text('service_description')->comment('Descripción del servicio solicitado');
            $table->dateTime('request_date')->comment('Fecha de la solicitud');
            $table->dateTime('required_service_date')->nullable()->comment('Fecha requerida para el servicio');
            $table->enum('urgency', ['normal', 'urgent'])->default('normal')
                  ->comment('Nivel de prioridad');
            $table->enum('origin', ['whatsapp', 'email', 'phone', 'in_person', 'system'])->default('email')
                  ->comment('Origen de la solicitud');
            $table->foreignId('state_id')->constrained('process_states')->comment('Estado actual del proceso');
            $table->foreignId('created_by')->constrained('users')->comment('Usuario que creó el registro');
            $table->foreignId('updated_by')->nullable()->constrained('users')
                  ->comment('Usuario que actualizó por última vez');
            $table->text('notes')->nullable()->comment('Notas adicionales');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimizar consultas
            $table->index(['client_id', 'state_id'], 'idx_request_client_state');
            $table->index(['request_date', 'urgency'], 'idx_request_date_urgency');
            $table->index(['state_id', 'request_date'], 'idx_request_state_date');
            $table->index(['origin', 'request_date'], 'idx_request_origin_date');
            $table->index('created_by', 'idx_request_created_by');
            
            // Índice de texto completo para búsquedas
            $table->fullText('service_description', 'ft_request_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
