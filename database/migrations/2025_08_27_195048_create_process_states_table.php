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
        Schema::create('process_states', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 50)->comment('Nombre de entidad (request, quote, invoice, etc.)');
            $table->string('code', 50)->comment('Código de estado único por entidad');
            $table->string('name', 100)->comment('Nombre para mostrar');
            $table->text('description')->nullable()->comment('Descripción del estado');
            $table->string('color', 7)->nullable()->comment('Color hexadecimal para la interfaz');
            $table->integer('display_order')->default(0)->comment('Orden para mostrar');
            $table->boolean('is_initial_state')->default(false)->comment('Si es el estado inicial');
            $table->boolean('is_final_state')->default(false)->comment('Si es el estado final');
            $table->boolean('is_active')->default(true)->comment('Si el estado está activo');
            $table->timestamps();
            
            // Restricciones de unicidad
            $table->unique(['entity', 'code'], 'uk_state_entity_code');
            
            // Índices para optimizar consultas
            $table->index(['entity', 'is_active'], 'idx_state_entity_active');
            $table->index(['entity', 'display_order'], 'idx_state_entity_order');
            $table->index(['entity', 'is_initial_state'], 'idx_state_entity_initial');
            $table->index(['entity', 'is_final_state'], 'idx_state_entity_final');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_states');
    }
};
