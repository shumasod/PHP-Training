<?php

use App\Http\Controllers\LodgeController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\SymbolController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Lodge routes - using resource controller
Route::resource('lodges', LodgeController::class);

// Member routes
Route::prefix('lodges/{lodge}')->group(function () {
    Route::resource('members', MemberController::class)->except(['index', 'show']);
    Route::post('members/{member}/promote', [MemberController::class, 'promote'])->name('members.promote');
});

// Event routes
Route::prefix('lodges/{lodge}')->group(function () {
    Route::resource('events', EventController::class)->except(['index', 'show']);
});

// Symbol routes
Route::resource('symbols', SymbolController::class)->only(['index', 'show']);

Auth::routes();
