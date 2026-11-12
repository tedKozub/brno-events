<?php

namespace App\Filament\Resources\StudentPlaceCategoryResource\Pages;

use App\Filament\Resources\StudentPlaceCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentPlaceCategory extends CreateRecord
{
    protected static string $resource = StudentPlaceCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
