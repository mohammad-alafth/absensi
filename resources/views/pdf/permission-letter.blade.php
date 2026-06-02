<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Surat Permohonan Izin</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .logo {
            width: 80px;
        }

        .title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin: 20px 0;
            color: #1e3a8a;
        }

        .section-title {
            font-weight: bold;
            background: #f3f4f6;
            padding: 5px 10px;
            border-left: 4px solid #1e3a8a;
            margin: 15px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 5px 0;
        }

        .attachment-box {
            margin-top: 20px;
            text-align: center;
        }

        .attachment-img {
            max-width: 400px;
            border: 1px solid #ccc;
            padding: 5px;
        }

        .signature-table {
            margin-top: 40px;
        }

        .signature-box {
            width: 33%;
            text-align: center;
        }

        .signature {
            width: 100px;
            height: 60px;
            object-fit: contain;
        }
    </style>
</head>

<body>

    <table class="header">
        <tr>
            <td><img src="{{ public_path('images/rsprofile.png') }}" class="logo"></td>
            <td style="text-align: center;">
                <div style="font-size: 18pt; font-weight: bold; color: #1e3a8a;">RS MATA Pekanbaru Eye Center</div>
                <div style="font-size: 9pt;">Jl. Soekarno Hatta No. 236 – Pekanbaru – Riau</div>
            </td>
            <td style="text-align: right;"><img src="{{ public_path('images/Picture1.png') }}" class="logo"></td>
        </tr>
    </table>

    <div class="title">SURAT PERMOHONAN IZIN</div>

    <div class="section-title">Data Karyawan</div>
    <table class="info-table">
        <tr>
            <td width="200">Nama</td>
            <td>: {{ $permission->user->name }}</td>
        </tr>
        <tr>
            <td>Jenis Izin</td>
            <td>: {{ $permission->jenis }}</td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>: {{ \Carbon\Carbon::parse($permission->jam_mulai)->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($permission->jam_selesai)->format('d-m-Y') }}</td>
        </tr>
        <tr>
            <td>Alasan</td>
            <td>: {{ $permission->alasan }}</td>
        </tr>
    </table>

    @if($permission->lampiran)
    <div class="section-title">Bukti / Lampiran</div>
    <div class="attachment-box">
        <img src="{{ public_path('storage/' . $permission->lampiran) }}" class="attachment-img">
    </div>
    @endif

    <table class="signature-table">
        <tr>
            <td class="signature-box">
                Pemohon<br><br>
                @if($permission->employee_signature)
                <img src="{{ public_path($permission->employee_signature) }}" class="signature">
                @else <br><br><br> @endif
                <div style="text-decoration: underline;">{{ $permission->user->name }}</div>
            </td>
            <td class="signature-box">
                PJ<br><br>
                <div style="font-size: 8pt; font-style: italic;">[{{ $permission->pj_status }}]</div>
                @if($permission->pj_signature)
                <img src="{{ public_path($permission->pj_signature) }}" class="signature">
                @else <br><br><br> @endif
                <div style="text-decoration: underline;">{{ $permission->pjApprover->name ?? '-' }}</div>
            </td>
            <td class="signature-box">
                HRD<br><br>
                <div style="font-size: 8pt; font-style: italic;">[{{ $permission->hrd_status }}]</div>
                @if($permission->hrd_signature)
                <img src="{{ public_path($permission->hrd_signature) }}" class="signature">
                @else <br><br><br> @endif
                <div style="text-decoration: underline;">{{ $permission->hrdApprover->name ?? '-' }}</div>
            </td>
        </tr>
    </table>
</body>

</html>