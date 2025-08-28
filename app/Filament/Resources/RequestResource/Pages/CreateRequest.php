<?php

namespace App\Filament\Resources\RequestResource\Pages;

use App\Filament\Resources\RequestResource;
use App\Models\Attachment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateRequest extends CreateRecord
{
    protected static string $resource = RequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generar número de solicitud automático
        $year = date('Y');
        $lastRequest = \App\Models\Request::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $nextNumber = $lastRequest ? (int)substr($lastRequest->request_number, -4) + 1 : 1;
        $data['request_number'] = 'SOL-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        
        $data['created_by'] = auth()->id();
        
        return $data;
    }

    protected function afterCreate(): void
    {
        \Log::info('=== INICIO AfterCreate Debug ===');
        
        // Log del estado del formulario
        $rawState = $this->form->getRawState();
        \Log::info('Form Raw State completo:', $rawState);
        
        // Manejar los archivos subidos
        $attachmentFiles = $rawState['attachment_files'] ?? [];
        
        \Log::info('Attachment Files encontrados:', [
            'files' => $attachmentFiles,
            'count' => count($attachmentFiles),
            'type' => gettype($attachmentFiles),
            'is_empty' => empty($attachmentFiles)
        ]);
        
        if (!empty($attachmentFiles)) {
            \Log::info('Procesando archivos...');
            
            foreach ($attachmentFiles as $index => $file) {
                \Log::info("Archivo #{$index}:", [
                    'file' => $file,
                    'is_object' => is_object($file),
                    'type' => gettype($file),
                    'class' => is_object($file) ? get_class($file) : 'not_object'
                ]);
                
                // Solo procesar objetos UploadedFile, no strings
                if (is_object($file) && method_exists($file, 'getClientOriginalName')) {
                    \Log::info('Archivo válido, procesando...');
                    
                    try {
                        // Obtener información del archivo
                        $originalName = $file->getClientOriginalName();
                        $mimeType = $file->getMimeType();
                        $size = $file->getSize();
                        $fileHash = md5_file($file->getRealPath());
                        
                        \Log::info('Información del archivo:', [
                            'original_name' => $originalName,
                            'mime_type' => $mimeType,
                            'size' => $size,
                            'hash' => $fileHash
                        ]);
                        
                        // Generar nombre único para el archivo
                        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                        
                        // Almacenar el archivo
                        $filePath = $file->storeAs('request-attachments', $fileName, 'public');
                        
                        \Log::info('Archivo almacenado:', [
                            'file_name' => $fileName,
                            'file_path' => $filePath
                        ]);
                        
                        // Crear el registro en la tabla attachments
                        $attachment = Attachment::create([
                            'attachable_type' => 'App\\Models\\Request',
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
                        
                        \Log::info('Attachment creado exitosamente:', [
                            'attachment_id' => $attachment->id,
                            'attachment' => $attachment->toArray()
                        ]);
                        
                    } catch (\Exception $e) {
                        \Log::error('Error al procesar archivo:', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } else {
                    \Log::warning('Archivo no válido:', [
                        'file' => $file,
                        'is_object' => is_object($file),
                        'has_method' => is_object($file) ? method_exists($file, 'getClientOriginalName') : false
                    ]);
                }
            }
        } else {
            \Log::info('No se encontraron archivos para procesar');
        }
        
        // Verificar datos finales
        $finalAttachments = $this->record->attachments();
        \Log::info('Attachments asociados al record:', [
            'count' => $finalAttachments->count(),
            'attachments' => $finalAttachments->get()->toArray()
        ]);
        
        \Log::info('=== FIN AfterCreate Debug ===');
    }
}