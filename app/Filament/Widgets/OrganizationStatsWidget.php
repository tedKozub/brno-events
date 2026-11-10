<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Organization;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrganizationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Spolky a organizace', Organization::count())
                ->description('Registrované organizace')
                ->color('primary'),
            Stat::make('Kategorie akcí', Category::count())
                ->description('Dostupné kategorie')
                ->color('info'),
        ];
    }
}
