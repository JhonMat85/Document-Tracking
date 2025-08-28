<?php

namespace App\Filament\Resources;

use App\Models\Rfq;
use App\Models\Request;
use App\Models\ProcessState;
use App\Filament\Resources\RfqResource\Pages;
use App\Filament\Resources\RfqResource\RelationManagers;
use BackedEnum;
use UnitEnum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
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
use Illuminate\Database\Eloquent\Builder;

class RfqResource extends Resource
{
    protected static ?string $model = Rfq::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'RFQ Recibidos';

    protected static ?string $pluralModelLabel = 'RFQ Recibidos';

    protected static UnitEnum|string|null $navigationGroup = '📋 Gestión Comercial';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('rfq_tabs')
                    ->tabs([
                        Tab::make('📋 Información del RFQ')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('rfq_number')
                                                    ->label('🔢 Número de RFQ')
                                                    ->required()
                                                    ->placeholder('Ej: RQF375')
                                                    ->maxLength(255)
                                                    ->helperText('Número único del RFQ'),

                                                Forms\Components\Select::make('state_id')
                                                    ->label('📊 Estado')
                                                    ->relationship('state', 'name', function ($query) {
                                                        $query->where('entity', 'rfq')
                                                            ->where('is_active', true);
                                                    })
                                                    ->default(function () {
                                                        return ProcessState::where('entity', 'rfq')
                                                            ->where('is_initial_state', true)
                                                            ->first()?->id;
                                                    })
                                                    ->required()
                                                    ->helperText('Estado actual del proceso del RFQ'),
                                            ]),

