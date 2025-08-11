<?php

namespace App\Filament\Admin\Resources\PropertyResource\Pages;

use App\Filament\Admin\Resources\PropertyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Créer la propriété avec les données (y compris owner_id si fourni)
        $modelClass = static::getResource()::getModel();
        $property = $modelClass::create($data);

        Log::info("Property created", [
            'property_id' => $property->id,
            'property_name' => $property->name,
            'owner_id' => $property->owner_id,
        ]);

        return $property;
    }
}
