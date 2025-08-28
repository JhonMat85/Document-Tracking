<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Attachment;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        
        // Generar número de factura automático si no se proporciona
        if (empty($data['invoice_number'])) {
            $currentYear = date('Y');
            $lastInvoice = \App\Models\Invoice::whereYear('created_at', $currentYear)
                ->orderBy('invoice_number', 'desc')
                ->first();
            
            if ($lastInvoice && preg_match('/INV-' . $currentYear . '-(\d+)/', $lastInvoice->invoice_number, $matches)) {
                $nextNumber = intval($matches[1]) + 1;
            } else {
                $nextNumber = 1;
            }
            
            $data['invoice_number'] = 'INV-' . $currentYear . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        \Log::info('=== INICIO AfterCreate Invoice Debug ===');
        
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
            \Log::info('Procesando archivos Invoice...');
            
            foreach ($attachmentFiles as $index => $file) {
                \Log::info("Archivo Invoice #{$index}:", [
                    'file' => $file,
                    'is_object' => is_object($file),
                    'type' => gettype($file),
                    'class' => is_object($file) ? get_class($file) : 'not_object'
                ]);
                
                // Solo procesar objetos UploadedFile, no strings
                if (is_object($file) && method_exists($file, 'getClientOriginalName')) {
                    \Log::info('Archivo Invoice válido, procesando...');
                    
                    try {
                        // Obtener información del archivo
                        $originalName = $file->getClientOriginalName();
                        $mimeType = $file->getMimeType();
                        $size = $file->getSize();
                        $fileHash = md5_file($file->getRealPath());
                        
                        \Log::info('Información del archivo Invoice:', [
                            'original_name' => $originalName,
                            'mime_type' => $mimeType,
                            'size' => $size,
                            'hash' => $fileHash
                        ]);
                        
                        // Generar nombre único para el archivo
                        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                        
                        // Almacenar el archivo
                        $filePath = $file->storeAs('invoice-attachments', $fileName, 'public');
                        
                        \Log::info('Archivo Invoice almacenado:', [
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
                        
                        \Log::info('Attachment Invoice creado exitosamente:', [
                            'attachment_id' => $attachment->id,
                            'attachment' => $attachment->toArray()
                        ]);
                        
                    } catch (\Exception $e) {
                        \Log::error('Error al procesar archivo Invoice:', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } else {
                    \Log::warning('Archivo Invoice no válido:', [
                        'file' => $file,
                        'is_object' => is_object($file),
                        'has_method' => is_object($file) ? method_exists($file, 'getClientOriginalName') : false
                    ]);
                }
            }
        } else {
            \Log::info('No se encontraron archivos para procesar en Invoice');
        }
        
        // Verificar datos finales
        $finalAttachments = $this->record->attachments();
        \Log::info('Attachments asociados al Invoice:', [
            'count' => $finalAttachments->count(),
            'attachments' => $finalAttachments->get()->toArray()
        ]);
        
        \Log::info('=== FIN AfterCreate Invoice Debug ===');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}