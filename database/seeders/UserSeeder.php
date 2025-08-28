<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea usuarios base del sistema para pruebas y operación inicial
     */
    public function run(): void
    {
        // Usuario Administrador Principal (mencionado en las validaciones previas)
        User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Administrador Principal',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Usuario Jaime (Personal Administrativo mencionado en los casos de uso)
        User::updateOrCreate(
            ['email' => 'jaime@document-tracking.com'],
            [
                'name' => 'Jaime Martinez',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Usuario Andrés (Personal Administrativo mencionado en los casos de uso)
        User::updateOrCreate(
            ['email' => 'andres@document-tracking.com'],
            [
                'name' => 'Andrés Rodriguez',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Usuario Área de Contabilidad/Finanzas
        User::updateOrCreate(
            ['email' => 'contabilidad@document-tracking.com'],
            [
                'name' => 'Área Contabilidad',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Usuario Técnico Principal
        User::updateOrCreate(
            ['email' => 'tecnico@document-tracking.com'],
            [
                'name' => 'Carlos Técnico',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Usuario de Ventas/Cotizaciones
        User::updateOrCreate(
            ['email' => 'ventas@document-tracking.com'],
            [
                'name' => 'María Ventas',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Usuario de Operaciones
        User::updateOrCreate(
            ['email' => 'operaciones@document-tracking.com'],
            [
                'name' => 'Luis Operaciones',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Usuario de Facturación
        User::updateOrCreate(
            ['email' => 'facturacion@document-tracking.com'],
            [
                'name' => 'Ana Facturación',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Usuario de Pruebas Generales
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Usuario Prueba',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('✅ Users seeded successfully (9 users including admin, administrative staff, and operational roles)');
    }
}