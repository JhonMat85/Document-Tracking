<?php

namespace App\Filament\Resources\SystemConfigurationResource\Pages;

use App\Filament\Resources\SystemConfigurationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSystemConfiguration extends CreateRecord
{
    protected static string $resource = SystemConfigurationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
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
        }

        if ($data['key'] === 'bank_account_info' && isset($data['bank_info_input'])) {
            $data['value'] = $data['bank_info_input'];
        }

        return $data;
    }
}
