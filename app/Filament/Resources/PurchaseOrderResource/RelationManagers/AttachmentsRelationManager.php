<?php

namespace App\Filament\Resources\PurchaseOrderResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $recordTitleAttribute = 'original_name';

    protected static ?string $title = '📎 Documentos de OC';

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
                        'evidence' => 'primary',
                        'official' => 'success',
                        'backup' => 'warning',
                        'generated' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'evidence' => '📋 Evidencia',
                        'official' => '📜 Documento Oficial',
                        'backup' => '💾 Respaldo',
                        'generated' => '🔧 Generado',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('mime_type')
                    ->label('📋 Tipo')
                    ->badge()
                    ->formatStateUsing(function (string $state): string {
                        return match (true) {
                            Str::contains($state, 'pdf') => 'PDF',
                            Str::contains($state, 'image') => 'Imagen',
                            Str::contains($state, 'word') => 'Word',
                            default => 'Otro',
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
                        if (!$state) return 'N/A';
                        
                        $bytes = $state;
                        $units = ['B', 'KB', 'MB', 'GB'];
                        
                        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                            $bytes /= 1024;
                        }
                        
                        return round($bytes, 2) . ' ' . $units[$i];
                    }),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('👤 Subido por')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Fecha')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('📂 Categoría')
                    ->options([
                        'evidence' => '📋 Evidencia',
                        'official' => '📜 Documento Oficial',
                        'backup' => '💾 Respaldo',
                        'generated' => '🔧 Generado',
                    ])
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('mime_type')
                    ->label('📋 Tipo de Archivo')
                    ->options([
                        'application/pdf' => 'PDF',
                        'image/png' => 'PNG',
                        'image/jpeg' => 'JPG',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
                        'application/msword' => 'DOC',
                    ])
                    ->multiple()
                    ->preload(),
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('download')
                    ->label('🔽 Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn ($record) => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),
                    
                DeleteAction::make()
                    ->label('🗑️ Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar archivo de OC')
                    ->modalDescription('¿Estás seguro de que quieres eliminar este archivo? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar'),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('📄 No hay documentos adjuntos')
            ->emptyStateDescription('Los documentos de la OC aparecerán aquí una vez que se suban.')
            ->emptyStateIcon('heroicon-o-paper-clip');
    }
}