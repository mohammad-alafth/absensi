<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\FaceController;
use App\Http\Controllers\LeaveController;


/*
|--------------------------------------------------------------------------
| Auth API
|--------------------------------------------------------------------------
*/

Route::post('/login', [
    AuthController::class,
    'login'
]);


/*
|--------------------------------------------------------------------------
| User API
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [
        AuthController::class,
        'logout'
    ]);

    Route::post('/attendance', [
        AttendanceController::class,
        'store'
    ]);

    Route::post('/leave', [
        LeaveController::class,
        'store'
    ]);
});


/*
|--------------------------------------------------------------------------
| Admin API
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'role:admin'
])->group(function () {

    Route::get('/admin', function () {
        return response()->json([
            'message' => 'Admin Access'
        ]);
    });

    Route::get('/leave', [
        LeaveController::class,
        'index'
    ]);

    Route::put('/leave/{id}/approve', [
        LeaveController::class,
        'approve'
    ]);
});


/*
|--------------------------------------------------------------------------
| Biometric Device API
|--------------------------------------------------------------------------
*/

Route::prefix('device')
    ->middleware(['web', 'auth', 'throttle:10,1'])
    ->group(function () {

        Route::post('/face/register', [
            FaceController::class,
            'register'
        ]);

        Route::post('/face/scan', [
            FaceController::class,
            'matchFace'
        ]);

        // Catatan: endpoint sidik jari BELUM diimplementasikan (perangkat
        // fingerprint tidak dipakai). Akses ditolak dengan jelas, bukan error 500.
        Route::any('/fingerprint/{action?}', function () {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint fingerprint belum diimplementasikan pada sistem ini.'
            ], 501);
        });
    });
