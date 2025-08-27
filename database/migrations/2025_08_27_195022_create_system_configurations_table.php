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
        Schema::create('system_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('Clave de configuración única');
            $table->text('value')->nullable()->comment('Valor de la configuración');
            $table->enum('type', ['string', 'integer', 'decimal', 'boolean', 'json'])
                  ->default('string')
                  ->comment('Tipo de dato de la configuración');
            $table->text('description')->nullable()->comment('Descripción de la configuración');
            $table->boolean('is_editable')->default(true)->comment('Si la configuración puede ser editada');
            $table->string('group_name', 50)->default('general')->comment('Grupo de configuración');
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['group_name', 'key'], 'idx_config_group_key');
            $table->index('group_name', 'idx_config_group');
            $table->index('is_editable', 'idx_config_editable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_configurations');
    }
};
