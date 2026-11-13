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
        $countries = $service->getCountryStats(7);

        $countrySummary = !empty($countries)
            ? collect($countries)->take(2)->map(fn($c) => "{$c['flag']} {$c['code']} ({$c['percentage']}%)")->implode(' • ')
            : '🇨🇿 CZ (100 %)';

        return [
            Stat::make('Zobrazení stránek (7 dní)', number_format($summary['pageviews'], 0, ',', ' '))
                ->description("Reální návštěvníci • {$countrySummary}")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($trend['data'])
                ->color('primary'),

            Stat::make('Unikátní návštěvníci (7 dní)', number_format($summary['unique_visitors'], 0, ',', ' '))
                ->description("Top státy: {$countrySummary}")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Prokliky ke spolkům & Vstupenky', number_format($summary['outbound_clicks'], 0, ',', ' '))
                ->description('Návštěvníci poslaní na weby spolků')
                ->descriptionIcon('heroicon-m-arrow-top-right-on-square')
                ->color('warning'),

            Stat::make('Stažení do kalendáře (iCal)', number_format($summary['ical_downloads'], 0, ',', ' '))
                ->description("Odfiltrováno {$summary['excluded_internal_events']} interních testů")
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }
}
