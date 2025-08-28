<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\Invoice;
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
            ->components([
                Section::make('💰 Información del Pago')
                    ->description('Registro y reconciliación de pagos recibidos')
                    ->schema([
                        Forms\Components\Select::make('invoice_id')
                            ->label('Factura')
                            ->options(Invoice::query()
                                ->whereHas('hes', function ($query) {
                                    $query->where('state_id', 4); // HES aprobado
                                })
                                ->with(['client', 'request'])
                                ->get()
                                ->mapWithKeys(function ($invoice) {
                                    return [$invoice->id => "{$invoice->invoice_number} - {$invoice->client->name} - \${$invoice->total_amount}"];
                                }))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $invoice = Invoice::with('hes')->find($state);
                                    if ($invoice && $invoice->hes) {
                                        $set('invoiced_amount', $invoice->total_amount);
                                        // Pre-llenar descuento si es factoring
                                        if ($invoice->hes->payment_method && str_contains(strtolower($invoice->hes->payment_method), 'factoring')) {
                                            $set('discount_percentage', $invoice->hes->discount_percentage ?? 0);
                                        }
                                    }
                                }
                            }),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Fecha de Pago')
                            ->required()
                            ->default(now()),
                    ]),

                Section::make('💵 Montos y Cálculos')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('invoiced_amount')
                                    ->label('Monto Facturado')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $discountPercentage = $get('discount_percentage') ?? 0;
                                        $discountAmount = $state * ($discountPercentage / 100);
                                        $set('discount_amount', $discountAmount);
                                        $set('net_received_amount', $state - $discountAmount);
                                    }),

                                Forms\Components\TextInput::make('discount_percentage')
                                    ->label('% Descuento')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $invoicedAmount = $get('invoiced_amount') ?? 0;
                                        $discountAmount = $invoicedAmount * (($state ?? 0) / 100);
                                        $set('discount_amount', $discountAmount);
                                        $set('net_received_amount', $invoicedAmount - $discountAmount);
                                    }),

                                Forms\Components\TextInput::make('discount_amount')
                                    ->label('Monto Descuento')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled()
                                    ->default(0),
                            ]),

                        Forms\Components\TextInput::make('net_received_amount')
                            ->label('Monto Neto Recibido')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->disabled(),
                    ]),

                Section::make('🏦 Información Bancaria')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('payment_method')
                                    ->label('Método de Pago')
                                    ->options([
                                        'Transferencia Bancaria' => 'Transferencia Bancaria',
                                        'Cheque' => 'Cheque',
                                        'Efectivo' => 'Efectivo',
                                        'Factoring' => 'Factoring',
                                        'Pago Electrónico' => 'Pago Electrónico',
                                    ])
                                    ->required()
                                    ->searchable(),

                                Forms\Components\TextInput::make('bank_reference')
                                    ->label('Referencia Bancaria')
                                    ->maxLength(255),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('origin_bank')
                                    ->label('Banco Origen')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('delay_days')
                                    ->label('Días de Atraso')
                                    ->numeric()
                                    ->default(0),
                            ]),
                    ]),

                Section::make('📝 Observaciones')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->placeholder('Observaciones sobre el pago, diferencias, etc.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Fecha Pago')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Nº Factura')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice.client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoiced_amount')
                    ->label('Monto Facturado')
                    ->money('USD')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label('% Desc.')
                    ->suffix('%')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('net_received_amount')
                    ->label('Neto Recibido')
                    ->money('USD')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Factoring' => 'warning',
                        'Efectivo' => 'success',
                        'Transferencia Bancaria' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('delay_days')
                    ->label('Días Atraso')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null || $state === 0 => 'success',
                        $state <= 30 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('state.name')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pagado' => 'success',
                        'Pago Parcial' => 'warning',
                        'Por Identificar' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método de Pago')
                    ->options([
                        'Transferencia Bancaria' => 'Transferencia Bancaria',
                        'Cheque' => 'Cheque',
                        'Efectivo' => 'Efectivo',
                        'Factoring' => 'Factoring',
                        'Pago Electrónico' => 'Pago Electrónico',
                    ]),

                Tables\Filters\Filter::make('delayed')
                    ->label('Pagos Atrasados')
                    ->query(fn (Builder $query): Builder => $query->where('delay_days', '>', 0)),

                Tables\Filters\Filter::make('payment_date')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
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
            ->defaultSort('payment_date', 'desc');
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}