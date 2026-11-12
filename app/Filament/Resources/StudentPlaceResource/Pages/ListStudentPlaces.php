<?php

namespace App\Filament\Resources\StudentPlaceResource\Pages;

use App\Filament\Resources\StudentPlaceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentPlaces extends ListRecords
{
    protected static string $resource = StudentPlaceResource::class;

    public function getTitle(): string
    {
        return 'Místa v mapě';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nové místo'),
        ];
    }
}
