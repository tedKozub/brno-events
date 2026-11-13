<?php

namespace App\Filament\Resources\StudentPlaceSuggestionResource\Pages;

use App\Filament\Resources\StudentPlaceSuggestionResource;
use App\Models\StudentPlaceSuggestion;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListStudentPlaceSuggestions extends ListRecords
{
    protected static string $resource = StudentPlaceSuggestionResource::class;

    public function getDefaultActiveTab(): string | int | null
    {
        return StudentPlaceSuggestion::where('status', 'pending')->exists() ? 'pending' : 'all';
    }

    public function getTabs(): array
    {
        $pendingCount = StudentPlaceSuggestion::where('status', 'pending')->count();
        $approvedCount = StudentPlaceSuggestion::where('status', 'approved')->count();
        $rejectedCount = StudentPlaceSuggestion::where('status', 'rejected')->count();
        $allCount = StudentPlaceSuggestion::count();

        return [
            'pending' => Tab::make('Ke schválení')
                ->badge((string) $pendingCount)
                ->badgeColor($pendingCount > 0 ? 'warning' : 'gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
            'approved' => Tab::make('Schválené')
                ->badge((string) $approvedCount)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved')),
            'rejected' => Tab::make('Zamítnuté')
                ->badge((string) $rejectedCount)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
            'all' => Tab::make('Všechny')
                ->badge((string) $allCount)
                ->badgeColor('gray'),
        ];
    }
}
