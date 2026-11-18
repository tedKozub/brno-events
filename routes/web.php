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

// Dynamický Web App Manifest (PWA) přizpůsobený pro BrnoAkce i studyBrno|liveBrno
Route::get('/manifest.json', function () {
    $isStudy = \App\Helpers\BrandHelper::isStudyBrno();
    $short = \App\Helpers\BrandHelper::shortName();

    return response()->json([
        'name' => $isStudy ? 'studyBrno|liveBrno – Studentský a městský život v Brně' : 'BrnoAkce – Studentské akce v Brně',
        'short_name' => $short,
        'description' => $isStudy
            ? 'Průvodce brněnským studentským a městským životem. Akce, festivaly, kluby, studovny a spolky v Brně.'
            : 'Průvodce brněnským studentským životem. Párty, festivaly, přednášky, sport a spolkové akce v Brně.',
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'portrait',
        'background_color' => '#0f172a',
        'theme_color' => '#6366f1',
        'lang' => 'cs',
        'categories' => ['events', 'education', 'lifestyle', 'entertainment'],
        'icons' => [
            [
                'src' => '/icons/icon-192x192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
            [
                'src' => '/icons/icon-512x512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json; charset=utf-8']);
})->name('manifest');

// Právní stránky, autorská práva, GDPR a Notice & Takedown
Route::view('/podminky', 'pages.terms')->name('terms');
Route::view('/ochrana-soukromi', 'pages.privacy')->name('privacy');

// DaisyUI Live Theme Generator & Konfigurátor
Route::get('/theme-generator', function () {
    return redirect('/?theme-editor=1');
})->name('theme.generator');

Route::get('/theme', function () {
    return redirect('/?theme-editor=1');
});

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
