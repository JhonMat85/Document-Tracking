<?php

namespace App\Filament\Resources\SystemConfigurationResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SystemConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Clave')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable()
                    ->tooltip('Clic para copiar'),

                TextColumn::make('value')
                    ->label('Valor')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        if (!$record->value) return null;
                        if ($record->type === 'json') {
                            return json_encode($record->value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }
                        if ($record->type === 'boolean') {
                            return $record->value ? 'Verdadero' : 'Falso';
                        }
                        return (string) $record->value;
                    })
                    ->formatStateUsing(function ($state, $record) {
                        return match($record->type) {
                            'boolean' => $state === 'true' ? '✓ Sí' : '✗ No',
                            'json' => 'JSON: ' . str($state)->limit(30),
                            default => $state
                        };
                    }),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'string' => 'gray',
                        'integer' => 'blue',
                        'decimal' => 'green',
                        'boolean' => 'orange',
                        'json' => 'purple',
                        default => 'gray',
                    }),

                TextColumn::make('group_name')
                    ->label('Grupo')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'gray',
                        'numbering' => 'blue',
                        'notifications' => 'yellow',
                        'quotes' => 'green',
                        'services' => 'cyan',
                        'invoicing' => 'red',
                        'payments' => 'emerald',
                        'files' => 'violet',
                        'dashboard' => 'orange',
                        'system' => 'slate',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'general' => 'General',
                        'numbering' => 'Numeración',
                        'notifications' => 'Notificaciones',
                        'quotes' => 'Cotizaciones',
                        'services' => 'Servicios',
                        'invoicing' => 'Facturación',
                        'payments' => 'Pagos',
                        'files' => 'Archivos',
                        'dashboard' => 'Dashboard',
                        'system' => 'Sistema',
                        default => $state,
                    }),

                IconColumn::make('is_editable')
                    ->label('Editable')
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($record) => $record->is_editable 
                        ? 'Configuración editable' 
                        : 'Configuración protegida - Solo desde código'
                    ),

                TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description ? (string) $record->description : null)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Última Modificación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('group_name')
                    ->label('Grupo')
                    ->options([
                        'general' => 'General',
                        'numbering' => 'Numeración',
                        'notifications' => 'Notificaciones',
                        'quotes' => 'Cotizaciones',
                        'services' => 'Servicios',
                        'invoicing' => 'Facturación',
                        'payments' => 'Pagos',
                        'files' => 'Archivos',
                        'dashboard' => 'Dashboard',
                        'system' => 'Sistema',
                    ])
                    ->multiple(),

                SelectFilter::make('type')
                    ->label('Tipo de Dato')
                    ->options([
                        'string' => 'Texto (String)',
                        'integer' => 'Entero (Integer)',
                        'decimal' => 'Decimal',
                        'boolean' => 'Boolean',
                        'json' => 'JSON',
                    ])
                    ->multiple(),

                SelectFilter::make('is_editable')
                    ->label('Editable')
                    ->options([
                        1 => 'Editable',
                        0 => 'Solo Lectura',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value'])) {
                            return $query->where('is_editable', (bool) $data['value']);
                        }
                        return $query;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record) => $record && $record->is_editable)
                    ->tooltip(fn ($record) => !$record->is_editable 
                        ? 'Esta configuración no puede editarse desde la interfaz' 
                        : 'Editar configuración'
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar Configuraciones')
                        ->modalDescription('Está seguro de que desea eliminar las configuraciones seleccionadas? Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Eliminar')
                        ->action(function ($records) {
                            // Solo eliminar configuraciones editables
                            $records->filter(fn ($record) => $record->is_editable)->each(fn ($record) => $record->delete());
                        }),
                ]),
            ])
            ->defaultSort('group_name', 'asc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped();
    }
}
