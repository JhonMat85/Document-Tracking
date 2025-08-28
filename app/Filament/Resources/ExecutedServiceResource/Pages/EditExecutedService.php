<?php

namespace App\Filament\Resources\ExecutedServiceResource\Pages;

use App\Filament\Resources\ExecutedServiceResource;
use App\Models\Attachment;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EditExecutedService extends EditRecord
{
    protected static string $resource = ExecutedServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        
        return $data;
    }

    protected function afterSave(): void
    {
        Log::info('=== INICIO EditExecutedService AfterSave Debug ===');
        
        // Log del estado del formulario
        $rawState = $this->form->getRawState();
        Log::info('Form Raw State completo:', $rawState);
        
        // Manejar los archivos subidos nuevos
        $attachmentFiles = $rawState['attachment_files'] ?? [];
        
        Log::info('Attachment Files encontrados:', [
            'files' => $attachmentFiles,
            'count' => count($attachmentFiles),
            'type' => gettype($attachmentFiles),
            'is_empty' => empty($attachmentFiles)
        ]);
        
        if (!empty($attachmentFiles)) {
            Log::info('Procesando archivos...');
            
            foreach ($attachmentFiles as $fileId => $filePath) {
                Log::info("Archivo #{$fileId}:", [
                    'file_path' => $filePath,
                    'is_string' => is_string($filePath),
                    'type' => gettype($filePath)
                ]);
                
                // Filament ya guardó el archivo, ahora creamos el registro en attachments
                if (is_string($filePath) && !empty($filePath)) {
                    Log::info('Archivo ya procesado por Filament, creando registro...');
                    
                    try {
                        // Obtener la ruta completa del archivo
                        $fullPath = storage_path('app/public/' . $filePath);
                        
                        Log::info('Ruta completa del archivo:', [
                            'full_path' => $fullPath,
                            'exists' => file_exists($fullPath)
                        ]);
                        
                        if (file_exists($fullPath)) {
                            // Obtener información del archivo desde el disco
                            $originalName = basename($filePath); // Nombre del archivo
                            $mimeType = mime_content_type($fullPath);
                            $size = filesize($fullPath);
                            $fileHash = md5_file($fullPath);
                            $fileName = basename($filePath);
                            
                            Log::info('Información del archivo desde disco:', [
                                'original_name' => $originalName,
                                'mime_type' => $mimeType,
                                'size' => $size,
                                'hash' => $fileHash,
                                'file_name' => $fileName
                            ]);
                            
                            // Crear el registro en la tabla attachments
                            $attachment = Attachment::create([
                                'attachable_type' => 'App\\Models\\ExecutedService',
                                'attachable_id' => $this->record->id,
                                'file_name' => $fileName,
                                'original_name' => $originalName,
                                'file_path' => $filePath,
                                'mime_type' => $mimeType,
                                'size_bytes' => $size,
                                'file_hash' => $fileHash,
                                'is_public' => true,
                                'category' => 'evidence',
                                'uploaded_by' => auth()->id(),
                            ]);
                            
                            Log::info('Attachment creado exitosamente:', [
                                'attachment_id' => $attachment->id,
                                'attachment' => $attachment->toArray()
                            ]);
                        } else {
                            Log::error('Archivo no encontrado en disco:', [
                                'file_path' => $filePath,
                                'full_path' => $fullPath
                            ]);
                        }
                        
                    } catch (\Exception $e) {
                        Log::error('Error al procesar archivo:', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'file_path' => $filePath
                        ]);
                    }
                } else {
                    Log::warning('Archivo no válido (no es string o está vacío):', [
                        'file' => $filePath,
                        'is_string' => is_string($filePath),
                        'is_empty' => empty($filePath)
                    ]);
                }
            }
        } else {
            Log::info('No se encontraron archivos para procesar');
        }
        
        // Verificar datos finales
        $finalAttachments = $this->record->attachments();
        Log::info('Attachments asociados al record:', [
            'count' => $finalAttachments->count(),
            'attachments' => $finalAttachments->get()->toArray()
        ]);
        
        Log::info('=== FIN EditExecutedService AfterSave Debug ===');
    }
}