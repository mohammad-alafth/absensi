<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\PJ\PJDashboardController;
use App\Http\Controllers\PJ\PJLeaveController;
use App\Http\Controllers\PJ\PJPermissionController;
use App\Http\Controllers\PJ\PJOvertimeController;


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
    Route::get('/izin', [
        PermissionController::class,
        'create'
    ])->name('izin');

    Route::post('/izin', [
        PermissionController::class,
        'store'
    ])->name('izin.store');

    Route::get('/cuti', [
        LeaveController::class,
        'create'
    ])->name('cuti');

    Route::post('/cuti', [
        LeaveController::class,
        'store'
    ])->name('cuti.store');

    // izin
    Route::get('/izin/history', [
        PermissionController::class,
        'history'
    ])->name('izin.history');


    // cuti
    Route::get('/cuti/history', [
        LeaveController::class,
        'history'
    ])->name('cuti.history');

    /*
    |--------------------------------------------------------------------------
    | History
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/history',
        [HistoryController::class, 'index']
    )->name('history');

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


    Route::middleware(['auth'])
        ->prefix('pj')
        ->group(function () {

            /*
        |--------------------------------------------------------------------------
        | DASHBOARD PJ
        |--------------------------------------------------------------------------
        */
            Route::get('/dashboard', [
                PJDashboardController::class,
                'index'
            ])->name('pj.dashboard');


            /*
        |--------------------------------------------------------------------------
        | CUTI
        |--------------------------------------------------------------------------
        */
            Route::get('/cuti', [
                PJLeaveController::class,
                'index'
            ])->name('pj.cuti');

            Route::post('/cuti/{id}/approve', [
                PJLeaveController::class,
                'approve'
            ])->name('pj.cuti.approve');

            Route::post('/cuti/{id}/reject', [
                PJLeaveController::class,
                'reject'
            ])->name('pj.cuti.reject');

            /*
        |--------------------------------------------------------------------------
        | IZIN
        |--------------------------------------------------------------------------
        */
            Route::get('/izin', [
                PJPermissionController::class,
                'index'
            ])->name('pj.izin');


            /*
        |--------------------------------------------------------------------------
        | LEMBUR
        |--------------------------------------------------------------------------
        */
            Route::get('/lembur', [
                PJOvertimeController::class,
                'index'
            ])->name('pj.lembur');
        });
    Route::prefix('hrd')
        ->middleware(['auth'])
        ->group(function () {

            Route::get('/cuti', [
                PJLeaveController::class,
                'index'
            ])->name('hrd.cuti');
        });
});

require __DIR__ . '/auth.php';
