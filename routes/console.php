<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Console Schedule Configuration
|--------------------------------------------------------------------------
| Automatický noční běh scraperu pro Brno studentské akce
| Běží denně v 03:30 ráno (mimo špičku) a posílá souhrn do Discord kanálu.
*/
Schedule::command('events:scrape')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scraper_cron.log'))
    ->runInBackground();
