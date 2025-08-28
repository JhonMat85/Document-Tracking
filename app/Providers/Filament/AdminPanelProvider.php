<?php

namespace App\Providers\Filament;

use App\Filament\Resources\AttachmentResource;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ExecutedServiceResource;
use App\Filament\Resources\HesResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Resources\QuoteResource;
use App\Filament\Resources\RequestResource;
use App\Filament\Resources\RfqResource;
use App\Filament\Resources\SystemConfigurationResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resources([
                // 📋 GESTIÓN COMERCIAL (Flujo principal del negocio)
                ClientResource::class,           // 10 - Clientes
                RequestResource::class,          // 20 - Solicitudes
                RfqResource::class,              // 30 - RFQ Recibidos
                QuoteResource::class,            // 40 - Cotizaciones
                
                // 🔧 GESTIÓN OPERATIVA (Ejecución de servicios)
                PurchaseOrderResource::class,    // 10 - Órdenes de Compra
                ExecutedServiceResource::class,  // 20 - Servicios Ejecutados
                HesResource::class,              // 30 - HES (Crítico para facturación)
                
                // 💰 GESTIÓN FINANCIERA (Facturación y cobros)
                InvoiceResource::class,          // 10 - Facturas
                PaymentResource::class,          // 20 - Pagos
                
                // 📁 GESTIÓN DOCUMENTAL (Archivos y documentos)
                AttachmentResource::class,       // 10 - Documentos Adjuntos
                
                // ⚙️ ADMINISTRACIÓN (Configuración del sistema)
                SystemConfigurationResource::class, // 10 - Configuraciones
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
