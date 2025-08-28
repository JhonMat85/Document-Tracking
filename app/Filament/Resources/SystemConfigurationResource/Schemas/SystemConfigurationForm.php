<?php

namespace App\Filament\Resources\SystemConfigurationResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SystemConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Clave')
                    ->required(),
                    
                TextInput::make('value')
                    ->label('Valor')
                    ->required(),
                    
                Select::make('type')
                    ->label('Tipo')
                    ->options([
                        'string' => 'String',
                        'integer' => 'Integer',
                        'decimal' => 'Decimal',
                        'boolean' => 'Boolean',
                        'json' => 'JSON',
                    ])
                    ->default('string')
                    ->required(),
                    
                Select::make('group_name')
                    ->label('Grupo')
                    ->options([
                        'general' => 'General',
                        'numbering' => 'Numeración',
                        'notifications' => 'Notificaciones',
                        'quotes' => 'Cotizaciones',
                        'services' => 'Servicios',
                        'invoicing' => 'Facturación',
                        'payments' => 'Pagos',
                        'files' => 'Archivos',
                        'dashboard' => 'Dashboard',
                        'system' => 'Sistema',
                    ])
                    ->default('general')
                    ->required(),
                    
                Textarea::make('description')
                    ->label('Descripción')
                    ->required(),
                    
                Toggle::make('is_editable')
                    ->label('Editable')
                    ->default(true),
            ]);
    }
}
