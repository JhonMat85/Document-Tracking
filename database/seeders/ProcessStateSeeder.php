<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcessStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seedea los estados para cada entidad del proceso según los casos de uso UC000-UC011
     */
    public function run(): void
    {
        $states = [
            // ESTADOS PARA REQUESTS (UC000 - Registrar Solicitud Simple)
            ['entity' => 'request', 'code' => 'received', 'name' => 'Solicitud Recibida', 'description' => 'Solicitud inicial recibida por WhatsApp, email o teléfono', 'color' => '#3B82F6', 'display_order' => 1, 'is_initial_state' => true, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'request', 'code' => 'pending_quote', 'name' => 'Pendiente de Cotizar', 'description' => 'Solicitud en espera de elaborar cotización', 'color' => '#F59E0B', 'display_order' => 2, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'request', 'code' => 'quoted', 'name' => 'Cotizado', 'description' => 'Cotización generada y enviada al cliente', 'color' => '#10B981', 'display_order' => 3, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'request', 'code' => 'closed', 'name' => 'Cerrado', 'description' => 'Proceso cerrado (aprobado o rechazado)', 'color' => '#6B7280', 'display_order' => 4, 'is_initial_state' => false, 'is_final_state' => true, 'is_active' => true],
            
            // ESTADOS PARA RFQs (UC001 - Registrar RFQ Recibido)
            ['entity' => 'rfq', 'code' => 'received', 'name' => 'RFQ Recibido', 'description' => 'RFQ formal recibido de la clínica', 'color' => '#3B82F6', 'display_order' => 1, 'is_initial_state' => true, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'rfq', 'code' => 'pending_quote', 'name' => 'Pendiente Cotizar', 'description' => 'RFQ pendiente de elaborar cotización', 'color' => '#F59E0B', 'display_order' => 2, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'rfq', 'code' => 'quoted', 'name' => 'Cotizado', 'description' => 'Cotización enviada en respuesta al RFQ', 'color' => '#10B981', 'display_order' => 3, 'is_initial_state' => false, 'is_final_state' => true, 'is_active' => true],
            
            // ESTADOS PARA QUOTES (UC002/UC003 - Gestionar Cotizaciones)
            ['entity' => 'quote', 'code' => 'draft', 'name' => 'Borrador', 'description' => 'Cotización en proceso de elaboración', 'color' => '#6B7280', 'display_order' => 1, 'is_initial_state' => true, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'quote', 'code' => 'sent', 'name' => 'Enviada', 'description' => 'Cotización enviada al cliente', 'color' => '#3B82F6', 'display_order' => 2, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'quote', 'code' => 'approved', 'name' => 'Aprobada', 'description' => 'Cotización aprobada por el cliente', 'color' => '#10B981', 'display_order' => 3, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'quote', 'code' => 'rejected', 'name' => 'Rechazada', 'description' => 'Cotización rechazada por el cliente', 'color' => '#EF4444', 'display_order' => 4, 'is_initial_state' => false, 'is_final_state' => true, 'is_active' => true],
            ['entity' => 'quote', 'code' => 'negotiation', 'name' => 'En Negociación', 'description' => 'Cotización en proceso de negociación', 'color' => '#F59E0B', 'display_order' => 5, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'quote', 'code' => 'pending', 'name' => 'Pendiente', 'description' => 'Cotización pendiente de respuesta', 'color' => '#8B5CF6', 'display_order' => 6, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            
            // ESTADOS PARA PURCHASE ORDERS (UC004 - Gestionar Orden de Compra)
            ['entity' => 'purchase_order', 'code' => 'received', 'name' => 'OC Recibida', 'description' => 'Orden de compra recibida del cliente', 'color' => '#3B82F6', 'display_order' => 1, 'is_initial_state' => true, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'purchase_order', 'code' => 'ready_execution', 'name' => 'Listo para Ejecutar', 'description' => 'OC procesada, listo para ejecutar servicio', 'color' => '#10B981', 'display_order' => 2, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'purchase_order', 'code' => 'executed', 'name' => 'Ejecutado', 'description' => 'Servicio ejecutado según OC', 'color' => '#059669', 'display_order' => 3, 'is_initial_state' => false, 'is_final_state' => true, 'is_active' => true],
            
            // ESTADOS PARA EXECUTED SERVICES (UC005 - Ejecutar Servicio)
            ['entity' => 'executed_service', 'code' => 'scheduled', 'name' => 'Programado', 'description' => 'Servicio programado para ejecución', 'color' => '#F59E0B', 'display_order' => 1, 'is_initial_state' => true, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'executed_service', 'code' => 'in_progress', 'name' => 'En Ejecución', 'description' => 'Servicio en proceso de ejecución', 'color' => '#3B82F6', 'display_order' => 2, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'executed_service', 'code' => 'executed', 'name' => 'Ejecutado', 'description' => 'Servicio ejecutado exitosamente', 'color' => '#10B981', 'display_order' => 3, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'executed_service', 'code' => 'pending_hes', 'name' => 'Pendiente HES', 'description' => 'Servicio ejecutado, esperando HES del cliente', 'color' => '#F59E0B', 'display_order' => 4, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'executed_service', 'code' => 'with_hes', 'name' => 'Con HES', 'description' => 'Servicio con HES recibido', 'color' => '#059669', 'display_order' => 5, 'is_initial_state' => false, 'is_final_state' => true, 'is_active' => true],
            
            // ESTADOS PARA HES (UC006 - Registrar HES) - CRÍTICO PARA FACTURACIÓN
            ['entity' => 'hes', 'code' => 'received', 'name' => 'HES Recibido', 'description' => 'HES recibido del cliente', 'color' => '#3B82F6', 'display_order' => 1, 'is_initial_state' => true, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'hes', 'code' => 'authorized', 'name' => 'Autorizado para Facturar', 'description' => 'HES autorizado - HABILITA FACTURACIÓN', 'color' => '#10B981', 'display_order' => 2, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'hes', 'code' => 'invoiced', 'name' => 'Facturado', 'description' => 'HES ya utilizado para facturación', 'color' => '#059669', 'display_order' => 3, 'is_initial_state' => false, 'is_final_state' => true, 'is_active' => true],
            
            // ESTADOS PARA INVOICES (UC007/UC008 - Gestionar Facturación)
            ['entity' => 'invoice', 'code' => 'draft', 'name' => 'Borrador', 'description' => 'Factura en elaboración', 'color' => '#6B7280', 'display_order' => 1, 'is_initial_state' => true, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'invoice', 'code' => 'sent', 'name' => 'Factura Enviada', 'description' => 'Factura enviada a facturación electrónica', 'color' => '#3B82F6', 'display_order' => 2, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'invoice', 'code' => 'conform', 'name' => 'Conforme', 'description' => 'Factura conforme en el sistema de la clínica', 'color' => '#10B981', 'display_order' => 3, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'invoice', 'code' => 'observed', 'name' => 'Observada', 'description' => 'Factura observada - requiere correcciones', 'color' => '#F59E0B', 'display_order' => 4, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'invoice', 'code' => 'rejected', 'name' => 'Rechazada', 'description' => 'Factura rechazada - requiere nueva factura', 'color' => '#EF4444', 'display_order' => 5, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'invoice', 'code' => 'pending_payment', 'name' => 'Pendiente de Pago', 'description' => 'Factura conforme, pendiente de pago por finanzas', 'color' => '#8B5CF6', 'display_order' => 6, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'invoice', 'code' => 'paid', 'name' => 'Pagada', 'description' => 'Factura pagada completamente', 'color' => '#059669', 'display_order' => 7, 'is_initial_state' => false, 'is_final_state' => true, 'is_active' => true],
            
            // ESTADOS PARA PAYMENTS (UC009 - Gestionar Pagos)
            ['entity' => 'payment', 'code' => 'received', 'name' => 'Pago Recibido', 'description' => 'Pago recibido en cuenta', 'color' => '#3B82F6', 'display_order' => 1, 'is_initial_state' => true, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'payment', 'code' => 'unidentified', 'name' => 'Por Identificar', 'description' => 'Pago no identificable con facturas', 'color' => '#F59E0B', 'display_order' => 2, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'payment', 'code' => 'partial', 'name' => 'Pago Parcial', 'description' => 'Pago parcial de factura', 'color' => '#8B5CF6', 'display_order' => 3, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'payment', 'code' => 'reconciled', 'name' => 'Conciliado', 'description' => 'Pago reconciliado con facturas', 'color' => '#10B981', 'display_order' => 4, 'is_initial_state' => false, 'is_final_state' => false, 'is_active' => true],
            ['entity' => 'payment', 'code' => 'closed', 'name' => 'Cerrado', 'description' => 'Ciclo de pago cerrado exitosamente', 'color' => '#059669', 'display_order' => 5, 'is_initial_state' => false, 'is_final_state' => true, 'is_active' => true],
        ];

        foreach ($states as $state) {
            DB::table('process_states')->updateOrInsert(
                ['entity' => $state['entity'], 'code' => $state['code']],
                array_merge($state, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        $this->command->info('✅ Process states seeded successfully for all entities (UC000-UC011)');
    }
}