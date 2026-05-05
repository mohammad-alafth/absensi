<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fingerprint Absensi</title>

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

        .box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        input {
            width: 100%;
            max-width: 300px;
            padding: 10px;
            margin-top: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
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
            cursor: pointer;
        }

        button:active {
            background: #218838;
        }

        .info {
            margin-top: 10px;
            font-size: 14px;
        }
    </style>
</head>

<body>

<div class="container">
    <div class="box">
        <h2>Scan Fingerprint</h2>

        <input type="text" id="finger_id" placeholder="Masukkan Finger ID">

        <button onclick="absen()">Scan Sekarang</button>

        <p class="info" id="status"></p>
    </div>
</div>

<script>
async function absen() {

    const finger_id = document.getElementById('finger_id').value;
    const status = document.getElementById('status');

    if (!finger_id) {
        alert("Masukkan Finger ID dulu");
        return;
    }

    status.innerText = "⏳ Memproses...";

    try {
        const res = await fetch('/api/device/fingerprint/scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-KEY': 'ABSENSI_SECRET_2026' // wajib sesuai backend
            },
            body: JSON.stringify({
                finger_id: finger_id
            })
        });

        const data = await res.json();

        status.innerText = data.message;
        alert(data.message);

    } catch (err) {
        console.error(err);
        status.innerText = "❌ Error koneksi";
    }
}
</script>

</body>
</html>