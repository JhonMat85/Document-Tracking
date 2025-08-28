<?php

namespace App\Filament\Resources;

use App\Models\Invoice;
use App\Models\Hes;
use App\Models\ProcessState;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
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
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Facturas';

    protected static ?string $modelLabel = 'Factura';

    protected static ?string $pluralModelLabel = 'Facturas';

    protected static UnitEnum|string|null $navigationGroup = '💰 Gestión Financiera';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('invoice_tabs')
                    ->tabs([
                        Tab::make('💰 Información de la Factura')
                            ->schema([
                                Section::make('⚠️ REQUISITO HES INDISPENSABLE')
                                    ->description('Sin HES registrado NO ES POSIBLE CREAR FACTURA')
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('invoice_number')
                                                    ->label('🧾 Número de Factura')
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(50)
                                                    ->placeholder('ej: INV-2024-001')
                                                    ->helperText('Número único de la factura'),

                                                Forms\Components\Select::make('hes_id')
                                                    ->label('📋 HES (REQUERIDO)')
                                                    ->relationship('hes', 'hes_number')
                                                    ->getOptionLabelFromRecordUsing(fn ($record) => "HES: {$record->hes_number} - {$record->approver_name}")
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->helperText('Solo HES aprobados pueden generar facturas'),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('accounting_area')
                                                    ->label('🏢 Área de Contabilidad')
                                                    ->maxLength(100)
                                                    ->placeholder('ej: Contabilidad General')
                                                    ->helperText('Departamento responsable'),

                                                Forms\Components\Select::make('state_id')
                                                    ->label('📊 Estado')
                                                    ->relationship('state', 'name', function ($query) {
                                                        $query->where('entity', 'invoice')
                                                            ->where('is_active', true);
                                                    })
                                                    ->default(function () {
                                                        return ProcessState::where('entity', 'invoice')
                                                            ->where('is_initial_state', true)
                                                            ->first()?->id;
                                                    })
                                                    ->required()
                                                    ->helperText('Estado actual del proceso'),
                                            ]),
                                    ])
                                    ->columnSpan('full'),
                            ]),

                        Tab::make('📅 Fechas y Montos')
                            ->schema([
                                Section::make('Fechas Importantes')
                                    ->description('Fechas de emisión, envío y vencimiento')
                                    ->icon('heroicon-o-calendar-days')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\DateTimePicker::make('issue_date')
                                                    ->label('📅 Fecha de Emisión')
                                                    ->required()
                                                    ->default(now())
                                                    ->helperText('Fecha en que se emitió la factura'),

                                                Forms\Components\DateTimePicker::make('sent_to_client_date')
                                                    ->label('📤 Fecha de Envío al Cliente')
                                                    ->helperText('Fecha en que se envió al cliente'),

                                                Forms\Components\DateTimePicker::make('due_date')
                                                    ->label('⏰ Fecha de Vencimiento')
                                                    ->helperText('Fecha límite para el pago'),
                                            ]),
                                    ]),

                                Section::make('Montos de Facturación')
                                    ->description('Importes y moneda de la factura - Cálculo automático del IGV')
                                    ->icon('heroicon-o-currency-dollar')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('subtotal')
                                                    ->label('💰 Subtotal')
                                                    ->numeric()
                                                    ->prefix('S/')
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                                        $subtotal = floatval($state ?? 0);
                                                        
                                                        // Calcular IGV (18%)
                                                        $taxAmount = $subtotal * 0.18;
                                                        
                                                        // Calcular total
                                                        $total = $subtotal + $taxAmount;
                                                        
                                                        // Actualizar campos automáticamente
                                                        $set('tax_amount', number_format($taxAmount, 2, '.', ''));
                                                        $set('total', number_format($total, 2, '.', ''));
                                                    })
                                                    ->helperText('Monto antes de impuestos - Se calculará automáticamente el IGV (18%)'),

                                                Forms\Components\TextInput::make('tax_amount')
                                                    ->label('📊 Monto de Impuestos (IGV 18%)')
                                                    ->numeric()
                                                    ->prefix('S/')
                                                    ->required()
                                                    ->readOnly()
                                                    ->helperText('Calculado automáticamente: Subtotal × 0.18')
                                                    ->hint('🔄 Auto-calculado')
                                                    ->hintColor('success'),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('total')
                                                    ->label('💵 Total')
                                                    ->numeric()
                                                    ->prefix('S/')
                                                    ->required()
                                                    ->readOnly()
                                                    ->helperText('Calculado automáticamente: Subtotal + IGV')
                                                    ->hint('🔄 Auto-calculado')
                                                    ->hintColor('success'),

                                                Forms\Components\Select::make('currency')
                                                    ->label('💱 Moneda')
                                                    ->options([
                                                        'PEN' => 'Soles (PEN)',
                                                        'USD' => 'Dólares (USD)',
                                                    ])
                                                    ->default('PEN')
                                                    ->required()
                                                    ->helperText('Moneda de la factura'),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('📝 Observaciones')
                            ->schema([
                                Section::make('Notas Adicionales')
                                    ->description('Comentarios y observaciones importantes')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label('📝 Notas Adicionales')
                                            ->rows(6)
                                            ->placeholder('Observaciones adicionales, comentarios importantes o información relevante de la factura...')
                                            ->helperText('Información adicional que pueda ser útil para el procesamiento de la factura'),
                                    ]),
                            ]),

                        Tab::make('📎 Documentos')
                            ->schema([
                                Section::make('Archivos Adjuntos')
                                    ->description('Sube documentos relacionados con esta factura')
                                    ->icon('heroicon-o-document-arrow-up')
                                    ->schema([
                                        FileUpload::make('attachment_files')
                                            ->label('📄 Documentos de la Factura')
                                            ->multiple()
                                            ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg'])
                                            ->disk('public')
                                            ->directory('invoice-attachments')
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
                        Tables\Columns\TextColumn::make('invoice_number')
                            ->label('🧾 Número Factura')
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->color('primary')
                            ->copyable()
                            ->tooltip('Número único de la factura'),

                        Tables\Columns\TextColumn::make('hes.hes_number')
                            ->label('📋 HES Relacionado')
                            ->searchable()
                            ->sortable()
                            ->size('lg')
                            ->weight('medium')
                            ->limit(25)
                            ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                                $state = $column->getState();
                                return strlen($state) > 25 ? $state : null;
                            })
                            ->placeholder('Sin HES'),

                        Tables\Columns\TextColumn::make('hes.executedService.purchaseOrder.quote.request.client.business_name')
                            ->label('🏢 Cliente')
                            ->badge()
                            ->color('gray')
                            ->placeholder('Sin cliente')
                            ->limit(20),
                    ])->space(1),

                    Stack::make([
                        Tables\Columns\TextColumn::make('total')
                            ->label('💰 Total')
                            ->badge()
                            ->money('PEN')
                            ->sortable()
                            ->formatStateUsing(function ($state, $record) {
                                $currency = $record->currency === 'USD' ? '$' : 'S/';
                                return $currency . ' ' . number_format($state, 2);
                            })
                            ->color(function ($state) {
                                if ($state >= 100000) return 'danger';
                                if ($state >= 50000) return 'warning';
                                return 'success';
                            }),

                        Tables\Columns\TextColumn::make('currency')
                            ->label('💱 Moneda')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'PEN' => '🇵🇪 Soles',
                                'USD' => '🇺🇸 Dólares',
                                default => '❓ Sin definir',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'PEN' => 'success',
                                'USD' => 'info',
                                default => 'gray',
                            }),

                        Tables\Columns\TextColumn::make('accounting_area')
                            ->label('🏢 Área Contable')
                            ->searchable()
                            ->color('info')
                            ->placeholder('Sin área')
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
                            ->color('primary'),

                        Tables\Columns\IconColumn::make('has_hes')
                            ->label('✅ HES Válido')
                            ->getStateUsing(fn ($record) => !is_null($record->hes_id))
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-exclamation-triangle')
                            ->trueColor('success')
                            ->falseColor('danger')
                            ->tooltip(fn ($record) => $record && !is_null($record->hes_id) ? 'Factura con HES válido' : 'Factura SIN HES - Revisar urgente'),

                        Tables\Columns\TextColumn::make('due_date')
                            ->label('⏰ F. Vencimiento')
                            ->dateTime('d/m/Y')
                            ->sortable()
                            ->color(function ($record) {
                                if (!$record->due_date) return 'gray';
                                return $record->due_date < now() ? 'danger' : 'success';
                            })
                            ->size('sm')
                            ->placeholder('Sin vencimiento'),

                        Tables\Columns\TextColumn::make('issue_date')
                            ->label('📅 F. Emisión')
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
                Tables\Filters\Filter::make('high_amount')
                    ->label('💰 Montos Altos (≥100k)')
                    ->query(fn (Builder $query): Builder => $query->where('total', '>=', 100000))
                    ->toggle(),

                Tables\Filters\Filter::make('overdue')
                    ->label('🔴 Vencidas')
                    ->query(fn (Builder $query): Builder => $query->where('due_date', '<', now()))
                    ->toggle(),

                Tables\Filters\Filter::make('no_hes')
                    ->label('⚠️ Sin HES')
                    ->query(fn (Builder $query): Builder => $query->whereNull('hes_id'))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('currency')
                    ->label('💱 Moneda')
                    ->options([
                        'PEN' => 'Soles (PEN)',
                        'USD' => 'Dólares (USD)',
                    ])
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('state_id')
                    ->label('📊 Estado')
                    ->relationship('state', 'name', function ($query) {
                        $query->where('entity', 'invoice')
                            ->where('is_active', true);
                    })
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('recent')
                    ->label('🆕 Recientes (7 días)')
                    ->query(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7)))
                    ->toggle(),

                Tables\Filters\Filter::make('issue_date')
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
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('issue_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('issue_date', '<=', $date),
                            );
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info'),
                    EditAction::make()
                        ->color('warning'),
                    DeleteAction::make()
                        ->color('danger'),
                ])
                ->label('Acciones')
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
            ->emptyStateHeading('💰 No hay facturas registradas')
            ->emptyStateDescription('Comience creando su primera factura en el sistema.')
            ->emptyStateIcon('heroicon-o-receipt-percent');
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}