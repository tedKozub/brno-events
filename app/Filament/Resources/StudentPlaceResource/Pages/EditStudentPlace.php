<?php

namespace App\Filament\Resources\StudentPlaceResource\Pages;

use App\Filament\Resources\StudentPlaceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentPlace extends EditRecord
{
    protected static string $resource = StudentPlaceResource::class;

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
