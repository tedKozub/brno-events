<?php

namespace App\Filament\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\Widget;

class AnalyticsCountryWidget extends Widget
{
    protected static string $view = 'filament.widgets.analytics-country-widget';
    protected static ?int $sort = 2;
    protected static bool $isLazy = false;
    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $service = app(AnalyticsService::class);
        $countries = $service->getCountryStats(14);
        $traffic = $service->getTrafficBreakdown(14);
        $isInternalDevice = request()->cookie('brnoakce_internal') === '1';

        return [
            'countries' => $countries,
            'traffic' => $traffic,
            'isInternalDevice' => $isInternalDevice,
        ];
    }
}
