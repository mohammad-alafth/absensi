<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Redirect Root
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/dashboard');
});


/*
|--------------------------------------------------------------------------
| Protected Pages
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [
        DashboardController::class,
        'index'
    ])->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | Attendance Pages
    |--------------------------------------------------------------------------
    */
    Route::view('/face', 'face')
        ->name('face');

    Route::view('/fingerprint', 'fingerprint')
        ->name('fingerprint');

    Route::view('/register-face', 'register-face')
        ->name('register.face');


    /*
    |--------------------------------------------------------------------------
    | Leave Pages
    |--------------------------------------------------------------------------
    */
    Route::view('/izin', 'izin')
        ->name('izin');

    Route::view('/cuti', 'cuti')
        ->name('cuti');


    /*
    |--------------------------------------------------------------------------
    | History
    |--------------------------------------------------------------------------
    */
    Route::view('/history', 'history')
        ->name('history');


    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [
        ProfileController::class,
        'edit'
    ])->name('profile.edit');

    Route::patch('/profile', [
        ProfileController::class,
        'update'
    ])->name('profile.update');

    Route::delete('/profile', [
        ProfileController::class,
        'destroy'
    ])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
