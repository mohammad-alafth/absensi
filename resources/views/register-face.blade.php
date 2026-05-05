<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Wajah</title>
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

    <h2>Register Wajah</h2>

    <video id="video" autoplay width="400"></video>

    <p id="info">Posisi: Depan</p>
    <p id="count">Data: 0 / 3</p>

    <button onclick="capture()">Ambil Wajah</button>
    <button onclick="submitData()">Simpan</button>

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
                    facingMode: "user" // 🔥 ini penting untuk HP
                }
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