<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExecutedServiceResource\Pages;
use App\Filament\Resources\ExecutedServiceResource\RelationManagers;
use App\Models\ExecutedService;
use App\Models\PurchaseOrder;
use App\Models\ProcessState;
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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
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
                Tabs::make('Información del Servicio Ejecutado')
                    ->tabs([
                        Tab::make('🔧 Información del Servicio')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Section::make('🔧 Información del Servicio Ejecutado')
                                    ->description('Registro de la ejecución del servicio técnico')
                                    ->schema([
                                        Forms\Components\Select::make('purchase_order_id')
                                            ->label('Orden de Compra')
                                            ->options(PurchaseOrder::with(['quote.request.client'])
                                                ->get()
                                                ->mapWithKeys(function ($po) {
                                                    return [$po->id => "OC-{$po->po_number} - {$po->quote->request->client->business_name}"];
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

                                        Forms\Components\Select::make('state_id')
                                            ->label('Estado del Servicio')
                                            ->relationship('state', 'name')
                                            ->options(function () {
                                                return ProcessState::where('entity', 'executed_service')
                                                    ->where('is_active', true)
                                                    ->orderBy('display_order')
                                                    ->pluck('name', 'id');
                                            })
                                            ->default(function () {
                                                return ProcessState::where('entity', 'executed_service')
                                                    ->where('is_initial_state', true)
                                                    ->where('is_active', true)
                                                    ->value('id');
                                            })
                                            ->required()
                                            ->searchable()
                                            ->preload(),
                                    ]),

                                Section::make('📝 Notas Adicionales')
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Notas')
                                            ->rows(3)
                                            ->placeholder('Información adicional relevante'),
                                    ]),
                            ]),

                        Tab::make('📎 Documentos')
                            ->icon('heroicon-o-paper-clip')
                            ->schema([
                                Section::make('Archivos Adjuntos')
                                    ->description('Sube fotos del trabajo realizado y documentos relacionados')
                                    ->icon('heroicon-o-document-arrow-up')
                                    ->schema([
                                        FileUpload::make('attachment_files')
                                            ->label('📷 Fotos y Documentos del Servicio')
                                            ->multiple()
                                            ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'])
                                            ->disk('public')
                                            ->directory('executed-service-attachments')
                                            ->visibility('public')
                                            ->panelLayout('grid')
                                            ->maxFiles(20)
                                            ->helperText('Sube fotos del trabajo realizado, documentos técnicos, reportes, etc. Máximo 20 archivos.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    // Stack 1 - Información de OC y Cliente
                    Stack::make([
                        Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                            ->label('🔢 Núm. OC')
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->color('primary')
                            ->copyable()
                            ->tooltip('Número único de la Orden de Compra'),

                        Tables\Columns\TextColumn::make('purchaseOrder.quote.request.client.business_name')
                            ->label('🏥 Cliente')
                            ->searchable()
                            ->sortable()
                            ->size('lg')
                            ->weight('medium')
                            ->limit(25)
                            ->tooltip(function ($record) {
                                return $record->purchaseOrder?->quote?->request?->client?->business_name;
                            }),

                        Tables\Columns\TextColumn::make('created_at')
                            ->label('📅 Fecha Registro')
                            ->date('d/m/Y')
                            ->sortable()
                            ->color('gray')
                            ->size('sm')
                            ->icon('heroicon-o-calendar-days'),
                    ])->space(1),

                    // Stack 2 - Técnico, Equipo y Ejecución
                    Stack::make([
                        Tables\Columns\TextColumn::make('main_technician')
                            ->label('👨‍🔧 Técnico Principal')
                            ->searchable()
                            ->icon('heroicon-o-user-circle')
                            ->color('info')
                            ->weight('medium'),

                        Tables\Columns\TextColumn::make('work_team')
                            ->label('👥 Equipo')
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(function ($state) {
                                if (is_array($state) && count($state) > 0) {
                                    return count($state) . ' miembros';
                                }
                                return 'No especificado';
                            })
                            ->tooltip(function ($record) {
                                if (is_array($record->work_team) && count($record->work_team) > 0) {
                                    return 'Equipo: ' . implode(', ', $record->work_team);
                                }
                                return null;
                            }),

                        Tables\Columns\TextColumn::make('start_date')
                            ->label('⏰ Fecha Ejecución')
                            ->date('d/m/Y')
                            ->sortable()
                            ->color('gray')
                            ->size('sm')
                            ->icon('heroicon-o-clock'),

                        Tables\Columns\TextColumn::make('execution_hours')
                            ->label('⏱️ Horas')
                            ->suffix(' hrs')
                            ->alignEnd()
                            ->sortable()
                            ->color('warning')
                            ->weight('medium'),
                    ])->space(1),

                    // Stack 3 - Estado y Resultados
                    Stack::make([
                        Tables\Columns\TextColumn::make('state.name')
                            ->label('📊 Estado del Proceso')
                            ->badge()
                            ->color('primary')
                            ->icon('heroicon-o-clipboard-document-check'),

                        Tables\Columns\TextColumn::make('hes_count')
                            ->label('📝 HES')
                            ->counts('hes')
                            ->alignCenter()
                            ->badge()
                            ->color(fn (int $state): string => $state > 0 ? 'success' : 'warning')
                            ->icon('heroicon-o-document-text'),

                        Tables\Columns\IconColumn::make('has_incidents')
                            ->label('⚠️ Incidentes')
                            ->getStateUsing(fn ($record) => !empty($record->incidents))
                            ->boolean()
                            ->trueIcon('heroicon-o-exclamation-triangle')
                            ->falseIcon('heroicon-o-check-circle')
                            ->trueColor('warning')
                            ->falseColor('success')
                            ->tooltip(function ($record) {
                                return !empty($record->incidents) ? 'Con incidentes reportados' : 'Sin incidentes';
                            }),
                    ])->space(1),
                ])->from('md'),
            ])
            ->contentGrid([
                'md' => 1,
                'lg' => 1,
            ])
            ->filters([
                Tables\Filters\Filter::make('with_incidents')
                    ->label('⚠️ Con Incidentes')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('incidents')->where('incidents', '!=', ''))
                    ->toggle(),

                Tables\Filters\Filter::make('without_hes')
                    ->label('📝 Sin HES')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('hes'))
                    ->toggle(),

                Tables\Filters\Filter::make('this_week')
                    ->label('📅 Esta Semana')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereBetween('start_date', [now()->startOfWeek(), now()->endOfWeek()])
                    )
                    ->toggle(),

                Tables\Filters\SelectFilter::make('main_technician')
                    ->label('👨‍🔧 Técnico Principal')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return ExecutedService::distinct()
                            ->whereNotNull('main_technician')
                            ->pluck('main_technician', 'main_technician')
                            ->filter()
                            ->toArray();
                    }),

                Tables\Filters\SelectFilter::make('state')
                    ->label('📊 Estado')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->relationship('state', 'name'),

                Tables\Filters\Filter::make('execution_date')
                    ->label('📅 Fecha de Ejecución')
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
            ->defaultSort('start_date', 'desc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->poll('30s')
            ->deferLoading()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('🔧 No hay servicios ejecutados registrados')
            ->emptyStateDescription('Comience registrando su primer servicio ejecutado en el sistema.')
            ->emptyStateIcon('heroicon-o-wrench-screwdriver');
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
            'index' => Pages\ListExecutedServices::route('/'),
            'create' => Pages\CreateExecutedService::route('/create'),
            'view' => Pages\ViewExecutedService::route('/{record}'),
            'edit' => Pages\EditExecutedService::route('/{record}/edit'),
        ];
    }
}