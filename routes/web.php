<?php

use App\Http\Controllers\SitemapController;
use App\Livewire\EventBoard;
use App\Livewire\EventDetail;
use Illuminate\Support\Facades\Route;

Route::get('/', EventBoard::class)->name('home');
Route::get('/akce/{slug}', EventDetail::class)->name('event.detail');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
