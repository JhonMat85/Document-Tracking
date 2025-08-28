<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea clientes de prueba representando diferentes tipos de entidades
     * según los casos de uso definidos
     */
    public function run(): void
    {
        $adminUserId = DB::table('users')->where('email', 'admin@test.com')->value('id');
        
        $clients = [
            // CLÍNICA EJEMPLO (Tipo principal de cliente)
            [
                'client_code' => 'CLI001',
                'business_name' => 'Clínica San Juan',
                'legal_name' => 'Clínica San Juan S.A.C.',
                'client_type' => 'clinic',
                'tax_id' => '20123456789',
                'fiscal_address' => 'Av. Javier Prado Este 1234, San Isidro, Lima',
                'main_phone' => '+51-1-234-5678',
                'main_email' => 'administracion@clinicasanjuan.com',
                'business_sector' => 'Salud',
                'notes' => 'Cliente principal - Clínica privada con múltiples especialidades',
                'is_active' => true,
                'created_by' => $adminUserId,
                'updated_by' => null,
            ],

            // HOSPITAL PÚBLICO
            [
                'client_code' => 'HOS001',
                'business_name' => 'Hospital Nacional Dos de Mayo',
                'legal_name' => 'Hospital Nacional Dos de Mayo',
                'client_type' => 'hospital',
                'tax_id' => '20987654321',
                'fiscal_address' => 'Av. Grau 13, Cercado de Lima, Lima',
                'main_phone' => '+51-1-328-0000',
                'main_email' => 'logistica@hn2demayo.gob.pe',
                'business_sector' => 'Salud Pública',
                'notes' => 'Hospital público nacional - Procesos de compra más extensos',
                'is_active' => true,
                'created_by' => $adminUserId,
                'updated_by' => null,
            ],

            // EMPRESA PRIVADA
            [
                'client_code' => 'EMP001',
                'business_name' => 'Laboratorios ABC',
                'legal_name' => 'Laboratorios ABC S.A.',
                'client_type' => 'private_company',
                'tax_id' => '20456789123',
                'fiscal_address' => 'Jr. de la Unión 456, Lima Centro, Lima',
                'main_phone' => '+51-1-567-8900',
                'main_email' => 'compras@laboratoriosABC.com',
                'business_sector' => 'Laboratorio Clínico',
                'notes' => 'Laboratorio privado - Servicios de transporte de muestras',
                'is_active' => true,
                'created_by' => $adminUserId,
                'updated_by' => null,
            ],

            // INSTITUCIÓN PÚBLICA
            [
                'client_code' => 'INS001',
                'business_name' => 'ESSALUD - Red Asistencial Lima',
                'legal_name' => 'Seguro Social de Salud del Perú',
                'client_type' => 'public_institution',
                'tax_id' => '20131257681',
                'fiscal_address' => 'Av. 28 de Julio 1056, Lima',
                'main_phone' => '+51-1-265-6000',
                'main_email' => 'adquisiciones.lima@essalud.gob.pe',
                'business_sector' => 'Seguridad Social',
                'notes' => 'Red asistencial ESSALUD - Procesos de licitación pública',
                'is_active' => true,
                'created_by' => $adminUserId,
                'updated_by' => null,
            ],

            // CLÍNICA PRIVADA SEGUNDA
            [
                'client_code' => 'CLI002',
                'business_name' => 'Centro Médico Miraflores',
                'legal_name' => 'Centro Médico Miraflores E.I.R.L.',
                'client_type' => 'clinic',
                'tax_id' => '20789123456',
                'fiscal_address' => 'Av. Larco 789, Miraflores, Lima',
                'main_phone' => '+51-1-445-6789',
                'main_email' => 'operaciones@centromedicomiraflores.com',
                'business_sector' => 'Salud',
                'notes' => 'Centro médico boutique - Servicios especializados',
                'is_active' => true,
                'created_by' => $adminUserId,
                'updated_by' => null,
            ],

            // CLIENTE OTROS
            [
                'client_code' => 'OTH001',
                'business_name' => 'Instituto de Investigación Médica',
                'legal_name' => 'Instituto de Investigación Médica S.A.C.',
                'client_type' => 'others',
                'tax_id' => '20321654987',
                'fiscal_address' => 'Av. Universitaria 1500, San Miguel, Lima',
                'main_phone' => '+51-1-578-9000',
                'main_email' => 'admin@investigacionmedica.org',
                'business_sector' => 'Investigación',
                'notes' => 'Instituto de investigación - Servicios especializados de transporte',
                'is_active' => true,
                'created_by' => $adminUserId,
                'updated_by' => null,
            ],

            // HOSPITAL PRIVADO
            [
                'client_code' => 'HOS002',
                'business_name' => 'Hospital Británico',
                'legal_name' => 'Hospital Británico Lima S.A.',
                'client_type' => 'hospital',
                'tax_id' => '20654321987',
                'fiscal_address' => 'Av. Italia 1525, San Borja, Lima',
                'main_phone' => '+51-1-619-6161',
                'main_email' => 'logistica@hospitalbrit.com',
                'business_sector' => 'Salud Privada',
                'notes' => 'Hospital privado internacional - Altos estándares',
                'is_active' => true,
                'created_by' => $adminUserId,
                'updated_by' => null,
            ],

            // CLIENTE INACTIVO
            [
                'client_code' => 'CLI003',
                'business_name' => 'Clínica Cerrada',
                'legal_name' => 'Clínica Cerrada S.A.C.',
                'client_type' => 'clinic',
                'tax_id' => '20111222333',
                'fiscal_address' => 'Jr. Antigua 123, Lima',
                'main_phone' => '+51-1-111-2222',
                'main_email' => 'info@clinicacerrada.com',
                'business_sector' => 'Salud',
                'notes' => 'Cliente inactivo - Para pruebas de filtros',
                'is_active' => false,
                'created_by' => $adminUserId,
                'updated_by' => null,
            ],
        ];

        foreach ($clients as $client) {
            DB::table('clients')->updateOrInsert(
                ['client_code' => $client['client_code']],
                array_merge($client, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }

        $this->command->info('✅ Clients seeded successfully (8 clients: clinics, hospitals, companies, institutions)');
    }
}