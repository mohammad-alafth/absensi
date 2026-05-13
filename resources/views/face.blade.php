<x-app-layout>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9ff;
        }

        .scan-ring {
            position: relative;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            border: 4px solid rgba(30, 64, 175, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .scan-ring::after {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            border: 2px solid #1E40AF;
            opacity: .3;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        .scan-line {
            position: absolute;
            width: 100%;
            height: 2px;

            background: linear-gradient(90deg,
                    transparent,
                    #1E40AF,
                    transparent);

            animation: scan 3s infinite;
        }

        @keyframes scan {

            0% {
                top: 20%;
            }

            50% {
                top: 80%;
            }

            100% {
                top: 20%;
            }
        }
    </style>


    <main class="flex-1 px-6 py-8 flex flex-col items-center min-h-screen bg-[#f8f9ff]">

        <!-- BACK -->
        <div class="w-full max-w-md mb-6">

            <a href="{{ route('dashboard') }}"
                class="text-[#1E40AF] font-semibold text-sm">

                ← Kembali

            </a>

        </div>


        <!-- TITLE -->
        <h2 class="text-2xl font-bold mb-2">

            Halaman Absensi

        </h2>

        <p class="text-sm text-gray-500 mb-6">

            Posisikan wajah Anda dalam bingkai

        </p>


        <!-- CAMERA -->
        <div class="scan-ring mb-8">

            <div class="scan-line"></div>

            <video
                id="video"
                autoplay
                playsinline
                class="w-64 h-64 rounded-full object-cover border-4 border-white shadow-xl">
            </video>

        </div>


        <!-- GPS INFO -->
        <div class="grid grid-cols-2 gap-4 w-full max-w-xs mb-6">

            <div
                id="gpsStatus"
                class="flex justify-center items-center gap-2 py-3 bg-blue-50 rounded-2xl text-xs">

                📍 GPS...

            </div>

            <div
                id="distanceStatus"
                class="flex justify-center items-center gap-2 py-3 bg-blue-50 rounded-2xl text-xs">

                📏 Menghitung...

            </div>

        </div>


        <!-- LOCATION -->
        <div
            class="w-full max-w-md bg-white p-4 rounded-3xl mb-6 text-sm shadow">

            <b>Lokasi Anda</b>

            <div id="locationText">

                Mengambil lokasi...

            </div>

        </div>


        <!-- BUTTON -->
        <button
            id="absenBtn"
            onclick="absen()"
            class="w-full max-w-xs bg-[#1E40AF] hover:bg-blue-800 transition text-white py-4 rounded-3xl font-bold shadow">

            Absen Sekarang

        </button>

    </main>



    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        /*
        |--------------------------------------------------------------------------
        | KOORDINAT KANTOR
        |--------------------------------------------------------------------------
        */

        const officeLat = 0.4761258;
        const officeLng = 101.4190600;


        /*
        |--------------------------------------------------------------------------
        | GLOBAL VARIABLE
        |--------------------------------------------------------------------------
        */

        let currentLat = null;
        let currentLng = null;
        let currentAccuracy = null;
        let currentDistance = null;



        /*
        |--------------------------------------------------------------------------
        | START CAMERA
        |--------------------------------------------------------------------------
        */

        async function startCamera() {

            try {

                const stream =
                    await navigator.mediaDevices.getUserMedia({

                        video: {
                            facingMode: 'user'
                        }

                    });

                document
                    .getElementById('video')
                    .srcObject = stream;

            } catch (error) {

                Swal.fire({

                    icon: 'error',

                    title: 'Camera tidak diizinkan'
                });

                console.log(error);
            }
        }

        startCamera();



        /*
        |--------------------------------------------------------------------------
        | HITUNG JARAK
        |--------------------------------------------------------------------------
        */

        function calculateDistance(
            lat1,
            lon1,
            lat2,
            lon2
        ) {

            const earthRadius = 6371000;

            const dLat =
                (lat2 - lat1) * Math.PI / 180;

            const dLon =
                (lon2 - lon1) * Math.PI / 180;

            const a =

                Math.sin(dLat / 2) *
                Math.sin(dLat / 2) +

                Math.cos(lat1 * Math.PI / 180) *
                Math.cos(lat2 * Math.PI / 180) *

                Math.sin(dLon / 2) *
                Math.sin(dLon / 2);

            const c =

                2 *
                Math.atan2(
                    Math.sqrt(a),
                    Math.sqrt(1 - a)
                );

            return earthRadius * c;
        }



        /*
        |--------------------------------------------------------------------------
        | GPS REALTIME
        |--------------------------------------------------------------------------
        */

        navigator.geolocation.watchPosition(

            (pos) => {

                currentLat =
                    pos.coords.latitude;

                currentLng =
                    pos.coords.longitude;

                currentAccuracy =
                    pos.coords.accuracy;


                /*
                |--------------------------------------------------------------------------
                | HITUNG JARAK
                |--------------------------------------------------------------------------
                */

                currentDistance =
                    calculateDistance(

                        officeLat,
                        officeLng,

                        currentLat,
                        currentLng
                    );


                /*
                |--------------------------------------------------------------------------
                | GPS STATUS
                |--------------------------------------------------------------------------
                */

                document
                    .getElementById('gpsStatus')
                    .innerHTML =

                    `📍 ${Math.round(currentAccuracy)}m`;



                /*
                |--------------------------------------------------------------------------
                | DISTANCE STATUS
                |--------------------------------------------------------------------------
                */

                document
                    .getElementById('distanceStatus')
                    .innerHTML =

                    `📏 ${Math.round(currentDistance)}m`;



                /*
                |--------------------------------------------------------------------------
                | LOCATION INFO
                |--------------------------------------------------------------------------
                */

                document
                    .getElementById('locationText')
                    .innerHTML =

                    `
                    Accuracy: ${Math.round(currentAccuracy)} meter<br>
                    Jarak ke kantor: ${Math.round(currentDistance)} meter
                    `;

            },

            (err) => {

                Swal.fire({

                    icon: 'error',

                    title: 'GPS tidak aktif'
                });

                console.log(err);

            },

            {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: 10000
            }
        );



        /*
        |--------------------------------------------------------------------------
        | CAPTURE IMAGE
        |--------------------------------------------------------------------------
        */

        function captureImage() {

            const video =
                document.getElementById('video');

            const canvas =
                document.createElement('canvas');

            canvas.width =
                video.videoWidth;

            canvas.height =
                video.videoHeight;

            const ctx =
                canvas.getContext('2d');

            ctx.drawImage(
                video,
                0,
                0
            );

            return canvas.toDataURL(
                'image/jpeg'
            );
        }



        /*
        |--------------------------------------------------------------------------
        | ABSEN
        |--------------------------------------------------------------------------
        */

        async function absen() {

            /*
            |--------------------------------------------------------------------------
            | VALIDASI LOKASI
            |--------------------------------------------------------------------------
            */

            if (!currentLat || !currentLng) {

                Swal.fire({

                    icon: 'error',

                    title: 'Lokasi belum ditemukan'
                });

                return;
            }



            /*
            |--------------------------------------------------------------------------
            | VALIDASI GPS
            |--------------------------------------------------------------------------
            */

            if (currentAccuracy > 100) {

                Swal.fire({

                    icon: 'warning',

                    title: 'GPS kurang akurat',

                    text: 'Pindah ke area terbuka atau aktifkan GPS'
                });

                return;
            }



            /*
            |--------------------------------------------------------------------------
            | VALIDASI RADIUS
            |--------------------------------------------------------------------------
            */

            if (currentDistance > 200) {

                Swal.fire({

                    icon: 'error',

                    title: 'Di luar radius kantor',

                    text: `${Math.round(currentDistance)} meter`
                });

                return;
            }



            /*
            |--------------------------------------------------------------------------
            | LOADING BUTTON
            |--------------------------------------------------------------------------
            */

            const btn =
                document.getElementById('absenBtn');

            btn.disabled = true;

            btn.innerHTML =
                'Memproses...';



            /*
            |--------------------------------------------------------------------------
            | CAPTURE FOTO
            |--------------------------------------------------------------------------
            */

            const image =
                captureImage();



            /*
            |--------------------------------------------------------------------------
            | REQUEST
            |--------------------------------------------------------------------------
            */

            try {

                const response =
                    await fetch('/api/device/face/scan', {

                        method: 'POST',

                        headers: {

                            'Content-Type': 'application/json',

                            'X-CSRF-TOKEN': '{{ csrf_token() }}',

                            'Accept': 'application/json'
                        },

                        credentials: 'include',

                        body: JSON.stringify({

                            latitude: currentLat,

                            longitude: currentLng,

                            accuracy: currentAccuracy,

                            image: image
                        })
                    });


                const data =
                    await response.json();


                Swal.fire({

                    icon: data.success ?
                        'success' : 'error',

                    title: data.message,

                    text: data.distance ?? ''

                }).then(() => {

                    if (
                        data.success &&
                        data.type === 'checkin'
                    ) {

                        window.location.href =
                            '/dashboard';
                    }

                });

            } catch (error) {

                Swal.fire({

                    icon: 'error',

                    title: 'Terjadi kesalahan server'
                });

                console.log(error);

            } finally {

                btn.disabled = false;

                btn.innerHTML =
                    'Absen Sekarang';
            }
        }
    </script>

</x-app-layout>