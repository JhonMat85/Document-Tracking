<?php

namespace App\Filament\Resources;

use App\Models\Invoice;
use App\Models\Hes;
use App\Models\ProcessState;
use App\Filament\Resources\InvoiceResource\Pages;
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
use Filament\Tables;
use Filament\Tables\Table;

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
                Section::make('⚠️ REQUISITO HES INDISPENSABLE')
                    ->description('Sin HES registrado NO ES POSIBLE CREAR FACTURA')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Número de Factura')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        Forms\Components\Select::make('hes_id')
                            ->label('HES (REQUERIDO)')
                            ->relationship('hes', 'hes_number')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "HES: {$record->hes_number} - {$record->approver_name}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Solo HES aprobados pueden generar facturas'),

                        Forms\Components\TextInput::make('accounting_area')
                            ->label('Área de Contabilidad')
                            ->maxLength(100),
                    ])
                    ->columns(2),

                Section::make('Fechas')
                    ->schema([
                        Forms\Components\DateTimePicker::make('issue_date')
                            ->label('Fecha de Emisión')
                            ->required()
                            ->default(now()),

                        Forms\Components\DateTimePicker::make('sent_to_client_date')
                            ->label('Fecha de Envío al Cliente'),

                        Forms\Components\DateTimePicker::make('due_date')
                            ->label('Fecha de Vencimiento'),
                    ])
                    ->columns(3),

                Section::make('Montos')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('S/')
                            ->required(),

                        Forms\Components\TextInput::make('tax_amount')
                            ->label('Monto de Impuestos')
                            ->numeric()
                            ->prefix('S/')
                            ->required(),

                        Forms\Components\TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->prefix('S/')
                            ->required(),

                        Forms\Components\Select::make('currency')
                            ->label('Moneda')
                            ->options([
                                'PEN' => 'Soles (PEN)',
                                'USD' => 'Dólares (USD)',
                            ])
                            ->default('PEN')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Estado y Notas')
                    ->schema([
                        Forms\Components\Select::make('state_id')
                            ->label('Estado')
                            ->relationship('state', 'name', function ($query) {
                                $query->where('entity', 'invoice')
                                    ->where('is_active', true);
                            })
                            ->default(function () {
                                return ProcessState::where('entity', 'invoice')
                                    ->where('is_initial_state', true)
                                    ->first()?->id;
                            })
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas Adicionales')
                            ->rows(3),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Número Factura')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('hes.hes_number')
                    ->label('HES Relacionado')
                    ->searchable()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('hes.executedService.purchaseOrder.po_number')
                    ->label('OC')
                    ->searchable(),

                Tables\Columns\TextColumn::make('hes.executedService.purchaseOrder.quote.request.client.business_name')
                    ->label('Cliente')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('PEN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency')
                    ->label('Moneda')
                    ->badge(),

                Tables\Columns\TextColumn::make('state.name')
                    ->label('Estado')
                    ->badge(),

                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Fecha Emisión')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimiento')
                    ->dateTime()
                    ->sortable()
                    ->color(function ($record) {
                        if (!$record->due_date) return null;
                        return $record->due_date < now() ? 'danger' : 'success';
                    }),

                Tables\Columns\IconColumn::make('has_hes')
                    ->label('HES Válido')
                    ->getStateUsing(fn ($record) => !is_null($record->hes_id))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency')
                    ->label('Moneda')
                    ->options([
                        'PEN' => 'Soles (PEN)',
                        'USD' => 'Dólares (USD)',
                    ]),

                Tables\Filters\SelectFilter::make('state_id')
                    ->label('Estado')
                    ->relationship('state', 'name', function ($query) {
                        $query->where('entity', 'invoice');
                    }),

                Tables\Filters\Filter::make('overdue')
                    ->label('Vencidas')
                    ->query(fn ($query) => $query->where('due_date', '<', now())),

                Tables\Filters\Filter::make('no_hes')
                    ->label('Sin HES')
                    ->query(fn ($query) => $query->whereNull('hes_id')),
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
            ->defaultSort('created_at', 'desc')
            ->striped();
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}