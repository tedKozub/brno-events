<?php

namespace App\Filament\Resources\ScrapeSourceResource\Pages;

use App\Filament\Resources\ScrapeSourceResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class ListScrapeSources extends ListRecords
{
    protected static string $resource = ScrapeSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('scrape_all')
                ->label('Spustit scraping všech zdrojů')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function () {
                    Artisan::call('events:scrape');
                    $output = Artisan::output();

                    Notification::make()
                        ->title('Dávkový scraping dokončen')
                        ->body(Str::limit($output, 200))
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make()->label('Nový zdroj'),
        ];
    }
}
