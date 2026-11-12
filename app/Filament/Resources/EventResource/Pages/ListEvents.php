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

    protected ?string $pollingInterval = '5s';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nová akce'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        $user = auth()->user();
        $query = Event::where('status', 'pending_review');
        if ($user && $user->isClubAdmin() && $user->organization_id) {
            $query->where('organization_id', $user->organization_id);
        }
        return $query->exists() ? 'pending_review' : 'published';
    }

    public function getTabs(): array
    {
        $user = auth()->user();
        $isClub = $user && $user->isClubAdmin() && $user->organization_id;

        $pendingQuery = Event::where('status', 'pending_review');
        $missingQuery = Event::withMissingData()->where('status', 'pending_review');
        $publishedQuery = Event::where('status', 'published');
        $rejectedQuery = Event::where('status', 'rejected');
        $allQuery = Event::query();

        if ($isClub) {
            $pendingQuery->where('organization_id', $user->organization_id);
            $missingQuery->where('organization_id', $user->organization_id);
            $publishedQuery->where('organization_id', $user->organization_id);
            $rejectedQuery->where('organization_id', $user->organization_id);
            $allQuery->where('organization_id', $user->organization_id);
        }

        $pendingCount = $pendingQuery->count();
        $missingCount = $missingQuery->count();
        $publishedCount = $publishedQuery->count();
        $rejectedCount = $rejectedQuery->count();
        $allCount = $allQuery->count();

        return [
            'pending_review' => Tab::make('Ke schválení (moderace)')
                ->badge((string) $pendingCount)
                ->badgeColor($pendingCount > 0 ? 'warning' : 'gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending_review')),
            'missing_data' => Tab::make('Vyžaduje doplnění dat')
                ->badge((string) $missingCount)
                ->badgeColor($missingCount > 0 ? 'danger' : 'gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->withMissingData()->where('status', 'pending_review')),
            'published' => Tab::make('Publikované')
                ->badge((string) $publishedCount)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'published')),
            'rejected' => Tab::make('Vyřazené / Zamítnuté')
                ->badge((string) $rejectedCount)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
            'all' => Tab::make('Všechny akce')
                ->badge((string) $allCount)
                ->badgeColor('gray'),
        ];
    }
}
