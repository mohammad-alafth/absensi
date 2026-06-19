


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
use App\Http\Controllers\HRD\HRDUserController;

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
use App\Http\Controllers\HRD\ShiftManagementController;

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

    Route::middleware(['auth'])->group(function () {

        // Rute Unduh Cetak Dokumen PDF Resmi
        Route::get('/leaves/{id}/download-pdf', [LeaveController::class, 'downloadPdf'])->name('leaves.download-pdf');
        Route::get('/permissions/{id}/download-pdf', [PermissionController::class, 'downloadPdf'])->name('permissions.download-pdf');
        Route::get('/overtimes/{id}/download-pdf', [OvertimeController::class, 'downloadPdf'])->name('overtimes.download-pdf');

        // Tambahkan rute history index Anda jika belum ada
        // Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    });
    /*
    |--------------------------------------------------------------------------
    | HRD AREA
    |--------------------------------------------------------------------------
    */

    Route::prefix('hrd')->middleware(['auth', 'verified'])->group(function () {
        Route::get('/users/approval', [HRDUserController::class, 'index'])->name('hrd.users.approval');
        Route::post('/users/{id}/approve', [HRDUserController::class, 'approve'])->name('hrd.users.approve');
        Route::post('/users/{id}/reset-password', [HRDUserController::class, 'resetPassword'])->name('hrd.users.reset-password');
    });
    Route::prefix('hrd')
        ->middleware([
            'auth',
            'role:hrd,head_pegawai,director'
        ])
        ->name('hrd.')
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | DASHBOARD
            |--------------------------------------------------------------------------
            */
            Route::get('/export-excel', [HRDController::class, 'exportExcel'])->name('export.excel');

            Route::get('/dashboard', [HRDDashboardController::class, 'index'])
                ->name('dashboard');

            /*
            |--------------------------------------------------------------------------
            | REKAP 
            |--------------------------------------------------------------------------
            */

            Route::get('/rekap', [
                HRDController::class,
                'index'
            ])->name('rekap');

            /*
            |--------------------------------------------------------------------------
            | CUTI
            |--------------------------------------------------------------------------
            */

            Route::get('/cuti', [
                HRDLeaveController::class,
                'index'
            ])->name('cuti');

            Route::post('/cuti/{id}/approve', [
                HRDLeaveController::class,
                'approve'
            ])->name('cuti.approve');

            Route::post('/cuti/{id}/reject', [
                HRDLeaveController::class,
                'reject'
            ])->name('cuti.reject');
            Route::post('/update-leave-quota/{user}', [HRDController::class, 'updateLeaveQuota'])
                ->name('update.leave.quota');

            /*
            |--------------------------------------------------------------------------
            | IZIN
            |--------------------------------------------------------------------------
            */
            Route::get('/rekap', [HRDController::class, 'index'])->name('rekap');
            Route::get('/izin', [
                HRDPermissionController::class,
                'index'
            ])->name('izin');

            Route::post('/izin/{id}/approve', [
                HRDPermissionController::class,
                'approve'
            ])->name('izin.approve');

            Route::post('/izin/{id}/reject', [
                HRDPermissionController::class,
                'reject'
            ])->name('izin.reject');

            /*
            |--------------------------------------------------------------------------
            | LEMBUR
            |--------------------------------------------------------------------------
            */

            Route::get('/lembur', [
                HRDOvertimeController::class,
                'index'
            ])->name('lembur');

            Route::post('/lembur/{id}/approve', [
                HRDOvertimeController::class,
                'approve'
            ])->name('lembur.approve');

            Route::post('/lembur/{id}/reject', [
                HRDOvertimeController::class,
                'reject'
            ])->name('lembur.reject');

            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/attendance/daily', [HRDController::class, 'reportAttendanceDaily'])->name('attendance.daily');
                Route::get('/absent/daily', [HRDController::class, 'reportAbsentDaily'])->name('absent.daily');
                Route::get('/leave', [HRDController::class, 'reportLeave'])->name('leave');
                Route::get('/permission', [HRDController::class, 'reportPermission'])->name('permission');
                Route::get('/overtime', [HRDController::class, 'reportOvertime'])->name('overtime');
                Route::get('/attendance/monthly', [HRDController::class, 'index'])->name('attendance.monthly');
                Route::get('/export', [HRDController::class, 'exportReport'])->name('export');
            });

            Route::get('/tracking', [HRDController::class, 'tracking'])->name('tracking');
        });

    Route::get(
        '/hrd/calendar/export',
        [HRDController::class, 'exportCalendar']
    )->name('hrd.calendar.export');

    Route::get(
        '/hrd/calendar/export-user/{user}',
        [HRDController::class, 'exportCalendarUser']
    )->name('hrd.calendar.export.user');
    Route::get('/hrd/calendar/export-all', [HRDController::class, 'exportCalendarAll']);
    /*
        |--------------------------------------------------------------------------
        | SHIFT
        |--------------------------------------------------------------------------
    */
    Route::get(
        '/hrd/employee-shifts/{user}',
        [HRDController::class, 'employeeShifts']
    )->name('hrd.employee.shifts');
    Route::middleware(['auth'])->group(function () {
        // Rute Shift Umum (Bisa diakses user)
        Route::get('/shift', [ShiftController::class, 'index'])->name('shift.index');
        Route::post('/shift/assign', [ShiftController::class, 'assign'])->name('shift.assign');
        Route::get('/shift/data', [ShiftController::class, 'data'])->name('shift.data');
        Route::get('/shifts/calendar-events', [ShiftController::class, 'calendarEvents'])->name('shift.calendar');

        // Rute Shift Management (Khusus HRD)
        Route::prefix('hrd')->name('hrd.')->group(function () {
            Route::get('/shifts', [ShiftManagementController::class, 'index'])->name('shifts.index');
            // Ubah POST menjadi PUT agar sinkron dengan @method('PUT') di Blade
            Route::put('/shifts/update/{id}', [ShiftManagementController::class, 'update'])->name('shifts.update');
            // Store tetap menggunakan POST
            Route::post('/shifts/store', [ShiftManagementController::class, 'store'])->name('shifts.store');
        });
        Route::get(
            '/hrd/calendar-employee/{user}',
            [HrdController::class, 'calendarEmployee']
        );
    });
});

require __DIR__ . '/auth.php';
