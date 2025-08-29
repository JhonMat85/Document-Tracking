<?php

namespace App\Filament\Resources\ClientResource\Pages;

use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\ClientResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\ClientContact; // Add this import

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $departmentsData = $this->data['departments'] ?? [];
        
        foreach ($departmentsData as $deptData) {
            $departmentName = $deptData['name'] ?? 'General';
            $contactsData = $deptData['contacts'] ?? [];

            foreach ($contactsData as $contactData) {
                ClientContact::create(array_merge($contactData, [
                    'client_id' => $this->record->id,
                    'department' => $departmentName,
                    'is_active' => true,
                ]));
            }
        }
    }
}