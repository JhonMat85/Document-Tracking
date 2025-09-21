<?php

namespace App\Filament\Resources\SystemConfigurationResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;

class SystemConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('key')
                    ->label('Clave')
                    ->required(),
                    
                TextInput::make('value')
                    ->label('Valor')
                    ->required(fn ($get) => !in_array($get('key'), ['company_logo', 'bank_account_info']))
                    ->hidden(fn ($get) => in_array($get('key'), ['company_logo', 'bank_account_info']))
                    ->helperText(function ($get) {
                        $key = $get('key');
                        if ($key === 'company_logo') {
                            return 'La ruta se actualiza automáticamente al subir el archivo';
                        } elseif ($key === 'bank_account_info') {
                            return 'El valor se actualiza automáticamente al editar el campo de información bancaria';
                        }
                        return 'Valor de la configuración';
                    }),
                    
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

                // FUNCIONALIDAD DE CARGA DE LOGO - Sube y guarda logos de empresa en storage/app/public/logos/
                FileUpload::make('logo_upload')
                    ->label('Logo de la Empresa')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif'])
                    ->maxSize(2048) // 2MB
                    ->disk('public')
                    ->directory('logos')
                    ->visibility('public')
                    ->imagePreviewHeight('150')
                    ->helperText('Logo que se mostrará en las cotizaciones (JPG, JPEG, PNG, GIF - máx. 2MB). La ruta se guarda automáticamente.')
                    ->columnSpanFull()
                    ->visible(fn ($get) => $get('key') === 'company_logo')
                    ->storeFiles(true)
                    ->validationMessages([
                        'mimetypes' => 'El archivo debe ser una imagen válida. Formatos permitidos: JPEG, PNG, GIF.',
                    ]),

                // Campo especial para información bancaria
                Textarea::make('bank_info_input')
                    ->label('Información Bancaria')
                    ->rows(4)
                    ->placeholder('Cta. BCP  CUENTA CORRIENTE BCP.
NRO. 193-2426603-0-40')
                    ->helperText('Información bancaria que se mostrará en las cotizaciones')
                    ->columnSpanFull()
                    ->visible(fn ($get) => $get('key') === 'bank_account_info')
                    ->afterStateUpdated(function ($state, $set) {
                        $set('value', $state ?: '');
                    }),

                Toggle::make('is_editable')
                    ->label('Editable')
                    ->default(true),
            ]);
    }
}
