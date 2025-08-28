<?php

namespace App\Filament\Resources\RequestResource\RelationManagers;

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

    protected static ?string $title = '📎 Documentos Adjuntos';

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
                        
                        if ($state >= 1048576) {
                            return round($state / 1048576, 1) . ' MB';
                        } elseif ($state >= 1024) {
                            return round($state / 1024, 1) . ' KB';
                        }
                        return $state . ' B';
                    })
                    ->alignEnd(),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('👁️ Público')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash'),

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
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn ($record) => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),
                    
                DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar archivo')
                    ->modalDescription('¿Estás seguro de que quieres eliminar este archivo? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar'),
            ])
            ->bulkActions([
                //
            ]);
    }
}