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

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Estado de Documentos';

    protected static ?string $pluralModelLabel = 'Dashboard de Documentos';

    protected static UnitEnum|string|null $navigationGroup = '📊 Reportes y Dashboard';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    // Stack 1 - Información Básica
                    Stack::make([
                        Tables\Columns\TextColumn::make('quote_number')
                            ->label('# Cotización')
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->color('primary')
                            ->copyable(),

                        Tables\Columns\TextColumn::make('total')
                            ->label('💰 Costo')
                            ->money('PEN')
                            ->sortable()
                            ->formatStateUsing(function ($state, $record) {
                                $currency = $record->currency === 'USD' ? '$' : 'S/';
                                return $currency . ' ' . number_format($state, 2);
                            })
                            ->color('success'),

                        Tables\Columns\TextColumn::make('sent_date')
                            ->label('📅 Fecha Envío')
                            ->date('d/m/Y')
                            ->sortable()
                            ->placeholder('No enviado')
                            ->color('gray'),
                    ])->space(1),

                    // Stack 2 - Cliente y Contacto
                    Stack::make([
                        Tables\Columns\TextColumn::make('request.requesting_department')
                            ->label('🏢 Área')
                            ->searchable()
                            ->limit(20)
                            ->placeholder('Sin área')
                            ->color('info'),

                        Tables\Columns\TextColumn::make('destinationContact.full_name')
                            ->label('👤 Contacto')
                            ->searchable()
                            ->limit(25)
                            ->placeholder('Sin contacto')
                            ->color('warning'),

                        Tables\Columns\TextColumn::make('service_description')
                            ->label('📝 Descripción')
                            ->searchable()
                            ->limit(30)
                            ->placeholder('Sin descripción')
                            ->color('gray'),
                    ])->space(1),

                    // Stack 3 - Estados de Documentos
                    Stack::make([
                        Tables\Columns\TextColumn::make('rfq.rfq_number')
                            ->label('📋 RFQ o ERP')
                            ->placeholder('Sin RFQ')
                            ->limit(15)
                            ->color('info'),

                        Tables\Columns\TextColumn::make('purchase_order_number')
                            ->label('📄 OC')
                            ->getStateUsing(function ($record) {
                                return $record->purchaseOrders->first()?->po_number ?? null;
                            })
                            ->placeholder('Sin OC')
                            ->limit(15)
                            ->color('warning'),

                        Tables\Columns\BadgeColumn::make('executed_status')
                            ->label('⚙️ Ejecutado')
                            ->getStateUsing(function ($record) {
                                $po = $record->purchaseOrders->first();
                                return $po && $po->executedServices->count() > 0 ? 'Sí' : 'No';
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'Sí' => 'success',
                                'No' => 'danger',
                                default => 'gray',
                            }),
                    ])->space(1),

                    // Stack 4 - HES, Factura y Pago
                    Stack::make([
                        Tables\Columns\TextColumn::make('hes_number')
                            ->label('📊 HES/MIGO')
                            ->getStateUsing(function ($record) {
                                $po = $record->purchaseOrders->first();
                                $es = $po?->executedServices->first();
                                return $es?->hes->first()?->hes_number ?? null;
                            })
                            ->placeholder('Sin HES')
                            ->limit(15)
                            ->color('info'),

                        Tables\Columns\BadgeColumn::make('invoice_sent')
                            ->label('📧 Factura Enviada')
                            ->getStateUsing(function ($record) {
                                $po = $record->purchaseOrders->first();
                                $es = $po?->executedServices->first();
                                $hes = $es?->hes->first();
                                return $hes && $hes->invoices->count() > 0 ? 'Sí' : 'No';
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'Sí' => 'success',
                                'No' => 'danger',
                                default => 'gray',
                            }),

                        Tables\Columns\TextColumn::make('invoice_number')
                            ->label('🧾 Num Factura')
                            ->getStateUsing(function ($record) {
                                $po = $record->purchaseOrders->first();
                                $es = $po?->executedServices->first();
                                $hes = $es?->hes->first();
                                return $hes?->invoices->first()?->invoice_number ?? null;
                            })
                            ->placeholder('Sin factura')
                            ->limit(15)
                            ->color('warning'),
                    ])->space(1),

                    // Stack 5 - Pago y Observaciones
                    Stack::make([
                        Tables\Columns\BadgeColumn::make('paid_status')
                            ->label('💳 Pagado')
                            ->getStateUsing(function ($record) {
                                $po = $record->purchaseOrders->first();
                                $es = $po?->executedServices->first();
                                $hes = $es?->hes->first();
                                $invoice = $hes?->invoices->first();
                                return $invoice && $invoice->payments->count() > 0 ? 'Sí' : 'No';
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'Sí' => 'success',
                                'No' => 'danger',
                                default => 'gray',
                            }),

                        Tables\Columns\TextColumn::make('notes')
                            ->label('📝 Observaciones')
                            ->limit(30)
                            ->placeholder('Sin observaciones')
                            ->color('gray'),
                    ])->space(1),
                ])->from('md'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency')
                    ->label('💱 Moneda')
                    ->options([
                        'PEN' => 'Soles (PEN)',
                        'USD' => 'Dólares (USD)',
                    ]),

                Tables\Filters\Filter::make('with_po')
                    ->label('📄 Con OC')
                    ->query(fn (Builder $query): Builder => $query->has('purchaseOrders'))
                    ->toggle(),

                Tables\Filters\Filter::make('executed')
                    ->label('⚙️ Ejecutados')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('purchaseOrders.executedServices'))
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
            ->paginated([10, 25, 50])
            ->poll('30s'); // Actualización automática cada 30 segundos
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