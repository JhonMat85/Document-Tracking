<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExecutedServiceResource\Pages;
use App\Models\ExecutedService;
use App\Models\PurchaseOrder;
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

class ExecutedServiceResource extends Resource
{
    protected static ?string $model = ExecutedService::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Servicios Ejecutados';

    protected static ?string $pluralModelLabel = 'Servicios Ejecutados';

    protected static UnitEnum|string|null $navigationGroup = '🔧 Gestión Operativa';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('🔧 Información del Servicio Ejecutado')
                    ->description('Registro de la ejecución del servicio técnico')
                    ->schema([
                        Forms\Components\Select::make('purchase_order_id')
                            ->label('Orden de Compra')
                            ->options(PurchaseOrder::with(['quote.request.client'])
                                ->get()
                                ->mapWithKeys(function ($po) {
                                    return [$po->id => "OC-{$po->po_number} - {$po->quote->request->client->name}"];
                                }))
                            ->required()
                            ->searchable()
                            ->preload(),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('start_date')
                                    ->label('Fecha y Hora de Inicio')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\DateTimePicker::make('end_date')
                                    ->label('Fecha y Hora de Fin')
                                    ->after('start_date'),
                            ]),
                    ]),

                Section::make('👥 Equipo de Trabajo')
                    ->schema([
                        Forms\Components\TextInput::make('main_technician')
                            ->label('Técnico Principal')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TagsInput::make('work_team')
                            ->label('Equipo de Trabajo')
                            ->placeholder('Ingrese los nombres del equipo y presione Enter')
                            ->helperText('Agregue todos los miembros del equipo que participaron'),
                    ]),

                Section::make('📋 Descripción del Trabajo')
                    ->schema([
                        Forms\Components\Textarea::make('work_description')
                            ->label('Descripción del Trabajo Realizado')
                            ->required()
                            ->rows(4)
                            ->placeholder('Detalle completo del trabajo ejecutado, procedimientos, tareas realizadas, etc.'),

                        Forms\Components\Textarea::make('incidents')
                            ->label('Incidentes o Observaciones')
                            ->rows(3)
                            ->placeholder('Cualquier incidente, problema o observación durante la ejecución'),

                        Forms\Components\TextInput::make('execution_hours')
                            ->label('Horas de Ejecución')
                            ->numeric()
                            ->step(0.5)
                            ->suffix('horas')
                            ->placeholder('8.5'),
                    ]),

                Section::make('📝 Notas Adicionales')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->placeholder('Información adicional relevante'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('Nº OC')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('purchaseOrder.quote.request.client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha Inicio')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha Fin')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('main_technician')
                    ->label('Técnico Principal')
                    ->searchable(),

                Tables\Columns\TextColumn::make('work_team')
                    ->label('Equipo')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state) && count($state) > 0) {
                            return count($state) . ' miembros';
                        }
                        return 'No especificado';
                    })
                    ->tooltip(function ($record) {
                        if (is_array($record->work_team) && count($record->work_team) > 0) {
                            return implode(', ', $record->work_team);
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('execution_hours')
                    ->label('Horas')
                    ->suffix(' hrs')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('work_description')
                    ->label('Descripción')
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 40) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\IconColumn::make('has_incidents')
                    ->label('Incidentes')
                    ->getStateUsing(fn ($record) => !empty($record->incidents))
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('hes_count')
                    ->label('HES')
                    ->counts('hes')
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('state.name')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Ejecutado - Pendiente HES' => 'warning',
                        'HES Recibido - Autorizado para Facturar' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('with_incidents')
                    ->label('Con Incidentes')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('incidents')->where('incidents', '!=', '')),

                Tables\Filters\Filter::make('without_hes')
                    ->label('Sin HES')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('hes')),

                Tables\Filters\Filter::make('this_week')
                    ->label('Esta Semana')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereBetween('start_date', [now()->startOfWeek(), now()->endOfWeek()])
                    ),

                Tables\Filters\SelectFilter::make('main_technician')
                    ->label('Técnico Principal')
                    ->options(function () {
                        return ExecutedService::distinct()
                            ->pluck('main_technician', 'main_technician')
                            ->filter()
                            ->toArray();
                    }),

                Tables\Filters\Filter::make('execution_date')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
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
            ->defaultSort('start_date', 'desc');
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
            'index' => Pages\ListExecutedServices::route('/'),
            'create' => Pages\CreateExecutedService::route('/create'),
            'view' => Pages\ViewExecutedService::route('/{record}'),
            'edit' => Pages\EditExecutedService::route('/{record}/edit'),
        ];
    }
}