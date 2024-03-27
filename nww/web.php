<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ContactFormController;
use App\Http\Controllers\CustomerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route:: get('tests/test', [ TestController::class, 'index' ]);

// Route::resource('contacts', ContactFormController::class);

Route::group(['prefix' => 'contacts', 'middleware' => ['auth'], 'as' => 'contacts.'], function () {
    Route::get('/', [ContactFormController::class, 'index'])->name('index');
    Route::get('/create', [ContactFormController::class, 'create'])->name('create');
    Route::post('/', [ContactFormController::class, 'store'])->name('store');
    Route::get('/{id}', [ContactFormController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [ContactFormController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ContactFormController::class, 'update'])->name('update');
    Route::delete('/{id}', [ContactFormController::class, 'destroy'])->name('destroy');
});

Route::get('/dashboard', [CustomerController::class, 'index'])->middleware(['auth'])->name('dashboard');
Route::get('/dashboard/search', [CustomerController::class, 'search'])->middleware(['auth'])->name('dashboard.search');

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

require __DIR__.'/auth.php';
