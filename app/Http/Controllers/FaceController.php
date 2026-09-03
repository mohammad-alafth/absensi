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
        if ($request->accuracy && $request->accuracy > 200) {
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

        /*
        |--------------------------------------------------------------------------
        | AMBIL SCHEDULE (sudah handle cross-day shift)
        |--------------------------------------------------------------------------
        */
        $schedule = ScheduleService::getTodaySchedule($user);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki jadwal hari ini'
            ], 403);
        }

        if (!empty($schedule['invalid_window'])) {
            return response()->json([
                'success' => false,
                'message' => 'Diluar jam absensi'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | SHIFT TIME (gunakan dari schedule, bukan Carbon::today())
        |--------------------------------------------------------------------------
        | ScheduleService sudah menghitung shift_start dan shift_end dengan
        | benar untuk cross-day shift (overnight).
        |--------------------------------------------------------------------------
        */
        $shiftStart = $schedule['shift_start'];
        $shiftEnd   = $schedule['shift_end'];
        $shiftDate  = $schedule['shift_date'];

        /*
        |--------------------------------------------------------------------------
        | GRACE PERIOD (15 MENIT)
        |--------------------------------------------------------------------------
        */
        $graceMinutes = $schedule['grace_minutes'] ?? 15;
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
        | CEK ABSENSI - gunakan shift_date dari schedule
        |--------------------------------------------------------------------------
        | Untuk cross-day shift, shift_date adalah tanggal mulai shift (kemarin).
        | Jadi attendance yang dibuat kemarin malam masih bisa ditemukan pagi ini.
        |--------------------------------------------------------------------------
        */
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('tanggal', $shiftDate)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | CHECK IN
        |--------------------------------------------------------------------------
        */
        if (!$attendance) {
            if ($now->lt($checkinStart)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Belum masuk jam absensi'
                ], 403);
            }

            $status = 'hadir';
            $lateMinutes = 0;

            if ($now->gt($lateLimit)) {
                $status = 'terlambat';
                $lateMinutes = (int) round($lateLimit->diffInMinutes($now));
            }

            Attendance::create([
                'user_id'      => $user->id,
                'tanggal'      => $shiftDate,
                'jam_masuk'    => $now,
                'latitude'     => $request->latitude,
                'longitude'    => $request->longitude,
                'status'       => $status,
                'late_minutes' => $lateMinutes,
                'scheduled_checkin' => $shiftStart,
                'scheduled_checkout' => $shiftEnd,
            ]);

            return response()->json([
                'success' => true,
                'type' => 'checkin',
                'message' => $status === 'terlambat'
                    ? 'Check In berhasil (Terlambat ' . $lateMinutes . ' menit)'
                    : 'Check In berhasil',
                'status' => $status,
                'late_minutes' => (int) round($lateMinutes),
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
        | HITUNG OVERTIME
        |--------------------------------------------------------------------------
        */
        $overtimeMinutes = 0;

        if ($now->gt($shiftEnd)) {
            $overtimeMinutes = $shiftEnd->diffInMinutes($now);
        }

        /*
        |--------------------------------------------------------------------------
        | CHECKOUT
        |--------------------------------------------------------------------------
        */
        $attendance->update([
            'jam_keluar'       => $now,
            'overtime_minutes' => $overtimeMinutes
        ]);

        return response()->json([
            'success' => true,
            'type'    => 'checkout',
            'message' => $overtimeMinutes > 0
                ? 'Check Out berhasil (Lembur ' . $overtimeMinutes . ' menit)'
                : 'Check Out berhasil',
            'overtime_minutes' => $overtimeMinutes,
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
        $earthRadius = 637200;

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

    public function showFacePage()
    {
        // Cek apakah hari ini libur
        if ($this->isHoliday(Carbon::today())) {
            return redirect()->back()->with('error', 'Tidak bisa absen di hari libur!');
        }

        return view('face.scan');
    }
}