<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnalyticsOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected static ?int $sort = 1;
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $service = app(AnalyticsService::class);
        $summary = $service->getSummaryStats(7);
        $trend = $service->getDailyTrend(7);

        return [
            Stat::make('Zobrazení stránek (7 dní)', number_format($summary['pageviews'], 0, ',', ' '))
                ->description('Anonymní zobrazení webu a akcí')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($trend['data'])
                ->color('primary'),

            Stat::make('Unikátní návštěvníci (7 dní)', number_format($summary['unique_visitors'], 0, ',', ' '))
                ->description('Denní anonymizovaný hash bez cookies')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Prokliky ke spolkům & Vstupenky', number_format($summary['outbound_clicks'], 0, ',', ' '))
                ->description('Návštěvníci poslaní na weby spolků')
                ->descriptionIcon('heroicon-m-arrow-top-right-on-square')
                ->color('warning'),

            Stat::make('Stažení do kalendáře (iCal)', number_format($summary['ical_downloads'], 0, ',', ' '))
                ->description('Uložené akce do mobilních kalendářů')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }
}
