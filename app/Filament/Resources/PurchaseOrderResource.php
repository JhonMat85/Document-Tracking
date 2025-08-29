<?php

namespace App\Filament\Resources;

use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\ProcessState;
use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use BackedEnum;
use UnitEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction; 
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Órdenes de Compra';

    protected static ?string $pluralModelLabel = 'Órdenes de Compra';

    protected static UnitEnum|string|null $navigationGroup = '🔧 Gestión Operativa';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('purchase_order_tabs')
                    ->tabs([
                        Tab::make('🛒 Información General')
                            ->icon('heroicon-o-shopping-cart')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('po_number')
                                                    ->label('🔢 Número de OC')
                                                    ->required()
                                                    ->placeholder('Ej: 4600021590-00010')
                                                    ->maxLength(255)
                                                    ->helperText('Número único de la orden de compra'),

                                                TextInput::make('solped_number')
                                                    ->label('📋 Número de SOLPED')
                                                    ->placeholder('Ej: 2000013516-00020')
                                                    ->maxLength(255)
                                                    ->helperText('Número de solicitud de pedido (opcional)'),
                                            ]),

                                        Grid::make(1)
                                            ->schema([
                                                Select::make('quote_id')
                                                    ->label('📊 Cotización Asociada')
                                                    ->options(function() {
                                                        return Quote::with(['request.client', 'state'])
                                                            ->whereHas('state', function($query) {
                                                                $query->where('entity', 'quote')
                                                                      ->whereIn('code', ['sent', 'approved']);
                                                            })
                                                            ->get()
                                                            ->mapWithKeys(function ($quote) {
                                                                return [$quote->id => "COT-{$quote->quote_number} - {$quote->request->client->business_name}"];
                                                            });
                                                    })
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->helperText('Selecciona la cotización aprobada para esta OC'),
                                            ]),
                                    ])
                                    ->columnSpan('full'),
                            ]),

                        Tab::make('📅 Fechas y Programación')
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                Section::make('Fechas Importantes')
                                    ->description('Establece las fechas clave para la orden de compra')
                                    ->icon('heroicon-o-calendar')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                DatePicker::make('issue_date')
                                                    ->label('📅 Fecha de Emisión OC')
                                                    ->required()
                                                    ->default(now())
                                                    ->helperText('Fecha en que se emitió la OC'),

                                                DatePicker::make('validity_date')
                                                    ->label('⏰ Fecha de Validez')
                                                    ->after('issue_date')
                                                    ->helperText('Fecha límite de validez de la OC'),

                                                DatePicker::make('scheduled_execution_date')
                                                    ->label('🎯 Fecha Programada Ejecución')
                                                    ->required()
                                                    ->helperText('Fecha programada para ejecutar el servicio'),
                                            ]),
                                    ]),

                                Section::make('Asignación y Área')
                                    ->description('Define el personal y área responsable')
                                    ->icon('heroicon-o-user-group')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('assigned_technician')
                                                    ->label('👨‍🔧 Técnico Asignado')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Nombre del técnico responsable')
                                                    ->helperText('Técnico que ejecutará el servicio'),

                                                TextInput::make('reception_area')
                                                    ->label('🏢 Área de Recepción')
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Cardiología, Laboratorio')
                                                    ->helperText('Área donde se recibirá el servicio'),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('💰 Información Financiera')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('Montos y Moneda')
                                    ->description('Establece el monto autorizado y la moneda')
                                    ->icon('heroicon-o-banknotes')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('authorized_amount')
                                                    ->label('💵 Monto Autorizado')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->required()
                                                    ->step(0.01)
                                                    ->helperText('Monto total autorizado para la orden'),

                                                Select::make('currency')
                                                    ->label('💱 Moneda')
                                                    ->options([
                                                        'PEN' => 'PEN - Soles Peruanos',
                                                        'USD' => 'USD - Dólar Americano',
                                                    ])
                                                    ->default('PEN')
                                                    ->required()
                                                    ->helperText('Moneda de la orden de compra'),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('📎 Documentos')
                            ->icon('heroicon-o-paper-clip')
                            ->schema([
                                Section::make('Archivos Adjuntos')
                                    ->description('Sube la orden de compra y documentos relacionados')
                                    ->icon('heroicon-o-document-arrow-up')
                                    ->schema([
                                        FileUpload::make('attachment_files')
                                            ->label('📄 Documentos de OC')
                                            ->multiple()
                                            ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'])
                                            ->disk('public')
                                            ->directory('purchase-order-attachments')
                                            ->visibility('public')
                                            ->panelLayout('grid')
                                            ->maxFiles(10)
                                            ->maxSize(20480) // 20MB
                                            ->helperText('Formatos permitidos: PDF, PNG, JPG, DOC, DOCX. Máximo 10 archivos de 20MB cada uno.')
                                            ->hint('Incluye la OC original y cualquier documento relacionado.')
                                            ->hintIcon('heroicon-o-information-circle')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('📋 Condiciones y Observaciones')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Section::make('Estado y Condiciones')
                                    ->description('Gestiona el estado y condiciones específicas')
                                    ->icon('heroicon-o-clipboard-document-list')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('state_id')
                                                    ->label('📊 Estado')
                                                    ->relationship('state', 'name', function ($query) {
                                                        $query->where('entity', 'purchase_order')
                                                            ->where('is_active', true);
                                                    })
                                                    ->default(function () {
                                                        return ProcessState::where('entity', 'purchase_order')
                                                            ->where('is_initial_state', true)
                                                            ->first()?->id;
                                                    })
                                                    ->required()
                                                    ->helperText('Estado actual del proceso de la OC'),

                                                Toggle::make('is_urgent')
                                                    ->label('🚨 Servicio Urgente')
                                                    ->helperText('Marcar si el servicio es urgente y requiere atención inmediata'),
                                            ]),

                                        Textarea::make('special_conditions')
                                            ->label('📋 Condiciones Especiales')
                                            ->rows(3)
                                            ->placeholder('Describe las condiciones especiales de la OC...')
                                            ->helperText('Condiciones particulares que debe cumplir la ejecución'),

                                        Textarea::make('notes')
                                            ->label('📝 Notas Adicionales')
                                            ->rows(4)
                                            ->placeholder('Agrega cualquier observación, comentario o información adicional relevante...')
                                            ->helperText('Información adicional importante para la ejecución de la OC'),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Stack::make([
                        Tables\Columns\TextColumn::make('po_number')
                            ->label('🔢 Número OC')
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->color('primary')
                            ->copyable()
                            ->tooltip('Número único de la orden de compra'),

                        Tables\Columns\TextColumn::make('quote.request.client.name')
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

                        Tables\Columns\TextColumn::make('solped_number')
                            ->label('📋 SOLPED')
                            ->badge()
                            ->color('gray')
                            ->placeholder('Sin SOLPED')
                            ->limit(20),
                    ])->space(1),

                    Stack::make([
                        Tables\Columns\TextColumn::make('authorized_amount')
                            ->label('💰 Monto')
                            ->money(fn ($record) => $record->currency ?? 'USD')
                            ->alignEnd()
                            ->sortable()
                            ->weight('bold')
                            ->color('success'),

                        Tables\Columns\TextColumn::make('assigned_technician')
                            ->label('👨‍🔧 Técnico')
                            ->searchable()
                            ->icon('heroicon-o-user')
                            ->color('info')
                            ->limit(20),

                        Tables\Columns\IconColumn::make('is_urgent')
                            ->label('🚨 Urgente')
                            ->boolean()
                            ->trueIcon('heroicon-o-exclamation-triangle')
                            ->falseIcon('heroicon-o-clock')
                            ->trueColor('danger')
                            ->falseColor('gray'),
                    ])->space(1),

                    Stack::make([
                        Tables\Columns\TextColumn::make('scheduled_execution_date')
                            ->label('🎯 F. Programada')
                            ->date('d/m/Y')
                            ->sortable()
                            ->color(fn ($record) => $record->scheduled_execution_date < now() ? 'danger' : 'success')
                            ->icon('heroicon-o-calendar-days'),

                        Tables\Columns\TextColumn::make('issue_date')
                            ->label('📅 F. Emisión')
                            ->date('d/m/Y')
                            ->sortable()
                            ->color('gray')
                            ->size('sm')
                            ->icon('heroicon-o-calendar'),

                        Tables\Columns\TextColumn::make('executed_services_count')
                            ->label('🔧 Servicios')
                            ->counts('executedServices')
                            ->alignCenter()
                            ->badge()
                            ->color(fn (int $state): string => $state > 0 ? 'success' : 'warning'),
                    ])->space(1),
                ])->from('md'),
            ])
            ->contentGrid([
                'md' => 1,
                'lg' => 1,
            ])
            ->filters([
                Tables\Filters\Filter::make('urgent')
                    ->label('🚨 Servicios Urgentes')
                    ->query(fn (Builder $query): Builder => $query->where('is_urgent', true))
                    ->toggle(),

                Tables\Filters\Filter::make('overdue')
                    ->label('⏰ Vencidas')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('scheduled_execution_date', '<', now())
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('pending_execution')
                    ->label('⏳ Pendientes de Ejecutar')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereDoesntHave('executedServices')
                    )
                    ->toggle(),

                Tables\Filters\SelectFilter::make('currency')
                    ->label('💱 Moneda')
                    ->options([
                        'USD' => 'USD',
                        'CLP' => 'CLP',
                        'EUR' => 'EUR',
                    ])
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('recent')
                    ->label('🆕 Recientes (7 días)')
                    ->query(fn ($query) => $query->where('created_at', '>=', now()->subDays(7)))
                    ->toggle(),

                Tables\Filters\Filter::make('issue_date')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('issue_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('issue_date', '<=', $date),
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
            ->defaultSort('issue_date', 'desc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->poll('30s')
            ->deferLoading()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('🛒 No hay órdenes de compra registradas')
            ->emptyStateDescription('Comience creando su primera orden de compra en el sistema.')
            ->emptyStateIcon('heroicon-o-shopping-cart');
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}