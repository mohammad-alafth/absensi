<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;

class FaceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REGISTER FACE (CREATE USER + MULTI DESCRIPTOR)
    |--------------------------------------------------------------------------
    */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'descriptors' => 'required|array|min:3'
        ]);

        // validasi descriptor (harus 128 length)
        foreach ($request->descriptors as $desc) {
            if (!is_array($desc) || count($desc) !== 128) {
                return response()->json([
                    'message' => 'Descriptor tidak valid'
                ], 422);
            }
        }

        // 🔥 buat user baru
        $user = User::create([
            'name' => $request->name,
            'email' => uniqid('face_') . '@face.local',
            'password' => bcrypt(str()->random(10)),
            'role' => 'user'
        ]);

        // 🔥 simpan MULTI descriptor
        $user->face_descriptor = json_encode($request->descriptors);
        $user->save();

        return response()->json([
            'message' => 'Face registered (multi-sample)',
            'user_id' => $user->id
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | REGISTER FINGERPRINT (CREATE USER)
    |--------------------------------------------------------------------------
    */
    public function registerFingerprint(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'finger_id' => 'required|string|unique:users,finger_id'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => uniqid('finger_') . '@finger.local',
            'password' => bcrypt(str()->random(10)),
            'role' => 'user',
            'finger_id' => $request->finger_id
        ]);

        return response()->json([
            'message' => 'Fingerprint registered',
            'user_id' => $user->id
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | FACE MATCH + ABSENSI (MASUK / KELUAR)
    |--------------------------------------------------------------------------
    */
    public function matchFace(Request $request)
    {
        // 🔐 WAJIB LOGIN
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Session habis, silakan login ulang'
            ], 401);
        }

        // 📅 VALIDASI HARI KERJA
        if ($scheduleError = $this->validateWorkingSchedule()) {
            return $scheduleError;
        }

        // 📥 VALIDASI INPUT
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'required|numeric'
        ]);

        // 📍 TOLERANSI GPS (INDOOR FRIENDLY)
        if ($request->accuracy > 150) {
            return response()->json([
                'message' => 'GPS kurang akurat (' . round($request->accuracy) . 'm)'
            ], 403);
        }

        // 🏢 KOORDINAT KANTOR (PEKANBARU)
        $officeLat = 0.4761258;
        $officeLng = 101.41906;
        $radius = 100;

        $jarak = $this->distance(
            $request->latitude,
            $request->longitude,
            $officeLat,
            $officeLng
        );

        // DEBUG LOG
        \Log::info('ABSENSI GPS', [
            'user' => $user->id,
            'lat' => $request->latitude,
            'lng' => $request->longitude,
            'accuracy' => $request->accuracy,
            'jarak' => $jarak
        ]);

        // 🚫 DI LUAR AREA
        if ($jarak > $radius) {
            return response()->json([
                'message' => 'Di luar area (' . round($jarak) . ' meter)'
            ], 403);
        }

        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        // =====================
        // ✅ ABSEN MASUK
        // =====================
        if (!$attendance) {

            Attendance::create([
                'user_id' => $user->id,
                'tanggal' => now(),
                'jam_masuk' => now(),
                'status' => now()->format('H:i') > '08:00' ? 'terlambat' : 'hadir'
            ]);

            return response()->json([
                'message' => 'Absen MASUK: ' . $user->name
            ]);
        }

        // =====================
        // ✅ ABSEN KELUAR
        // =====================
        if (!$attendance->jam_keluar) {

            if (now()->format('H:i') < '17:00') {
                return response()->json([
                    'message' => 'Belum waktunya absen pulang'
                ], 403);
            }

            $attendance->update([
                'jam_keluar' => now()
            ]);

            return response()->json([
                'message' => 'Absen KELUAR: ' . $user->name
            ]);
        }

        return response()->json([
            'message' => 'Sudah absen lengkap'
        ], 400);
    }

    private function validateWorkingSchedule()
    {
        $now = now();

        // 1 = Monday, 7 = Sunday
        $day = $now->dayOfWeekIso;

        // hanya Senin-Jumat
        if ($day > 5) {
            return response()->json([
                'message' => 'Hari ini bukan hari kerja'
            ], 403);
        }

        return null;
    }
    /*
    |--------------------------------------------------------------------------
    | FINGERPRINT ABSENSI (MASUK / KELUAR)
    |--------------------------------------------------------------------------
    */
    public function fingerprint(Request $request)
    {
        if ($request->header('X-API-KEY') !== env('DEVICE_API_KEY')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $request->validate([
            'finger_id' => 'required'
        ]);

        $user = User::where('finger_id', $request->finger_id)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Fingerprint tidak dikenali'
            ], 404);
        }

        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        // MASUK
        if (!$attendance) {

            Attendance::create([
                'user_id' => $user->id,
                'tanggal' => now(),
                'jam_masuk' => now(),
                'status' => 'hadir'
            ]);

            return response()->json([
                'message' => 'Fingerprint MASUK: ' . $user->name
            ]);
        }

        // KELUAR
        if (!$attendance->jam_keluar) {

            // checkout hanya setelah jam 17:00
            if (now()->format('H:i') < '17:00') {
                return response()->json([
                    'message' => 'Belum waktunya absen pulang'
                ], 403);
            }

            $attendance->update([
                'jam_keluar' => now()
            ]);

            return response()->json([
                'message' => 'Absen KELUAR: ' . $user->name
            ]);
        }

        return response()->json([
            'message' => 'Sudah absen lengkap'
        ], 400);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER: FACE DISTANCE
    |--------------------------------------------------------------------------
    */
    private function compare($a, $b)
    {
        $sum = 0;

        for ($i = 0; $i < count($a); $i++) {
            $sum += pow($a[$i] - $b[$i], 2);
        }

        return sqrt($sum);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER: GEO DISTANCE (METER)
    |--------------------------------------------------------------------------
    */
    private function distance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
