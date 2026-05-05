const net = require('net');
const axios = require('axios');

const DEVICE_IP = '192.168.1.151';
const DEVICE_PORT = 4370;

// koneksi ke device
const client = new net.Socket();

client.connect(DEVICE_PORT, DEVICE_IP, () => {
    console.log('✅ Connected ke fingerprint device');
});

// menerima data dari device
client.on('data', (data) => {
    console.log('📥 Data diterima:', data.toString());

    // ⚠️ parsing tergantung format device
    const finger_id = parseData(data);

    if (finger_id) {
        kirimKeLaravel(finger_id);
    }
});

client.on('close', () => {
    console.log('❌ Koneksi ditutup');
});

// fungsi parsing (dummy dulu)
function parseData(data) {
    try {
        // contoh parsing sederhana
        return parseInt(data.toString());
    } catch {
        return null;
    }
}

// kirim ke Laravel
function kirimKeLaravel(finger_id) {
    axios.post(
        'http://127.0.0.1:8000/api/fingerprint-scan',
        {
            finger_id: finger_id
        },
        {
            headers: {
                'X-API-KEY': 'ABSENSI_SECRET_2026'
            }
        }
    )
        .then(res => console.log('✅ Laravel:', res.data))
        .catch(err => console.log('❌ Error:', err.message));
}