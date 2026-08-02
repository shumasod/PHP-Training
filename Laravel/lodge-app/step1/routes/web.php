<?php

use App\Http\Controllers\LodgeController;

Route::get('/lodges', [LodgeController::class, 'index'])->name('lodges.index');
Route::get('/lodges/{lodge}', [LodgeController::class, 'show'])->name('lodges.show');
