<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    // Solo el widget de usuario para mantener el dashboard limpio
    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
        ];
    }

    // Sin widgets en el header para mantener simplicidad
    public function getHeaderWidgets(): array
    {
        return [
            // Sin widgets en el header del dashboard principal
        ];
    }

    // Sin widgets en el footer para mantener simplicidad  
    public function getFooterWidgets(): array
    {
        return [
            // Sin widgets en el footer del dashboard principal
        ];
    }
}