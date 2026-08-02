<?php

use App\Http\Controllers\LodgeController;

Route::get('/lodges', [LodgeController::class, 'index'])->name('lodges.index');
Route::get('/lodges/create', [LodgeController::class, 'create'])->name('lodges.create');
Route::post('/lodges', [LodgeController::class, 'store'])->name('lodges.store');
Route::get('/lodges/{lodge}', [LodgeController::class, 'show'])->name('lodges.show');
