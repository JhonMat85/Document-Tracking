<?php

namespace App\Filament\Resources\SystemConfigurationResource\Pages;

use App\Filament\Resources\SystemConfigurationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditSystemConfiguration extends EditRecord
{
    protected static string $resource = SystemConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Para configuraciones especiales, inicializar campos específicos
        if ($data['key'] === 'company_logo' && $data['value']) {
            $data['logo_upload'] = [$data['value']];
        }

        if ($data['key'] === 'bank_account_info') {
            $data['bank_info_input'] = $data['value'];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        Log::info('EDIT SYSTEM CONFIG DEBUG - mutateFormDataBeforeSave called', [
            'key' => $data['key'] ?? 'N/A',
            'has_logo_upload' => isset($data['logo_upload']),
            'logo_upload_type' => isset($data['logo_upload']) ? gettype($data['logo_upload']) : 'N/A',
            'logo_upload_value' => $data['logo_upload'] ?? 'N/A',
            'current_value' => $data['value'] ?? 'N/A',
            'timestamp' => now()
        ]);

        // Limpiar campos que no corresponden a esta configuración
        if ($data['key'] !== 'company_logo') {
            unset($data['logo_upload']);
        }

        if ($data['key'] !== 'bank_account_info') {
            unset($data['bank_info_input']);
        }

        // Para configuraciones especiales, asegurar que el value tenga el valor correcto
        if ($data['key'] === 'company_logo' && isset($data['logo_upload']) && !empty($data['logo_upload'])) {
            // FileUpload puede devolver un string (un archivo) o array (múltiples)
            $data['value'] = is_array($data['logo_upload']) ? $data['logo_upload'][0] : $data['logo_upload'];

            Log::info('EDIT SYSTEM CONFIG DEBUG - Logo value set', [
                'new_value' => $data['value'],
                'original_logo_upload' => $data['logo_upload']
            ]);
        }

        if ($data['key'] === 'bank_account_info' && isset($data['bank_info_input'])) {
            $data['value'] = $data['bank_info_input'];
        }

        return $data;
    }
}
