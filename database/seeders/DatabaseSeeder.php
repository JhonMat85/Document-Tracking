<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 
     * Orden de seeders respetando dependencias:
     * 1. SystemConfigurationSeeder - Configuraciones base del sistema
     * 2. ProcessStateSeeder - Estados base para todas las entidades
     * 3. UserSeeder - Usuarios del sistema
     * 4. ClientSeeder - Clientes del sistema
     * 5. ClientContactSeeder - Contactos de clientes
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting database seeding...');
        
        // 1. Configuraciones del sistema (independiente, requerido por todo el sistema)
        $this->call(SystemConfigurationSeeder::class);
        
        // 2. Estados del proceso (independiente, requerido por todas las entidades)
        $this->call(ProcessStateSeeder::class);
        
        // 3. Usuarios del sistema (independiente)
        $this->call(UserSeeder::class);
        
        // 4. Clientes (depende de usuarios para created_by)
        $this->call(ClientSeeder::class);
        
        // 5. Contactos de clientes (depende de clientes)
        $this->call(ClientContactSeeder::class);
        
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->info('📊 Summary:');
        $this->command->info('   - System configurations for all operational aspects (UC000-UC011)');
        $this->command->info('   - Process states for all entities (UC000-UC011)');
        $this->command->info('   - 9 system users including admin and operational roles');
        $this->command->info('   - 8 diverse clients (clinics, hospitals, companies, institutions)');
        $this->command->info('   - 15 client contacts with various roles and departments');
    }
}
