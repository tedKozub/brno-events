<?php

namespace App\Filament\Resources\StudentPlaceResource\Pages;

use App\Filament\Resources\StudentPlaceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentPlace extends CreateRecord
{
    protected static string $resource = StudentPlaceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