                                        Grid::make(1)
                                            ->schema([
                                                Forms\Components\Select::make('request_id')
                                                    ->label('📝 Solicitud Asociada')
                                                    ->options(Request::all()->pluck('request_number', 'id'))
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccionar solicitud existente (opcional)')
                                                    ->helperText('Vincula este RFQ con una solicitud existente si corresponde'),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\DateTimePicker::make('received_date')
                                                    ->label('📅 Fecha de Recepción')
                                                    ->required()
                                                    ->default(now())
                                                    ->helperText('Fecha en que se recibió el RFQ'),

                                                Forms\Components\DateTimePicker::make('quote_deadline')
                                                    ->label('⏰ Fecha Límite para Cotizar')
                                                    ->required()
                                                    ->after('received_date')
                                                    ->helperText('Fecha límite para enviar la cotización'),
                                            ]),
                                    ])
                                    ->columnSpan('full'),
                            ]),

                        Tab::make('📝 Descripción del Servicio')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Section::make('Descripción Detallada')
                                    ->description('Detalla el servicio solicitado en el RFQ')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Forms\Components\Textarea::make('detailed_description')
                                            ->label('📝 Descripción del Servicio')
                                            ->required()
                                            ->rows(6)
                                            ->placeholder('Descripción completa y detallada del servicio solicitado en el RFQ...')
                                            ->helperText('Proporciona una descripción clara y completa del servicio requerido'),
                                    ]),

                                Section::make('Observaciones')
                                    ->description('Notas adicionales y comentarios')
                                    ->icon('heroicon-o-chat-bubble-left-right')
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label('📝 Notas Adicionales')
                                            ->rows(4)
                                            ->placeholder('Observaciones adicionales, comentarios o información relevante del RFQ...')
                                            ->helperText('Información adicional que pueda ser útil para el procesamiento del RFQ'),
                                    ]),
                            ]),

                        Tab::make('📎 Documentos')
                            ->icon('heroicon-o-paper-clip')
                            ->schema([
                                Section::make('Archivos Adjuntos')
                                    ->description('Sube documentos relacionados con este RFQ')
                                    ->icon('heroicon-o-document-arrow-up')
                                    ->schema([
                                        FileUpload::make('attachment_files')
                                            ->label('📄 Documentos del RFQ')
                                            ->multiple()
                                            ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                            ->disk('public')
                                            ->directory('rfq-attachments')
                                            ->visibility('public')
                                            ->panelLayout('grid')
                                            ->maxFiles(10)
                                            ->maxSize(15360) // 15MB
                                            ->helperText('Formatos permitidos: PDF, PNG, JPG, DOC, DOCX. Máximo 10 archivos de 15MB cada uno.')
                                            ->hint('Los documentos se almacenarán de forma segura y estarán disponibles para consulta.')
                                            ->hintIcon('heroicon-o-information-circle')
                                            ->columnSpanFull()
                                            ->storeFiles(false), // No almacenar automáticamente
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
                        Tables\Columns\TextColumn::make('rfq_number')
                            ->label('🔢 Número RFQ')
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->color('primary')
                            ->copyable()
                            ->tooltip('Número único del RFQ'),

                        Tables\Columns\TextColumn::make('request.client.business_name')
                            ->label('🏥 Cliente')
                            ->searchable()
                            ->sortable()
                            ->size('lg')
                            ->weight('medium')
                            ->limit(25)
                            ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                                $state = $column->getState();
                                return strlen($state) > 25 ? $state : null;
                            })
                            ->placeholder('Sin cliente'),

                        Tables\Columns\TextColumn::make('request.requesting_department')
                            ->label('🏢 Área')
                            ->badge()
                            ->color('gray')
                            ->placeholder('Sin área')
                            ->limit(20),
                    ])->space(1),

                    Stack::make([
                        Tables\Columns\TextColumn::make('quote_deadline')
                            ->label('⏰ Urgencia')
                            ->badge()
                            ->formatStateUsing(function ($state) {
                                if (!$state) return '⚪ Sin fecha';
                                $now = now();
                                if ($state < $now) {
                                    return '🔴 Vencido';
                                } elseif ($state <= $now->addDays(3)) {
                                    return '🟡 Próximo a vencer';
                                } else {
                                    return '🟢 En tiempo';
                                }
                            })
                            ->color(function ($state) {
                                if (!$state) return 'gray';
                                $now = now();
                                if ($state < $now) {
                                    return 'danger';
                                } elseif ($state <= $now->addDays(3)) {
                                    return 'warning';
                                } else {
                                    return 'success';
                                }
                            }),

                        Tables\Columns\TextColumn::make('quotes_count')
                            ->label('📊 Cotizaciones')
                            ->badge()
                            ->counts('quotes')
                            ->formatStateUsing(fn (int $state): string => match ($state) {
                                0 => '📋 Sin cotizaciones',
                                1 => '📄 1 cotización',
                                default => "📄 {$state} cotizaciones",
                            })
                            ->color(fn (int $state): string => match ($state) {
                                0 => 'warning',
                                1 => 'info',
                                default => 'success',
                            }),

                        Tables\Columns\TextColumn::make('detailed_description')
                            ->label('📝 Descripción')
                            ->searchable()
                            ->icon('heroicon-o-document-text')
                            ->color('info')
                            ->placeholder('Sin descripción')
                            ->limit(30)
                            ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                                $state = $column->getState();
                                return strlen($state) > 30 ? $state : null;
                            }),
                    ])->space(1),

                    Stack::make([
                        Tables\Columns\TextColumn::make('state.name')
                            ->label('📊 Estado')
                            ->badge()
                            ->color('primary')
                            ->icon('heroicon-o-clipboard-document-check'),

                        Tables\Columns\TextColumn::make('received_date')
                            ->label('📅 F. Recepción')
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
                Tables\Filters\Filter::make('pending_deadline')
                    ->label('⏰ Próximos a Vencer (3 días)')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('quote_deadline', '>', now())
                              ->where('quote_deadline', '<=', now()->addDays(3))
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('overdue')
                    ->label('🔴 Vencidos')
                    ->query(fn (Builder $query): Builder => $query->where('quote_deadline', '<', now()))
                    ->toggle(),

                Tables\Filters\Filter::make('no_quotes')
                    ->label('📋 Sin Cotizaciones')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('quotes'))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('state_id')
                    ->label('📊 Estado')
                    ->relationship('state', 'name', function ($query) {
                        $query->where('entity', 'rfq')
                            ->where('is_active', true);
                    })
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('recent')
                    ->label('🆕 Recientes (7 días)')
                    ->query(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7)))
                    ->toggle(),

                Tables\Filters\Filter::make('received_date')
                    ->label('📅 Rango de Fechas')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('received_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('received_date', '<=', $date),
                            );
                    }),
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
            ->emptyStateHeading('📋 No hay RFQs registrados')
            ->emptyStateDescription('Comience creando su primer RFQ en el sistema.')
            ->emptyStateIcon('heroicon-o-document-text');
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
            'index' => Pages\ListRfqs::route('/'),
            'create' => Pages\CreateRfq::route('/create'),
            'view' => Pages\ViewRfq::route('/{record}'),
            'edit' => Pages\EditRfq::route('/{record}/edit'),
        ];
    }
}