<?php

namespace App\Filament\Resources;

use App\Models\Request;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ProcessState;
use App\Filament\Resources\RequestResource\Pages;
use App\Filament\Resources\RequestResource\RelationManagers;
use BackedEnum;
use UnitEnum;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Solicitudes';

    protected static ?string $modelLabel = 'Solicitud';

    protected static ?string $pluralModelLabel = 'Solicitudes';

    protected static UnitEnum|string|null $navigationGroup = '📋 Gestión Comercial';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('request_tabs')
                    ->tabs([
                        Tab::make('📋 Información General')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('request_number')
                                                    ->label('🔢 Número de Solicitud')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->placeholder('Se generará automáticamente')
                                                    ->helperText('Este número se asigna automáticamente al guardar'),

                                                Select::make('client_id')
                                                    ->label('🏥 Cliente/Clínica')
                                                    ->relationship('client', 'business_name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(fn ($state, Set $set) => $set('contact_id', null))
                                                    ->helperText('Selecciona el cliente para esta solicitud'),
                                            ]),

                                        Grid::make(1)
                                            ->schema([
                                                Select::make('requesting_department')
                                                    ->label('🏢 Área Requirente')
                                                    ->options(function (Get $get) {
                                                        $clientId = $get('client_id');
                                                        if (!$clientId) {
                                                            return [];
                                                        }
                                                        return ClientContact::where('client_id', $clientId)
                                                            ->where('is_active', true)
                                                            ->pluck('department') // Get the department column
                                                            ->unique() // Get unique department names
                                                            ->filter() // Remove null/empty values
                                                            ->mapWithKeys(fn ($dept) => [$dept => $dept]) // Format for Select options
                                                            ->toArray();
                                                    })
                                                    ->live() // Make it reactive
                                                    ->searchable()
                                                    ->preload()
                                                    ->helperText('Departamento o área que solicita el servicio'),

                                                Select::make('contact_id')
                                                    ->label('👤 Contacto')
                                                    ->options(function (Get $get) {
                                                        $clientId = $get('client_id');
                                                        $department = $get('requesting_department');
                                                        
                                                        if (!$clientId) {
                                                            return [];
                                                        }
                                                        
                                                        $query = ClientContact::where('client_id', $clientId)
                                                            ->where('is_active', true);
                                                        
                                                        // If a department is selected, filter by it
                                                        if ($department) {
                                                            $query->where('department', $department);
                                                        }
                                                        
                                                        return $query->pluck('full_name', 'id');
                                                    })
                                                    ->live() // Make it reactive to changes in other fields
                                                    ->searchable()
                                                    ->preload()
                                                    ->helperText('Persona de contacto para esta solicitud'),
                                            ]),
                                    ])
                                    ->columnSpan('full'),
                            ]),

                        Tab::make('📝 Detalles del Servicio')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Descripción del Servicio')
                                    ->description('Detalla el servicio solicitado y las fechas importantes')
                                    ->icon('heroicon-o-clipboard-document-list')
                                    ->schema([
                                        Textarea::make('service_description')
                                            ->label('📋 Descripción del Servicio Solicitado')
                                            ->required()
                                            ->rows(4)
                                            ->placeholder('Describe detalladamente el servicio que se necesita...')
                                            ->helperText('Proporciona una descripción clara y completa del servicio requerido'),
                                    ]),

                                Section::make('Fechas y Prioridad')
                                    ->description('Establece las fechas y la urgencia de la solicitud')
                                    ->icon('heroicon-o-calendar-days')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                DateTimePicker::make('request_date')
                                                    ->label('📅 Fecha de Solicitud')
                                                    ->required()
                                                    ->default(now())
                                                    ->helperText('Fecha en que se realiza la solicitud'),

                                                DateTimePicker::make('required_service_date')
                                                    ->label('⏰ Fecha Requerida para Servicio')
                                                    ->helperText('Fecha límite para completar el servicio'),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Select::make('urgency')
                                                    ->label('🚨 Urgencia')
                                                    ->options([
                                                        'normal' => '🟢 Normal',
                                                        'urgent' => '🔴 Urgente',
                                                    ])
                                                    ->default('normal')
                                                    ->required()
                                                    ->helperText('Nivel de prioridad de la solicitud'),

                                                Select::make('origin')
                                                    ->label('📞 Origen de la Solicitud')
                                                    ->options([
                                                        'whatsapp' => '💬 WhatsApp',
                                                        'email' => '📧 Email',
                                                        'phone' => '📞 Teléfono',
                                                        'in_person' => '👥 Presencial',
                                                        'system' => '💻 Sistema',
                                                    ])
                                                    ->default('email')
                                                    ->required()
                                                    ->helperText('Canal por el cual se recibió la solicitud'),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('📎 Documentos')
                            ->icon('heroicon-o-paper-clip')
                            ->schema([
                                Section::make('Archivos Adjuntos')
                                    ->description('Sube documentos relacionados con esta solicitud')
                                    ->icon('heroicon-o-document-arrow-up')
                                    ->schema([
                                        FileUpload::make('attachment_files')
                                            ->label('📄 Documentos')
                                            ->multiple()
                                            ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg'])
                                            ->disk('public')
                                            ->directory('request-attachments')
                                            ->visibility('public')
                                            ->panelLayout('grid')
                                            ->maxFiles(5)
                                            ->maxSize(10240) // 10MB
                                            ->helperText('Formatos permitidos: PDF, PNG, JPG. Máximo 5 archivos de 10MB cada uno.')
                                            ->hint('Los documentos se almacenarán de forma segura y estarán disponibles para consulta.')
                                            ->hintIcon('heroicon-o-information-circle')
                                            ->columnSpanFull()
                                            ->storeFiles(false), // No almacenar automáticamente
                                    ]),
                            ]),

                        Tab::make('📊 Estado y Notas')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Section::make('Estado y Observaciones')
                                    ->description('Gestiona el estado de la solicitud y agrega comentarios')
                                    ->icon('heroicon-o-clipboard-document-check')
                                    ->schema([
                                        Select::make('state_id')
                                            ->label('📊 Estado')
                                            ->relationship('state', 'name', function ($query) {
                                                $query->where('entity', 'request')
                                                    ->where('is_active', true);
                                            })
                                            ->default(function () {
                                                return ProcessState::where('entity', 'request')
                                                    ->where('is_initial_state', true)
                                                    ->first()?->id;
                                            })
                                            ->required()
                                            ->helperText('Estado actual del proceso de la solicitud'),

                                        Textarea::make('notes')
                                            ->label('📝 Notas Adicionales')
                                            ->rows(4)
                                            ->placeholder('Agrega cualquier observación, comentario o información adicional relevante...')
                                            ->helperText('Información adicional que pueda ser útil para el procesamiento de la solicitud'),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                Split::make([
                    Stack::make([
                        Tables\Columns\TextColumn::make('request_number')
                            ->label('🔢 Número')
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->color('primary')
                            ->copyable()
                            ->tooltip('Número único de la solicitud'),

                        Tables\Columns\TextColumn::make('client.business_name')
                            ->label('🏥 Cliente')
                            ->searchable()
                            ->sortable()
                            ->size('lg')
                            ->weight('medium')
                            ->limit(25)
                            ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                                $state = $column->getState();
                                return strlen($state) > 25 ? $state : null;
                            }),

                        Tables\Columns\TextColumn::make('requesting_department')
                            ->label('🏢 Área')
                            ->badge()
                            ->color('gray')
                            ->placeholder('Sin área')
                            ->limit(20),
                    ])->space(1),

                    Stack::make([
                        Tables\Columns\TextColumn::make('urgency')
                            ->label('🚨 Urgencia')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'urgent' => '🔴 Urgente',
                                'normal' => '🟢 Normal',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'urgent' => 'danger',
                                'normal' => 'success',
                            }),

                        Tables\Columns\TextColumn::make('origin')
                            ->label('📞 Origen')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'whatsapp' => '💬 WhatsApp',
                                'email' => '📧 Email',
                                'phone' => '📞 Teléfono',
                                'in_person' => '👥 Presencial',
                                'system' => '💻 Sistema',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'whatsapp' => 'success',
                                'email' => 'info',
                                'phone' => 'warning',
                                'in_person' => 'primary',
                                'system' => 'gray',
                            }),

                        Tables\Columns\TextColumn::make('contact.full_name')
                            ->label('👤 Contacto')
                            ->searchable()
                            ->icon('heroicon-o-user')
                            ->color('info')
                            ->placeholder('Sin contacto')
                            ->limit(20),
                    ])->space(1),

                    Stack::make([
                        Tables\Columns\TextColumn::make('state.name')
                            ->label('📊 Estado')
                            ->badge()
                            ->color('primary')
                            ->icon('heroicon-o-clipboard-document-check'),

                        Tables\Columns\TextColumn::make('request_date')
                            ->label('📅 F. Solicitud')
                            ->dateTime('d/m/Y')
                            ->sortable()
                            ->color('gray')
                            ->size('sm')
                            ->icon('heroicon-o-calendar-days'),

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
                Tables\Filters\SelectFilter::make('urgency')
                    ->label('🚨 Urgencia')
                    ->options([
                        'urgent' => '🔴 Urgente',
                        'normal' => '🟢 Normal',
                    ])
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('origin')
                    ->label('📞 Origen de Solicitud')
                    ->options([
                        'whatsapp' => '💬 WhatsApp',
                        'email' => '📧 Email',
                        'phone' => '📞 Teléfono',
                        'in_person' => '👥 Presencial',
                        'system' => '💻 Sistema',
                    ])
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('state_id')
                    ->label('📊 Estado')
                    ->relationship('state', 'name', function ($query) {
                        $query->where('entity', 'request')
                            ->where('is_active', true);
                    })
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('🏥 Cliente')
                    ->relationship('client', 'business_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('recent')
                    ->label('🆕 Recientes (7 días)')
                    ->query(fn ($query) => $query->where('created_at', '>=', now()->subDays(7)))
                    ->toggle(),

                Tables\Filters\Filter::make('urgent_requests')
                    ->label('⚡ Solo Urgentes')
                    ->query(fn ($query) => $query->where('urgency', 'urgent'))
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
                    DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->color('danger'),
                ])
                ->label('Acciones')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->poll('30s')
            ->deferLoading()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('📋 No hay solicitudes registradas')
            ->emptyStateDescription('Comience creando su primera solicitud en el sistema.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequests::route('/'),
            'create' => Pages\CreateRequest::route('/create'),
            'view' => Pages\ViewRequest::route('/{record}'),
            'edit' => Pages\EditRequest::route('/{record}/edit'),
        ];
    }
}