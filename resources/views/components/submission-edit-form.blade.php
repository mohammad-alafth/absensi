@props(['type', 'submission'])

{{--
    Tombol + modal edit pengajuan untuk menu Riwayat (izin / cuti / lembur).
    Hanya tampil bila pengajuan masih boleh diubah pemiliknya
    (status `pending` atau `rejected` - lihat App\Support\SubmissionStatus).
--}}
@php
    $routeName = match ($type) {
        'leave' => 'cuti.update',
        'overtime' => 'lembur.update',
        default => 'izin.update',
    };

    $judul = match ($type) {
        'leave' => 'Edit Pengajuan Cuti',
        'overtime' => 'Edit Pengajuan Lembur',
        default => 'Edit Pengajuan Izin',
    };

    $editable = \App\Support\SubmissionStatus::isEditable($submission);
    $isRejected = $submission->status === 'rejected';

    $dateValue = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : '';
    $timeValue = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('H:i') : '';

    $inputClass = 'w-full border border-gray-300 rounded-xl px-3 py-2 text-xs outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500';
    $labelClass = 'block text-[11px] font-bold text-gray-600 mb-1';
@endphp

@if($editable)
<div x-data="{ open: false }" class="mt-1.5">
    <button type="button" @click="open = true" class="w-full bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1">
        ✏️ Edit Pengajuan
    </button>

    <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[60] overflow-y-auto bg-black/50 p-4 backdrop-blur-xs" style="display:none;">
        <div @click.away="open = false" class="bg-white rounded-3xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 relative shadow-2xl modal-animate border mx-auto my-8">
            <button type="button" @click="open = false" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-lg transition font-bold">✕</button>

            <h2 class="text-lg font-black text-[#1E40AF] border-b pb-3 mb-3">✏️ {{ $judul }}</h2>

            <p class="text-[11px] leading-snug mb-4 rounded-xl border px-3 py-2 {{ $isRejected ? 'bg-red-50 border-red-100 text-red-700' : 'bg-blue-50 border-blue-100 text-blue-700' }}">
                @if($isRejected)
                Pengajuan ini <strong>ditolak</strong>. Menyimpan perubahan akan <strong>mengirim ulang</strong> pengajuan ke tahap approval awal.
                @else
                Pengajuan masih <strong>menunggu verifikasi</strong>. Perubahan langsung tersimpan pada pengajuan berjalan.
                @endif
            </p>

            <form method="POST" action="{{ route($routeName, $submission->id) }}" class="space-y-3" @if($type === 'permission') enctype="multipart/form-data" @endif>
                @csrf
                @method('PUT')

                @if($type === 'permission')
                @php
                    $jenisOptions = ['izin pribadi', 'sakit', 'keperluan keluarga', 'terlambat masuk', 'pulang lebih awal', 'dinas luar'];

                    if ($submission->jenis && !in_array($submission->jenis, $jenisOptions, true)) {
                        $jenisOptions[] = $submission->jenis;
                    }
                @endphp

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">Tanggal Mulai</label>
                        <input type="date" name="tanggal" value="{{ old('tanggal', $dateValue($submission->tanggal)) }}" required class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai', $dateValue($submission->tanggal_selesai)) }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Jam Mulai</label>
                        <input type="time" name="jam_mulai" value="{{ old('jam_mulai', $timeValue($submission->jam_mulai)) }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Jam Selesai</label>
                        <input type="time" name="jam_selesai" value="{{ old('jam_selesai', $timeValue($submission->jam_selesai)) }}" class="{{ $inputClass }}">
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}">Jenis Izin</label>
                    <select name="jenis" required class="{{ $inputClass }}">
                        @foreach($jenisOptions as $option)
                        <option value="{{ $option }}" @selected(old('jenis', $submission->jenis) === $option)>{{ ucfirst($option) }}</option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-gray-400 mt-1">Jam dipakai untuk izin 1 hari; izin &gt;1 hari cukup diisi tanggal mulai &amp; selesai.</p>
                </div>

                <div>
                    <label class="{{ $labelClass }}">Alasan / Keterangan</label>
                    <textarea name="alasan" rows="3" required class="{{ $inputClass }}">{{ old('alasan', $submission->alasan) }}</textarea>
                </div>

                <div>
                    <label class="{{ $labelClass }}">Ganti Lampiran (opsional, jpg/png/pdf)</label>
                    <input type="file" name="lampiran" accept=".jpg,.jpeg,.png,.pdf" class="{{ $inputClass }} bg-white">
                </div>
                @elseif($type === 'leave')
                @php
                    $leaveTypes = ['Tahunan', 'Besar', 'Sakit', 'Melahirkan', 'Menikah', 'DLL'];

                    if ($submission->leave_type && !in_array($submission->leave_type, $leaveTypes, true)) {
                        $leaveTypes[] = $submission->leave_type;
                    }
                @endphp

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">Tanggal Mulai</label>
                        <input type="date" name="start_date" value="{{ old('start_date', $dateValue($submission->start_date)) }}" required class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Tanggal Selesai</label>
                        <input type="date" name="end_date" value="{{ old('end_date', $dateValue($submission->end_date)) }}" required class="{{ $inputClass }}">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">Tanggal Kembali Aktif</label>
                        <input type="date" name="return_date" value="{{ old('return_date', $dateValue($submission->return_date)) }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Jenis Cuti</label>
                        <select name="leave_type" required class="{{ $inputClass }}">
                            @foreach($leaveTypes as $option)
                            <option value="{{ $option }}" @selected(old('leave_type', $submission->leave_type) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}">Alasan / Keperluan</label>
                    <textarea name="reason" rows="3" required class="{{ $inputClass }}">{{ old('reason', $submission->reason) }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">Delegasi Pelaksana Tugas</label>
                        <input type="text" name="delegate_name" maxlength="100" value="{{ old('delegate_name', $submission->delegate_name) }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">NIK Delegasi</label>
                        <input type="text" name="delegate_nik" maxlength="30" value="{{ old('delegate_nik', $submission->delegate_nik) }}" class="{{ $inputClass }}">
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}">Kontak Darurat</label>
                    <input type="text" name="emergency_contact" maxlength="30" value="{{ old('emergency_contact', $submission->emergency_contact) }}" class="{{ $inputClass }}">
                </div>
                @else
                @php
                    $dayTypes = ['hari_kerja' => 'Hari Kerja Aktif', 'hari_libur' => 'Hari Libur / Off'];

                    if ($submission->day_type && !array_key_exists($submission->day_type, $dayTypes)) {
                        $dayTypes[$submission->day_type] = $submission->day_type;
                    }
                @endphp

                <div>
                    <label class="{{ $labelClass }}">Tanggal SPL Lembur</label>
                    <input type="date" name="overtime_date" value="{{ old('overtime_date', $dateValue($submission->overtime_date)) }}" required class="{{ $inputClass }}">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">Jam Mulai</label>
                        <input type="time" name="start_time" value="{{ old('start_time', $timeValue($submission->start_time)) }}" required class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Jam Selesai</label>
                        @if($submission->is_open_ended)
                        {{-- Lembur hari libur mode "sampai selesai": Jam Berakhir boleh kosong --}}
                        <input type="time" name="end_time" value="{{ old('end_time') }}" placeholder="sampai selesai" class="{{ $inputClass }}">
                        <p class="text-[10px] text-gray-400 mt-1">
                            Biarkan kosong bila lembur berjalan sampai selesai; volume jam diambil dari absen pulang.
                        </p>
                        @else
                        <input type="time" name="end_time" value="{{ old('end_time', $timeValue($submission->end_time)) }}" required class="{{ $inputClass }}">
                        @endif
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}">Klasifikasi Hari</label>
                    <select name="day_type" required class="{{ $inputClass }}">
                        @foreach($dayTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('day_type', $submission->day_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="{{ $labelClass }}">Uraian Alasan / Tugas Lembur</label>
                    <textarea name="reason" rows="3" required class="{{ $inputClass }}">{{ old('reason', $submission->reason) }}</textarea>
                </div>
                @endif

                <div class="flex gap-2 pt-3 border-t">
                    <button type="submit" class="flex-1 bg-[#1E40AF] hover:bg-[#1e3a8a] text-white py-2.5 rounded-xl text-xs font-bold transition shadow-sm">
                        💾 Simpan Perubahan
                    </button>
                    <button type="button" @click="open = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-xs font-bold transition">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
