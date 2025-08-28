<?php

namespace App\Filament\Resources\ExecutedServiceResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $recordTitleAttribute = 'original_name';

    protected static ?string $title = '📎 Documentos del Servicio';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                Tables\Columns\TextColumn::make('original_name')
                    ->label('📄 Archivo')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('category')
                    ->label('📂 Categoría')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'evidence' => 'info',
                        'official' => 'success',
                        'backup' => 'warning',
                        'generated' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'evidence' => '📋 Evidencia',
                        'official' => '📄 Oficial',
                        'backup' => '💾 Respaldo',
                        'generated' => '🤖 Generado',
                        default => '📁 General',
                    }),

                Tables\Columns\TextColumn::make('mime_type')
                    ->label('📋 Tipo')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(function (string $state): string {
                        return match (true) {
                            str_contains($state, 'pdf') => '📄 PDF',
                            str_contains($state, 'image') => '🖼️ Imagen',
                            str_contains($state, 'word') => '📝 Word',
                            str_contains($state, 'excel') => '📊 Excel',
                            default => '📁 Archivo',
                        };
                    }),

                Tables\Columns\TextColumn::make('size_bytes')
                    ->label('📏 Tamaño')
                    ->formatStateUsing(function ($state): string {
                        if (!$state) return 'N/A';
                        
                        $bytes = (int) $state;
                        $units = ['B', 'KB', 'MB', 'GB'];
                        
                        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                            $bytes /= 1024;
                        }
                        
                        return round($bytes, 2) . ' ' . $units[$i];
                    })
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('👤 Subido por')
                    ->color('info')
                    ->placeholder('Sistema')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->size('sm'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('📂 Categoría')
                    ->options([
                        'evidence' => '📋 Evidencia',
                        'official' => '📄 Oficial',
                        'backup' => '💾 Respaldo',
                        'generated' => '🤖 Generado',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('mime_type')
                    ->label('📋 Tipo de Archivo')
                    ->options([
                        'application/pdf' => '📄 PDF',
                        'image/jpeg' => '🖼️ JPEG',
                        'image/png' => '🖼️ PNG',
                        'application/msword' => '📝 Word',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '📝 Word (nuevo)',
                    ])
                    ->multiple(),
            ])
            ->headerActions([
                // Los archivos se suben desde el formulario principal
            ])
            ->actions([
                Action::make('download')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record) => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),

                Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => str_contains($record->mime_type ?? '', 'image') || str_contains($record->mime_type ?? '', 'pdf')),

                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->color('danger'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->emptyStateHeading('📎 No hay documentos adjuntos')
            ->emptyStateDescription('Los documentos se subirán desde el formulario principal.')
            ->emptyStateIcon('heroicon-o-paper-clip');
    }
}