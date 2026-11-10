<?php

use App\Livewire\EventBoard;
use Illuminate\Support\Facades\Route;

Route::get('/', EventBoard::class)->name('home');
