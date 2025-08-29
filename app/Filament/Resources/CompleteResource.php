<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompleteResource\Pages;
use App\Models\Quote;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Tables\Actions\ExportBulkAction;
use Illuminate\Database\Eloquent\Builder;

class CompleteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static ?string $slug = 'complete-dashboard';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Dashboard Completo';

    protected static ?string $pluralModelLabel = 'Dashboard Completo de Cotizaciones';

    protected static UnitEnum|string|null $navigationGroup = '📊 Reportes y Dashboard';

    protected static ?int $navigationSort = 5;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 📋 # Cotización
                Tables\Columns\TextColumn::make('quote_number')
                    ->label('# Cotización')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Número copiado!')
                    ->size('sm'),

                // 💰 Costo
                Tables\Columns\TextColumn::make('total')
                    ->label('Costo')
                    ->money('PEN')
                    ->sortable()
                    ->weight('semibold')
                    ->color('success')
                    ->getStateUsing(function ($record) {
                        return $record->currency === 'USD' 
                            ? 'USD ' . number_format($record->total, 2)
                            : 'S/ ' . number_format($record->total, 2);
                    }),

                // 📅 Fecha Envío
                Tables\Columns\TextColumn::make('sent_date')
                    ->label('Fecha Envío')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Sin enviar')
                    ->icon('heroicon-o-calendar-days'),

                // 🏢 Área
                Tables\Columns\TextColumn::make('request.requesting_department')
                    ->label('Área')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->placeholder('Sin área'),

                // 👤 Contacto
                Tables\Columns\TextColumn::make('destinationContact.full_name')
                    ->label('Contacto')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(function ($record) {
                        return $record->destinationContact?->full_name;
                    })
                    ->icon('heroicon-o-user')
                    ->placeholder('Sin contacto'),

                // 📝 Descripción
                Tables\Columns\TextColumn::make('service_description')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function ($record) {
                        return $record->service_description;
                    })
                    ->wrap()
                    ->placeholder('Sin descripción'),

                // 📄 RFQ o ERP
                Tables\Columns\TextColumn::make('rfq.rfq_number')
                    ->label('RFQ o ERP')
                    ->searchable()
                    ->placeholder('--')
                    ->badge()
                    ->color('gray'),

                // 📋 OC (Orden de Compra)
                Tables\Columns\BadgeColumn::make('has_purchase_order')
                    ->label('OC')
                    ->getStateUsing(function ($record) {
                        return $record->purchaseOrders->isNotEmpty() ? 'Sí' : 'No';
                    })
                    ->colors([
                        'success' => 'Sí',
                        'danger' => 'No',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Sí',
                        'heroicon-o-x-circle' => 'No',
                    ]),

                // ⚙️ Ejecutado
                Tables\Columns\BadgeColumn::make('is_executed')
                    ->label('Ejecutado')
                    ->getStateUsing(function ($record) {
                        $hasExecuted = $record->purchaseOrders()
                            ->whereHas('executedServices')
                            ->exists();
                        return $hasExecuted ? 'Sí' : 'No';
                    })
                    ->colors([
                        'success' => 'Sí',
                        'danger' => 'No',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Sí',
                        'heroicon-o-x-circle' => 'No',
                    ]),

                // 📊 HES/MIGO
                Tables\Columns\BadgeColumn::make('has_hes')
                    ->label('HES/MIGO')
                    ->getStateUsing(function ($record) {
                        $hasHes = $record->purchaseOrders()
                            ->whereHas('executedServices.hes')
                            ->exists();
                        return $hasHes ? 'Sí' : 'No';
                    })
                    ->colors([
                        'success' => 'Sí',
                        'danger' => 'No',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Sí',
                        'heroicon-o-x-circle' => 'No',
                    ]),

                // 📧 Factura Enviada
                Tables\Columns\BadgeColumn::make('is_invoiced')
                    ->label('Factura Enviada')
                    ->getStateUsing(function ($record) {
                        $hasInvoice = $record->purchaseOrders()
                            ->whereHas('executedServices.hes.invoices')
                            ->exists();
                        return $hasInvoice ? 'Sí' : 'No';
                    })
                    ->colors([
                        'success' => 'Sí',
                        'danger' => 'No',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Sí',
                        'heroicon-o-x-circle' => 'No',
                    ]),

                // 🔢 Num Factura
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Num Factura')
                    ->getStateUsing(function ($record) {
                        $invoice = $record->purchaseOrders()
                            ->with('executedServices.hes.invoices')
                            ->get()
                            ->flatMap(fn($po) => $po->executedServices)
                            ->flatMap(fn($es) => $es->hes)
                            ->flatMap(fn($hes) => $hes->invoices)
                            ->first();
                        
                        return $invoice?->invoice_number ?? '--';
                    })
                    ->searchable()
                    ->placeholder('--')
                    ->badge()
                    ->color('info'),

                // 💳 Pagado
                Tables\Columns\BadgeColumn::make('is_paid')
                    ->label('Pagado')
                    ->getStateUsing(function ($record) {
                        $hasPaid = $record->purchaseOrders()
                            ->whereHas('executedServices.hes.invoices.payments')
                            ->exists();
                        return $hasPaid ? 'Sí' : 'No';
                    })
                    ->colors([
                        'success' => 'Sí',
                        'danger' => 'No',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Sí',
                        'heroicon-o-x-circle' => 'No',
                    ]),

                // 📝 OBSERVACIONES
                Tables\Columns\TextColumn::make('notes')
                    ->label('Observaciones')
                    ->limit(30)
                    ->tooltip(function ($record) {
                        return $record->notes;
                    })
                    ->placeholder('Sin observaciones')
                    ->wrap(),
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
                    ->searchable()
                    ->preload(),

                // 💱 Filtro por Moneda
                Tables\Filters\SelectFilter::make('currency')
                    ->label('💱 Moneda')
                    ->options([
                        'PEN' => 'Soles (PEN)',
                        'USD' => 'Dólares (USD)',
                    ]),

                // 📋 Con OC
                Tables\Filters\Filter::make('with_po')
                    ->label('📋 Con OC')
                    ->query(fn (Builder $query): Builder => $query->has('purchaseOrders'))
                    ->toggle(),

                // ⚙️ Ejecutados
                Tables\Filters\Filter::make('executed')
                    ->label('⚙️ Ejecutados')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('purchaseOrders.executedServices'))
                    ->toggle(),

                // 📊 Con HES
                Tables\Filters\Filter::make('with_hes')
                    ->label('📊 Con HES')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('purchaseOrders.executedServices.hes'))
                    ->toggle(),

                // 📧 Facturados
                Tables\Filters\Filter::make('invoiced')
                    ->label('📧 Facturados')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('purchaseOrders.executedServices.hes.invoices'))
                    ->toggle(),

                // 💳 Pagados
                Tables\Filters\Filter::make('paid')
                    ->label('💳 Pagados')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('purchaseOrders.executedServices.hes.invoices.payments'))
                    ->toggle(),

                // 📅 Filtro por fechas
                Tables\Filters\Filter::make('sent_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('sent_from')
                            ->label('Enviado desde'),
                        \Filament\Forms\Components\DatePicker::make('sent_until')
                            ->label('Enviado hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['sent_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('sent_date', '>=', $date),
                            )
                            ->when(
                                $data['sent_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('sent_date', '<=', $date),
                            );
                    })
                    ->label('📅 Fechas de Envío'),
            ])
            ->actions([
                // Sin acciones individuales para mantener vista limpia
            ])
            ->bulkActions([
                // Sin acciones masivas - se enfocará en exportación completa desde el header
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([25, 50, 100])
            ->poll('30s') // Actualización automática cada 30 segundos
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->emptyStateHeading('📊 No hay cotizaciones para mostrar')
            ->emptyStateDescription('Las cotizaciones aparecerán aquí cuando se creen.')
            ->emptyStateIcon('heroicon-o-chart-pie');
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
            'index' => Pages\ListCompleteQuotes::route('/'),
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