<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DeviceController;
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

Route::middleware(['auth:sanctum', 'permission'])->group(function () {

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
    'role:admin',
    'permission'
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
    ->middleware(['auth:sanctum', 'permission', 'throttle:10,1'])
    ->group(function () {

        Route::post('/face/register', [
            FaceController::class,
            'register'
        ]);

        Route::post('/face/scan', [
            FaceController::class,
            'matchFace'
        ]);

        // Perangkat pengguna (device binding).
        // Keputusan D1: hanya MENCATAT perangkat, tidak pernah menolak absen.
        // Dipanggil halaman Profil dan (fase berikutnya) alur absen.
        Route::post('/bind', [
            DeviceController::class,
            'bind'
        ]);

    });


/*
|--------------------------------------------------------------------------
| Telegram Bot Webhook API
|--------------------------------------------------------------------------
*/
Route::post('/telegram/webhook', [
    \App\Http\Controllers\TelegramWebhookController::class,
    'handle'
]);

Route::get('/announcement/active', function () {
    $announcement = \App\Models\SystemAnnouncement::current();
    if (!$announcement) {
        return response()->json(['active' => false]);
    }
    return response()->json([
        'active' => true,
        'id' => $announcement->id,
        'title' => $announcement->title ?? 'Pemberitahuan Sistem',
        'message' => $announcement->message,
        'level' => $announcement->level,
        'time' => $announcement->created_at->diffForHumans(),
    ]);
});
