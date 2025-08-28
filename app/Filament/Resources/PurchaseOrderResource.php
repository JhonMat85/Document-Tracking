<?php

namespace App\Filament\Resources;

use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Filament\Resources\PurchaseOrderResource\Pages;
use BackedEnum;
use UnitEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction; 
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Tables;
use Filament\Tables\Table;
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
                Section::make('🛒 Información de la Orden de Compra')
                    ->description('Registro de OC recibida del cliente')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('po_number')
                                    ->label('Número de OC')
                                    ->required()
                                    ->placeholder('Ej: 4600021590-00010')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('solped_number')
                                    ->label('Número de SOLPED')
                                    ->placeholder('Ej: 2000013516-00020')
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Select::make('quote_id')
                            ->label('Cotización Asociada')
                            ->options(Quote::with(['request.client'])
                                ->whereIn('state_id', [3, 4]) // Aprobadas o enviadas
                                ->get()
                                ->mapWithKeys(function ($quote) {
                                    return [$quote->id => "COT-{$quote->quote_number} - {$quote->request->client->name}"];
                                }))
                            ->required()
                            ->searchable()
                            ->preload(),

                        Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('issue_date')
                                    ->label('Fecha de Emisión OC')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\DatePicker::make('validity_date')
                                    ->label('Fecha de Validez')
                                    ->after('issue_date'),

                                Forms\Components\DatePicker::make('scheduled_execution_date')
                                    ->label('Fecha Programada Ejecución')
                                    ->required(),
                            ]),
                    ]),

                Section::make('👨‍🔧 Asignación y Área')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('assigned_technician')
                                    ->label('Técnico Asignado')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('reception_area')
                                    ->label('Área de Recepción')
                                    ->maxLength(255),
                            ]),
                    ]),

                Section::make('💰 Información Financiera')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('authorized_amount')
                                    ->label('Monto Autorizado')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),

                                Forms\Components\Select::make('currency')
                                    ->label('Moneda')
                                    ->options([
                                        'USD' => 'USD - Dólar Americano',
                                        'CLP' => 'CLP - Peso Chileno',
                                        'EUR' => 'EUR - Euro',
                                    ])
                                    ->default('USD')
                                    ->required(),
                            ]),
                    ]),

                Section::make('📋 Condiciones y Observaciones')
                    ->schema([
                        Forms\Components\Textarea::make('special_conditions')
                            ->label('Condiciones Especiales')
                            ->rows(3)
                            ->placeholder('Condiciones especiales de la OC'),

                        Forms\Components\Toggle::make('is_urgent')
                            ->label('Servicio Urgente')
                            ->helperText('Marcar si el servicio es urgente y se ejecuta sin OC'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->placeholder('Observaciones adicionales'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('Nº OC')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('quote.request.client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Fecha Emisión')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('scheduled_execution_date')
                    ->label('Fecha Programada')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->scheduled_execution_date < now() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('assigned_technician')
                    ->label('Técnico')
                    ->searchable(),

                Tables\Columns\TextColumn::make('authorized_amount')
                    ->label('Monto Autorizado')
                    ->money(fn ($record) => $record->currency ?? 'USD')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_urgent')
                    ->label('Urgente')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('executed_services_count')
                    ->label('Servicios')
                    ->counts('executedServices')
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('state.name')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'OC Recibida - Listo para Ejecutar' => 'info',
                        'En Ejecución' => 'warning',
                        'Ejecutado - Pendiente HES' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('urgent')
                    ->label('Servicios Urgentes')
                    ->query(fn (Builder $query): Builder => $query->where('is_urgent', true)),

                Tables\Filters\Filter::make('overdue')
                    ->label('Vencidas')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('scheduled_execution_date', '<', now())
                    ),

                Tables\Filters\Filter::make('pending_execution')
                    ->label('Pendientes de Ejecutar')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereDoesntHave('executedServices')
                    ),

                Tables\Filters\SelectFilter::make('currency')
                    ->label('Moneda')
                    ->options([
                        'USD' => 'USD',
                        'CLP' => 'CLP',
                        'EUR' => 'EUR',
                    ]),

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
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('issue_date', 'desc');
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}