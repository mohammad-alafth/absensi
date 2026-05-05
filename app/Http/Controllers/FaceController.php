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

        if ($scheduleError = $this->validateWorkingSchedule()) {
            return $scheduleError;
        }
        $request->validate([
            'descriptor' => 'required|array',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'required|numeric'
        ]);
        if ($request->accuracy > 80) {
            return response()->json([
                'message' => 'GPS kurang akurat. Pindah ke area terbuka.'
            ], 403);
        }
        $input = $request->descriptor;
        $users = User::whereNotNull('face_descriptor')->get();

        foreach ($users as $user) {

            $savedDescriptors = json_decode($user->face_descriptor, true);

            if (!$savedDescriptors || !is_array($savedDescriptors)) {
                continue;
            }

            // detect old format (single descriptor)
            if (isset($savedDescriptors[0]) && is_float($savedDescriptors[0])) {
                $savedDescriptors = [$savedDescriptors];
            }

            $bestDistance = 999;

            foreach ($savedDescriptors as $saved) {

                if (!is_array($saved)) {
                    continue;
                }

                $distance = $this->compare($input, $saved);

                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                }
            }

            // 🔥 threshold ketat
            if ($bestDistance < 0.45) {

                // 🔥 VALIDASI LOKASI
                $officeLat = 1.045678;
                $officeLng = 104.030123;

                $jarak = $this->distance(
                    $request->latitude,
                    $request->longitude,
                    $officeLat,
                    $officeLng
                );

                if ($jarak > 100) {
                    return response()->json([
                        'message' => 'Di luar area (' . round($jarak) . ' m)'
                    ], 403);
                }

                $today = now()->toDateString();

                $attendance = Attendance::where('user_id', $user->id)
                    ->whereDate('tanggal', $today)
                    ->first();

                // 🔥 MASUK
                if (!$attendance) {

                    Attendance::create([
                        'user_id' => $user->id,
                        'tanggal' => now(),
                        'jam_masuk' => now(),
                        'status' => now()->format('H:i') > '08:00' ? 'terlambat' : 'hadir'
                    ]);

                    return response()->json([
                        'message' => 'Absen MASUK: ' . $user->name,
                        'confidence' => round(1 - $bestDistance, 3)
                    ]);
                }

                // 🔥 KELUAR
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
        }

        return response()->json([
            'message' => 'Wajah tidak dikenali'
        ], 404);
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
        $theta = $lon1 - $lon2;

        $dist =
            sin(deg2rad($lat1)) *
            sin(deg2rad($lat2)) +

            cos(deg2rad($lat1)) *
            cos(deg2rad($lat2)) *
            cos(deg2rad($theta));

        $dist = acos($dist);
        $dist = rad2deg($dist);

        return $dist * 60 * 1.1515 * 1609.344;
    }
}
