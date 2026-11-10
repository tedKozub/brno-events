<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EventStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Publikované akce', Event::where('status', 'published')->count())
                ->description('Viditelné na webu')
                ->color('success'),
            Stat::make('Čekají na schválení', Event::where('status', 'pending_review')->count())
                ->description('Ke schválení v moderaci')
                ->color('warning'),
            Stat::make('Automaticky stažené', Event::where('source_type', 'scraper_web')->count())
                ->description('Ze scrapovacích zdrojů')
                ->color('info'),
        ];
    }
}
