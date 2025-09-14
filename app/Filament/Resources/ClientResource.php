<?php

namespace App\Filament\Resources;

use App\Models\Client;
use BackedEnum;
use UnitEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater; // Add this import
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use App\Filament\Resources\ClientResource\Pages;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static UnitEnum|string|null $navigationGroup = '📋 Gestión Comercial';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Información del Cliente')
                    ->tabs([
                        Tabs\Tab::make('📋 Información Básica')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Section::make('🏢 Datos de la Empresa')
                                            ->description('Información principal del cliente')
                                            ->icon('heroicon-o-building-office')
                                            ->schema([
                                                TextInput::make('client_code')
                                                    ->label('🔖 Código Cliente')
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(20)
                                                    ->placeholder('Ej: CLI001')
                                                    ->helperText('Código único que identifica al cliente')
                                                    ->prefixIcon('heroicon-o-hashtag'),

                                                TextInput::make('business_name')
                                                    ->label('🏪 Nombre Comercial')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Clínica San Juan')
                                                    ->prefixIcon('heroicon-o-building-storefront'),

                                                TextInput::make('legal_name')
                                                    ->label('⚖️ Razón Social')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Clínica San Juan S.A.C.')
                                                    ->prefixIcon('heroicon-o-scale'),

                                                TextInput::make('tax_id')
                                                    ->label('📄 RUC')
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(20)
                                                    ->placeholder('Ej: 20123456789')
                                                    ->prefixIcon('heroicon-o-document-text'),
                                            ])
                                            ->columns(2),

                                        Section::make('🏷️ Clasificación')
                                            ->description('Tipo y sector del cliente')
                                            ->icon('heroicon-o-tag')
                                            ->schema([
                                                Select::make('client_type')
                                                    ->label('🏥 Tipo de Cliente')
                                                    ->options([
                                                        'clinic' => '🏥 Clínica',
                                                        'hospital' => '🏨 Hospital',
                                                        'private_company' => '🏢 Empresa Privada',
                                                        'public_institution' => '🏛️ Institución Pública',
                                                        'others' => '📋 Otros',
                                                    ])
                                                    ->required()
                                                    ->searchable()
                                                    ->prefixIcon('heroicon-o-building-office-2'),

                                                TextInput::make('business_sector')
                                                    ->label('🏭 Sector Empresarial')
                                                    ->maxLength(100)
                                                    ->placeholder('Ej: Salud, Educación, etc.')
                                                    ->prefixIcon('heroicon-o-briefcase'),

                                                Toggle::make('is_active')
                                                    ->label('✅ Cliente Activo')
                                                    ->default(true)
                                                    ->helperText('Indica si el cliente está actualmente activo')
                                                    ->onIcon('heroicon-o-check-circle')
                                                    ->offIcon('heroicon-o-x-circle'),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('📞 Contacto y Ubicación')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Section::make('📍 Información de Contacto')
                                    ->description('Datos de contacto y ubicación del cliente')
                                    ->icon('heroicon-o-phone')
                                    ->schema([
                                        Textarea::make('fiscal_address')
                                            ->label('🏠 Dirección Fiscal')
                                            ->rows(3)
                                            ->placeholder('Ingrese la dirección completa del cliente...')
                                            ->helperText('Dirección fiscal registrada del cliente')
                                            ->columnSpanFull(),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('main_phone')
                                                    ->label('📱 Teléfono Principal')
                                                    ->tel()
                                                    ->maxLength(50)
                                                    ->placeholder('Ej: +51 999 888 777')
                                                    ->prefixIcon('heroicon-o-phone')
                                                    ->suffixIcon('heroicon-o-phone-arrow-up-right'),

                                                TextInput::make('main_email')
                                                    ->label('📧 Email Principal')
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->placeholder('contacto@cliente.com')
                                                    ->prefixIcon('heroicon-o-envelope')
                                                    ->suffixIcon('heroicon-o-at-symbol'),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('📝 Notas Adicionales')
                            ->icon('heroicon-o-pencil-square')
                            ->schema([
                                Section::make('💭 Información Adicional')
                                    ->description('Notas, observaciones y comentarios sobre el cliente')
                                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                    ->schema([
                                        Textarea::make('notes')
                                            ->label('📝 Notas y Observaciones')
                                            ->rows(6)
                                            ->placeholder('Ingrese cualquier información adicional relevante sobre el cliente, como preferencias especiales, historial de servicios, observaciones importantes, etc.')
                                            ->helperText('Esta información será visible para todos los usuarios con acceso al cliente')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('👥 Áreas y Contactos')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Section::make('Áreas del Cliente')
                                    ->description('Defina áreas/departamentos y sus contactos asociados')
                                    ->icon('heroicon-o-folder')
                                    ->schema([
                                        Repeater::make('departments')
                                            ->label(' ')
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    TextInput::make('name')
                                                        ->label('Nombre del Área/Departamento')
                                                        ->required()
                                                        ->columnSpanFull(),
                                                ]),
                                                Repeater::make('contacts')
                                                    ->label('Contactos')
                                                    ->schema([
                                                        Grid::make(2)->schema([
                                                            TextInput::make('full_name')
                                                                ->label('Nombre Completo')
                                                                ->required()
                                                                ->maxLength(255),

                                                            TextInput::make('position')
                                                                ->label('Cargo')
                                                                ->maxLength(150),

                                                            TextInput::make('phone')
                                                                ->label('Teléfono')
                                                                ->maxLength(50),

                                                            TextInput::make('email')
                                                                ->label('Email')
                                                                ->email()
                                                                ->maxLength(255),
                                                        ])
                                                    ])
                                                    ->collapsible()
                                                    ->defaultItems(1)
                                                    ->minItems(1)
                                                    ->maxItems(4)
                                                    ->itemLabel(fn (array $state): ?string => $state['full_name'] ?? null)
                                            ])
                                            ->collapsible()
                                            ->defaultItems(0)
                                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                    ])
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Stack::make([
                        Tables\Columns\TextColumn::make('client_code')
                            ->label('🔖 Código')
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->color('primary')
                            ->copyable()
                            ->tooltip('Código único del cliente'),

                        Tables\Columns\TextColumn::make('business_name')
                            ->label('🏪 Nombre Comercial')
                            ->searchable()
                            ->sortable()
                            ->size('lg')
                            ->weight('medium')
                            ->limit(30)
                            ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                                $state = $column->getState();
                                return strlen($state) > 30 ? $state : null;
                            }),

                        Tables\Columns\TextColumn::make('client_type')
                            ->label('🏷️ Tipo')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'clinic' => '🏥 Clínica',
                                'hospital' => '🏨 Hospital',
                                'private_company' => '🏢 Empresa Privada',
                                'public_institution' => '🏛️ Institución Pública',
                                'others' => '📋 Otros',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'clinic' => 'success',
                                'hospital' => 'info',
                                'private_company' => 'warning',
                                'public_institution' => 'primary',
                                'others' => 'gray',
                            }),
                    ])->space(1),

                    Stack::make([
                        Tables\Columns\TextColumn::make('main_phone')
                            ->label('📱 Teléfono')
                            ->searchable()
                            ->icon('heroicon-o-phone')
                            ->color('success')
                            ->copyable()
                            ->placeholder('Sin teléfono'),

                        Tables\Columns\TextColumn::make('main_email')
                            ->label('📧 Email')
                            ->searchable()
                            ->icon('heroicon-o-envelope')
                            ->color('info')
                            ->copyable()
                            ->limit(25)
                            ->placeholder('Sin email'),

                        Tables\Columns\TextColumn::make('business_sector')
                            ->label('🏭 Sector')
                            ->badge()
                            ->color('gray')
                            ->placeholder('Sin sector'),
                    ])->space(1),

                    Stack::make([
                        Tables\Columns\IconColumn::make('is_active')
                            ->label('✅ Estado')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger')
                            ->tooltip(fn ($state) => $state ? 'Cliente Activo' : 'Cliente Inactivo'),

                        Tables\Columns\TextColumn::make('created_at')
                            ->label('📅 Creado')
                            ->dateTime('d/m/Y')
                            ->sortable()
                            ->color('gray')
                            ->size('sm')
                            ->toggleable(isToggledHiddenByDefault: true),
                    ])->space(1),
                ])->from('md'),
            ])
            ->contentGrid([
                'md' => 1,
                'lg' => 1,
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client_type')
                    ->label('🏷️ Tipo de Cliente')
                    ->options([
                        'clinic' => '🏥 Clínica',
                        'hospital' => '🏨 Hospital',
                        'private_company' => '🏢 Empresa Privada',
                        'public_institution' => '🏛️ Institución Pública',
                        'others' => '📋 Otros',
                    ])
                    ->multiple()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('✅ Estado del Cliente')
                    ->placeholder('Todos los clientes')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos')
                    ->native(false),

                Tables\Filters\Filter::make('recent')
                    ->label('🆕 Recientes (30 días)')
                    ->query(fn ($query) => $query->where('created_at', '>=', now()->subDays(30)))
                    ->toggle(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color('info'),
                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning'),
                    Action::make('toggle_status')
                        ->label(fn ($record) => $record->is_active ? 'Desactivar' : 'Activar')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->action(fn ($record) => $record->update(['is_active' => !$record->is_active]))
                        ->requiresConfirmation()
                        ->modalDescription('Esta acción cambiará el estado del cliente.')
                        ->modalSubmitActionLabel('Confirmar'),
                ])
                ->label('Acciones')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Activar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['is_active' => true])))
                        ->requiresConfirmation(),
                    BulkAction::make('deactivate')
                        ->label('Desactivar seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['is_active' => false])))
                        ->requiresConfirmation(),
                    DeleteBulkAction::make()
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('30s')
            ->deferLoading()
            ->emptyStateHeading('😅 No hay clientes registrados')
            ->emptyStateDescription('Comience agregando su primer cliente al sistema.')
            ->emptyStateIcon('heroicon-o-building-office')
            ->recordAction('edit')
            ->recordUrl(fn ($record) => Pages\EditClient::getUrl([$record->id]));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}