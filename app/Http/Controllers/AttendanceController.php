<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\User;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        $now = Carbon::now();

        // VALIDASI JAM KERJA (contoh: 08:00 - 17:00)
        if ($now->format('H:i') < '08:00' || $now->format('H:i') > '17:00') {
            return response()->json([
                'message' => 'Di luar jam kerja'
            ], 403);
        }

        if (!$attendance) {
            Attendance::create([
                'user_id' => $user->id,
                'tanggal' => $today,
                'jam_masuk' => $now,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status' => $now->format('H:i') > '08:00' ? 'terlambat' : 'hadir'
            ]);

            return response()->json(['message' => 'Absen masuk']);
        }
        // CEK SUDAH ABSEN KELUAR
        if ($attendance->jam_keluar) {
            return response()->json([
                'message' => 'Sudah absen hari ini'
            ], 400);
        }

        // ABSEN KELUAR
        $attendance->update([
            'jam_keluar' => $now
        ]);

        return response()->json(['message' => 'Absen keluar']);
    }

    public function faceAbsen(Request $request)
    {
        $input = $request->descriptor;

        $users = User::whereNotNull('face_descriptor')->get();

        foreach ($users as $user) {
            $saved = json_decode($user->face_descriptor);

            $distance = $this->compare($input, $saved);

            if ($distance < 0.5) {
                // wajah cocok
                Attendance::create([
                    'user_id' => $user->id,
                    'tanggal' => now(),
                    'jam_masuk' => now(),
                    'status' => 'hadir'
                ]);

                return response()->json([
                    'message' => 'Absen berhasil: ' . $user->name
                ]);
            }
        }

        return response()->json([
            'message' => 'Wajah tidak dikenali'
        ], 404);
    }

    private function compare($a, $b)
    {
        $sum = 0;

        for ($i = 0; $i < count($a); $i++) {
            $sum += pow($a[$i] - $b[$i], 2);
        }

        return sqrt($sum);
    }

    public function fingerprint(Request $request)
    {
        $user = User::where('finger_id', $request->finger_id)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Fingerprint tidak dikenali'
            ]);
        }

        Attendance::create([
            'user_id' => $user->id,
            'tanggal' => now(),
            'jam_masuk' => now(),
            'status' => 'hadir'
        ]);

        return response()->json([
            'message' => 'Absensi berhasil: ' . $user->name
        ]);
    }
}
