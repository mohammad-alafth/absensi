<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Permohonan Cuti / Izin</title>
    <style>
        /* Optimasi DomPDF jangkauan ukuran kertas A4 */
        @page {
            size: a4 portrait;
            margin: 20mm 15mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000000;
            margin: 0;
            padding: 0;
        }

        .title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 25px;
            text-transform: uppercase;
        }

        .recipient {
            margin-bottom: 20px;
        }

        .dots-line {
            display: inline-block;
            border-bottom: 1px dotted #000;
            font-weight: bold;
            padding: 0 4px;
        }

        /* Tabel Form Data Diri */
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .form-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .form-table td.label {
            width: 75px;
        }

        .form-table td.colon {
            width: 15px;
            text-align: center;
        }

        /* Tabel Kotak Pilihan Jenis Cuti */
        .options-table {
            width: 100%;
            margin: 8px 0 12px 0;
            border-collapse: collapse;
        }

        .options-table td {
            width: 50%;
            padding: 4px 0;
            vertical-align: middle;
        }

        .checkbox {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1px solid #000;
            margin-right: 6px;
            text-align: center;
            line-height: 11px;
            font-size: 9pt;
            font-weight: bold;
            font-family: Arial, sans-serif;
        }

        /* Tanggal, Alasan & Delegasi */
        .dates-section {
            margin-bottom: 15px;
            text-align: justify;
        }

        .reason-section {
            margin-bottom: 15px;
        }

        .delegation-section {
            margin-bottom: 20px;
        }

        .delegation-table {
            width: 100%;
            margin-top: 3px;
            margin-left: 20px;
        }

        /* Tabel Jatah Cuti Baku */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #000000;
            padding: 8px 4px;
            text-align: center;
            font-size: 10pt;
        }

        .summary-table th {
            font-weight: bold;
            background-color: #f2f2f2;
        }

        .summary-table td.left {
            text-align: left;
            padding-left: 8px;
        }

        /* Wrapper Tanda Tangan Kompatibel DomPDF */
        .date-place {
            text-align: right;
            margin-bottom: 15px;
            padding-right: 15px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .signature-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 5px;
        }

        /* Ruangan tanda tangan dinamis tanpa tinggi mengunci keras agar nama tidak kepotong */
        .signature-space {
            min-height: 65px;
            padding: 5px 0;
        }

        .signature-img {
            max-height: 60px;
            max-width: 120px;
            display: block;
            margin: 0 auto;
        }

        .status-text {
            font-size: 8.5pt;
            color: #444444;
            font-style: italic;
            margin-bottom: 5px;
        }

        .name-line {
            width: 140px;
            border-bottom: 1px solid #000;
            margin: 5px auto 3px auto;
            font-weight: bold;
            white-space: nowrap;
        }

        /* Catatan Kaki */
        .note {
            font-style: italic;
            font-size: 9pt;
            margin-top: 25px;
            border-top: 1px dashed #000;
            padding-top: 6px;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <table
        width="100%"
        style="border-bottom:2px solid #000; padding-bottom:8px; margin-bottom:5px;">

        <tr>
            <!-- LOGO KIRI -->
            <td width="15%" style="text-align:left; vertical-align:middle;">
                <img src="{{ public_path('images/rsprofile.png') }}" style="width:75px; height:auto;">
            </td>

            <!-- TEXT HEADER -->
            <td width="70%" style="text-align:center;">
                <div style="font-size:20px; color:#5a6ea8; font-weight:bold; line-height:1.2;">
                    RS MATA Pekanbaru Eye Center
                </div>
                <div style="font-size:11px; margin-top:4px; line-height:1.4;">
                    Jl. Soekarno Hatta No. 236 – Pekanbaru – Riau<br>
                    Telp. (0761) 7875191, 0811 7605191 Fax. (0761) 7875195<br>
                    www.pekanbarueyecenter.com
                </div>
            </td>

            <!-- LOGO KANAN -->
            <td width="15%" style="text-align:right; vertical-align:middle;">
                <img src="{{ public_path('images/Picture1.png') }}" style="width:75px; height:auto;">
            </td>
        </tr>
    </table>
    <!-- TITLE -->
    <div class="title">PERMOHONAN CUTI/IZIN</div>

    <div class="recipient">
        <div>Kepada Yth.</div>
        <div><span class="dots-line" style="width: 220px;">{{ $leave->recipient ?? 'HRD / Pimpinan' }}</span></div>
    </div>

    <div style="margin-bottom: 8px;">Yang bertanda tangan dibawah ini, saya :</div>

    <table class="form-table">
        <tr>
            <td class="label">Nama</td>
            <td class="colon">:</td>
            <td><span class="dots-line" style="width: 100%;">{{ $leave->user->name }}</span></td>
        </tr>
        <tr>
            <td class="label">NIK</td>
            <td class="colon">:</td>
            <td><span class="dots-line" style="width: 100%;">{{ $leave->user->nik ?? '-' }}</span></td>
        </tr>
        <tr>
            <td class="label">Bagian</td>
            <td class="colon">:</td>
            <td><span class="dots-line" style="width: 100%;">{{ $leave->user->role_label ?? '-' }}</span></td>
        </tr>
    </table>

    <div style="margin-top: 12px;">Dengan ini mengajukan permohonan :</div>

    @php
    // Normalisasi jatah opsi pilihan agar singkron dengan radio input dari Form
    $currentType = trim($leave->leave_type);

    $isCutiTahunan = (strtolower($currentType) === 'tahunan' || strtolower($currentType) === 'cuti tahunan');
    $isCutiBesar = (strtolower($currentType) === 'besar' || strtolower($currentType) === 'cuti besar');
    $isCutiHamil = (strtolower($currentType) === 'melahirkan' || strtolower($currentType) === 'cuti hamil');

    // Pilihan Sakit, Menikah, DLL diarahkan ke opsi nomor 4 (Izin) sesuai file asli
    $isIzin = (!$isCutiTahunan && !$isCutiBesar && !$isCutiHamil);
    @endphp

    <table class="options-table">
        <tr>
            <td>
                <span class="checkbox">{!! $isCutiTahunan ? 'v' : '&nbsp;' !!}</span> 1. Cuti Tahunan
            </td>
            <td>
                <span class="checkbox">{!! $isIzin ? 'v' : '&nbsp;' !!}</span>
                4. Izin <span class="dots-line" style="width: 190px;">{{ $isIzin ? $leave->leave_type : '' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="checkbox">{!! $isCutiBesar ? 'v' : '&nbsp;' !!}</span> 2. Cuti Besar
            </td>
            <td>
                <span class="checkbox">{!! $currentType === 'DLL' ? 'v' : '&nbsp;' !!}</span> 5. Dll <span class="dots-line" style="width: 190px;">&nbsp;</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="checkbox">{!! $isCutiHamil ? 'v' : '&nbsp;' !!}</span> 3. Cuti Hamil
            </td>
            <td></td>
        </tr>
    </table>

    <div class="dates-section">
        Mulai tanggal <span class="dots-line" style="width: 120px;">{{ \Carbon\Carbon::parse($leave->start_date)->format('d-m-Y') }}</span>
        sampai dengan tanggal <span class="dots-line" style="width: 120px;">{{ \Carbon\Carbon::parse($leave->end_date)->format('d-m-Y') }}</span>
        dan bekerja kembali pada tanggal <span class="dots-line" style="width: 120px;">{{ $leave->return_date ? \Carbon\Carbon::parse($leave->return_date)->format('d-m-Y') : '.................' }}</span>
    </div>

    <div class="reason-section">
        Alasan / Keperluan : <span class="dots-line" style="width: 82%;">{{ $leave->reason }}</span>
    </div>

    <div class="delegation-section">
        Selama cuti/izin saya mendelegasikan tugas kepada :
        <table class="form-table delegation-table">
            <tr>
                <td class="label" style="width: 50px;">Nama</td>
                <td class="colon">:</td>
                <td><span class="dots-line" style="width: 250px;">{{ $leave->delegate_name ?? '.....................................' }}</span></td>
            </tr>
            <tr>
                <td class="label" style="width: 50px;">NIK</td>
                <td class="colon">:</td>
                <td><span class="dots-line" style="width: 250px;">{{ $leave->delegate_nik ?? '.....................................' }}</span></td>
            </tr>
        </table>
        <div style="margin-top: 4px;">Dengan surat terlampir (Hubungan Kontak Darurat: {{ $leave->emergency_contact ?? '-' }}).</div>
    </div>

    <table class="summary-table">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 32%;">Jenis Cuti/Izin</th>
                <th style="width: 14%;">&sum; Kuota Cuti *</th>
                <th style="width: 14%;">Masih ada *</th>
                <th style="width: 12%;">Diambil</th>
                <th style="width: 12%;">Sisa Cuti *</th>
                <th style="width: 10%;">Ket</th>
            </tr>
        </thead>
        <tbody>
            @php
            $quotaTotal = $leave->user->leave_quota;

            $usedLeave = $usedLeave ?? 0;

            $remainingLeave = $remainingLeave ?? 0;

            $masihAdaSebelumnya = $remainingLeave;

            $sisaCutiSetelahnya = max(
            $remainingLeave - $leave->total_days,
            0
            );
            @endphp
            <tr>
                <td>1</td>
                <td class="left">{{ $leave->leave_type }}</td>
                <td>{{ $quotaTotal }} Hari</td>
                <td>{{ $masihAdaSebelumnya }} Hari</td>
                <td style="font-weight: bold; color: #b91c1c;">{{ $leave->total_days }} Hari</td>
                <td style="font-weight: bold; color: #15803d;">{{ $sisaCutiSetelahnya }} Hari</td>
                <td>{{ strtoupper($leave->status) }}</td>
            </tr>
            <tr>
                <td>2</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>3</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        </tbody>
    </table>

    <div class="date-place">
        Pekanbaru, <span class="dots-line" style="width: 130px;">{{ \Carbon\Carbon::parse($leave->created_at)->translatedFormat('d F Y') }}</span>
    </div>

    <table class="signature-table">
        <tr>
            <td>
                <div>Menyetujui,</div>
                <div>Atasan Langsung</div>
                <div class="status-text">
                    @if($leave->pj_status) ({{ strtoupper($leave->pj_status) }}) @endif
                </div>
                <div class="signature-space">
                    @if($leave->pj_signature)
                    <img src="{{ $leave->pj_signature }}" class="signature-img">
                    @endif
                </div>
                <div class="name-line">{{ $leave->pjApprover->name ?? '........................' }}</div>
            </td>

            <td>
                @php
                // Logika menentukan siapa yang tampil di tengah
                if ($leave->director_approved_by) {
                $jabatan = 'Direktur';
                $status = $leave->director_status;
                $sig = $leave->director_signature;
                $nama = $leave->directorApprover->name ?? '........................';
                } elseif ($leave->head_approved_by) {
                $jabatan = 'Kepala Unit';
                $status = $leave->head_status;
                $sig = $leave->head_signature;
                $nama = $leave->headApprover->name ?? '........................';
                } else {
                $jabatan = 'HRD';
                $status = $leave->hrd_status;
                $sig = $leave->hrd_signature;
                $nama = $leave->hrdApprover->name ?? '........................';
                }
                @endphp

                <div>Mengetahui,</div>
                <div>{{ $jabatan }}</div>
                <div class="status-text">
                    @if($status) ({{ strtoupper($status) }}) @endif
                </div>
                <div class="signature-space">
                    @if($sig)
                    <img src="{{ (strpos($sig, 'data:image') === 0) ? $sig : public_path('storage/'.$sig) }}" class="signature-img">
                    @endif
                </div>
                <div class="name-line">{{ $nama }}</div>
            </td>

            <td>
                <div>Hormat Saya,</div>
                <div>Pemohon</div>
                <div class="status-text">&nbsp;</div>
                <div class="signature-space">
                    @if($leave->employee_signature)
                    <img src="{{ $leave->employee_signature }}" class="signature-img">
                    @endif
                </div>
                <div class="name-line">{{ $leave->user->name }}</div>
            </td>
        </tr>
    </table>

    <div class="note">
        * Noted : setelah melengkapi tanda tangan dari nama yang tertera diatas barulah surat cuti tersebut berlaku diserahkan ke unit HRD
    </div>

</body>

</html>