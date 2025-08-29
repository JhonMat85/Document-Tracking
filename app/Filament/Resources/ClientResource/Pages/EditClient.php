<?php

namespace App\Filament\Resources\ClientResource\Pages;

use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\ClientContact;
use Illuminate\Support\Facades\DB; // Add this import

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load departments and contacts data for the form
        $client = $this->getRecord();
        $contacts = $client->contacts()->get();

        // Group contacts by department
        $departments = [];
        $departmentContacts = $contacts->groupBy('department');
        
        foreach ($departmentContacts as $deptName => $deptContacts) {
            $contactsArray = $deptContacts->map(function ($contact) {
                return $contact->only(['full_name', 'position', 'phone', 'email']);
            })->toArray();

            $departments[] = [
                'name' => $deptName,
                'contacts' => $contactsArray
            ];
        }

        $data['departments'] = $departments;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Delete existing contacts associated with this client to avoid duplicates
        // In a more complex scenario, you might want to implement update logic instead of delete/create
        ClientContact::where('client_id', $this->record->id)->delete();

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