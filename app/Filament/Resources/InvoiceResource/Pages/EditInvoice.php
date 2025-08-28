<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Attachment;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar archivos existentes para mostrar en el formulario
        $existingAttachments = $this->record->attachments;
        $attachmentFiles = [];
        
        foreach ($existingAttachments as $attachment) {
            if (Storage::disk('public')->exists($attachment->file_path)) {
                $attachmentFiles[] = $attachment->file_path;
            }
        }
        
        $data['attachment_files'] = $attachmentFiles;
        
        \Log::info('Cargando archivos existentes Invoice:', [
            'invoice_id' => $this->record->id,
            'existing_files' => $attachmentFiles,
            'count' => count($attachmentFiles)
        ]);
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        
        return $data;
    }

    protected function afterSave(): void
    {
        \Log::info('=== INICIO AfterSave Invoice Debug ===');
        
        // Log del estado del formulario
        $rawState = $this->form->getRawState();
        \Log::info('Form Raw State Invoice completo:', $rawState);
        
        // Manejar los archivos subidos
        $attachmentFiles = $rawState['attachment_files'] ?? [];
        
        \Log::info('Attachment Files Invoice encontrados:', [
            'files' => $attachmentFiles,
            'count' => count($attachmentFiles),
            'type' => gettype($attachmentFiles),
            'is_empty' => empty($attachmentFiles)
        ]);
        
        if (!empty($attachmentFiles)) {
            \Log::info('Procesando archivos Invoice en edición...');
            
            foreach ($attachmentFiles as $index => $file) {
                \Log::info("Archivo Invoice #{$index}:", [
                    'file' => $file,
                    'is_object' => is_object($file),
                    'type' => gettype($file),
                    'class' => is_object($file) ? get_class($file) : 'not_object'
                ]);
                
                // Solo procesar objetos UploadedFile nuevos, no strings (archivos existentes)
                if (is_object($file) && method_exists($file, 'getClientOriginalName')) {
                    \Log::info('Archivo Invoice nuevo válido, procesando...');
                    
                    try {
                        // Obtener información del archivo
                        $originalName = $file->getClientOriginalName();
                        $mimeType = $file->getMimeType();
                        $size = $file->getSize();
                        $fileHash = md5_file($file->getRealPath());
                        
                        \Log::info('Información del archivo Invoice nuevo:', [
                            'original_name' => $originalName,
                            'mime_type' => $mimeType,
                            'size' => $size,
                            'hash' => $fileHash
                        ]);
                        
                        // Generar nombre único para el archivo
                        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                        
                        // Almacenar el archivo
                        $filePath = $file->storeAs('invoice-attachments', $fileName, 'public');
                        
                        \Log::info('Archivo Invoice nuevo almacenado:', [
                            'file_name' => $fileName,
                            'file_path' => $filePath
                        ]);
                        
                        // Crear el registro en la tabla attachments
                        $attachment = Attachment::create([
                            'attachable_type' => 'App\\Models\\Invoice',
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
                        
                        \Log::info('Attachment Invoice nuevo creado exitosamente:', [
                            'attachment_id' => $attachment->id,
                            'attachment' => $attachment->toArray()
                        ]);
                        
                    } catch (\Exception $e) {
                        \Log::error('Error al procesar archivo Invoice nuevo:', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } else {
                    \Log::info('Archivo Invoice existente (string) o no válido:', [
                        'file' => $file,
                        'is_string' => is_string($file),
                        'is_object' => is_object($file)
                    ]);
                }
            }
        } else {
            \Log::info('No se encontraron archivos para procesar en Invoice (edición)');
        }
        
        // Verificar datos finales
        $finalAttachments = $this->record->attachments();
        \Log::info('Attachments asociados al Invoice (después de edición):', [
            'count' => $finalAttachments->count(),
            'attachments' => $finalAttachments->get()->toArray()
        ]);
        
        \Log::info('=== FIN AfterSave Invoice Debug ===');
    }
}