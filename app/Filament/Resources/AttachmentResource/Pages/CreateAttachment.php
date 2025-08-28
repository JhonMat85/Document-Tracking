<?php

namespace App\Filament\Resources\AttachmentResource\Pages;

use App\Filament\Resources\AttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateAttachment extends CreateRecord
{
    protected static string $resource = AttachmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = auth()->id();
        
        // Si se subió un archivo, extraer metadatos
        if (isset($data['file_path']) && is_string($data['file_path'])) {
            $filePath = $data['file_path'];
            
            if (Storage::disk('public')->exists($filePath)) {
                $fullPath = Storage::disk('public')->path($filePath);
                
                $data['file_name'] = basename($filePath);
                $data['mime_type'] = Storage::disk('public')->mimeType($filePath);
                $data['size_bytes'] = Storage::disk('public')->size($filePath);
                $data['file_hash'] = md5_file($fullPath);
                
                // Si no se especificó nombre original, usar el nombre del archivo
                if (empty($data['original_name'])) {
                    $data['original_name'] = $data['file_name'];
                }
            }
        }
        
        return $data;
    }
}