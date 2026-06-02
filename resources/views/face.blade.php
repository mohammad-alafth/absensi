<x-app-layout>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f9;
            /* Selaras dengan halaman rekap & dashboard */
        }

        .scan-ring {
            position: relative;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            border: 4px solid rgba(30, 64, 175, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .scan-ring::after {
            content: '';
            position: absolute;
            inset: -6px;
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
                transform: scale(1.04);
            }

            100% {
                transform: scale(1);
            }
        }

        .scan-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #1E40AF, transparent);
            animation: scan 2.5s infinite ease-in-out;
            z-index: 10;
        }

        @keyframes scan {
            0% {
                top: 15%;
            }

            50% {
                top: 85%;
            }

            100% {
                top: 15%;
            }
        }
    </style>

    <div class="min-h-screen bg-[#f0f2f9] px-3 sm:px-5 py-6 pb-24 flex items-center justify-center">

        <div class="w-full max-w-md">

            <div class="bg-white rounded-3xl shadow-xl shadow-slate-100/70 border border-white p-5 sm:p-6 flex flex-col items-center">

                <div class="w-full flex items-center justify-between pb-4 mb-5 border-b border-gray-100">
                    <a href="{{ route('dashboard') }}" class="text-[#1E40AF] text-xs font-bold hover:underline transition">
                        ← Kembali
                    </a>
                    <span class="text-[10px] bg-blue-50 text-blue-700 border border-blue-100 px-2 py-0.5 rounded-md font-bold">
                        Face Scan & GPS
                    </span>
                </div>

                <div class="text-center mb-5">
                    <h2 class="text-lg font-black text-gray-800 tracking-wide">Halaman Absensi Digital</h2>
                    <p class="text-[11px] text-gray-400 mt-0.5">Posisikan wajah Anda tepat di dalam bingkai lingkaran</p>
                </div>

                <div class="scan-ring mb-5 bg-slate-50 shadow-inner">
                    <div class="scan-line"></div>
                    <video id="video" autoplay playsinline class="w-52 h-52 rounded-full object-cover border-4 border-white shadow-md z-0"></video>
                </div>

                <div class="grid grid-cols-2 gap-2 w-full max-w-xs mb-3">
                    <div id="gpsStatus" class="flex justify-center items-center gap-1.5 py-2.5 bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200/60 rounded-xl text-[11px] font-bold text-gray-600 shadow-3xs">
                        📍 Mencari GPS...
                    </div>
                    <div id="distanceStatus" class="flex justify-center items-center gap-1.5 py-2.5 bg-gradient-to-br from-blue-50 to-indigo-50/60 border border-blue-100 rounded-xl text-[11px] font-bold text-[#1E40AF] shadow-3xs">
                        📏 Menghitung...
                    </div>
                </div>

                <div class="w-full max-w-xs bg-slate-50/80 border border-slate-200/60 p-3 rounded-xl mb-5 text-[11px] text-gray-500 shadow-inner leading-relaxed">
                    <b class="text-gray-700 block mb-0.5">Informasi Radius Anda:</b>
                    <div id="locationText">Mengambil koordinat satelit...</div>
                </div>

                <button id="absenBtn" onclick="absen()" class="w-full max-w-xs bg-gradient-to-r from-[#1E40AF] to-blue-600 hover:opacity-90 transition text-white py-3 rounded-xl text-xs font-bold tracking-wide shadow-md">
                    Absen Sekarang
                </button>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const officeLat = 0.4761258;
        const officeLng = 101.4190600;

        let currentLat = null;
        let currentLng = null;
        let currentAccuracy = null;
        let currentDistance = null;

        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user'
                    }
                });
                document.getElementById('video').srcObject = stream;
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Akses Kamera Ditolak',
                    text: 'Mohon izinkan akses kamera pada browser Anda untuk melakukan scan wajah.'
                });
                console.error(error);
            }
        }
        startCamera();

        function calculateDistance(lat1, lon1, lat2, lon2) {
            const earthRadius = 6371000;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return earthRadius * c;
        }

        navigator.geolocation.watchPosition(
            (pos) => {
                currentLat = pos.coords.latitude;
                currentLng = pos.coords.longitude;
                currentAccuracy = pos.coords.accuracy;

                currentDistance = calculateDistance(officeLat, officeLng, currentLat, currentLng);

                document.getElementById('gpsStatus').innerHTML = `📍 Akurasi: ${Math.round(currentAccuracy)}m`;
                document.getElementById('distanceStatus').innerHTML = `📏 Jarak: ${Math.round(currentDistance)}m`;
                document.getElementById('locationText').innerHTML = `
                    Akurasi GPS: ${Math.round(currentAccuracy)} meter<br>
                    Jarak ke RS Mata PEK Eye Center: ${Math.round(currentDistance)} meter
                `;
            },
            (err) => {
                Swal.fire({
                    icon: 'error',
                    title: 'GPS Tidak Aktif',
                    text: 'Nyalakan GPS lokasi pada perangkat Anda.'
                });
                console.error(err);
            }, {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: 10000
            }
        );

        function captureImage() {
            const video = document.getElementById('video');
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            return canvas.toDataURL('image/jpeg');
        }

        async function absen() {
            if (!currentLat || !currentLng) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lokasi Belum Siap',
                    text: 'Tunggu hingga modul GPS mengunci koordinat Anda.'
                });
                return;
            }

            if (currentAccuracy > 200) {
                Swal.fire({
                    icon: 'warning',
                    title: 'GPS Kurang Akurat',
                    text: 'Pindahlah ke area terbuka agar akurasi GPS meningkat.'
                });
                return;
            }

            if (currentDistance > 200) {
                Swal.fire({
                    icon: 'error',
                    title: 'Di Luar Radius Kantor',
                    text: `Anda berada ${Math.round(currentDistance)} meter di luar area operasional rumah sakit.`
                });
                return;
            }

            const btn = document.getElementById('absenBtn');
            btn.disabled = true;
            btn.innerHTML = 'Memproses Scan...';

            const image = captureImage();

            try {
                const response = await fetch('/api/device/face/scan', {
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

                const data = await response.json();

                Swal.fire({
                    icon: data.success ? 'success' : 'error',
                    title: data.message,
                    text: data.late_minutes ? `Terlambat masuk ${Math.round(data.late_minutes)} menit` : (data.distance ?? '')
                }).then(() => {
                    if (data.success && data.type === 'checkin') {
                        window.location.href = '/dashboard';
                    }
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Gagal menghubungi sistem absensi pusat.'
                });
                console.error(error);
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Absen Sekarang';
            }
        }
    </script>
</x-app-layout>