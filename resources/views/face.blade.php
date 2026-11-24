<x-app-layout>

    <style>
        .scan-ring {
            position: relative;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            border: 4px solid rgba(29, 78, 216, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .scan-ring::after {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            border: 2px solid #1D4ED8;
            opacity: .35;
            animation: pulse 2.5s infinite ease-in-out;
        }

        @keyframes pulse {
            0% {
                transform: scale(0.98);
                opacity: 0.2;
            }

            50% {
                transform: scale(1.04);
                opacity: 0.6;
            }

            100% {
                transform: scale(0.98);
                opacity: 0.2;
            }
        }

        .scan-line {
            position: absolute;
            width: 85%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #38bdf8, #1D4ED8, transparent);
            animation: scan 2.5s infinite ease-in-out alternate;
            z-index: 10;
            box-shadow: 0 0 10px #38bdf8;
            pointer-events: none;
        }

        @keyframes scan {
            0% {
                top: 15%;
            }

            100% {
                top: 85%;
            }
        }
    </style>

    <div class="min-h-screen bg-[#f8f9ff] px-4 py-6 sm:py-8 pb-28 flex justify-center">

        <div class="w-full max-w-md space-y-4">

            <!-- Top Header & Breadcrumb Card -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                <div class="flex items-center justify-between mb-3">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-[#1E40AF] font-bold text-xs hover:underline transition">
                        <span>&larr;</span> Kembali ke Dashboard
                    </a>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-[#1E40AF] text-[11px] font-bold">
                        <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                        Face Scan &amp; GPS
                    </span>
                </div>

                <div>
                    <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Presensi Mandiri</h2>
                    <p class="text-xs text-slate-500 mt-0.5">RS Mata PBEC &bull; Sentra Operasional</p>
                </div>
            </div>

            <!-- Main Scanner Terminal Card -->
            <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 flex flex-col items-center">

                <div class="text-center mb-4">
                    <p class="text-xs font-semibold text-slate-700">Posisikan wajah Anda tepat di dalam lingkaran</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Pastikan pencahayaan cukup dan wajah terlihat jelas</p>
                </div>

                <!-- Circular Scanner Terminal -->
                <div class="scan-ring mb-5 bg-slate-950 shadow-inner overflow-hidden">
                    <div class="scan-line"></div>
                    <video id="video" autoplay playsinline class="w-52 h-52 rounded-full object-cover border-4 border-white shadow-md z-0" style="transform: scaleX(-1);"></video>
                </div>

                <!-- GPS & Distance Telemetry Cards -->
                <div class="grid grid-cols-2 gap-2.5 w-full mb-3">
                    <div id="gpsStatus" class="flex justify-center items-center gap-1.5 py-2.5 px-3 bg-slate-50 border border-slate-200/90 rounded-xl text-[11px] font-bold text-slate-700 shadow-sm text-center">
                        &#128205; Mencari GPS...
                    </div>
                    <div id="distanceStatus" class="flex justify-center items-center gap-1.5 py-2.5 px-3 bg-blue-50/80 border border-blue-200/90 rounded-xl text-[11px] font-bold text-[#1E40AF] shadow-sm text-center">
                        &#128207; Menghitung...
                    </div>
                </div>

                <!-- Radius Details Bento Box -->
                <div class="w-full bg-slate-50 border border-slate-200/80 p-3.5 rounded-xl mb-5 text-[11px] text-slate-600 shadow-sm leading-relaxed">
                    <b class="text-slate-800 block mb-1">Informasi Radius Kehadiran:</b>
                    <div id="locationText" class="text-slate-500">Mengambil koordinat satelit...</div>
                </div>

                <!-- Action Button -->
                <button id="absenBtn" onclick="absen()" class="w-full bg-[#1D4ED8] hover:bg-[#1E40AF] active:scale-95 transition-all text-white py-3.5 rounded-xl text-sm font-bold tracking-wide shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                    Absen Sekarang
                </button>

                <p class="text-[11px] text-slate-400 text-center mt-3 leading-relaxed">
                    Presensi otomatis mencocokkan verifikasi biometrik wajah dan geofence lokasi kantor dalam radius maksimum 200 meter.
                </p>

            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/camera-permission.js') }}"></script>
    <script>
        // Nilai koordinat & radius diambil dari pengaturan face recognition (FaceSettings),
        // bukan angka yang ditulis mati di halaman ini.
        const officeLat = {{ $officeLat }};
        const officeLng = {{ $officeLng }};
        const officeRadius = {{ $officeRadius }};
        const officeName = @json($officeName);

        let currentLat = null;
        let currentLng = null;
        let currentAccuracy = null;
        let currentDistance = null;

        async function startCamera() {
            try {
                const stream = await window.openAbsensiCamera({
                    video: {
                        facingMode: 'user'
                    }
                });
                document.getElementById('video').srcObject = stream;
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Akses Kamera Ditolak',
                    text: 'Mohon izinkan akses kamera pada browser Anda untuk melakukan scan wajah.',
                    confirmButtonColor: '#1D4ED8',
                    borderRadius: '16px'
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

                document.getElementById('gpsStatus').innerHTML = `&#128205; Akurasi: ${Math.round(currentAccuracy)}m`;
                document.getElementById('distanceStatus').innerHTML = `&#128207; Jarak: ${Math.round(currentDistance)}m`;
                document.getElementById('locationText').innerHTML = `
                    Akurasi GPS: <strong>${Math.round(currentAccuracy)} meter</strong><br>
                    Jarak ke ${officeName}: <strong>${Math.round(currentDistance)} meter</strong> (radius ${Math.round(officeRadius)} m)
                `;
            },
            (err) => {
                console.error('Geolocation error:', err.code, err.message);
                let title = 'GPS Tidak Aktif';
                let text = 'Nyalakan GPS lokasi pada perangkat Anda.';
                if (err.code === 1) {
                    title = 'Izin Lokasi Ditolak';
                    text = 'Buka Settings -> Privacy & Security -> Location Services -> Browser -> "While Using the App", lalu muat ulang. Catatan: iOS memblokir GPS di alamat http://, gunakan https://.';
                } else if (err.code === 2) {
                    title = 'Sinyal Lokasi Lemah';
                    text = 'Pindah ke area terbuka / dekat jendela agar GPS mengunci, lalu tunggu.';
                } else if (err.code === 3) {
                    title = 'Masih Mengunci GPS';
                    text = 'Sinyal GPS belum didapat dalam 20 detik. Coba lagi di area terbuka.';
                }
                Swal.fire({
                    icon: 'error',
                    title: title,
                    text: text,
                    confirmButtonColor: '#1D4ED8',
                    borderRadius: '16px'
                });
            }, {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: 20000
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
                    text: 'Tunggu hingga modul GPS mengunci koordinat Anda.',
                    confirmButtonColor: '#1D4ED8',
                    borderRadius: '16px'
                });
                return;
            }

            if (currentAccuracy > 200) {
                Swal.fire({
                    icon: 'warning',
                    title: 'GPS Kurang Akurat',
                    text: 'Pindahlah ke area terbuka agar akurasi GPS meningkat.',
                    confirmButtonColor: '#1D4ED8',
                    borderRadius: '16px'
                });
                return;
            }

            if (currentDistance > officeRadius) {
                Swal.fire({
                    icon: 'error',
                    title: 'Di Luar Radius Kantor',
                    text: `Anda berada ${Math.round(currentDistance - officeRadius)} meter di luar radius kantor ${Math.round(officeRadius)} meter.`,
                    confirmButtonColor: '#1D4ED8',
                    borderRadius: '16px'
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
                        'Accept': 'application/json',
                        'X-Device-Id': (() => {
                            try { return localStorage.getItem('absensi.device_uuid') || ''; } catch (error) { return ''; }
                        })()
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

                // Fail-open: absen tetap dicatat walau verifikasi wajah
                // gagal atau layanan mati. TANPA peringatan, absen wajah
                // orang lain terlihat sama sahnya dengan absen asli.
                if (data.degraded) {
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Absen tercatat, TIDAK terverifikasi',
                        html: '<div style="text-align:left;font-size:13px;line-height:1.5">'
                            + (data.warning || 'Layanan pengenalan wajah sedang tidak tersedia.')
                            + '<br><br><b>Wajah Anda tidak dibandingkan dengan data terdaftar.</b>'
                            + '<br>Absen ini menunggu verifikasi HRD.</div>',
                        confirmButtonText: 'Saya mengerti',
                        confirmButtonColor: '#d97706',
                        allowOutsideClick: false,
                        borderRadius: '16px'
                    });

                    window.location.href = '/dashboard';
                    return;
                }

                if (data.device_unverified && data.warning) {
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Perangkat belum terverifikasi',
                        text: data.warning,
                        confirmButtonColor: '#1D4ED8',
                        borderRadius: '16px'
                    });
                }

                Swal.fire({
                    icon: data.success ? 'success' : 'error',
                    title: data.success ? 'Wajah terverifikasi - ' + data.message : data.message,
                    text: data.late_minutes ? `Terlambat masuk ${Math.round(data.late_minutes)} menit` : (data.distance ?? ''),
                    confirmButtonColor: '#1D4ED8',
                    borderRadius: '16px'
                }).then(() => {
                    if (data.success && data.type === 'checkin') {
                        window.location.href = '/dashboard';
                    }
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Gagal menghubungi sistem absensi pusat.',
                    confirmButtonColor: '#1D4ED8',
                    borderRadius: '16px'
                });
                console.error(error);
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Absen Sekarang';
            }
        }
    </script>
</x-app-layout>
