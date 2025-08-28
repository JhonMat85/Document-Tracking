<?php

namespace App\Filament\Resources;

use App\Models\Hes;
use App\Models\ExecutedService;
use App\Models\ProcessState;
use App\Filament\Resources\HesResource\Pages;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables;
use Filament\Tables\Table;

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
                Section::make('⚠️ DOCUMENTO CRÍTICO PARA FACTURACIÓN')
                    ->description('Sin HES registrado NO ES POSIBLE FACTURAR')
                    ->schema([
                        Forms\Components\TextInput::make('hes_number')
                            ->label('Número de HES')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('ej: 1000070935'),

                        Forms\Components\Select::make('executed_service_id')
                            ->label('Servicio Ejecutado')
                            ->relationship('executedService', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "Servicio #{$record->id} - {$record->purchaseOrder->po_number}")
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('reference_po_number')
                            ->label('Número de OC de Referencia')
                            ->required()
                            ->maxLength(50)
                            ->helperText('Debe coincidir con la OC registrada'),

                        Forms\Components\TextInput::make('reference_solped_number')
                            ->label('Número de SOLPED de Referencia')
                            ->maxLength(50),
                    ])
                    ->columns(2),

                Section::make('Datos del Aprobador')
                    ->schema([
                        Forms\Components\DateTimePicker::make('approval_date')
                            ->label('Fecha de Aprobación del HES')
                            ->required(),

                        Forms\Components\TextInput::make('approver_name')
                            ->label('Nombre del Aprobador')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('ej: MENDOZA ANTONIO FRANCISCO'),

                        Forms\Components\TextInput::make('approver_position')
                            ->label('Cargo del Aprobador')
                            ->maxLength(150),
                    ])
                    ->columns(2),

                Section::make('Montos y Condiciones de Pago')
                    ->schema([
                        Forms\Components\TextInput::make('authorized_amount')
                            ->label('Monto Autorizado para Facturar')
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

                        Forms\Components\Select::make('payment_method')
                            ->label('Forma de Pago')
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
                            }),

                        Forms\Components\TextInput::make('payment_days')
                            ->label('Término de Pago (días)')
                            ->numeric()
                            ->helperText('Solo si aplica'),

                        Forms\Components\TextInput::make('discount_percentage')
                            ->label('Porcentaje de Descuento (%)')
                            ->numeric()
                            ->suffix('%')
                            ->visible(fn (Get $get): bool => $get('payment_method') === 'factoring')
                            ->helperText('Solo para factoring'),

                        Forms\Components\TextInput::make('imputation_type')
                            ->label('Tipo de Imputación')
                            ->maxLength(100),
                    ])
                    ->columns(3),

                Section::make('Estado y Notas')
                    ->schema([
                        Forms\Components\Select::make('state_id')
                            ->label('Estado')
                            ->relationship('state', 'name', function ($query) {
                                $query->where('entity', 'hes')
                                    ->where('is_active', true);
                            })
                            ->default(function () {
                                return ProcessState::where('entity', 'hes')
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
                Tables\Columns\TextColumn::make('hes_number')
                    ->label('Número HES')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('executedService.purchaseOrder.po_number')
                    ->label('OC Relacionada')
                    ->searchable(),

                Tables\Columns\TextColumn::make('reference_po_number')
                    ->label('OC Referencia')
                    ->searchable(),

                Tables\Columns\TextColumn::make('approver_name')
                    ->label('Aprobador')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('authorized_amount')
                    ->label('Monto Autorizado')
                    ->money('PEN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Forma de Pago')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'factoring' => 'warning',
                        'direct_payment' => 'success',
                        'cash' => 'info',
                        'others' => 'gray',
                    }),

                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label('% Descuento')
                    ->suffix('%')
                    ->visible(fn ($record) => $record && $record->payment_method === 'factoring'),

                Tables\Columns\TextColumn::make('state.name')
                    ->label('Estado')
                    ->badge(),

                Tables\Columns\TextColumn::make('approval_date')
                    ->label('Fecha Aprobación')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\IconColumn::make('can_invoice')
                    ->label('Puede Facturar')
                    ->getStateUsing(fn ($record) => !is_null($record->hes_number))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Forma de Pago')
                    ->options([
                        'factoring' => 'Factoring',
                        'direct_payment' => 'Pago Directo',
                        'cash' => 'Contado',
                        'others' => 'Otros',
                    ]),

                Tables\Filters\SelectFilter::make('state_id')
                    ->label('Estado')
                    ->relationship('state', 'name', function ($query) {
                        $query->where('entity', 'hes');
                    }),

                Tables\Filters\SelectFilter::make('currency')
                    ->label('Moneda')
                    ->options([
                        'PEN' => 'Soles (PEN)',
                        'USD' => 'Dólares (USD)',
                    ]),
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
            ->striped()
            ->poll('30s'); // Auto-refresh cada 30 segundos por ser crítico
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
            'index' => Pages\ListHes::route('/'),
            'create' => Pages\CreateHes::route('/create'),
            'view' => Pages\ViewHes::route('/{record}'),
            'edit' => Pages\EditHes::route('/{record}/edit'),
        ];
    }
}