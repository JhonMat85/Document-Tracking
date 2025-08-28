<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers\AttachmentsRelationManager;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\ProcessState;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction; 
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Pagos';

    protected static ?string $pluralModelLabel = 'Pagos';

    protected static UnitEnum|string|null $navigationGroup = '💰 Gestión Financiera';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('payment_tabs')
                    ->tabs([
                        Tab::make('💰 Información del Pago')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('invoice_id')
                                            ->label('📄 Factura *')
                                            ->relationship('invoice', 'invoice_number')
                                            ->getOptionLabelFromRecordUsing(function ($record) {
                                                $clientName = $record->hes?->executedService?->purchaseOrder?->quote?->request?->client?->business_name ?? 'Sin cliente';
                                                $stateName = $record->state?->name ?? 'Sin estado';
                                                $amount = number_format($record->total ?? 0, 2);
                                                
                                                return "{$record->invoice_number} - {$clientName} - S/ {$amount} ({$stateName})";
                                            })
                                            ->searchable(['invoice_number'])
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state) {
                                                    $invoice = Invoice::with(['hes.executedService.purchaseOrder.quote.request.client', 'state'])->find($state);
                                                    if ($invoice) {
                                                        $set('invoiced_amount', number_format($invoice->total ?? 0, 2, '.', ''));
                                                        
                                                        // Pre-llenar descuento si el HES tiene información de factoring
                                                        if ($invoice->hes && $invoice->hes->payment_method && str_contains(strtolower($invoice->hes->payment_method), 'factoring')) {
                                                            $set('discount_percentage', $invoice->hes->discount_percentage ?? 0);
                                                        }
                                                    }
                                                }
                                            })
                                            ->placeholder('Selecciona una factura emitida...')
                                            ->helperText('Solo facturas emitidas están disponibles para registrar pagos'),

                                        Forms\Components\DatePicker::make('payment_date')
                                            ->label('📅 Fecha de Pago')
                                            ->required()
                                            ->default(now())
                                            ->displayFormat('d/m/Y')
                                            ->format('Y-m-d'),
                                    ]),

                                Grid::make(1)
                                    ->schema([
                                        Forms\Components\Select::make('state_id')
                                            ->label('📊 Estado del Pago')
                                            ->options(ProcessState::where('entity', 'payment')
                                                ->where('is_active', true)
                                                ->pluck('name', 'id'))
                                            ->default(function () {
                                                return ProcessState::where('entity', 'payment')
                                                    ->where('is_initial_state', true)
                                                    ->first()?->id;
                                            })
                                            ->required()
                                            ->searchable()
                                            ->preload(),
                                    ]),
                            ]),

                        Tab::make('🏦 Datos Bancarios')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('payment_method')
                                            ->label('💳 Método de Pago')
                                            ->options([
                                                'Transferencia Bancaria' => '🏦 Transferencia Bancaria',
                                                'Cheque' => '📝 Cheque',
                                                'Efectivo' => '💵 Efectivo',
                                                'Factoring' => '📋 Factoring',
                                                'Pago Electrónico' => '💻 Pago Electrónico',
                                            ])
                                            ->required()
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, $get) {
                                                if ($state === 'Factoring') {
                                                    // Si es factoring, mantener el descuento actual o usar el de la factura
                                                    $currentDiscount = $get('discount_percentage') ?? 0;
                                                } else {
                                                    // Si NO es factoring, descuento = 0
                                                    $set('discount_percentage', 0);
                                                    $currentDiscount = 0;
                                                }
                                                
                                                // Recalcular montos
                                                $invoicedAmount = floatval($get('invoiced_amount') ?? 0);
                                                $discountAmount = $invoicedAmount * ($currentDiscount / 100);
                                                $netReceived = $invoicedAmount - $discountAmount;
                                                
                                                $set('discount_amount', number_format($discountAmount, 2, '.', ''));
                                                $set('net_received_amount', number_format($netReceived, 2, '.', ''));
                                            })
                                            ->placeholder('Selecciona el método...'),

                                        Forms\Components\TextInput::make('bank_reference')
                                            ->label('🔢 Referencia Bancaria')
                                            ->maxLength(255)
                                            ->placeholder('Número de referencia...'),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('origin_bank')
                                            ->label('🏢 Banco Origen')
                                            ->maxLength(255)
                                            ->placeholder('Nombre del banco origen...'),

                                        Forms\Components\TextInput::make('delay_days')
                                            ->label('⏰ Días de Atraso')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->placeholder('0'),
                                    ]),
                            ]),

                        Tab::make('📊 Cálculos')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('invoiced_amount')
                                            ->label('💰 Monto Facturado')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, $get) {
                                                $discountPercentage = floatval($get('discount_percentage') ?? 0);
                                                $invoicedAmount = floatval($state ?? 0);
                                                $discountAmount = $invoicedAmount * ($discountPercentage / 100);
                                                $netReceived = $invoicedAmount - $discountAmount;
                                                
                                                $set('discount_amount', number_format($discountAmount, 2, '.', ''));
                                                $set('net_received_amount', number_format($netReceived, 2, '.', ''));
                                            })
                                            ->afterStateHydrated(function ($state, Set $set, $get) {
                                                // Calcular al cargar el formulario
                                                $discountPercentage = floatval($get('discount_percentage') ?? 0);
                                                $invoicedAmount = floatval($state ?? 0);
                                                $discountAmount = $invoicedAmount * ($discountPercentage / 100);
                                                $netReceived = $invoicedAmount - $discountAmount;
                                                
                                                $set('discount_amount', number_format($discountAmount, 2, '.', ''));
                                                $set('net_received_amount', number_format($netReceived, 2, '.', ''));
                                            })
                                            ->placeholder('0.00'),

                                        Forms\Components\TextInput::make('discount_percentage')
                                            ->label('📉 % Descuento')
                                            ->numeric()
                                            ->suffix('%')
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->live()
                                            ->disabled(fn ($get) => $get('payment_method') !== 'Factoring')
                                            ->helperText(fn ($get) => $get('payment_method') === 'Factoring' ? 
                                                'Descuento aplicable por factoring' : 
                                                'Solo disponible para factoring'
                                            )
                                            ->afterStateUpdated(function ($state, Set $set, $get) {
                                                $invoicedAmount = floatval($get('invoiced_amount') ?? 0);
                                                $discountPercentage = floatval($state ?? 0);
                                                $discountAmount = $invoicedAmount * ($discountPercentage / 100);
                                                $netReceived = $invoicedAmount - $discountAmount;
                                                
                                                $set('discount_amount', number_format($discountAmount, 2, '.', ''));
                                                $set('net_received_amount', number_format($netReceived, 2, '.', ''));
                                            })
                                            ->afterStateHydrated(function ($state, Set $set, $get) {
                                                // Calcular al cargar el formulario
                                                $invoicedAmount = floatval($get('invoiced_amount') ?? 0);
                                                $discountPercentage = floatval($state ?? 0);
                                                $discountAmount = $invoicedAmount * ($discountPercentage / 100);
                                                $netReceived = $invoicedAmount - $discountAmount;
                                                
                                                $set('discount_amount', number_format($discountAmount, 2, '.', ''));
                                                $set('net_received_amount', number_format($netReceived, 2, '.', ''));
                                            })
                                            ->placeholder('0'),

                                        Forms\Components\TextInput::make('discount_amount')
                                            ->label('💸 Monto Descuento')
                                            ->numeric()
                                            ->prefix('$')
                                            ->readOnly()
                                            ->default(0)
                                            ->dehydrated()
                                            ->helperText(fn ($get) => $get('payment_method') === 'Factoring' ? 
                                                'Descuento calculado automáticamente' : 
                                                'No aplica descuento para este método de pago'
                                            )
                                            ->placeholder('0.00'),
                                    ]),

                                Grid::make(1)
                                    ->schema([
                                        Forms\Components\TextInput::make('net_received_amount')
                                            ->label('💎 Monto Neto Recibido')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->placeholder('0.00'),
                                    ]),
                            ]),

                        Tab::make('📝 Observaciones')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('📝 Notas del Pago')
                                    ->rows(4)
                                    ->placeholder('Observaciones sobre el pago, diferencias, condiciones especiales, etc.')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('📎 Documentos')
                            ->schema([
                                Section::make('Archivos Adjuntos')
                                    ->description('Sube comprobantes de pago, transferencias y documentos relacionados')
                                    ->icon('heroicon-o-document-arrow-up')
                                    ->schema([
                                        Forms\Components\FileUpload::make('attachment_files')
                                            ->label('📄 Documentos del Pago')
                                            ->multiple()
                                            ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg'])
                                            ->disk('public')
                                            ->directory('payment-attachments')
                                            ->visibility('public')
                                            ->panelLayout('grid')
                                            ->maxFiles(10)
                                            ->maxSize(15360) // 15MB
                                            ->helperText('Formatos permitidos: PDF, PNG, JPG. Máximo 10 archivos de 15MB cada uno.')
                                            ->hint('Los documentos se almacenarán de forma segura y estarán disponibles para consulta.')
                                            ->hintIcon('heroicon-o-information-circle')
                                            ->columnSpanFull()
                                            ->storeFiles(false), // No almacenar automáticamente
                                    ]),
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
                        Tables\Columns\TextColumn::make('payment_date')
                            ->label('📅 Fecha de Pago')
                            ->date('d/m/Y')
                            ->sortable()
                            ->searchable()
                            ->weight('medium')
                            ->size('sm')
                            ->color('primary'),

                        Tables\Columns\TextColumn::make('invoice.invoice_number')
                            ->label('📄 Número de Factura')
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->color('info')
                            ->copyable()
                            ->tooltip('Haz clic para copiar'),

                        Tables\Columns\TextColumn::make('invoice.client.name')
                            ->label('🏥 Cliente')
                            ->searchable()
                            ->sortable()
                            ->limit(25)
                            ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                                $state = $column->getState();
                                return strlen($state) > 25 ? $state : null;
                            })
                            ->placeholder('👤 Sin cliente'),
                    ]),

                    Stack::make([
                        Tables\Columns\TextColumn::make('payment_method')
                            ->label('💳 Método de Pago')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Factoring' => 'warning',
                                'Efectivo' => 'success',
                                'Transferencia Bancaria' => 'info',
                                'Cheque' => 'gray',
                                'Pago Electrónico' => 'primary',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'Transferencia Bancaria' => '🏦 Transferencia',
                                'Cheque' => '📝 Cheque',
                                'Efectivo' => '💵 Efectivo',
                                'Factoring' => '📋 Factoring',
                                'Pago Electrónico' => '💻 Electrónico',
                                default => $state,
                            }),

                        Tables\Columns\TextColumn::make('invoiced_amount')
                            ->label('💰 Monto Facturado')
                            ->money('USD')
                            ->alignEnd()
                            ->sortable()
                            ->weight('medium')
                            ->color('gray'),

                        Tables\Columns\TextColumn::make('discount_percentage')
                            ->label('📉 % Descuento')
                            ->suffix('%')
                            ->alignEnd()
                            ->badge()
                            ->color(fn (?float $state): string => match (true) {
                                $state === null || $state === 0.0 => 'gray',
                                $state <= 5 => 'success',
                                $state <= 15 => 'warning',
                                default => 'danger',
                            })
                            ->placeholder('➖ Sin desc.'),
                    ]),

                    Stack::make([
                        Tables\Columns\TextColumn::make('net_received_amount')
                            ->label('💎 Neto Recibido')
                            ->money('USD')
                            ->alignEnd()
                            ->sortable()
                            ->weight('bold')
                            ->color('success')
                            ->size('lg'),

                        Tables\Columns\TextColumn::make('delay_days')
                            ->label('⏰ Días de Atraso')
                            ->badge()
                            ->color(fn (?int $state): string => match (true) {
                                $state === null || $state === 0 => 'success',
                                $state <= 30 => 'warning',
                                $state <= 60 => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?int $state): string => match (true) {
                                $state === null || $state === 0 => '✅ Al día',
                                $state === 1 => '🟡 1 día',
                                default => "🔴 {$state} días",
                            }),

                        Tables\Columns\TextColumn::make('state.name')
                            ->label('📊 Estado del Pago')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Pagado' => 'success',
                                'Pago Parcial' => 'warning',
                                'Por Identificar' => 'info',
                                'Rechazado' => 'danger',
                                default => 'gray',
                            })
                            ->placeholder('🔄 Sin estado'),
                    ]),
                ])->from('md'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('💳 Método de Pago')
                    ->options([
                        'Transferencia Bancaria' => '🏦 Transferencia Bancaria',
                        'Cheque' => '📝 Cheque',
                        'Efectivo' => '💵 Efectivo',
                        'Factoring' => '📋 Factoring',
                        'Pago Electrónico' => '💻 Pago Electrónico',
                    ])
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('delayed')
                    ->label('⏰ Pagos Atrasados')
                    ->query(fn (Builder $query): Builder => $query->where('delay_days', '>', 0))
                    ->toggle(),

                Tables\Filters\Filter::make('with_discount')
                    ->label('📉 Con Descuento')
                    ->query(fn (Builder $query): Builder => $query->where('discount_percentage', '>', 0))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('state_id')
                    ->label('📊 Estado')
                    ->relationship('state', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\Filter::make('payment_date')
                    ->label('📅 Rango de Fechas')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('📅 Desde')
                            ->placeholder('Fecha inicial')
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('until')
                            ->label('📅 Hasta')
                            ->placeholder('Fecha final')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'Desde: ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Hasta: ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),

                Tables\Filters\Filter::make('recent')
                    ->label('🆕 Recientes (7 días)')
                    ->query(fn (Builder $query): Builder => $query->where('payment_date', '>=', now()->subDays(7)))
                    ->toggle(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('👁️ Ver')
                        ->color('info'),
                    EditAction::make()
                        ->label('✏️ Editar')
                        ->color('warning'),
                    DeleteAction::make()
                        ->label('🗑️ Eliminar')
                        ->color('danger'),
                ])
                ->label('Acciones')
                ->color('gray')
                ->button()
                ->size('sm'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('🗑️ Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('payment_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->searchOnBlur()
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->deferLoading()
            ->emptyStateHeading('💰 No hay pagos registrados')
            ->emptyStateDescription('Comience registrando el primer pago en el sistema.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getRelations(): array
    {
        return [
            AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}