<?php

namespace App\Filament\Resources;

use App\Models\Hes;
use App\Models\ExecutedService;
use App\Models\ProcessState;
use App\Models\PurchaseOrder;
use App\Filament\Resources\HesResource\Pages;
use App\Filament\Resources\HesResource\RelationManagers;
use BackedEnum;
use UnitEnum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
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

class HesResource extends Resource
{
    protected static ?string $model = Hes::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'HES (Crítico)';

    protected static ?string $modelLabel = 'HES';

    protected static ?string $pluralModelLabel = 'HES';

    protected static UnitEnum|string|null $navigationGroup = '🔧 Gestión Operativa';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('hes_tabs')
                    ->tabs([
                        Tab::make('⚠️ Información Crítica del HES')
                            ->icon('heroicon-o-document-check')
                            ->schema([
                                Section::make('⚠️ DOCUMENTO CRÍTICO PARA FACTURACIÓN')
                                    ->description('Sin HES registrado NO ES POSIBLE FACTURAR')
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('hes_number')
                                                    ->label('🔢 Número de HES')
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(50)
                                                    ->placeholder('ej: 1000070935')
                                                    ->helperText('Número único del documento HES'),

                                                Forms\Components\Select::make('state_id')
                                                    ->label('📊 Estado')
                                                    ->relationship('state', 'name', function ($query) {
                                                        $query->where('entity', 'hes')
                                                            ->where('is_active', true);
                                                    })
                                                    ->default(function () {
                                                        return ProcessState::where('entity', 'hes')
                                                            ->where('is_initial_state', true)
                                                            ->first()?->id;
                                                    })
                                                    ->required()
                                                    ->helperText('Estado actual del proceso del HES'),
                                            ]),

                                        Grid::make(1)
                                            ->schema([
                                                Forms\Components\Select::make('executed_service_id')
                                                    ->label('🔗 Servicio Ejecutado')
                                                    ->relationship('executedService', 'id')
                                                    ->getOptionLabelFromRecordUsing(fn ($record) => "Servicio #{$record->id} - {$record->purchaseOrder->po_number}")
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->helperText('Vincula este HES con un servicio ejecutado'),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('reference_po_number')
                                                    ->label('📋 Número de OC de Referencia')
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->options(function () {
                                                        return PurchaseOrder::with('state')
                                                            ->orderBy('po_number')
                                                            ->get()
                                                            ->mapWithKeys(function ($po) {
                                                                $label = $po->po_number;
                                                                if ($po->state) {
                                                                    $label .= ' (' . $po->state->name . ')';
                                                                }
                                                                return [$po->po_number => $label];
                                                            })
                                                            ->toArray();
                                                    })
                                                    ->helperText('Seleccione una OC registrada en el sistema')
                                                    ->placeholder('Buscar OC...')
                                                    ->noSearchResultsMessage('No se encontraron OC con ese número'),

                                                Forms\Components\TextInput::make('reference_solped_number')
                                                    ->label('📄 Número de SOLPED de Referencia')
                                                    ->maxLength(50)
                                                    ->helperText('Número de SOLPED relacionado (opcional)'),
                                            ]),
                                    ])
                                    ->columnSpan('full'),
                            ]),

                        Tab::make('👤 Datos del Aprobador')
                            ->schema([
                                Section::make('Información del Responsable')
                                    ->description('Datos del funcionario que aprueba el HES')
                                    ->icon('heroicon-o-identification')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\DateTimePicker::make('approval_date')
                                                    ->label('📅 Fecha de Aprobación del HES')
                                                    ->required()
                                                    ->default(now())
                                                    ->helperText('Fecha en que se aprobó el HES'),

                                                Forms\Components\TextInput::make('approver_position')
                                                    ->label('💼 Cargo del Aprobador')
                                                    ->maxLength(150)
                                                    ->placeholder('ej: Jefe de Operaciones')
                                                    ->helperText('Posición jerárquica del aprobador'),
                                            ]),

                                        Grid::make(1)
                                            ->schema([
                                                Forms\Components\TextInput::make('approver_name')
                                                    ->label('👤 Nombre del Aprobador')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('ej: MENDOZA ANTONIO FRANCISCO')
                                                    ->helperText('Nombre completo del funcionario que aprueba'),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('💰 Condiciones Comerciales')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Section::make('Montos Autorizados')
                                    ->description('Importes y condiciones de facturación')
                                    ->icon('heroicon-o-currency-dollar')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('authorized_amount')
                                                    ->label('💰 Monto Autorizado para Facturar')
                                                    ->numeric()
                                                    ->prefix('S/')
                                                    ->required()
                                                    ->helperText('Monto máximo que se puede facturar'),

                                                Forms\Components\Select::make('currency')
                                                    ->label('💱 Moneda')
                                                    ->options([
                                                        'PEN' => 'Soles (PEN)',
                                                        'USD' => 'Dólares (USD)',
                                                    ])
                                                    ->default('PEN')
                                                    ->required()
                                                    ->helperText('Moneda de la transacción'),

                                                Forms\Components\TextInput::make('imputation_type')
                                                    ->label('📊 Tipo de Imputación')
                                                    ->maxLength(100)
                                                    ->placeholder('ej: OPEX, CAPEX')
                                                    ->helperText('Clasificación contable'),
                                            ]),
                                    ]),

                                Section::make('Condiciones de Pago')
                                    ->description('Términos y modalidades de pago')
                                    ->icon('heroicon-o-credit-card')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\Select::make('payment_method')
                                                    ->label('💳 Forma de Pago')
                                                    ->options([
                                                        'factoring' => 'Factoring',
                                                        'direct_payment' => 'Pago Directo',
                                                        'cash' => 'Contado',
                                                        'others' => 'Otros',
                                                    ])
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, Set $set) {
                                                        if ($state !== 'factoring') {
                                                            $set('discount_percentage', null);
                                                        }
                                                    })
                                                    ->helperText('Modalidad de pago acordada'),

                                                Forms\Components\TextInput::make('payment_days')
                                                    ->label('⏰ Término de Pago (días)')
                                                    ->numeric()
                                                    ->placeholder('ej: 30, 60, 90')
                                                    ->helperText('Días para el pago (si aplica)'),

                                                Forms\Components\TextInput::make('discount_percentage')
                                                    ->label('📉 Porcentaje de Descuento (%)')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->visible(fn (Get $get): bool => $get('payment_method') === 'factoring')
                                                    ->helperText('Descuento aplicable (solo factoring)'),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('📝 Observaciones')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Section::make('Notas Adicionales')
                                    ->description('Comentarios y observaciones importantes')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label('📝 Notas Adicionales')
                                            ->rows(6)
                                            ->placeholder('Observaciones adicionales, comentarios importantes o información relevante del HES...')
                                            ->helperText('Información adicional que pueda ser útil para el procesamiento del HES'),
                                    ]),
                            ]),

                        Tab::make('📎 Documentos')
                            ->icon('heroicon-o-paper-clip')
                            ->schema([
                                Section::make('Archivos Adjuntos')
                                    ->description('Sube documentos relacionados con este HES')
                                    ->icon('heroicon-o-document-arrow-up')
                                    ->schema([
                                        FileUpload::make('attachment_files')
                                            ->label('📄 Documentos del HES')
                                            ->multiple()
                                            ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg'])
                                            ->disk('public')
                                            ->directory('hes-attachments')
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

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                Split::make([
                    Stack::make([
                        Tables\Columns\TextColumn::make('hes_number')
                            ->label('🔢 Número HES')
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->color('primary')
                            ->copyable()
                            ->tooltip('Número único del HES crítico'),

                        Tables\Columns\TextColumn::make('executedService.purchaseOrder.po_number')
                            ->label('📋 OC Relacionada')
                            ->searchable()
                            ->sortable()
                            ->size('lg')
                            ->weight('medium')
                            ->limit(25)
                            ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                                $state = $column->getState();
                                return strlen($state) > 25 ? $state : null;
                            })
                            ->placeholder('Sin OC'),

                        Tables\Columns\TextColumn::make('reference_po_number')
                            ->label('🏢 OC Referencia')
                            ->badge()
                            ->color('gray')
                            ->placeholder('Sin referencia')
                            ->limit(20),
                    ])->space(1),

                    Stack::make([
                        Tables\Columns\TextColumn::make('authorized_amount')
                            ->label('💰 Monto Autorizado')
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

                        Tables\Columns\TextColumn::make('payment_method')
                            ->label('💳 Forma de Pago')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'factoring' => '🏦 Factoring',
                                'direct_payment' => '💳 Pago Directo',
                                'cash' => '💰 Contado',
                                'others' => '📄 Otros',
                                default => '❓ Sin definir',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'factoring' => 'warning',
                                'direct_payment' => 'success',
                                'cash' => 'info',
                                'others' => 'gray',
                                default => 'gray',
                            }),

                        Tables\Columns\TextColumn::make('approver_name')
                            ->label('👤 Aprobador')
                            ->searchable()
                            ->color('info')
                            ->placeholder('Sin aprobador')
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

                        Tables\Columns\IconColumn::make('can_invoice')
                            ->label('✅ Puede Facturar')
                            ->getStateUsing(fn ($record) => !is_null($record->hes_number))
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger')
                            ->tooltip(fn ($record) => $record && !is_null($record->hes_number) ? 'HES aprobado - Listo para facturar' : 'HES pendiente - No se puede facturar'),

                        Tables\Columns\TextColumn::make('approval_date')
                            ->label('📅 F. Aprobación')
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
                Tables\Filters\Filter::make('high_amount')
                    ->label('💰 Montos Altos (≥100k)')
                    ->query(fn (Builder $query): Builder => $query->where('authorized_amount', '>=', 100000))
                    ->toggle(),

                Tables\Filters\Filter::make('factoring_only')
                    ->label('🏦 Solo Factoring')
                    ->query(fn (Builder $query): Builder => $query->where('payment_method', 'factoring'))
                    ->toggle(),

                Tables\Filters\Filter::make('can_invoice')
                    ->label('✅ Listos para Facturar')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('hes_number'))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('💳 Forma de Pago')
                    ->options([
                        'factoring' => 'Factoring',
                        'direct_payment' => 'Pago Directo',
                        'cash' => 'Contado',
                        'others' => 'Otros',
                    ])
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('state_id')
                    ->label('📊 Estado')
                    ->relationship('state', 'name', function ($query) {
                        $query->where('entity', 'hes')
                            ->where('is_active', true);
                    })
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('currency')
                    ->label('💱 Moneda')
                    ->options([
                        'PEN' => 'Soles (PEN)',
                        'USD' => 'Dólares (USD)',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('recent')
                    ->label('🆕 Recientes (7 días)')
                    ->query(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7)))
                    ->toggle(),

                Tables\Filters\Filter::make('approval_date')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('approval_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('approval_date', '<=', $date),
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
            ->emptyStateHeading('⚠️ No hay HES registrados')
            ->emptyStateDescription('Comience creando su primer HES crítico en el sistema.')
            ->emptyStateIcon('heroicon-o-document-check');
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
            'index' => Pages\ListHes::route('/'),
            'create' => Pages\CreateHes::route('/create'),
            'view' => Pages\ViewHes::route('/{record}'),
            'edit' => Pages\EditHes::route('/{record}/edit'),
        ];
    }
}