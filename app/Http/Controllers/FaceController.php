<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Services\ScheduleService;

class FaceController extends Controller
{
    public function matchFace(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | AUTH
        |--------------------------------------------------------------------------
        */
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI INPUT
        |--------------------------------------------------------------------------
        */
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy'  => 'nullable|numeric',
            'image'     => 'required|string'
        ]);

        /*
        |--------------------------------------------------------------------------
        | KOORDINAT KANTOR
        |--------------------------------------------------------------------------
        */
        $officeLat = 0.4761258;
        $officeLng = 101.4190600;

        /*
        |--------------------------------------------------------------------------
        | HITUNG JARAK
        |--------------------------------------------------------------------------
        */
        $distance = $this->calculateDistance(
            $officeLat,
            $officeLng,
            $request->latitude,
            $request->longitude
        );

        /*
        |--------------------------------------------------------------------------
        | VALIDASI GPS
        |--------------------------------------------------------------------------
        */
        if ($request->accuracy && $request->accuracy > 100) {
            return response()->json([
                'success' => false,
                'message' => 'GPS tidak akurat, aktifkan GPS',
                'accuracy' => round($request->accuracy) . ' meter'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI RADIUS
        |--------------------------------------------------------------------------
        */
        if ($distance > 200) {
            return response()->json([
                'success' => false,
                'message' => 'Anda berada di luar radius kantor',
                'distance' => round($distance, 2) . ' meter'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | TIME
        |--------------------------------------------------------------------------
        */
        $now = Carbon::now();
        $today = Carbon::today();

        /*
        |--------------------------------------------------------------------------
        | AMBIL SCHEDULE
        |--------------------------------------------------------------------------
        */
        $schedule = ScheduleService::getTodaySchedule($user);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki jadwal hari ini'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | SHIFT TIME
        |--------------------------------------------------------------------------
        */
        $shiftStart = Carbon::parse($schedule['start_time']);
        $shiftEnd   = Carbon::parse($schedule['end_time']);

        if (!empty($schedule['is_overnight']) && $schedule['is_overnight']) {
            $shiftEnd->addDay();
        }

        /*
        |--------------------------------------------------------------------------
        | GRACE PERIOD (15 MENIT)
        |--------------------------------------------------------------------------
        */
        $graceMinutes = 15;
        $lateLimit = $shiftStart->copy()->addMinutes($graceMinutes);

        /*
        |--------------------------------------------------------------------------
        | CHECKIN WINDOW
        |--------------------------------------------------------------------------
        */
        $checkinStart = $shiftStart->copy()->subHours(2);
        $checkinEnd   = $shiftStart->copy()->addHours(2);

        /*
        |--------------------------------------------------------------------------
        | SIMPAN FOTO
        |--------------------------------------------------------------------------
        */
        $image = str_replace('data:image/jpeg;base64,', '', $request->image);
        $image = str_replace(' ', '+', $image);

        $fileName = 'faces/' . $user->id . '_' . time() . '.jpg';

        Storage::disk('public')->put($fileName, base64_decode($image));

        if (!$user->face_descriptor) {
            $user->update([
                'face_descriptor' => $fileName
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CEK ABSENSI
        |--------------------------------------------------------------------------
        */
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | CHECK IN
        |--------------------------------------------------------------------------
        */
        if (!$attendance) {

            /*
            |--------------------------------------------------------------
            | VALIDASI JAM MASUK
            |--------------------------------------------------------------
            */

            if ($now->lt($checkinStart)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Belum masuk jam absensi'
                ], 403);
            }

            /*
            |--------------------------------------------------------------
            | TERLAMBAT > GRACE PERIOD
            |--------------------------------------------------------------
            */
            if ($now->gt($lateLimit)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda terlambat lebih dari 15 menit, tidak bisa melakukan absen'
                ], 403);
            }

            /*
            |--------------------------------------------------------------
            | CREATE ATTENDANCE
            |--------------------------------------------------------------
            */
            Attendance::create([
                'user_id'   => $user->id,
                'tanggal'   => $today,
                'jam_masuk' => $now,
                'latitude'  => $request->latitude,
                'longitude' => $request->longitude,
                'status'    => $now->gt($shiftStart) ? 'terlambat' : 'hadir'
            ]);

            return response()->json([
                'success' => true,
                'type'    => 'checkin',
                'message' => 'Check In berhasil',
                'distance' => round($distance, 2) . ' meter'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | SUDAH CHECKOUT
        |--------------------------------------------------------------------------
        */
        if ($attendance->jam_keluar) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah check out hari ini'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI CHECKOUT
        |--------------------------------------------------------------------------
        */
        $checkoutTime = $shiftEnd->copy()->subMinutes(30);

        if ($now->lt($checkoutTime)) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout hanya bisa mendekati jam pulang'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | CHECKOUT
        |--------------------------------------------------------------------------
        */
        $attendance->update([
            'jam_keluar' => $now
        ]);

        return response()->json([
            'success' => true,
            'type'    => 'checkout',
            'message' => 'Check Out berhasil',
            'distance' => round($distance, 2) . ' meter'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | HITUNG JARAK
    |--------------------------------------------------------------------------
    */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a =
            sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) *
            cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
