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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_code', 20)->unique()->comment('Código único del cliente');
            $table->string('business_name', 255)->comment('Nombre comercial');
            $table->string('legal_name', 255)->comment('Razón social');
            $table->enum('client_type', ['clinic', 'hospital', 'private_company', 'public_institution', 'others'])
                  ->comment('Tipo de cliente');
            $table->string('tax_id', 20)->unique()->comment('Número de RUC');
            $table->text('fiscal_address')->nullable()->comment('Dirección fiscal registrada');
            $table->string('main_phone', 50)->nullable()->comment('Teléfono principal');
            $table->string('main_email', 255)->nullable()->comment('Email principal');
            $table->string('business_sector', 100)->nullable()->comment('Sector empresarial');
            $table->text('notes')->nullable()->comment('Notas adicionales');
            $table->boolean('is_active')->default(true)->comment('Si el cliente está activo');
            $table->foreignId('created_by')->constrained('users')->comment('Usuario que creó el registro');
            $table->foreignId('updated_by')->nullable()->constrained('users')->comment('Usuario que actualizó por última vez');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimizar consultas
            $table->index(['client_type', 'is_active'], 'idx_client_type_active');
            $table->index(['is_active', 'deleted_at'], 'idx_client_active_deleted');
            $table->index('business_sector', 'idx_client_sector');
            $table->index('created_by', 'idx_client_created_by');
            
            // Índice de texto completo para búsquedas
            $table->fullText(['business_name', 'legal_name'], 'ft_client_names');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
