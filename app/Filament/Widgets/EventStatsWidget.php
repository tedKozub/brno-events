<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EventStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '5s';
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $upcomingCount = Event::published()->upcoming()->count();
        $publishedTotal = Event::where('status', 'published')->count();
        $pendingCount = Event::where('status', 'pending_review')->count();
        $missingCount = Event::withMissingData()->where('status', 'pending_review')->count();
        $rejectedCount = Event::where('status', 'rejected')->count();

        return [
            Stat::make('Aktivní na webu', $upcomingCount)
                ->description("Z {$publishedTotal} publikovaných celkem")
                ->color('success'),
            Stat::make('Čekají na schválení', $pendingCount)
                ->description($pendingCount > 0 ? 'Vyžaduje kontrolu moderátora' : 'Všechny akce vyřízeny')
                ->color($pendingCount > 0 ? 'warning' : 'success'),
            Stat::make('K doplnění dat', $missingCount)
                ->description('V moderaci chybí datum, lokace...')
                ->color($missingCount > 0 ? 'danger' : 'gray'),
            Stat::make('Zamítnuté', $rejectedCount)
                ->description('Vyřazeno (znovu se nescrapují)')
                ->color('gray'),
        ];
    }
}

