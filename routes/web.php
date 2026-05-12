<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\HRD\HRDController;

/*
|--------------------------------------------------------------------------
| PJ CONTROLLER
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\PJ\PJDashboardController;
use App\Http\Controllers\PJ\PJLeaveController;
use App\Http\Controllers\PJ\PJPermissionController;
use App\Http\Controllers\PJ\PJOvertimeController;

/*
|--------------------------------------------------------------------------
| HRD CONTROLLER
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\HRD\HRDDashboardController;
use App\Http\Controllers\HRD\HRDLeaveController;
use App\Http\Controllers\HRD\HRDPermissionController;
use App\Http\Controllers\HRD\HRDOvertimeController;

/*
|--------------------------------------------------------------------------
| ROOT
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/dashboard');
});

/*
|--------------------------------------------------------------------------
| AUTH AREA
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified'
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [
        DashboardController::class,
        'index'
    ])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | ATTENDANCE
    |--------------------------------------------------------------------------
    */

    Route::view('/face', 'face')
        ->middleware('auth')
        ->name('face');

    Route::view('/fingerprint', 'fingerprint')
        ->name('fingerprint');

    Route::view('/register-face', 'register-face')
        ->name('register.face');

    /*
    |--------------------------------------------------------------------------
    | IZIN
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

    Route::get('/izin/history', [
        PermissionController::class,
        'history'
    ])->name('izin.history');

    /*
    |--------------------------------------------------------------------------
    | CUTI
    |--------------------------------------------------------------------------
    */

    Route::get('/cuti', [
        LeaveController::class,
        'create'
    ])->name('cuti');

    Route::post('/cuti', [
        LeaveController::class,
        'store'
    ])->name('cuti.store');

    Route::get('/cuti/history', [
        LeaveController::class,
        'history'
    ])->name('cuti.history');

    /*
    |--------------------------------------------------------------------------
    | LEMBUR
    |--------------------------------------------------------------------------
    */

    Route::get('/lembur', [
        OvertimeController::class,
        'create'
    ])->name('lembur');

    Route::post('/lembur', [
        OvertimeController::class,
        'store'
    ])->name('lembur.store');

    Route::get('/lembur/history', [
        OvertimeController::class,
        'history'
    ])->name('lembur.history');

    /*
    |--------------------------------------------------------------------------
    | HISTORY
    |--------------------------------------------------------------------------
    */

    Route::get('/history', [
        HistoryController::class,
        'index'
    ])->name('history');

    /*
    |--------------------------------------------------------------------------
    | PROFILE
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

    /*
    |--------------------------------------------------------------------------
    | PJ AREA
    |--------------------------------------------------------------------------
    */

    Route::prefix('pj')
        ->middleware([
            'auth',
            'role:pj'
        ])
        ->group(function () {

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

            Route::post('/izin/{id}/approve', [
                PJPermissionController::class,
                'approve'
            ])->name('pj.izin.approve');

            Route::post('/izin/{id}/reject', [
                PJPermissionController::class,
                'reject'
            ])->name('pj.izin.reject');

            /*
            |--------------------------------------------------------------------------
            | LEMBUR
            |--------------------------------------------------------------------------
            */

            Route::get('/lembur', [
                PJOvertimeController::class,
                'index'
            ])->name('pj.lembur');

            Route::post('/lembur/{id}/approve', [
                PJOvertimeController::class,
                'approve'
            ])->name('pj.lembur.approve');

            Route::post('/lembur/{id}/reject', [
                PJOvertimeController::class,
                'reject'
            ])->name('pj.lembur.reject');
        });

    /*
    |--------------------------------------------------------------------------
    | HRD AREA
    |--------------------------------------------------------------------------
    */

    Route::prefix('hrd')
        ->middleware([
            'auth',
            'role:hrd'
        ])
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | DASHBOARD
            |--------------------------------------------------------------------------
            */


            Route::get('/dashboard', [
                HRDDashboardController::class,
                'index'
            ])->name('hrd.dashboard');

            /*
            |--------------------------------------------------------------------------
            | REKAP 
            |--------------------------------------------------------------------------
            */

            Route::get('/rekap', [
                HRDController::class,
                'index'
            ])->name('hrd.rekap');

            /*
            |--------------------------------------------------------------------------
            | CUTI
            |--------------------------------------------------------------------------
            */

            Route::get('/cuti', [
                HRDLeaveController::class,
                'index'
            ])->name('hrd.cuti');

            Route::post('/cuti/{id}/approve', [
                HRDLeaveController::class,
                'approve'
            ])->name('hrd.cuti.approve');

            Route::post('/cuti/{id}/reject', [
                HRDLeaveController::class,
                'reject'
            ])->name('hrd.cuti.reject');

            /*
            |--------------------------------------------------------------------------
            | IZIN
            |--------------------------------------------------------------------------
            */

            Route::get('/izin', [
                HRDPermissionController::class,
                'index'
            ])->name('hrd.izin');

            Route::post('/izin/{id}/approve', [
                HRDPermissionController::class,
                'approve'
            ])->name('hrd.izin.approve');

            Route::post('/izin/{id}/reject', [
                HRDPermissionController::class,
                'reject'
            ])->name('hrd.izin.reject');

            /*
            |--------------------------------------------------------------------------
            | LEMBUR
            |--------------------------------------------------------------------------
            */

            Route::get('/lembur', [
                HRDOvertimeController::class,
                'index'
            ])->name('hrd.lembur');

            Route::post('/lembur/{id}/approve', [
                HRDOvertimeController::class,
                'approve'
            ])->name('hrd.lembur.approve');

            Route::post('/lembur/{id}/reject', [
                HRDOvertimeController::class,
                'reject'
            ])->name('hrd.lembur.reject');
        });

    /*
        |--------------------------------------------------------------------------
        | SHIFT
        |--------------------------------------------------------------------------
    */

    Route::middleware(['auth'])->group(function () {

        Route::get('/shift', [ShiftController::class, 'index'])
            ->name('shift.index');

        Route::post('/shift/assign', [ShiftController::class, 'assign'])
            ->name('shift.assign');
    });
});

require __DIR__ . '/auth.php';
