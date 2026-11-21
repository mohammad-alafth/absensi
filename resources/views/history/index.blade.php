@extends('layouts.submission')

@section('content')

    <style>
        .history-page { font-family: Inter, ui-sans-serif, system-ui, sans-serif; }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-animate {
            animation: fadeIn 0.2s ease-out forwards;
        }
    </style>

    <div class="history-page min-h-screen bg-[#f8f9ff] px-3 py-3 pb-24 sm:px-5">

        <div class="w-full max-w-5xl mx-auto space-y-4">

            <header class="bg-white border border-[#c4c5d7]/50 rounded-2xl shadow-sm p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-full border border-[#2151da]/20 bg-[#eff4ff] text-[#0037b0] flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[23px]">person</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] uppercase tracking-[0.08em] font-bold text-[#565e74]">RS Mata PEK · PBEC</p>
                            <p class="text-sm font-bold text-[#0b1c30] truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] text-[#565e74] truncate">{{ auth()->user()->role_label }}</p>
                        </div>
                    </div>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-[#1E40AF] font-semibold text-xs hover:underline shrink-0">
                        <span class="material-symbols-outlined text-[16px]">arrow_back</span> Dashboard
                    </a>
                </div>
                <div class="mt-4 pt-3 border-t border-[#c4c5d7]/40 flex items-end justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#0b1c30]">Riwayat &amp; Aktivitas</h1>
                        <p class="text-[11px] uppercase tracking-[0.08em] text-[#565e74] mt-1">Log pengajuan kepegawaian Anda</p>
                    </div>
                    <a href="{{ route('history.rekap') }}" class="inline-flex items-center gap-1.5 bg-[#1D4ED8] hover:bg-[#0037b0] text-white px-3 py-2 rounded-xl shadow-sm transition text-[11px] font-bold whitespace-nowrap">
                        <span class="material-symbols-outlined text-[17px]">insights</span> Rekap Absen
                    </a>
                </div>
            </header>

            <x-flash-message />

            @forelse($years as $year)

            <div class="bg-white rounded-2xl shadow-sm border border-[#c4c5d7]/50 p-4 sm:p-5">

                <div class="border-b border-[#c4c5d7]/40 pb-3 mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-[#0b1c30]">Aktivitas Bulan {{ $currentMonthLabel }}</h2>
                        <p class="text-[11px] text-[#565e74] mt-1">Pengajuan cuti, izin, dan lembur pada bulan berjalan.</p>
                    </div>
                    <span class="material-symbols-outlined text-[#2151da] text-[22px]" aria-hidden="true">calendar_month</span>
                </div>

                <div class="space-y-4">

                    <div class="bg-[#f8f9ff] rounded-2xl border border-[#c4c5d7]/60 overflow-hidden flex flex-col shadow-sm">
                        <div class="bg-[#0037b0] text-white px-4 py-3 flex items-center justify-between">
                            <h3 class="font-bold text-sm flex items-center gap-1.5"><span class="material-symbols-outlined text-[19px]">event_note</span> Riwayat Cuti</h3>
                            <span class="bg-white/20 text-[11px] px-2 py-0.5 rounded-full font-bold">{{ count($leaves[$year] ?? []) }} Data</span>
                        </div>

                        <div class="p-3 space-y-3 flex-1">
                            @forelse($leaves[$year] ?? [] as $leave)
                            <div class="border border-blue-50 rounded-xl p-3 bg-[#f8f9ff] hover:shadow-md transition flex flex-col justify-between min-h-[140px]">
                                <div>
                                    <div class="flex justify-between items-start mb-2 gap-2">
                                        <div>
                                            <p class="font-bold text-gray-800 text-xs">{{ $leave->leave_type }}</p>
                                            <p class="text-[10px] text-gray-400 font-medium mt-1 bg-white border px-2 py-0.5 rounded-lg inline-block">
                                                {{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M Y') }}
                                            </p>
                                        </div>

                                        @if($leave->status == 'approved')
                                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Approved</span>
                                        @elseif($leave->status == 'rejected')
                                        <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Rejected</span>
                                        @elseif($leave->status == 'waiting_head')
                                        <span class="bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">PJS Head</span>
                                        @elseif($leave->status == 'waiting_director')
                                        <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">PJS Direktur</span>
                                        @elseif($leave->status == 'waiting_medical_service')
                                        <span class="bg-cyan-100 text-cyan-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Waiting YANMED</span>
                                        @elseif($leave->status == 'waiting_kabag_umum')
                                        <span class="bg-sky-100 text-sky-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Waiting Kabag Umum</span>
                                        @elseif($leave->status == 'waiting_manager_umum')
                                        <span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Waiting Manager Umum</span>
                                        @elseif($leave->status == 'waiting_kabag_marketing')
                                        <span class="bg-fuchsia-100 text-fuchsia-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Waiting Kabag Marketing</span>
                                        @elseif($leave->status == 'waiting_manager_finance')
                                        <span class="bg-teal-100 text-teal-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Waiting Manager Finance</span>
                                        @else
                                        <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Pending</span>
                                        @endif
                                    </div>

                                    <div class="text-[11px] text-gray-500 mb-2">
                                        Durasi Hak: <span class="font-bold text-gray-800">{{ $leave->total_days }} Hari Kerja</span>
                                    </div>
                                </div>

                                <x-rejection-banner :submission="$leave" />

                                <div class="grid grid-cols-2 gap-2">
                                    <button onclick="openModal('leave-{{ $leave->id }}')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1">
                                         <span class="material-symbols-outlined text-[16px]">description</span> Detail
                                    </button>
                                    <a href="{{ route('leaves.download-pdf', $leave->id) }}" target="_blank" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1">
                                         <span class="material-symbols-outlined text-[16px]">picture_as_pdf</span> Cetak PDF
                                    </a>
                                </div>

                                <x-submission-edit-form type="leave" :submission="$leave" />
                            </div>

                            <div id="leave-{{ $leave->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 p-4 backdrop-blur-xs">
                                <div class="bg-white rounded-3xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 relative shadow-2xl modal-animate border mx-auto my-8">
                                     <button onclick="closeModal('leave-{{ $leave->id }}')" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-lg transition font-bold" aria-label="Tutup detail"><span class="material-symbols-outlined">close</span></button>
                                     <h2 class="text-xl font-black text-[#1E40AF] border-b pb-3 mb-4 flex items-center gap-1.5"><span class="material-symbols-outlined">description</span> Rincian Form Dokumen Cuti</h2>
                                    <div class="grid grid-cols-2 gap-4 text-xs leading-relaxed">
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Kategori Klasifikasi</p>
                                            <p class="font-bold text-gray-800 mt-0.5 text-sm">{{ $leave->leave_type }}</p>
                                        </div>
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Total Rentang Cuti</p>
                                            <p class="font-bold text-gray-800 mt-0.5 text-sm">{{ $leave->total_days }} Hari</p>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Periode Kalender Efektif</p>
                                            <p class="font-bold text-gray-800 mt-0.5">{{ $leave->start_date }} s/d {{ $leave->end_date }}</p>
                                        </div>
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Tanggal Kembali Aktif</p>
                                            <p class="font-bold text-emerald-600 mt-0.5">{{ $leave->return_date }}</p>
                                        </div>
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Kontak Darurat Panggilan</p>
                                            <p class="font-bold text-gray-800 mt-0.5">{{ $leave->emergency_contact ?? '-' }}</p>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Delegasi Pelaksana Tugas</p>
                                            <p class="font-bold text-indigo-600 mt-0.5">{{ $leave->delegate_name ?? '-' }} <span class="text-gray-400 text-[11px]">({{ $leave->delegate_nik ?? '-' }})</span></p>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Uraian Keperluan / Alasan</p>
                                            <p class="font-medium text-gray-700 italic mt-1 bg-white p-2 rounded-lg border-dashed border">{{ $leave->reason }}</p>
                                        </div>
                                    </div>

                                    <x-approval-notes :submission="$leave" />
                                    <div class="mt-6 pt-3 border-t flex gap-2">
                                        <a href="{{ route('leaves.download-pdf', $leave->id) }}" target="_blank" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white text-center py-3 rounded-xl text-xs font-bold transition shadow-sm">
                                             <span class="material-symbols-outlined text-[17px] align-middle">download</span> Download Dokumen PDF Resmi
                                        </a>
                                        <button onclick="closeModal('leave-{{ $leave->id }}')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-3 rounded-xl text-xs font-bold transition">
                                            Tutup
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-8">
                                 <span class="material-symbols-outlined text-3xl mb-1 text-slate-300">event_note</span>
                                <p class="text-[11px] text-gray-400">Belum ada rekaman data cuti</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-[#f8f9ff] rounded-2xl border border-[#c4c5d7]/60 overflow-hidden flex flex-col shadow-sm">
                        <div class="bg-[#1D4ED8] text-white px-4 py-3 flex items-center justify-between">
                            <h3 class="font-bold text-sm flex items-center gap-1.5"><span class="material-symbols-outlined text-[19px]">assignment</span> Riwayat Izin</h3>
                            <span class="bg-white/20 text-[11px] px-2 py-0.5 rounded-full font-bold">{{ count($permissions[$year] ?? []) }} Data</span>
                        </div>

                        <div class="p-3 space-y-3 flex-1">
                            @forelse($permissions[$year] ?? [] as $permission)
                            <div class="border border-blue-50 rounded-xl p-3 bg-[#f8f9ff] hover:shadow-md transition flex flex-col justify-between min-h-[140px]">
                                <div>
                                    <div class="flex justify-between items-start mb-2 gap-2">
                                        <div>
                                            <p class="font-bold text-gray-800 text-xs capitalize">{{ $permission->jenis }}</p>
                                            <p class="text-[10px] text-gray-400 font-medium mt-1 bg-white border px-2 py-0.5 rounded-lg inline-block">
                                                 <span class="material-symbols-outlined text-[13px] align-middle">event</span> {{ \Carbon\Carbon::parse($permission->tanggal)->translatedFormat('d M Y') }}
                                            </p>
                                        </div>

                                        @if($permission->status == 'approved')
                                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Approved</span>
                                        @elseif($permission->status == 'rejected')
                                        <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Rejected</span>
                                        @else
                                        <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Pending</span>
                                        @endif
                                    </div>

                                    <div class="text-[11px] text-gray-500 mb-2">
                                        Alokasi Waktu: <span class="font-bold text-gray-800">{{ $permission->jam_mulai }} - {{ $permission->jam_selesai }} WIB</span>
                                    </div>
                                </div>

                                <x-rejection-banner :submission="$permission" />

                                <div class="grid grid-cols-2 gap-2">
                                    <button onclick="openModal('permission-{{ $permission->id }}')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1">
                                         <span class="material-symbols-outlined text-[16px]">description</span> Detail
                                    </button>
                                    <a href="{{ route('permissions.download-pdf', $permission->id) }}" target="_blank" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1">
                                         <span class="material-symbols-outlined text-[16px]">picture_as_pdf</span> Cetak PDF
                                    </a>
                                </div>

                                <x-submission-edit-form type="permission" :submission="$permission" />
                            </div>

                            <div id="permission-{{ $permission->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 p-4 backdrop-blur-xs">
                                <div class="bg-white rounded-3xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 relative shadow-2xl modal-animate border mx-auto my-8">
                                     <button onclick="closeModal('permission-{{ $permission->id }}')" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-lg transition font-bold" aria-label="Tutup detail"><span class="material-symbols-outlined">close</span></button>
                                     <h2 class="text-xl font-black text-indigo-600 border-b pb-3 mb-4 flex items-center gap-1.5"><span class="material-symbols-outlined">description</span> Rincian Form Surat Keperluan Izin</h2>
                                    <div class="grid grid-cols-2 gap-4 text-xs leading-relaxed">
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Hari / Tanggal Izin</p>
                                            <p class="font-bold text-gray-800 mt-0.5 text-sm">{{ $permission->tanggal }}</p>
                                        </div>
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Jenis Pengajuan</p>
                                            <p class="font-bold text-indigo-600 mt-0.5 text-sm capitalize">{{ $permission->jenis }}</p>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Waktu Durasi Izin</p>
                                            <p class="font-bold text-gray-800 mt-0.5 text-sm">{{ $permission->jam_mulai }} s/d {{ $permission->jam_selesai }} WIB</p>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Lampiran Berkas / Surat Keterangan Pendukung</p>
                                            <div class="mt-1.5">
                                                @if($permission->lampiran)
                                                <a href="{{ asset('storage/'.$permission->lampiran) }}" target="_blank" class="inline-flex items-center gap-1 bg-white border px-3 py-1.5 rounded-lg font-bold text-indigo-600 hover:bg-indigo-50 shadow-xs transition">
                                                    📁 Lihat Lembar Lampiran Dokumen
                                                </a>
                                                @else
                                                <p class="font-semibold text-gray-400 italic">Berkas berkas lampiran tidak disertakan</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Alasan Konteks Izin</p>
                                            <p class="font-medium text-gray-700 italic mt-1 bg-white p-2 rounded-lg border-dashed border">{{ $permission->alasan }}</p>
                                        </div>
                                    </div>

                                    <x-approval-notes :submission="$permission" />
                                    <div class="mt-6 pt-3 border-t flex gap-2">
                                        <a href="{{ route('permissions.download-pdf', $permission->id) }}" target="_blank" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white text-center py-3 rounded-xl text-xs font-bold transition shadow-sm">
                                             <span class="material-symbols-outlined text-[17px] align-middle">download</span> Download Dokumen PDF Resmi
                                        </a>
                                        <button onclick="closeModal('permission-{{ $permission->id }}')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-3 rounded-xl text-xs font-bold transition">
                                            Tutup
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-10">
                                 <span class="material-symbols-outlined text-3xl mb-1 text-slate-300">assignment</span>
                                <p class="text-[11px] text-gray-400">Belum ada rekaman data izin</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-[#f8f9ff] rounded-2xl border border-[#c4c5d7]/60 overflow-hidden flex flex-col shadow-sm">
                        <div class="bg-[#006948] text-white px-4 py-3 flex items-center justify-between">
                            <h3 class="font-bold text-sm flex items-center gap-1.5"><span class="material-symbols-outlined text-[19px]">more_time</span> Riwayat Lembur</h3>
                            <span class="bg-white/20 text-[11px] px-2 py-0.5 rounded-full font-bold">{{ count($overtimes[$year] ?? []) }} Data</span>
                        </div>

                        <div class="p-3 space-y-3 flex-1">
                            @forelse($overtimes[$year] ?? [] as $overtime)
                            <div class="border border-blue-50 rounded-xl p-3 bg-[#f8f9ff] hover:shadow-md transition flex flex-col justify-between min-h-[140px]">
                                <div>
                                    <div class="flex justify-between items-start mb-2 gap-2">
                                        <div>
                                            <p class="font-bold text-gray-800 text-xs">
                                                {{ \Carbon\Carbon::parse($overtime->overtime_date)->translatedFormat('d M Y') }}
                                            </p>
                                            <p class="text-[10px] text-gray-400 font-medium mt-1 bg-white border px-2 py-0.5 rounded-lg inline-block">
                                                ⏱️ {{ $overtime->planned_range_label }}
                                            </p>
                                        </div>

                                        @if($overtime->status == 'approved')
                                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Approved</span>
                                        @elseif($overtime->status == 'rejected')
                                        <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Rejected</span>
                                        @else
                                        <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Pending</span>
                                        @endif
                                    </div>

                                    <div class="text-[11px] text-gray-500 mb-2 line-clamp-2 bg-white border p-1.5 rounded-lg text-gray-600 font-medium leading-tight">
                                        {{ $overtime->reason }}
                                    </div>
                                </div>

                                <x-rejection-banner :submission="$overtime" />

                                <div class="grid grid-cols-2 gap-2">
                                    <button onclick="openModal('overtime-{{ $overtime->id }}')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1">
                                         <span class="material-symbols-outlined text-[16px]">description</span> Detail
                                    </button>
                                    <a href="{{ route('overtimes.download-pdf', $overtime->id) }}" target="_blank" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1">
                                         <span class="material-symbols-outlined text-[16px]">picture_as_pdf</span> Cetak PDF
                                    </a>
                                </div>

                                <x-submission-edit-form type="overtime" :submission="$overtime" />
                            </div>

                            <div id="overtime-{{ $overtime->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 p-4 backdrop-blur-xs">
                                <div class="bg-white rounded-3xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 relative shadow-2xl modal-animate border mx-auto my-8">
                                     <button onclick="closeModal('overtime-{{ $overtime->id }}')" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-lg transition font-bold" aria-label="Tutup detail"><span class="material-symbols-outlined">close</span></button>
                                     <h2 class="text-xl font-black text-cyan-600 border-b pb-3 mb-4 flex items-center gap-1.5"><span class="material-symbols-outlined">description</span> Rincian Form Perintah Kerja Lembur</h2>
                                    <div class="grid grid-cols-2 gap-4 text-xs leading-relaxed">
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Hari / Tanggal Lembur</p>
                                            <p class="font-bold text-gray-800 mt-0.5 text-sm">{{ $overtime->overtime_date }}</p>
                                        </div>
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Total Volume Waktu</p>
                                            <p class="font-bold text-cyan-600 mt-0.5 text-sm">{{ $overtime->hours_label }}@unless($overtime->is_open_ended) Kerja @endunless</p>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Alokasi Jam Operasional</p>
                                            <p class="font-bold text-gray-800 mt-0.5 text-sm">{{ $overtime->planned_range_label }} WIB</p>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Uraian Alasan / Konteks Tugas Lembur</p>
                                            <p class="font-medium text-gray-700 italic mt-1 bg-white p-2 rounded-lg border-dashed border">{{ $overtime->reason }}</p>
                                        </div>
                                    </div>

                                    <x-approval-notes :submission="$overtime" />
                                    <div class="mt-6 pt-3 border-t flex gap-2">
                                        <a href="{{ route('overtimes.download-pdf', $overtime->id) }}" target="_blank" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white text-center py-3 rounded-xl text-xs font-bold transition shadow-sm">
                                             <span class="material-symbols-outlined text-[17px] align-middle">download</span> Download Dokumen PDF Resmi
                                        </a>
                                        <button onclick="closeModal('overtime-{{ $overtime->id }}')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-3 rounded-xl text-xs font-bold transition">
                                            Tutup
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-10">
                                 <span class="material-symbols-outlined text-3xl mb-1 text-slate-300">more_time</span>
                                <p class="text-[11px] text-gray-400">Belum ada rekaman data lembur</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                </div>

            </div>
            @empty

            <div class="bg-white rounded-2xl shadow-sm border border-[#c4c5d7]/50 p-10 text-center max-w-md mx-auto mt-8">
                <span class="material-symbols-outlined text-5xl text-[#1D4ED8] mb-4">history</span>
                <h3 class="text-base font-bold text-gray-800">Riwayat Pengajuan Kosong</h3>
                <p class="text-xs text-gray-400 mt-1">Anda belum memiliki berkas log transaksional cuti, izin, atau lembur pada sistem.</p>
            </div>

            @endforelse

        </div>
    </div>

    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }
        }

        window.onclick = function(event) {
            document.querySelectorAll('[id^="leave-"], [id^="permission-"], [id^="overtime-"]').forEach(modal => {
                if (event.target === modal) {
                    modal.classList.remove('flex');
                    modal.classList.add('hidden');
                }
            });
        }
    </script>

@endsection
