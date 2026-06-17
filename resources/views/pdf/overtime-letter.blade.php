<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Surat Perintah Lembur</title>
    <style>
        /* Pengaturan halaman cetak PDF portrait */
        @page {
            size: a4 portrait;
            margin: 15mm 15mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000000;
            margin: 0;
            padding: 0;
        }

        /* Container Dokumen Utama Bergaris Kotak Tipis (Border Luar) */
        .document-wrapper {
            border: 1px solid #000;
            padding: 15px;
            min-height: 95%;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #000;
            margin-bottom: 10px;
            padding-bottom: 8px;
        }

        .header-table td {
            vertical-align: middle;
            padding: 4px;
        }

        .logo-area {
            width: 30%;
        }

        .logo-text {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .logo-subtext {
            font-size: 8pt;
            font-style: italic;
            color: #333;
        }

        .title-area {
            width: 70%;
            text-align: right;
            font-size: 15pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        /* Form Atas (Meta Data Lembur) */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .meta-table td {
            padding: 4px;
            vertical-align: middle;
            font-size: 11pt;
        }

        .dots-value {
            font-weight: bold;
            border-bottom: 1px dotted #000;
            padding-left: 5px;
            display: inline-block;
        }

        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            text-align: center;
            line-height: 11px;
            font-size: 9pt;
            font-weight: bold;
            font-family: Arial, sans-serif;
            margin-right: 4px;
        }

        /* Area Deskripsi / Uraian Tugas */
        .description-area {
            margin: 10px 0 15px 0;
            font-size: 11pt;
        }

        .description-box {
            width: 100%;
            min-height: 45px;
            border-bottom: 1px dotted #000;
            padding-top: 5px;
            font-style: italic;
            font-weight: bold;
        }

        .instruction-text {
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        /* Tabel Utama Daftar Karyawan Lembur */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .main-table th,
        .main-table td {
            border: 1px solid #000000;
            padding: 7px 5px;
            text-align: center;
            font-size: 10.5pt;
            vertical-align: middle;
        }

        .main-table th {
            background-color: #e6e6e6;
            font-weight: bold;
        }

        /* Blok Area Tanda Tangan */
        .signature-section-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .signature-section-table td {
            width: 50%;
            border: 1px solid #000000;
            vertical-align: top;
            padding: 0;
        }

        /* Sub-Tabel di Dalam Kotak Tanda Tangan */
        .inner-sig-table {
            width: 100%;
            border-collapse: collapse;
        }

        .inner-sig-table th {
            border-bottom: 1px solid #000;
            padding: 5px;
            background-color: #f2f2f2;
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
        }

        .inner-sig-table td {
            border: none;
            padding: 5px;
            text-align: center;
        }

        .sig-space-wrapper {
            height: 75px;
            position: relative;
        }

        .sig-image {
            max-height: 70px;
            max-width: 130px;
            display: block;
            margin: 0 auto;
        }

        .status-badge {
            font-size: 8pt;
            font-style: italic;
            color: #555555;
        }

        .name-output {
            font-weight: bold;
            text-decoration: underline;
            font-size: 10.5pt;
        }

        .footer-name-row {
            border-top: 1px solid #000 !important;
            background-color: #f9f9f9;
            font-size: 10pt;
            padding: 4px 8px !important;
            text-align: left !important;
        }
    </style>
</head>

<body>

    <div class="document-wrapper">

        <table class="header-table">
            <tr>
                <!-- Logo Kiri -->
                <td style="width: 18%; text-align: left;">
                    <img src="{{ public_path('images/rsprofile.png') }}" class="logo"
                        style="width: 90px;">
                </td>

                <!-- Informasi Tengah -->
                <td style="width: 64%; text-align: center; line-height: 1.3;">
                    <div style="font-size: 20px; font-weight: bold; color: #1e3a8a;">
                        RS MATA Pekanbaru Eye Center
                    </div>

                    <div style="font-size: 11px;">
                        Jl. Soekarno Hatta No. 236 – Pekanbaru – Riau
                    </div>

                    <div style="font-size: 11px;">
                        Telp. (0761) 7875191, 0811 7605191 Fax. (0761) 7875195
                    </div>

                    <div style="font-size: 11px; color: #444;">
                        www.pekanbarueyecenter.com
                    </div>
                </td>

                <!-- Logo Kanan -->
                <td style="width: 18%; text-align: right;">
                    <img src="{{ public_path('images/Picture1.png') }}"
                        style="width: 75px;">
                </td>
            </tr>
        </table>

        <!-- Judul Dokumen -->
        <div style="
    text-align:center;
    font-size:16pt;
    font-weight:bold;
    text-decoration: underline;
    margin-top:10px;
    margin-bottom:20px;
">
            SURAT PERINTAH LEMBUR
        </div>

        @php
        // Normalisasi pendeteksian jenis hari kerja / libur dari data DB Anda
        $dayType = strtolower(trim($overtime->day_type ?? ''));
        $isHariKerja = ($dayType === 'kerja' || $dayType === 'hari kerja' || $dayType === 'weekday');
        $isHariLibur = ($dayType === 'libur' || $dayType === 'hari libur' || $dayType === 'weekend');
        @endphp

        <table class="meta-table">
            <tr>
                <td style="width: 15%;">Tanggal SPL</td>
                <td style="width: 2%;">:</td>
                <td style="width: 33%;"><span class="dots-value" style="width: 90%;">{{ \Carbon\Carbon::parse($overtime->overtime_date)->format('d-m-Y') }}</span></td>

                <td style="width: 18%;">Lembur pada waktu</td>
                <td style="width: 2%;">:</td>
                <td style="width: 30%;">
                    <span class="checkbox">{!! $isHariKerja ? 'v' : '&nbsp;' !!}</span> Hari Kerja
                    &nbsp;&nbsp;
                    <span class="checkbox">{!! $isHariLibur ? 'v' : '&nbsp;' !!}</span> Hari Libur
                </td>
            </tr>
            <tr>
                <td>Departemen</td>
                <td>:</td>
                <td><span class="dots-value" style="width: 90%;">{{ strtoupper($overtime->department ?? '-') }}</span></td>

                <td>Lembur hari/Tanggal</td>
                <td>:</td>
                <td><span class="dots-value" style="width: 95%;">{{ \Carbon\Carbon::parse($overtime->overtime_date)->translatedFormat('l, d-m-Y') }}</span></td>
            </tr>
        </table>

        <div class="description-area">
            <div>Uraian Tugas Lembur :</div>
            <div class="description-box">
                {{ $overtime->reason ?? '-' }}
            </div>
        </div>

        <div class="instruction-text">Memberikan Perintah Kepada :</div>

        <table class="main-table">
            <thead>
                <tr>
                    <th style="width: 7%;">No</th>
                    <th style="width: 20%;">NIK</th>
                    <th style="width: 35%;">Nama Karyawan</th>
                    <th style="width: 18%;">Jabatan</th>
                    <th style="width: 20%;" colspan="2">Jam Lembur</th>
                </tr>
                <tr style="background-color: #f9f9f9; font-size: 9pt;">
                    <th colspan="4" style="display: none;"></th>
                    <th style="padding: 3px; font-size: 9pt; width: 10%;">Mulai</th>
                    <th style="padding: 3px; font-size: 9pt; width: 10%;">Berakhir</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>{{ $overtime->user->nik ?? '-' }}</td>
                    <td style="text-align: left; padding-left: 8px; font-weight: bold;">{{ $overtime->user->name ?? '-' }}</td>
                    <td>{{ $overtime->user->role_label ?? '-' }}</td>
                    <td style="color: blue; font-weight: bold;">{{ $overtime->start_time ?? '-' }}</td>
                    <td style="color: blue; font-weight: bold;">{{ $overtime->end_time ?? '-' }}</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align: right; font-weight: bold; padding-right: 10px;">Total Durasi Akumulasi Jam:</td>
                    <td colspan="2" style="font-weight: bold; background-color: #f2f2f2;">{{ intval($overtime->total_hours ?? 0) }} Jam</td>
                </tr>
            </tbody>
        </table>

        <table class="signature-section-table">
            <tr>
                <td>
                    <table class="inner-sig-table">
                        <tr>
                            <th colspan="2">Diajukan Oleh :</th>
                        </tr>
                        <tr>
                            <td style="width: 50%; border-right: 1px solid #eee;">
                                <div style="font-size: 9pt;">Karyawan</div>
                                <div class="sig-space-wrapper">
                                    @if($overtime->employee_signature)
                                    <img src="{{ $overtime->employee_signature }}" class="sig-image">
                                    @endif
                                </div>
                                <div class="name-output">{{ $overtime->user->name ?? '-' }}</div>
                                <div style="font-size: 8pt; color:#444;">NIK: {{ $overtime->user->nik ?? '-' }}</div>
                            </td>
                            <td style="width: 50%;">
                                <div style="font-size: 9pt;">Atasan / PJ</div>
                                <div class="status-badge">[{{ strtoupper($overtime->pj_status ?? 'PENDING') }}]</div>
                                <div class="sig-space-wrapper">
                                    @if($overtime->pj_signature)
                                    <img src="{{ $overtime->pj_signature }}" class="sig-image">
                                    @endif
                                </div>
                                <div class="name-output">{{ $overtime->pjApprover->name ?? '-' }}</div>
                                <div style="font-size: 8pt; color:#444;">Atasan Langsung</div>
                            </td>
                        </tr>
                    </table>
                </td>

                <td>
                    <table class="inner-sig-table">
                        <tr>
                            <th>Disetujui Oleh :</th>
                        </tr>
                        <tr>
                            <td>
                                @php
                                // Menentukan siapa yang tampil berdasarkan hirarki approval
                                if ($overtime->director_approved_by) {
                                $jabatan = 'Direktur';
                                $nama = $overtime->directorApprover->name ?? '-';
                                $sig = $overtime->director_signature;
                                $status = $overtime->director_status;
                                } elseif ($overtime->head_approved_by) {
                                $jabatan = 'Kepala Unit / Head';
                                $nama = $overtime->headApprover->name ?? '-';
                                $sig = $overtime->head_signature;
                                $status = $overtime->head_status;
                                } else {
                                $jabatan = 'Manajemen / HRD';
                                $nama = $overtime->hrdApprover->name ?? '-';
                                $sig = $overtime->hrd_signature;
                                $status = $overtime->hrd_status;
                                }
                                @endphp

                                <div style="font-size: 9pt;">{{ $jabatan }}</div>
                                <div class="status-badge">[{{ strtoupper($status ?? 'PENDING') }}]</div>

                                <div class="sig-space-wrapper">
                                    @if($sig)
                                    <img src="{{ (strpos($sig, 'data:image') === 0) ? $sig : public_path('storage/'.$sig) }}" class="sig-image">
                                    @endif
                                </div>

                                <div class="name-output">{{ $nama }}</div>
                                <div style="font-size: 8pt; color:#444;">{{ $jabatan }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>