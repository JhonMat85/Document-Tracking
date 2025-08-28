<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea contactos específicos para cada cliente según los casos de uso
     * que requieren contactos responsables para solicitudes y comunicaciones
     */
    public function run(): void
    {
        // Obtener IDs de clientes para referencias
        $clientIds = DB::table('clients')->pluck('id', 'client_code');

        $contacts = [
            // CONTACTOS PARA CLÍNICA SAN JUAN (CLI001)
            [
                'client_id' => $clientIds['CLI001'],
                'full_name' => 'Dr. Ricardo Mendoza',
                'position' => 'Director Médico',
                'department' => 'Dirección General',
                'phone' => '+51-1-234-5678 Ext. 101',
                'email' => 'r.mendoza@clinicasanjuan.com',
                'is_primary_contact' => true,
                'is_active' => true,
            ],
            [
                'client_id' => $clientIds['CLI001'],
                'full_name' => 'Lic. María González',
                'position' => 'Jefa de Compras',
                'department' => 'Logística',
                'phone' => '+51-1-234-5678 Ext. 201',
                'email' => 'm.gonzalez@clinicasanjuan.com',
                'is_primary_contact' => false,
                'is_active' => true,
            ],

            // CONTACTOS PARA HOSPITAL NACIONAL DOS DE MAYO (HOS001)
            [
                'client_id' => $clientIds['HOS001'],
                'full_name' => 'MENDOZA ANTONIO FRANCISCO',
                'position' => 'Jefe de Adquisiciones',
                'department' => 'Oficina de Logística',
                'phone' => '+51-1-328-0000 Ext. 150',
                'email' => 'a.mendoza@hn2demayo.gob.pe',
                'is_primary_contact' => true,
                'is_active' => true,
            ],

            // CONTACTOS PARA LABORATORIOS ABC (EMP001)
            [
                'client_id' => $clientIds['EMP001'],
                'full_name' => 'Dr. Ana Martínez',
                'position' => 'Directora de Operaciones',
                'department' => 'Operaciones',
                'phone' => '+51-1-567-8900 Ext. 100',
                'email' => 'a.martinez@laboratoriosABC.com',
                'is_primary_contact' => true,
                'is_active' => true,
            ],

            // CONTACTOS PARA ESSALUD (INS001)
            [
                'client_id' => $clientIds['INS001'],
                'full_name' => 'Lic. Carmen Flores',
                'position' => 'Especialista en Contrataciones',
                'department' => 'Órgano de Control Institucional',
                'phone' => '+51-1-265-6000 Ext. 2500',
                'email' => 'c.flores@essalud.gob.pe',
                'is_primary_contact' => true,
                'is_active' => true,
            ],

            // CONTACTOS PARA CENTRO MÉDICO MIRAFLORES (CLI002)
            [
                'client_id' => $clientIds['CLI002'],
                'full_name' => 'Dr. Luis Ramírez',
                'position' => 'Gerente General',
                'department' => 'Gerencia',
                'phone' => '+51-1-445-6789',
                'email' => 'l.ramirez@centromedicomiraflores.com',
                'is_primary_contact' => true,
                'is_active' => true,
            ],

            // CONTACTOS PARA INSTITUTO DE INVESTIGACIÓN (OTH001)
            [
                'client_id' => $clientIds['OTH001'],
                'full_name' => 'Dr. Miguel Herrera',
                'position' => 'Director de Investigación',
                'department' => 'Dirección Científica',
                'phone' => '+51-1-578-9000 Ext. 301',
                'email' => 'm.herrera@investigacionmedica.org',
                'is_primary_contact' => true,
                'is_active' => true,
            ],

            // CONTACTOS PARA HOSPITAL BRITÁNICO (HOS002)
            [
                'client_id' => $clientIds['HOS002'],
                'full_name' => 'Mrs. Katherine Johnson',
                'position' => 'Operations Manager',
                'department' => 'Operations',
                'phone' => '+51-1-619-6161 Ext. 200',
                'email' => 'k.johnson@hospitalbrit.com',
                'is_primary_contact' => true,
                'is_active' => true,
            ],
        ];

        foreach ($contacts as $contact) {
            DB::table('client_contacts')->updateOrInsert(
                [
                    'client_id' => $contact['client_id'],
                    'email' => $contact['email']
                ],
                array_merge($contact, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }

        $this->command->info('✅ Client contacts seeded successfully (8 primary contacts for all clients)');
    }
}