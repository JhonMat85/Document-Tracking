<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StatusResource\Pages;
use App\Models\Quote;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Illuminate\Database\Eloquent\Builder;

class StatusResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static ?string $slug = 'status-resources';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Estado de Documentos';

    protected static ?string $pluralModelLabel = 'Dashboard de Documentos';

    protected static UnitEnum|string|null $navigationGroup = '📊 Reportes y Dashboard';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 📋 ID y Número de Cotización
                Tables\Columns\TextColumn::make('quote_number')
                    ->label('📋 ID Cotización')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->size('sm'),

                // 💰 Costo/Valor
                Tables\Columns\TextColumn::make('total')
                    ->label('💰 Valor')
                    ->getStateUsing(function ($record) {
                        return $record->currency === 'USD' 
                            ? 'USD ' . number_format($record->total, 2)
                            : 'S/ ' . number_format($record->total, 2);
                    })
                    ->sortable()
                    ->weight('semibold')
                    ->color('success')
                    ->size('sm'),

                // 🏢 Área/Departamento
                Tables\Columns\TextColumn::make('request.requesting_department')
                    ->label('🏢 Área')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->placeholder('Sin área')
                    ->limit(15),

                // 📅 Fecha de Ingreso
                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Ingreso')
                    ->date('d/m/Y')
                    ->sortable()
                    ->size('sm'),

                // 📊 Estado Principal
                Tables\Columns\BadgeColumn::make('state.name')
                    ->label('📊 Estado')
                    ->colors([
                        'warning' => fn($state) => str_contains(strtolower($state), 'pendiente'),
                        'info' => fn($state) => str_contains(strtolower($state), 'revisión'),
                        'success' => fn($state) => str_contains(strtolower($state), 'aprobad'),
                        'danger' => fn($state) => str_contains(strtolower($state), 'rechazad'),
                        'gray' => fn($state) => str_contains(strtolower($state), 'finalizado'),
                    ])
                    ->size('sm'),

                // 📋 Tiene OC
                Tables\Columns\BadgeColumn::make('has_po')
                    ->label('📋 OC')
                    ->getStateUsing(function ($record) {
                        return $record->purchaseOrders->isNotEmpty() ? 'Sí' : 'No';
                    })
                    ->colors([
                        'success' => 'Sí',
                        'gray' => 'No',
                    ])
                    ->size('xs'),

                // ⚙️ Ejecutado
                Tables\Columns\BadgeColumn::make('executed')
                    ->label('⚙️ Ejec.')
                    ->getStateUsing(function ($record) {
                        $hasExecuted = $record->purchaseOrders()
                            ->whereHas('executedServices')
                            ->exists();
                        return $hasExecuted ? 'Sí' : 'No';
                    })
                    ->colors([
                        'success' => 'Sí',
                        'gray' => 'No',
                    ])
                    ->size('xs'),

                // 📧 Facturado
                Tables\Columns\BadgeColumn::make('invoiced')
                    ->label('📧 Fact.')
                    ->getStateUsing(function ($record) {
                        $hasInvoice = $record->purchaseOrders()
                            ->whereHas('executedServices.hes.invoices')
                            ->exists();
                        return $hasInvoice ? 'Sí' : 'No';
                    })
                    ->colors([
                        'success' => 'Sí',
                        'gray' => 'No',
                    ])
                    ->size('xs'),

                // 💳 Pagado
                Tables\Columns\BadgeColumn::make('paid')
                    ->label('💳 Pago')
                    ->getStateUsing(function ($record) {
                        $hasPaid = $record->purchaseOrders()
                            ->whereHas('executedServices.hes.invoices.payments')
                            ->exists();
                        return $hasPaid ? 'Sí' : 'No';
                    })
                    ->colors([
                        'success' => 'Sí',
                        'gray' => 'No',
                    ])
                    ->size('xs'),

                // 👤 Responsable/Contacto
                Tables\Columns\TextColumn::make('destinationContact.full_name')
                    ->label('👤 Contacto')
                    ->limit(20)
                    ->placeholder('Sin contacto')
                    ->size('sm'),

                // ⏱️ Días Transcurridos con Prioridad
                Tables\Columns\BadgeColumn::make('priority_days')
                    ->label('⏱️ Días')
                    ->getStateUsing(function ($record) {
                        $daysSince = (int) now()->diffInDays($record->created_at);
                        return $daysSince . 'd';
                    })
                    ->colors([
                        'danger' => fn($state) => (int)str_replace('d', '', $state) > 7,
                        'warning' => fn($state) => (int)str_replace('d', '', $state) > 3 && (int)str_replace('d', '', $state) <= 7,
                        'success' => fn($state) => (int)str_replace('d', '', $state) <= 3,
                    ])
                    ->sortable()
                    ->size('sm'),
            ])
            ->filters([
                // 🏢 Filtro por Área
                Tables\Filters\SelectFilter::make('area')
                    ->label('🏢 Área')
                    ->options(function () {
                        return \App\Models\Request::distinct()
                            ->whereNotNull('requesting_department')
                            ->where('requesting_department', '!=', '')
                            ->pluck('requesting_department', 'requesting_department')
                            ->sort()
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'])) {
                            return $query->whereHas('request', function (Builder $subQuery) use ($data) {
                                $subQuery->where('requesting_department', $data['value']);
                            });
                        }
                        return $query;
                    })
                    ->searchable(),

                // 📊 Estados del Proceso
                Tables\Filters\SelectFilter::make('state_id')
                    ->label('📊 Estado')
                    ->relationship('state', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                // 💱 Moneda
                Tables\Filters\SelectFilter::make('currency')
                    ->label('💱 Moneda')
                    ->options([
                        'PEN' => 'Soles (PEN)',
                        'USD' => 'Dólares (USD)',
                    ])
                    ->multiple(),

                // 📋 Progreso del Proceso
                Tables\Filters\Filter::make('with_po')
                    ->label('📋 Con Orden de Compra')
                    ->query(fn (Builder $query): Builder => $query->has('purchaseOrders'))
                    ->toggle(),

                Tables\Filters\Filter::make('executed')
                    ->label('⚙️ Servicios Ejecutados')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('purchaseOrders.executedServices'))
                    ->toggle(),

                Tables\Filters\Filter::make('with_hes')
                    ->label('📋 Con HES/MIGO')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('purchaseOrders.executedServices.hes'))
                    ->toggle(),

                Tables\Filters\Filter::make('invoiced')
                    ->label('📧 Facturados')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('purchaseOrders.executedServices.hes.invoices'))
                    ->toggle(),

                Tables\Filters\Filter::make('paid')
                    ->label('💳 Pagados')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('purchaseOrders.executedServices.hes.invoices.payments'))
                    ->toggle(),

                // ⏱️ Filtros por Tiempo/Prioridad
                Tables\Filters\Filter::make('high_priority')
                    ->label('🔴 Alta Prioridad (+7 días)')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('created_at', '<=', now()->subDays(7)))
                    ->toggle(),

                Tables\Filters\Filter::make('recent')
                    ->label('🆕 Recientes (7 días)')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('created_at', '>=', now()->subDays(7)))
                    ->toggle(),

                // 💰 Filtros por Valor
                Tables\Filters\Filter::make('high_value')
                    ->label('💰 Alto Valor (>S/10,000)')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('total', '>', 10000))
                    ->toggle(),
            ])
            ->actions([
                // Sin acciones para mantener simplicidad KISS
            ])
            ->bulkActions([
                // Sin acciones masivas para simplicidad
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('📊 No hay cotizaciones para mostrar')
            ->emptyStateDescription('Las cotizaciones aparecerán aquí cuando se creen.')
            ->emptyStateIcon('heroicon-o-chart-bar-square')
            ->paginated([25, 50, 100]); // Optimizado para rendimiento
    }

    public static function getRelations(): array
    {
        return [
            // Sin relation managers para mantener simplicidad
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDashboard::route('/'),
        ];
    }

    // Solo permitir vista, no crear/editar para mantener simplicidad
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}