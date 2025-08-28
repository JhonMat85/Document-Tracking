<?php

namespace App\Filament\Resources\RfqResource\Pages;

use App\Filament\Resources\RfqResource;
use App\Models\Attachment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CreateRfq extends CreateRecord
{
    protected static string $resource = RfqResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        
        return $data;
    }

    protected function afterCreate(): void
    {
        \Log::info('=== INICIO AfterCreate RFQ Debug ===');
        
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
            \Log::info('Procesando archivos RFQ...');
            
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
                        $filePath = $file->storeAs('rfq-attachments', $fileName, 'public');
                        
                        \Log::info('Archivo almacenado:', [
                            'file_name' => $fileName,
                            'file_path' => $filePath
                        ]);
                        
                        // Crear el registro en la tabla attachments
                        $attachment = Attachment::create([
                            'attachable_type' => 'App\\Models\\Rfq',
                            'attachable_id' => $this->record->id,
                            'file_name' => $fileName,
                            'original_name' => $originalName,
                            'file_path' => $filePath,
                            'mime_type' => $mimeType,
                            'size_bytes' => $size,
                            'file_hash' => $fileHash,
                            'is_public' => true,
                            'category' => 'official',
                            'uploaded_by' => Auth::id(),
                        ]);
                        
                        \Log::info('Attachment RFQ creado exitosamente:', [
                            'attachment_id' => $attachment->id,
                            'attachment' => $attachment->toArray()
                        ]);
                        
                    } catch (\Exception $e) {
                        \Log::error('Error al procesar archivo RFQ:', [
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
            \Log::info('No se encontraron archivos para procesar en RFQ');
        }
        
        // Verificar datos finales
        $finalAttachments = $this->record->attachments();
        \Log::info('Attachments asociados al RFQ:', [
            'count' => $finalAttachments->count(),
            'attachments' => $finalAttachments->get()->toArray()
        ]);
        
        \Log::info('=== FIN AfterCreate RFQ Debug ===');
    }
}