<x-app-layout>

    <!-- STYLE tetap -->
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
            opacity: 0.3;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.3;
            }

            50% {
                transform: scale(1.05);
                opacity: 0.1;
            }

            100% {
                transform: scale(1);
                opacity: 0.3;
            }
        }

        .scan-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #1E40AF, transparent);
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

    <!-- CONTENT (tidak berubah) -->
    <main class="flex-1 px-6 py-8 flex flex-col items-center min-h-screen text-slate-800 bg-[#f8f9ff]">

        <h2 class="text-2xl font-bold mb-2">Halaman Absensi</h2>
        <p class="text-sm text-gray-500 mb-6">Posisikan wajah Anda dalam bingkai</p>

        <!-- CAMERA -->
        <div class="scan-ring mb-8">
            <div class="scan-line"></div>

            <video id="video" autoplay playsinline
                class="w-64 h-64 rounded-full object-cover border-4 border-white shadow-xl">
            </video>
        </div>

        <!-- STATUS -->
        <div class="grid grid-cols-2 gap-4 w-full max-w-xs mb-6">
            <div id="gpsStatus" class="flex justify-center gap-2 py-3 bg-blue-50 rounded-2xl text-xs">
                📍 GPS...
            </div>
            <div class="flex justify-center gap-2 py-3 bg-blue-50 rounded-2xl text-xs">
                🌐 Stabil
            </div>
        </div>

        <!-- LOCATION -->
        <div class="w-full bg-white p-4 rounded-3xl mb-6 text-sm shadow">
            <b>Lokasi Anda</b>
            <div id="locationText">Mengambil lokasi...</div>
        </div>

        <!-- BUTTON -->
        <button onclick="absen()"
            class="w-full max-w-xs bg-blue-900 text-white py-4 rounded-3xl font-bold shadow-lg">
            Absen Sekarang
        </button>

    </main>

    <!-- SCRIPT tetap -->
    <script>
        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: "user"
                    }
                });
                document.getElementById('video').srcObject = stream;
            } catch (err) {
                alert("Camera tidak bisa diakses");
            }
        }
        startCamera();

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                document.getElementById('gpsStatus').innerHTML =
                    `📍 GPS ${Math.round(pos.coords.accuracy)}m`;

                document.getElementById('locationText').innerHTML =
                    `Lat: ${pos.coords.latitude}<br>Lng: ${pos.coords.longitude}`;
            },
            () => {
                document.getElementById('gpsStatus').innerHTML = "❌ GPS gagal";
            }, {
                enableHighAccuracy: true
            }
        );

        function absen() {
            const token = localStorage.getItem('token');

            if (!token) {
                alert("Silakan login dulu");
                return;
            }

            navigator.geolocation.getCurrentPosition((pos) => {

                fetch('/api/device/face/scan', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer ' + token
                        },
                        body: JSON.stringify({
                            latitude: pos.coords.latitude,
                            longitude: pos.coords.longitude,
                            accuracy: pos.coords.accuracy
                        })
                    })
                    .then(res => res.json())
                    .then(data => alert(data.message))
                    .catch(() => alert("Error koneksi"));

            });
        }
    </script>

</x-app-layout>