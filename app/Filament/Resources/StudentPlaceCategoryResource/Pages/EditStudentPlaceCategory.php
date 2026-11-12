<?php

namespace App\Filament\Resources\StudentPlaceCategoryResource\Pages;

use App\Filament\Resources\StudentPlaceCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentPlaceCategory extends EditRecord
{
    protected static string $resource = StudentPlaceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
