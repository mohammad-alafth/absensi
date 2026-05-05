<!DOCTYPE html>
<html>

<head>
    <title>Face Absensi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ✅ FIX CDN -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

    <style>
        body {
            font-family: Arial;
            text-align: center;
            background: #f4f4f4;
            margin: 0;
            padding: 10px;
        }

        .container {
            max-width: 500px;
            margin: auto;
        }

        video {
            width: 100%;
            max-width: 400px;
            border-radius: 10px;
            border: 2px solid #333;
        }

        button {
            width: 100%;
            max-width: 300px;
            margin-top: 15px;
            padding: 12px;
            border: none;
            background: #28a745;
            color: white;
            font-size: 16px;
            border-radius: 8px;
        }

        .info {
            margin-top: 10px;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>Absensi Wajah</h2>

        <video id="video" width="400" height="300" autoplay></video>
        <br>

        <button onclick="absen()">Absen Sekarang</button>
    </div>

    <script>
        const video = document.getElementById('video');
        let isModelLoaded = false;

        // 🔥 Kamera
        async function startCamera() {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: true
            });
            video.srcObject = stream;
        }

        // 🔥 Load Model (FIX PATH)
        async function loadModels() {
            try {
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('/models'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('/models')
                ]);

                isModelLoaded = true;
                console.log("✅ Model loaded");
            } catch (err) {
                console.error("❌ Model error:", err);
            }
        }

        async function startCamera() {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: "user" // 🔥 ini penting untuk HP
                }
            });
            video.srcObject = stream;
        }

        // 🔥 Ambil lokasi GPS
        function getLocation() {
            return new Promise((resolve, reject) => {

                navigator.geolocation.getCurrentPosition(
                    (pos) => {

                        console.log("GPS Accuracy:", pos.coords.accuracy);

                        resolve({
                            latitude: pos.coords.latitude,
                            longitude: pos.coords.longitude,
                            accuracy: pos.coords.accuracy
                        });

                    },
                    (err) => reject(err), {
                        enableHighAccuracy: true, // pakai GPS asli
                        timeout: 15000,
                        maximumAge: 0
                    }
                );

            });
        }


        // 🔥 ABSEN
        async function absen() {

            if (!isModelLoaded) {
                alert("⏳ Model belum siap");
                return;
            }

            const result = await faceapi
                .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!result) {
                alert("❌ Wajah tidak terdeteksi");
                return;
            }

            const descriptor = Array.from(result.descriptor);

            // 🔥 ambil lokasi
            let location;
            try {
                location = await getLocation();
            } catch (e) {
                alert("❌ Gagal ambil lokasi");
                return;
            }

            fetch('/api/device/face/scan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-KEY': 'ABSENSI_SECRET_2026'
                    },
                    body: JSON.stringify({
                        descriptor: descriptor,
                        latitude: location.latitude,
                        longitude: location.longitude,
                        accuracy: location.accuracy
                    })
                })
                .then(async (res) => {

                    const text = await res.text();

                    console.log("SERVER RESPONSE:");
                    console.log(text);

                    return JSON.parse(text);

                })
                .then(data => {
                    alert(data.message);
                })
                .catch(err => {
                    console.error("FETCH ERROR:", err);
                });
        }

        // start
        startCamera();
        loadModels();
    </script>

</body>

</html>