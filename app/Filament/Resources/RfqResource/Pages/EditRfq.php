<?php

namespace App\Filament\Resources\RfqResource\Pages;

use App\Filament\Resources\RfqResource;
use App\Models\Attachment;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class EditRfq extends EditRecord
{
    protected static string $resource = RfqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar los attachments existentes para mostrar en el formulario
        $attachments = $this->record->attachments;
        $attachmentPaths = [];
        
        foreach ($attachments as $attachment) {
            $attachmentPaths[] = $attachment->file_path;
        }
        
        $data['attachment_files'] = $attachmentPaths;
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Manejar los archivos subidos nuevos
        $attachmentFiles = $this->form->getRawState()['attachment_files'] ?? [];
        
        if (!empty($attachmentFiles)) {
            foreach ($attachmentFiles as $file) {
                // Solo procesar archivos nuevos (UploadedFile instances)
                if (is_object($file) && method_exists($file, 'getClientOriginalName')) {
                    // Obtener información del archivo
                    $originalName = $file->getClientOriginalName();
                    $mimeType = $file->getMimeType();
                    $size = $file->getSize();
                    $fileHash = md5_file($file->getRealPath());
                    
                    // Generar nombre único para el archivo
                    $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                    
                    // Almacenar el archivo
                    $filePath = $file->storeAs('rfq-attachments', $fileName, 'public');
                    
                    // Crear el registro en la tabla attachments
                    Attachment::create([
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
                }
            }
        }
    }
}