<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

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
        | HITUNG JARAK USER KE KANTOR
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
        | VALIDASI GPS ACCURACY
        |--------------------------------------------------------------------------
        */
        if (

            $request->accuracy &&

            $request->accuracy > 100

        ) {

            return response()->json([

                'success' => false,

                'message' =>
                'GPS tidak akurat, aktifkan GPS',

                'accuracy' =>
                round($request->accuracy) . ' meter'

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

                'message' =>
                'Anda berada di luar radius kantor',

                'distance' =>
                round($distance, 2) . ' meter'

            ], 403);
        }



        /*
        |--------------------------------------------------------------------------
        | WAKTU SEKARANG
        |--------------------------------------------------------------------------
        */
        $now = Carbon::now();



        /*
        |--------------------------------------------------------------------------
        | VALIDASI JAM KERJA
        |--------------------------------------------------------------------------
        */
        if (

            $now->format('H:i') < '08:00' ||

            $now->format('H:i') > '23:59'

        ) {

            return response()->json([

                'success' => false,

                'message' =>
                'Absensi hanya bisa pada jam kerja'

            ], 403);
        }



        /*
        |--------------------------------------------------------------------------
        | SIMPAN FOTO
        |--------------------------------------------------------------------------
        */
        $image = $request->image;

        $image = str_replace(
            'data:image/jpeg;base64,',
            '',
            $image
        );

        $image = str_replace(
            ' ',
            '+',
            $image
        );



        $fileName =

            'faces/' .
            $user->id .
            '_' .
            time() .
            '.jpg';



        Storage::disk('public')->put(

            $fileName,

            base64_decode($image)
        );



        /*
        |--------------------------------------------------------------------------
        | SIMPAN FACE PERTAMA
        |--------------------------------------------------------------------------
        */
        if (!$user->face_descriptor) {

            $user->update([

                'face_descriptor' => $fileName
            ]);
        }



        /*
        |--------------------------------------------------------------------------
        | CEK ABSENSI HARI INI
        |--------------------------------------------------------------------------
        */
        $today = Carbon::today();



        $attendance = Attendance::where(

            'user_id',
            $user->id

        )

            ->whereDate(
                'tanggal',
                $today
            )

            ->first();



        /*
        |--------------------------------------------------------------------------
        | CHECK IN
        |--------------------------------------------------------------------------
        */
        if (!$attendance) {

            Attendance::create([

                'user_id'   => $user->id,

                'tanggal'   => $today,

                'jam_masuk' => $now,

                'latitude'  => $request->latitude,

                'longitude' => $request->longitude,

                'status'    =>

                $now->format('H:i') > '08:00'
                    ? 'terlambat'
                    : 'hadir'
            ]);


            return response()->json([

                'success' => true,

                'type'    => 'checkin',

                'message' => 'Check In berhasil',

                'distance' =>
                round($distance, 2) . ' meter'
            ]);
        }



        /*
        |--------------------------------------------------------------------------
        | SUDAH CHECK OUT
        |--------------------------------------------------------------------------
        */
        if ($attendance->jam_keluar) {

            return response()->json([

                'success' => false,

                'message' =>
                'Anda sudah check out hari ini'

            ]);
        }



        /*
        |--------------------------------------------------------------------------
        | CHECKOUT HANYA JAM PULANG
        |--------------------------------------------------------------------------
        */
        if ($now->format('H:i') < '17:00') {

            return response()->json([

                'success' => false,

                'message' =>
                'Checkout hanya bisa setelah jam 17:00'

            ], 403);
        }



        /*
        |--------------------------------------------------------------------------
        | CHECK OUT
        |--------------------------------------------------------------------------
        */
        $attendance->update([

            'jam_keluar' => $now
        ]);



        return response()->json([

            'success' => true,

            'type'    => 'checkout',

            'message' => 'Check Out berhasil',

            'distance' =>
            round($distance, 2) . ' meter'
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | HITUNG JARAK (HAVERSINE)
    |--------------------------------------------------------------------------
    */
    private function calculateDistance(
        $lat1,
        $lon1,
        $lat2,
        $lon2
    ) {

        $earthRadius = 6371000;



        $dLat =
            deg2rad($lat2 - $lat1);

        $dLon =
            deg2rad($lon2 - $lon1);



        $a =

            sin($dLat / 2) *
            sin($dLat / 2) +

            cos(deg2rad($lat1)) *
            cos(deg2rad($lat2)) *

            sin($dLon / 2) *
            sin($dLon / 2);



        $c =
            2 *
            atan2(
                sqrt($a),
                sqrt(1 - $a)
            );



        return
            $earthRadius * $c;
    }
}
