<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Configuraciones básicas del sistema para el funcionamiento según UC000-UC011
     */
    public function run(): void
    {
        $configurations = [
            // CONFIGURACIONES GENERALES DEL SISTEMA
            [
                'key' => 'company_name',
                'value' => 'Sistema de Trackeo de Documentos',
                'type' => 'string',
                'description' => 'Nombre de la empresa en el sistema',
                'is_editable' => true,
                'group_name' => 'general',
            ],
            [
                'key' => 'company_logo',
                'value' => 'logos/logo.jpg',
                'type' => 'string',
                'description' => 'Logo de la empresa para cotizaciones (ruta del archivo)',
                'is_editable' => true,
                'group_name' => 'quotes',
            ],
            [
                'key' => 'bank_account_info',
                'value' => 'Cta. BCP  CUENTA CORRIENTE BCP.
NRO. 193-2426603-0-40',
                'type' => 'string',
                'description' => 'Información bancaria que se muestra en las cotizaciones',
                'is_editable' => true,
                'group_name' => 'quotes',
            ],
            [
                'key' => 'company_ruc',
                'value' => '20123456789',
                'type' => 'string',
                'description' => 'RUC de la empresa proveedora de servicios',
                'is_editable' => true,
                'group_name' => 'general',
            ],
            [
                'key' => 'company_address',
                'value' => 'Av. Principal 123, Lima, Perú',
                'type' => 'string',
                'description' => 'Dirección fiscal de la empresa',
                'is_editable' => true,
                'group_name' => 'general',
            ],
            [
                'key' => 'company_phone',
                'value' => '+51-1-234-5678',
                'type' => 'string',
                'description' => 'Teléfono principal de la empresa',
                'is_editable' => true,
                'group_name' => 'general',
            ],
            [
                'key' => 'company_email',
                'value' => 'info@document-tracking.com',
                'type' => 'string',
                'description' => 'Email principal de la empresa',
                'is_editable' => true,
                'group_name' => 'general',
            ],

            // CONFIGURACIONES DE NUMERACIÓN AUTOMÁTICA
            [
                'key' => 'request_number_prefix',
                'value' => 'REQ',
                'type' => 'string',
                'description' => 'Prefijo para números de solicitudes (UC000)',
                'is_editable' => true,
                'group_name' => 'numbering',
            ],
            [
                'key' => 'request_number_counter',
                'value' => '1',
                'type' => 'integer',
                'description' => 'Contador actual para números de solicitudes',
                'is_editable' => true,
                'group_name' => 'numbering',
            ],
            [
                'key' => 'quote_number_prefix',
                'value' => 'COT',
                'type' => 'string',
                'description' => 'Prefijo para números de cotizaciones (UC002)',
                'is_editable' => true,
                'group_name' => 'numbering',
            ],
            [
                'key' => 'quote_number_counter',
                'value' => '401',
                'type' => 'integer',
                'description' => 'Contador actual para números de cotizaciones',
                'is_editable' => true,
                'group_name' => 'numbering',
            ],

            // CONFIGURACIONES DE NOTIFICACIONES Y ALERTAS
            [
                'key' => 'quote_followup_days',
                'value' => '2',
                'type' => 'integer',
                'description' => 'Días para seguimiento automático de cotizaciones enviadas',
                'is_editable' => true,
                'group_name' => 'notifications',
            ],
            [
                'key' => 'invoice_verification_days',
                'value' => '2',
                'type' => 'integer',
                'description' => 'Días para alerta si factura no tiene confirmación (UC008)',
                'is_editable' => true,
                'group_name' => 'notifications',
            ],
            [
                'key' => 'hes_reminder_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Habilitar recordatorios para HES pendientes (CRÍTICO UC006)',
                'is_editable' => true,
                'group_name' => 'notifications',
            ],

            // CONFIGURACIONES DE COTIZACIONES (UC002)
            [
                'key' => 'default_tax_percentage',
                'value' => '18.00',
                'type' => 'decimal',
                'description' => 'Porcentaje de IGV por defecto para cotizaciones',
                'is_editable' => true,
                'group_name' => 'quotes',
            ],
            [
                'key' => 'default_currency',
                'value' => 'PEN',
                'type' => 'string',
                'description' => 'Moneda por defecto (PEN/USD)',
                'is_editable' => true,
                'group_name' => 'quotes',
            ],
            [
                'key' => 'default_payment_method',
                'value' => 'factoring',
                'type' => 'string',
                'description' => 'Método de pago por defecto en cotizaciones',
                'is_editable' => true,
                'group_name' => 'quotes',
            ],
            [
                'key' => 'quote_validity_days',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Días de validez por defecto para cotizaciones',
                'is_editable' => true,
                'group_name' => 'quotes',
            ],

            // CONFIGURACIONES DE SERVICIOS Y TÉCNICOS (UC005)
            [
                'key' => 'default_technician',
                'value' => 'Carlos Técnico',
                'type' => 'string',
                'description' => 'Técnico principal por defecto para servicios',
                'is_editable' => true,
                'group_name' => 'services',
            ],
            [
                'key' => 'service_execution_buffer_hours',
                'value' => '2',
                'type' => 'integer',
                'description' => 'Horas de buffer para programación de servicios',
                'is_editable' => true,
                'group_name' => 'services',
            ],

            // CONFIGURACIONES DE FACTURACIÓN (UC007) - CRÍTICAS
            [
                'key' => 'require_hes_for_invoice',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'CRÍTICO: HES obligatorio para facturar (UC006→UC007)',
                'is_editable' => false, // NO EDITABLE - REGLA DE NEGOCIO CRÍTICA
                'group_name' => 'invoicing',
            ],
            [
                'key' => 'accounting_area_default',
                'value' => 'Área Contabilidad',
                'type' => 'string',
                'description' => 'Área de contabilidad por defecto para facturas',
                'is_editable' => true,
                'group_name' => 'invoicing',
            ],
            [
                'key' => 'invoice_generation_mode',
                'value' => 'external',
                'type' => 'string',
                'description' => 'Modo de generación: external (área contabilidad) / internal',
                'is_editable' => true,
                'group_name' => 'invoicing',
            ],

            // CONFIGURACIONES DE PAGOS (UC009)
            [
                'key' => 'payment_identification_tolerance_days',
                'value' => '5',
                'type' => 'integer',
                'description' => 'Días de tolerancia para identificar pagos recibidos',
                'is_editable' => true,
                'group_name' => 'payments',
            ],
            [
                'key' => 'factoring_default_percentage',
                'value' => '5.00',
                'type' => 'decimal',
                'description' => 'Porcentaje de descuento por defecto para factoring',
                'is_editable' => true,
                'group_name' => 'payments',
            ],
            [
                'key' => 'direct_payment_days_default',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Días por defecto para pago directo',
                'is_editable' => true,
                'group_name' => 'payments',
            ],

            // CONFIGURACIONES DE ARCHIVOS Y DOCUMENTOS (UC011)
            [
                'key' => 'max_file_size_mb',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Tamaño máximo de archivos en MB',
                'is_editable' => true,
                'group_name' => 'files',
            ],
            [
                'key' => 'allowed_file_types',
                'value' => '["pdf","jpg","jpeg","png","doc","docx","xls","xlsx"]',
                'type' => 'json',
                'description' => 'Tipos de archivos permitidos para adjuntos',
                'is_editable' => true,
                'group_name' => 'files',
            ],
            [
                'key' => 'documents_storage_path',
                'value' => 'documents',
                'type' => 'string',
                'description' => 'Ruta de almacenamiento de documentos',
                'is_editable' => true,
                'group_name' => 'files',
            ],

            // CONFIGURACIONES DE DASHBOARD Y REPORTES (UC010)
            [
                'key' => 'dashboard_refresh_interval',
                'value' => '60',
                'type' => 'integer',
                'description' => 'Intervalo de actualización del dashboard en segundos',
                'is_editable' => true,
                'group_name' => 'dashboard',
            ],
            [
                'key' => 'urgent_service_alert_color',
                'value' => '#EF4444',
                'type' => 'string',
                'description' => 'Color para alertas de servicios urgentes sin OC',
                'is_editable' => true,
                'group_name' => 'dashboard',
            ],
            [
                'key' => 'missing_hes_alert_color',
                'value' => '#F59E0B',
                'type' => 'string',
                'description' => 'Color para alertas de servicios ejecutados sin HES',
                'is_editable' => true,
                'group_name' => 'dashboard',
            ],

            // CONFIGURACIONES DE SISTEMA INTERNO
            [
                'key' => 'system_version',
                'value' => '1.0.0',
                'type' => 'string',
                'description' => 'Versión actual del sistema',
                'is_editable' => false,
                'group_name' => 'system',
            ],
            [
                'key' => 'last_seeded_at',
                'value' => now()->toISOString(),
                'type' => 'string',
                'description' => 'Última fecha de ejecución de seeders',
                'is_editable' => false,
                'group_name' => 'system',
            ],
        ];

        foreach ($configurations as $config) {
            DB::table('system_configurations')->updateOrInsert(
                ['key' => $config['key']],
                array_merge($config, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }

        $this->command->info('✅ System configurations seeded successfully (32+ configs for UC000-UC011)');
        $this->command->info('   🔧 General company settings');
        $this->command->info('   🔢 Auto-numbering configurations');
        $this->command->info('   🔔 Notification and alert settings');
        $this->command->info('   💰 Quote and payment defaults');
        $this->command->info('   🚨 CRITICAL: HES requirement for invoicing enforced');
        $this->command->info('   📁 File and document management settings');
        $this->command->info('   📊 Dashboard and reporting configurations');
    }
}