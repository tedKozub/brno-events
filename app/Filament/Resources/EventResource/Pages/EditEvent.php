<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label(fn () => $this->record->status === 'rejected' ? 'Obnovit a publikovat' : 'Schválit a publikovat')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status !== 'published')
                ->action(function () {
                    $this->record->update(['status' => 'published']);
                    Notification::make()
                        ->title('Akce byla schválena a publikována na webu.')
                        ->success()
                        ->send();
                    $this->redirect(EventResource::getUrl('index'));
                }),

            Actions\Action::make('reject')
                ->label('Zamítnout a vyřadit')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status !== 'rejected')
                ->requiresConfirmation()
                ->modalHeading('Zamítnout a vyřadit tuto akci?')
                ->modalDescription('Akce se přesune do záložky "Vyřazené / Zamítnuté", nebude se zobrazovat na webu a scraper ji již znovu nestáhne.')
                ->action(function () {
                    $this->record->update(['status' => 'rejected']);
                    Notification::make()
                        ->title('Akce byla vyřazena (znovu se již nescrapne).')
                        ->warning()
                        ->send();
                    $this->redirect(EventResource::getUrl('index'));
                }),

            Actions\Action::make('view_web')
                ->label('Zobrazit na webu')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->visible(fn () => $this->record->status === 'published')
                ->url(fn () => url('/akce/' . $this->record->slug))
                ->openUrlInNewTab(),

            Actions\DeleteAction::make()
                ->label('Trvale smazat')
                ->modalHeading('Trvale smazat akci z databáze?')
                ->modalDescription('Akce bude nevratně smazána z databáze.')
                ->visible(fn () => $this->record->status === 'rejected'),
        ];
    }
}
