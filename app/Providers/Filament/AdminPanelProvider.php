<?php

namespace App\Providers\Filament;

use App\Filament\Resources\AttachmentResource;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\CompleteResource;
use App\Filament\Resources\ExecutedServiceResource;
use App\Filament\Resources\HesResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Resources\QuoteResource;
use App\Filament\Resources\RequestResource;
use App\Filament\Resources\RfqResource;
use App\Filament\Resources\StatusResource;
use App\Filament\Resources\SystemConfigurationResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Filament\Navigation\NavigationGroup;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Resma\FilamentAwinTheme\FilamentAwinTheme;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make('📋 Gestión Comercial')
                    ->icon('heroicon-o-building-office')
                    ->label('Comercial'),
                NavigationGroup::make('🔧 Gestión Operativa')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->label('Operativa'),
                NavigationGroup::make('💰 Gestión Financiera')
                    ->icon('heroicon-o-currency-dollar')
                    ->label('Financiera'),
                NavigationGroup::make('📁 Gestión Documental')
                    ->icon('heroicon-o-document')
                    ->label('Documentos'),
                NavigationGroup::make('📊 Reportes y Dashboard')
                    ->icon('heroicon-o-chart-bar')
                    ->label('Reportes'),
                NavigationGroup::make('⚙️ Administración')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->label('Admin'),
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
                
                // 📊 REPORTES Y DASHBOARD (Informes y análisis)
                CompleteResource::class,          // 05 - Dashboard Completo de Cotizaciones
                StatusResource::class,            // 10 - Dashboard Estado de Documentos
                
                // ⚙️ ADMINISTRACIÓN (Configuración del sistema)
                SystemConfigurationResource::class, // 10 - Configuraciones
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // Sin widgets globales - se manejan en cada página
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
            ->plugins([
                FilamentAwinTheme::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
