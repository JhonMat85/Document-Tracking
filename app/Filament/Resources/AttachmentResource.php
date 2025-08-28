<?php

namespace App\Filament\Resources;

use App\Models\Attachment;
use App\Filament\Resources\AttachmentResource\Pages;
use BackedEnum;
use UnitEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction; 
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AttachmentResource extends Resource
{
    protected static ?string $model = Attachment::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?string $navigationLabel = 'Documentos Adjuntos';

    protected static ?string $pluralModelLabel = 'Documentos Adjuntos';

    protected static UnitEnum|string|null $navigationGroup = '📁 Gestión Documental';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('📎 Información del Documento')
                    ->description('Gestión de archivos adjuntos del sistema')
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Archivo')
                            ->required()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/jpg',
                                'image/png',
                                'image/gif',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/plain',
                            ])
                            ->maxSize(10240) // 10MB máximo
                            ->directory('attachments')
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable(),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('original_name')
                                    ->label('Nombre Original')
                                    ->maxLength(255),

                                Forms\Components\Select::make('category')
                                    ->label('Categoría')
                                    ->options([
                                        'WhatsApp' => 'Captura WhatsApp',
                                        'Email' => 'Email Original', 
                                        'RFQ' => 'Documento RFQ',
                                        'Orden de Compra' => 'Orden de Compra',
                                        'HES' => 'HES del Cliente',
                                        'Factura' => 'Factura Generada',
                                        'Comprobante Pago' => 'Comprobante de Pago',
                                        'Reporte Servicio' => 'Reporte del Servicio',
                                        'Foto Documento' => 'Foto de Documento Físico',
                                        'Otro' => 'Otro',
                                    ])
                                    ->required()
                                    ->searchable(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('attachable_type')
                                    ->label('Tipo de Registro')
                                    ->options([
                                        'App\Models\Request' => 'Solicitud',
                                        'App\Models\Rfq' => 'RFQ',
                                        'App\Models\Quote' => 'Cotización',
                                        'App\Models\PurchaseOrder' => 'Orden de Compra',
                                        'App\Models\ExecutedService' => 'Servicio Ejecutado',
                                        'App\Models\Hes' => 'HES',
                                        'App\Models\Invoice' => 'Factura',
                                        'App\Models\Payment' => 'Pago',
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->searchable(),

                                Forms\Components\TextInput::make('attachable_id')
                                    ->label('ID del Registro')
                                    ->required()
                                    ->numeric()
                                    ->helperText('ID del registro al que se asocia este documento'),
                            ]),

                        Forms\Components\Toggle::make('is_public')
                            ->label('Documento Público')
                            ->helperText('Si está marcado, el documento es visible para todos los usuarios'),
                    ]),

                Section::make('📊 Información del Archivo')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('file_name')
                                    ->label('Nombre del Archivo')
                                    ->maxLength(255)
                                    ->disabled(),

                                Forms\Components\TextInput::make('mime_type')
                                    ->label('Tipo MIME')
                                    ->disabled(),

                                Forms\Components\TextInput::make('size_bytes')
                                    ->label('Tamaño (bytes)')
                                    ->numeric()
                                    ->disabled(),
                            ]),

                        Forms\Components\TextInput::make('file_hash')
                            ->label('Hash del Archivo')
                            ->disabled()
                            ->helperText('Hash MD5 para verificación de integridad'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('original_name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'WhatsApp' => 'success',
                        'Email' => 'info',
                        'RFQ' => 'warning',
                        'Orden de Compra' => 'primary',
                        'HES' => 'danger',
                        'Factura' => 'success',
                        'Comprobante Pago' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('attachable_type')
                    ->label('Tipo Registro')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'App\Models\Request' => 'Solicitud',
                        'App\Models\Rfq' => 'RFQ',
                        'App\Models\Quote' => 'Cotización',
                        'App\Models\PurchaseOrder' => 'Orden de Compra',
                        'App\Models\ExecutedService' => 'Servicio Ejecutado',
                        'App\Models\Hes' => 'HES',
                        'App\Models\Invoice' => 'Factura',
                        'App\Models\Payment' => 'Pago',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('attachable_id')
                    ->label('ID Registro')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('mime_type')
                    ->label('Tipo')
                    ->formatStateUsing(function (string $state): string {
                        return match (true) {
                            Str::contains($state, 'pdf') => 'PDF',
                            Str::contains($state, 'image') => 'Imagen',
                            Str::contains($state, 'word') => 'Word',
                            Str::contains($state, 'excel') => 'Excel',
                            Str::contains($state, 'text') => 'Texto',
                            default => 'Otro',
                        };
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('size_bytes')
                    ->label('Tamaño')
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
                    ->label('Público')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Subido por')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Subida')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoría')
                    ->options([
                        'WhatsApp' => 'Captura WhatsApp',
                        'Email' => 'Email Original',
                        'RFQ' => 'Documento RFQ',
                        'Orden de Compra' => 'Orden de Compra',
                        'HES' => 'HES del Cliente',
                        'Factura' => 'Factura Generada',
                        'Comprobante Pago' => 'Comprobante de Pago',
                        'Reporte Servicio' => 'Reporte del Servicio',
                        'Foto Documento' => 'Foto de Documento Físico',
                        'Otro' => 'Otro',
                    ]),

                Tables\Filters\SelectFilter::make('attachable_type')
                    ->label('Tipo de Registro')
                    ->options([
                        'App\Models\Request' => 'Solicitud',
                        'App\Models\Rfq' => 'RFQ',
                        'App\Models\Quote' => 'Cotización',
                        'App\Models\PurchaseOrder' => 'Orden de Compra',
                        'App\Models\ExecutedService' => 'Servicio Ejecutado',
                        'App\Models\Hes' => 'HES',
                        'App\Models\Invoice' => 'Factura',
                        'App\Models\Payment' => 'Pago',
                    ]),

                Tables\Filters\Filter::make('is_public')
                    ->label('Solo Públicos')
                    ->query(fn (Builder $query): Builder => $query->where('is_public', true)),

                Tables\Filters\Filter::make('large_files')
                    ->label('Archivos Grandes (>1MB)')
                    ->query(fn (Builder $query): Builder => $query->where('size_bytes', '>', 1048576)),

                Tables\Filters\Filter::make('created_at')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('download')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Attachment $record): string => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListAttachments::route('/'),
            'create' => Pages\CreateAttachment::route('/create'),
            'view' => Pages\ViewAttachment::route('/{record}'),
            'edit' => Pages\EditAttachment::route('/{record}/edit'),
        ];
    }
}