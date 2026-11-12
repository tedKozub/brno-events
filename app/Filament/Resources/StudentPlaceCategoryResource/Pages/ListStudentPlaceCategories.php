<?php

namespace App\Filament\Resources\StudentPlaceCategoryResource\Pages;

use App\Filament\Resources\StudentPlaceCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentPlaceCategories extends ListRecords
{
    protected static string $resource = StudentPlaceCategoryResource::class;

    public function getTitle(): string
    {
        return 'Kategorie míst';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nová kategorie'),
        ];
    }
}
