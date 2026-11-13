<?php

namespace App\Filament\Resources\StudentPlaceSuggestionResource\Pages;

use App\Filament\Resources\StudentPlaceSuggestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentPlaceSuggestion extends EditRecord
{
    protected static string $resource = StudentPlaceSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
