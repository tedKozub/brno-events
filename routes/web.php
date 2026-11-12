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
