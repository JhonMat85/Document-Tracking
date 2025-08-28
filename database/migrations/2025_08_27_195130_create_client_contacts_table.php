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
        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade')
                  ->comment('Referencia a tabla clients');
            $table->string('full_name', 255)->comment('Nombre completo de la persona');
            $table->string('position', 150)->nullable()->comment('Cargo');
            $table->string('department', 150)->nullable()->comment('Departamento');
            $table->string('phone', 50)->nullable()->comment('Número de teléfono');
            $table->string('email', 255)->nullable()->comment('Dirección de email');
            $table->boolean('is_primary_contact')->default(false)->comment('Si es el contacto principal');
            $table->boolean('is_active')->default(true)->comment('Si el contacto está activo');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimizar consultas
            $table->index(['client_id', 'is_active'], 'idx_contact_client_active');
            $table->index(['client_id', 'is_primary_contact'], 'idx_contact_client_primary');
            $table->index(['email', 'is_active'], 'idx_contact_email_active');
            $table->index('phone', 'idx_contact_phone');
            
            // Índice de texto completo para búsquedas
            $table->fullText('full_name', 'ft_contact_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_contacts');
    }
};
