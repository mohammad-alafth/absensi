<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Wajah</title>
    <!-- face-api.js dari server LOKAL (public/vendor/face-api) dengan `defer`:
         tidak memblokir parsing HTML, dan pasti sudah tersedia sebelum event
         DOMContentLoaded dipicu (script halaman memakainya di dalam listener
         DOMContentLoaded). Sebelumnya dari cdn.jsdelivr.net yang menggantung
         di jaringan internal tanpa internet. -->
    <script src="{{ asset('vendor/face-api/face-api.min.js') }}" defer></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background: #f8f9ff;
            margin: 0;
            padding: 16px;
        }

        .container {
            width: 100%;
            max-width: 520px;
            margin: auto;
        }

        .camera-card {
            position: relative;
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            overflow: hidden;
            aspect-ratio: 4 / 5;
            background: #020617;
            border: 2px solid #1d4ed8;
            border-radius: 16px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.16);
        }

        video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }

        .camera-card::after {
            content: '';
            position: absolute;
            inset: 12% 19%;
            border: 2px solid rgba(147, 197, 253, 0.9);
            border-radius: 50%;
            box-shadow: 0 0 24px rgba(29, 78, 216, 0.35);
            pointer-events: none;
        }

        .camera-status {
            position: absolute;
            right: 12px;
            bottom: 12px;
            left: 12px;
            z-index: 1;
            padding: 8px 10px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.8);
            color: #fff;
            font-size: 12px;
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
        <h2>Register Wajah</h2>

        <div class="camera-card">
            <video id="video" autoplay playsinline muted></video>
            <div class="camera-status">Posisikan seluruh wajah di dalam panduan</div>
        </div>

        <p id="info">Posisi: Depan</p>
        <p id="count">Data: 0 / 3</p>

        <button onclick="capture()">Ambil Wajah</button>
        <button onclick="submitData()">Simpan</button>
    </div>

    <script>
        const video = document.getElementById('video');

        let descriptors = [];
        let isModelLoaded = false;

        const positions = ['Depan', 'Kiri', 'Kanan'];
        let step = 0;

        // Kamera
        async function startCamera() {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: true
            });
            video.srcObject = stream;
        }

        // Load model
        async function loadModels() {

            if (typeof faceapi === "undefined") {
                alert("❌ face-api belum load");
                return;
            }

            try {
                console.log("⏳ loading model...");

                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('/models'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('/models')
                ]);

                isModelLoaded = true;
                console.log("✅ model ready");

            } catch (err) {
                console.error("❌ MODEL ERROR:", err);
            }
        }

        // Capture wajah
        async function capture() {

            if (!isModelLoaded) {
                alert("Model belum siap");
                return;
            }

            if (step >= 3) {
                alert("Sudah cukup 3 data");
                return;
            }

            const result = await faceapi
                .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!result) {
                alert("Wajah tidak terdeteksi");
                return;
            }

            descriptors.push(Array.from(result.descriptor));
            step++;

            document.getElementById('count').innerText = "Data: " + step + " / 3";

            if (step < 3) {
                document.getElementById('info').innerText = "Posisi: " + positions[step];
            } else {
                document.getElementById('info').innerText = "Siap disimpan ✅";
            }
        }

        async function startCamera() {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: "user",
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            });
            video.srcObject = stream;
        }
        // Kirim ke backend
        async function submitData() {

            if (descriptors.length < 3) {
                alert("Ambil 3 posisi dulu");
                return;
            }

            const name = prompt("Masukkan nama:");

            const res = await fetch('/api/device/face/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name: name,
                    descriptors: descriptors
                })
            });

            const data = await res.json();

            alert(data.message);
            console.log("USER ID:", data.user_id);
        }

        // start
        window.addEventListener('load', async () => {
            console.log("🔥 halaman siap");

            await startCamera();
            await loadModels();
        });
    </script>

</body>

</html>
