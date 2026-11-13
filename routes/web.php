<?php

use App\Http\Controllers\OgImageController;
use App\Http\Controllers\SitemapController;
use App\Livewire\EventBoard;
use App\Livewire\EventDetail;
use App\Livewire\StudentPlaces;
use Illuminate\Support\Facades\Route;

Route::get('/', EventBoard::class)->name('home');
Route::get('/mista', StudentPlaces::class)->name('places');
Route::get('/akce/{slug}', EventDetail::class)->name('event.detail');
Route::get('/akce/{slug}/og-image.jpg', [OgImageController::class, 'show'])->name('event.og-image');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Právní stránky, autorská práva, GDPR a Notice & Takedown
Route::view('/podminky', 'pages.terms')->name('terms');
Route::view('/ochrana-soukromi', 'pages.privacy')->name('privacy');

// Přepínač interního / osobního testovacího režimu pro vývojáře a adminy (vyloučení zařízení z analytiky)
Route::get('/analytics/toggle-device', function () {
    $current = request()->cookie('brnoakce_internal') === '1';
    if ($current) {
        \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forget('brnoakce_internal'));
        $msg = 'Zařízení bylo vráceno do běžného režimu. Vaše návštěvy se nyní započítávají do statistik.';
    } else {
        \Illuminate\Support\Facades\Cookie::queue('brnoakce_internal', '1', 60 * 24 * 365, null, null, false, false);
        $msg = 'Zařízení je nyní v INTERNÍM REŽIMU. Vaše návštěvy a testování jsou vyloučeny z reálné návštěvnosti.';
    }

    if (request()->has('redirect_to')) {
        return redirect()->to(request()->get('redirect_to'))->with('status', $msg);
    }

    return redirect()->back()->with('status', $msg);
})->name('analytics.toggle-device');
