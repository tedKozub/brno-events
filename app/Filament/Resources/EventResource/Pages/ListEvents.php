<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Models\Event;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nová akce'),
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();
        $isClub = $user && $user->isClubAdmin() && $user->organization_id;

        $pendingQuery = Event::where('status', 'pending_review');
        $missingQuery = Event::withMissingData();
        if ($isClub) {
            $pendingQuery->where('organization_id', $user->organization_id);
            $missingQuery->where('organization_id', $user->organization_id);
        }
        $pendingCount = $pendingQuery->count();
        $missingCount = $missingQuery->count();

        return [
            'all' => Tab::make('Všechny akce'),
            'pending_review' => Tab::make('Ke schválení (moderace)')
                ->badge($pendingCount > 0 ? (string) $pendingCount : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending_review')),
            'missing_data' => Tab::make('Vyžaduje doplnění dat')
                ->badge($missingCount > 0 ? (string) $missingCount : null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->withMissingData()),
            'published' => Tab::make('Publikované')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'published')),
            'draft' => Tab::make('Koncepty')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft')),
        ];
    }
}
