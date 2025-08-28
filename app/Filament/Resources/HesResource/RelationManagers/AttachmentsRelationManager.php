<?php

namespace App\Filament\Resources\HesResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $recordTitleAttribute = 'original_name';

    protected static ?string $title = '📎 Documentos del HES';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                Tables\Columns\TextColumn::make('original_name')
                    ->label('📄 Archivo')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('category')
                    ->label('📂 Categoría')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'evidence' => 'info',
                        'official' => 'primary',
                        'backup' => 'warning',
                        'generated' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'evidence' => '🔍 Evidencia',
                        'official' => '📋 Documento Oficial',
                        'backup' => '💾 Respaldo',
                        'generated' => '⚙️ Generado',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('mime_type')
                    ->label('📋 Tipo')
                    ->badge()
                    ->formatStateUsing(function (string $state): string {
                        return match (true) {
                            Str::contains($state, 'pdf') => '📄 PDF',
                            Str::contains($state, 'image') => '🖼️ Imagen',
                            Str::contains($state, 'word') => '📝 Word',
                            default => '📎 Otro',
                        };
                    })
                    ->color(fn (string $state): string => match (true) {
                        Str::contains($state, 'pdf') => 'danger',
                        Str::contains($state, 'image') => 'success',
                        Str::contains($state, 'word') => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('size_bytes')
                    ->label('📏 Tamaño')
                    ->formatStateUsing(function (?int $state): string {
                        if (!$state) return '🤷 N/A';
                        
                        if ($state >= 1048576) {
                            return '📊 ' . round($state / 1048576, 1) . ' MB';
                        } elseif ($state >= 1024) {
                            return '📈 ' . round($state / 1024, 1) . ' KB';
                        }
                        return '📏 ' . $state . ' B';
                    })
                    ->alignEnd(),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('👁️ Público')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('👤 Subido por')
                    ->searchable()
                    ->limit(20)
                    ->placeholder('👤 Sistema'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->color('gray')
                    ->size('sm'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('📂 Categoría')
                    ->options([
                        'evidence' => '🔍 Evidencia',
                        'official' => '📋 Documento Oficial',
                        'backup' => '💾 Respaldo',
                        'generated' => '⚙️ Generado',
                    ]),

                Tables\Filters\SelectFilter::make('mime_type')
                    ->label('📋 Tipo de Archivo')
                    ->options([
                        'application/pdf' => '📄 PDF',
                        'image/png' => '🖼️ PNG',
                        'image/jpeg' => '🖼️ JPG',
                    ]),

                Tables\Filters\Filter::make('is_public')
                    ->label('👁️ Solo Públicos')
                    ->query(fn (Builder $query): Builder => $query->where('is_public', true))
                    ->toggle(),
            ])
            ->headerActions([
                // No actions needed as upload is handled in main form
            ])
            ->recordActions([
                Action::make('download')
                    ->label('⬇️ Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn ($record) => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab()
                    ->tooltip('Descargar archivo HES'),
                    
                DeleteAction::make()
                    ->label('🗑️ Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('⚠️ Eliminar archivo del HES')
                    ->modalDescription('¿Estás seguro de que quieres eliminar este archivo crítico del HES? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar archivo')
                    ->modalCancelActionLabel('Cancelar')
                    ->successNotificationTitle('Archivo eliminado correctamente'),
            ])
            ->bulkActions([
                // Bulk actions disabled for simplicity
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('📎 No hay documentos adjuntos')
            ->emptyStateDescription('Los archivos subidos en el formulario del HES aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-paper-clip');
    }
}