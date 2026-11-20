@extends('layouts.submission')

@section('content')
    <style>
        .profile-page { font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
    </style>

    <div class="profile-page min-h-screen bg-[#f8f9ff] px-3 py-3 pb-24 sm:px-5">
        <main class="w-full max-w-lg mx-auto space-y-4">
            @php
                $faceStatus = $user->face_status ?? null;
                $faceStatusKnown = in_array($faceStatus, ['enrolled', 'none'], true);
                $faceEnrolled = $faceStatus === 'enrolled'
                    && \App\Support\FacePolicy::isEnrolled($user);
                $faceEnrollmentDate = $user->face_enrolled_at
                    ? \Carbon\Carbon::parse($user->face_enrolled_at)->translatedFormat('d M Y')
                    : null;
                $configuredRadius = \App\Support\FaceSettings::float('office.radius_meters');
            @endphp

            <header class="bg-white border border-[#c4c5d7]/50 rounded-2xl shadow-sm px-4 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-[#eff4ff] border border-[#c4c5d7] text-[#0037b0] flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[22px]">person</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] uppercase tracking-[0.08em] font-bold text-[#565e74]">RS Mata PEK</p>
                            <h1 class="text-base font-bold text-[#0037b0] leading-tight">Profil &amp; Pengaturan</h1>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-[#565e74] text-[22px]" aria-hidden="true">settings</span>
                </div>
            </header>

            <section class="relative overflow-hidden bg-white rounded-2xl border border-[#c4c5d7]/60 shadow-sm p-4">
                <div class="absolute -right-10 -top-10 w-32 h-32 rounded-full bg-[#e5eeff]/70 pointer-events-none"></div>
                <div class="relative z-10 flex items-start gap-4">
                    <div class="w-20 h-20 rounded-xl bg-[#eff4ff] border-2 border-[#1D4ED8]/20 text-[#0037b0] flex items-center justify-center shrink-0 shadow-sm">
                        <span class="material-symbols-outlined text-[42px]">person</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg font-bold text-[#0b1c30] truncate">{{ $user->name }}</h2>
                        <p class="text-xs text-[#565e74] flex items-center gap-1 mt-1">
                            <span class="material-symbols-outlined text-[15px] text-[#2151da]">badge</span>
                            {{ $user->email }}
                        </p>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#e5eeff] text-[#0037b0]">{{ $user->role_label }}</span>
                            @if($user->work_type)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#eff4ff] text-[#565e74]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#006948]"></span>{{ $user->work_type }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="relative z-10 mt-4 pt-3 border-t border-[#c4c5d7]/40 grid grid-cols-2 gap-3">
                    <div>
                        <span class="block text-[10px] font-semibold text-[#565e74]">Identitas User</span>
                        <span class="block text-sm font-semibold text-[#0b1c30]">#{{ $user->id }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-semibold text-[#565e74]">Email</span>
                        <span class="block text-sm font-semibold text-[#0b1c30] truncate">{{ $user->email }}</span>
                    </div>
                </div>
            </section>

            <section class="bg-white border border-[#c4c5d7]/60 rounded-2xl p-4 sm:p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-[#e5eeff] text-[#0037b0] flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[19px]">face</span>
                        </div>
                        <h2 class="text-sm font-bold text-[#0b1c30] truncate">Biometrik Wajah (/face SIMRS)</h2>
                    </div>
                    @if($faceEnrolled)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 whitespace-nowrap">
                            <span class="material-symbols-outlined text-[13px]">check_circle</span> Terdaftar
                        </span>
                    @elseif($faceStatus === 'none')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 whitespace-nowrap">
                            <span class="material-symbols-outlined text-[13px]">pending</span> Belum terdaftar
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200 whitespace-nowrap">
                            <span class="material-symbols-outlined text-[13px]">help_outline</span> Status belum tersedia
                        </span>
                    @endif
                </div>

                <div class="bg-[#eff4ff] rounded-xl p-3 border border-[#c4c5d7]/50 flex items-start gap-3">
                    <div class="w-14 h-14 rounded-lg bg-white border border-[#c4c5d7] text-[#565e74] flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[30px]">face_retouching_natural</span>
                    </div>
                    <div class="min-w-0 flex-1 space-y-1">
                        @if($faceEnrolled)
                            <p class="text-sm font-bold text-[#0b1c30]">Pendaftaran wajah aktif</p>
                            <p class="text-[11px] text-[#565e74]">
                                @if($faceEnrollmentDate) Enrollment: <strong class="text-[#0b1c30]">{{ $faceEnrollmentDate }}</strong> · @endif
                                {{ (int) ($user->face_samples ?? 0) }} sampel tersimpan
                            </p>
                            <p class="text-[11px] text-emerald-700 font-semibold flex items-center gap-1">
                                <span class="material-symbols-outlined text-[15px]">verified_user</span> Status mengikuti policy biometrik aplikasi
                            </p>
                        @elseif($faceStatus === 'none')
                            <p class="text-sm font-bold text-[#0b1c30]">Wajah belum terdaftar</p>
                            <p class="text-[11px] text-[#565e74]">Registrasi wajah tersedia melalui alur resmi halaman Face.</p>
                        @else
                            <p class="text-sm font-bold text-[#0b1c30]">Status biometrik belum tersedia</p>
                            <p class="text-[11px] text-[#565e74]">Profil tidak menyimpulkan status enrollment tanpa data status yang valid.</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <a href="{{ route('face.register') }}" class="flex-1 min-h-10 px-3 rounded-xl bg-[#e5eeff] text-[#0037b0] border border-[#c4c5d7] text-[11px] font-semibold flex items-center justify-center gap-1.5 hover:bg-[#dce9ff] transition">
                        <span class="material-symbols-outlined text-[18px]">photo_camera</span>
                        {{ $faceEnrolled ? 'Perbarui Foto Master' : 'Registrasi Wajah' }}
                    </a>
                    <a href="{{ route('face.scan') }}" class="min-h-10 px-3 rounded-xl bg-white text-[#0037b0] border border-[#c4c5d7] text-[11px] font-semibold flex items-center justify-center gap-1.5 hover:bg-[#eff4ff] transition">
                        <span class="material-symbols-outlined text-[18px]">center_focus_strong</span> Uji Deteksi
                    </a>
                </div>
            </section>

            <section class="bg-white border border-[#c4c5d7]/60 rounded-2xl p-4 sm:p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-[#e5eeff] text-[#0037b0] flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[19px]">phonelink_lock</span>
                        </div>
                        <h2 class="text-sm font-bold text-[#0b1c30] truncate">Perangkat Terikat &amp; Izin Lokasi</h2>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#e5eeff] text-[#0037b0] border border-[#c4c5d7] whitespace-nowrap">
                        <span class="material-symbols-outlined text-[13px]">devices</span>
                        {{ $devices->where('status', '!=', 'revoked')->count() }}/{{ $deviceMax }}
                    </span>
                </div>

                {{-- Perangkat yang sedang dipakai. Diisi oleh skrip di bawah. --}}
                <div class="bg-[#eff4ff] rounded-xl p-3 border border-[#c4c5d7]/50 flex items-start gap-3">
                    <span class="material-symbols-outlined text-[#2151da] text-[25px]">smartphone</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p id="currentDeviceTitle" class="text-sm font-bold text-[#0b1c30]">Memeriksa perangkat…</p>
                            <span id="currentDeviceBadge" class="hidden shrink-0 items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border"></span>
                        </div>
                        <p id="currentDeviceMeta" class="text-[11px] text-[#565e74] mt-0.5">Data perangkat dibaca langsung di browser ini.</p>
                        <p id="currentDeviceNote" class="hidden text-[11px] text-[#565e74] mt-1"></p>
                    </div>
                </div>

                {{-- Daftar perangkat yang tercatat pada akun. --}}
                <div class="space-y-2">
                    <p class="text-[10px] font-bold uppercase tracking-[0.08em] text-[#565e74]">Perangkat terikat akun</p>
                    <ul id="deviceList" class="space-y-2">
                        @forelse($devices as $device)
                            <li class="flex items-center gap-3 p-2.5 rounded-xl border border-[#c4c5d7]/50 bg-white" data-device-id="{{ $device->id }}">
                                <span class="material-symbols-outlined text-[20px] text-[#565e74]">devices_other</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-[#0b1c30] truncate">{{ $device->label ?: 'Perangkat tanpa nama' }}</p>
                                    <p class="text-[10px] text-[#565e74] truncate">
                                        {{ $device->platform ?: 'Platform tidak diketahui' }}
                                        @if($device->last_seen_at) · terakhir {{ $device->last_seen_at->translatedFormat('d M Y H:i') }} @endif
                                    </p>
                                </div>
                                @if($device->status === 'trusted')
                                    <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Terikat</span>
                                @elseif($device->status === 'pending')
                                    <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">Menunggu</span>
                                @else
                                    <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 text-red-700 border border-red-200">Dicabut</span>
                                @endif
                            </li>
                        @empty
                            <li id="deviceListEmpty" class="p-3 rounded-xl border border-dashed border-[#c4c5d7] text-[11px] text-[#565e74]">
                                Belum ada perangkat tercatat. Perangkat akan tercatat otomatis saat halaman ini dibuka.
                            </li>
                        @endforelse
                    </ul>
                </div>

                {{-- Status izin lokasi: hanya bisa dibaca browser, tidak dikirim ke server. --}}
                <div class="p-3 rounded-xl bg-[#eff4ff]/70 border border-[#c4c5d7]/50">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-[#dce9ff] text-[#0037b0] flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[20px]">near_me</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-[#0b1c30]">Izin lokasi browser</p>
                                <span id="geoPermissionBadge" class="inline-flex shrink-0 items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">Memeriksa…</span>
                            </div>
                            <p id="geoPermissionText" class="text-[11px] text-[#565e74] mt-0.5">
                                Status dibaca langsung dari browser dan tidak dikirim ke server.
                            </p>
                            @if($configuredRadius)
                                <p class="text-[10px] text-[#747686] mt-1">
                                    Radius absensi: {{ rtrim(rtrim(number_format($configuredRadius, 2, '.', ''), '0'), '.') }} m
                                </p>
                            @endif
                        </div>
                    </div>
                    <button type="button" id="geoPermissionButton" class="mt-2 w-full h-9 rounded-lg border border-[#c4c5d7] bg-white text-[11px] font-semibold text-[#0037b0] hover:bg-[#eff4ff] transition disabled:opacity-60">
                        Minta izin lokasi
                    </button>
                </div>

                <p class="text-[10px] text-[#747686] flex items-start gap-1">
                    <span class="material-symbols-outlined text-[14px]">shield</span>
                    Perangkat hanya dicatat untuk keperluan audit. Absensi tidak pernah diblokir karena perangkat.
                </p>

                <script>
                    (function () {
                        'use strict';

                        const STORAGE_KEY = 'absensi.device_uuid';
                        const DEVICE_ENABLED = @json((bool) $deviceEnabled);

                        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

                        const titleEl = document.getElementById('currentDeviceTitle');
                        const metaEl = document.getElementById('currentDeviceMeta');
                        const noteEl = document.getElementById('currentDeviceNote');
                        const badgeEl = document.getElementById('currentDeviceBadge');
                        const listEl = document.getElementById('deviceList');
                        const emptyEl = document.getElementById('deviceListEmpty');

                        const badgeStyles = {
                            trusted: ['Terikat', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                            pending: ['Menunggu', 'bg-amber-50 text-amber-700 border-amber-200'],
                            revoked: ['Dicabut', 'bg-red-50 text-red-700 border-red-200'],
                        };

                        function setText(el, text) {
                            if (el) el.textContent = text;
                        }

                        function showNote(text) {
                            if (!noteEl) return;
                            noteEl.textContent = text;
                            noteEl.classList.toggle('hidden', !text);
                        }

                        function setDeviceBadge(status) {
                            if (!badgeEl) return;
                            const style = badgeStyles[status];

                            if (!style) {
                                badgeEl.classList.add('hidden');
                                return;
                            }

                            badgeEl.textContent = style[0];
                            badgeEl.className = 'inline-flex shrink-0 items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border ' + style[1];
                        }

                        function readUuid() {
                            try {
                                return window.localStorage.getItem(STORAGE_KEY);
                            } catch (error) {
                                return null;
                            }
                        }

                        function storeUuid(uuid) {
                            try {
                                window.localStorage.setItem(STORAGE_KEY, uuid);
                                return true;
                            } catch (error) {
                                return false;
                            }
                        }

                        function createUuid() {
                            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                                return window.crypto.randomUUID();
                            }

                            const bytes = new Uint8Array(16);

                            if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
                                window.crypto.getRandomValues(bytes);
                            } else {
                                for (let i = 0; i < bytes.length; i += 1) {
                                    bytes[i] = Math.floor(Math.random() * 256);
                                }
                            }

                            bytes[6] = (bytes[6] & 0x0f) | 0x40;
                            bytes[8] = (bytes[8] & 0x3f) | 0x80;

                            const hex = Array.prototype.map.call(bytes, function (b) {
                                return b.toString(16).padStart(2, '0');
                            });

                            return [
                                hex.slice(0, 4).join(''),
                                hex.slice(4, 6).join(''),
                                hex.slice(6, 8).join(''),
                                hex.slice(8, 10).join(''),
                                hex.slice(10, 16).join(''),
                            ].join('-');
                        }

                        function detectDevice() {
                            const ua = navigator.userAgent || '';
                            let platform = 'Tidak diketahui';
                            const data = navigator.userAgentData;

                            if (data && data.platform) {
                                platform = data.mobile ? data.platform + ' (mobile)' : data.platform;
                            } else if (/Android/i.test(ua)) {
                                platform = 'Android';
                            } else if (/iPhone|iPad|iPod/i.test(ua)) {
                                platform = 'iOS';
                            } else if (/Windows/i.test(ua)) {
                                platform = 'Windows';
                            } else if (/Mac OS X/i.test(ua)) {
                                platform = 'macOS';
                            } else if (/Linux/i.test(ua)) {
                                platform = 'Linux';
                            }

                            let browser = 'Browser';

                            if (/Edg\//i.test(ua)) {
                                browser = 'Edge';
                            } else if (/OPR\/|Opera/i.test(ua)) {
                                browser = 'Opera';
                            } else if (/Chrome\//i.test(ua)) {
                                browser = 'Chrome';
                            } else if (/Firefox\//i.test(ua)) {
                                browser = 'Firefox';
                            } else if (/Safari\//i.test(ua)) {
                                browser = 'Safari';
                            }

                            return {
                                label: browser + ' · ' + platform,
                                platform: platform,
                            };
                        }

                        function formatDate(iso) {
                            if (!iso) return '';

                            const date = new Date(iso);

                            if (Number.isNaN(date.getTime())) return '';

                            return date.toLocaleString('id-ID', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                            });
                        }

                        function appendDeviceRow(device) {
                            if (!listEl || !device || !device.id) return;
                            if (listEl.querySelector('[data-device-id="' + device.id + '"]')) return;
                            if (emptyEl) emptyEl.remove();

                            const row = document.createElement('li');
                            row.className = 'flex items-center gap-3 p-2.5 rounded-xl border border-[#c4c5d7]/50 bg-white';
                            row.setAttribute('data-device-id', device.id);

                            const icon = document.createElement('span');
                            icon.className = 'material-symbols-outlined text-[20px] text-[#565e74]';
                            icon.textContent = 'devices_other';

                            const body = document.createElement('div');
                            body.className = 'min-w-0 flex-1';

                            const name = document.createElement('p');
                            name.className = 'text-xs font-semibold text-[#0b1c30] truncate';
                            name.textContent = device.label || 'Perangkat tanpa nama';

                            const detail = document.createElement('p');
                            detail.className = 'text-[10px] text-[#565e74] truncate';
                            detail.textContent = (device.platform || 'Platform tidak diketahui')
                                + (device.last_seen_at ? ' · terakhir ' + formatDate(device.last_seen_at) : '');

                            body.appendChild(name);
                            body.appendChild(detail);

                            const style = badgeStyles[device.status] || ['Tercatat', 'bg-slate-100 text-slate-600 border-slate-200'];
                            const badge = document.createElement('span');
                            badge.className = 'shrink-0 px-2 py-0.5 rounded-full text-[10px] font-semibold border ' + style[1];
                            badge.textContent = style[0];

                            row.appendChild(icon);
                            row.appendChild(body);
                            row.appendChild(badge);
                            listEl.appendChild(row);
                        }

                        function applyResult(data, meta) {
                            if (!data || !data.status) {
                                setText(titleEl, 'Data perangkat tidak tersedia');
                                showNote('Server tidak mengembalikan status perangkat.');
                                return;
                            }

                            if (data.status === 'disabled') {
                                setText(titleEl, 'Pencatatan perangkat tidak aktif');
                                showNote('Fitur ini dimatikan oleh admin. Absensi tetap berjalan normal.');
                                return;
                            }

                            if (data.status === 'invalid') {
                                setText(titleEl, 'Perangkat tidak dapat dicatat');
                                showNote('ID perangkat tidak valid. Coba muat ulang halaman ini.');
                                return;
                            }

                            if (data.status === 'limit_reached') {
                                setText(titleEl, meta.label);
                                setText(metaEl, meta.platform);
                                showNote('Batas ' + (data.max_per_user || 3) + ' perangkat aktif sudah tercapai. Hubungi admin untuk mencabut perangkat lama.');
                                return;
                            }

                            const device = data.device;

                            if (!device) {
                                setText(titleEl, 'Data perangkat tidak tersedia');
                                return;
                            }

                            setText(titleEl, device.label || meta.label);
                            setText(metaEl, (device.platform || meta.platform)
                                + (device.first_seen_at ? ' · terikat sejak ' + formatDate(device.first_seen_at) : ''));

                            setDeviceBadge(device.status);
                            appendDeviceRow(device);

                            if (data.status === 'revoked') {
                                showNote('Perangkat ini sudah dicabut admin. Hubungi admin bila perangkat benar milik Anda.');
                            } else if (device.status === 'pending') {
                                showNote('Perangkat menunggu persetujuan admin.');
                            } else {
                                showNote('');
                            }
                        }

                        async function bindDevice() {
                            if (!DEVICE_ENABLED) {
                                setText(titleEl, 'Pencatatan perangkat tidak aktif');
                                showNote('Fitur ini dimatikan oleh admin. Absensi tetap berjalan normal.');
                                return;
                            }

                            let uuid = readUuid();

                            if (!uuid) {
                                uuid = createUuid();

                                if (!storeUuid(uuid)) {
                                    setText(titleEl, 'Perangkat belum dapat dideteksi');
                                    showNote('Browser memblokir penyimpanan lokal (mode privat). Izinkan penyimpanan situs agar perangkat dapat dicatat.');
                                    return;
                                }
                            }

                            const meta = detectDevice();
                            setText(titleEl, 'Memeriksa perangkat…');

                            try {
                                const response = await fetch('/api/device/bind', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                        'X-Device-Id': uuid,
                                    },
                                    body: JSON.stringify({
                                        label: meta.label,
                                        platform: meta.platform,
                                    }),
                                });

                                const data = await response.json().catch(function () {
                                    return {};
                                });

                                applyResult(data, meta);
                            } catch (error) {
                                setText(titleEl, 'Gagal memuat data perangkat');
                                showNote('Periksa koneksi Anda, lalu muat ulang halaman ini.');
                            }
                        }

                        const geoStates = {
                            granted: ['Diizinkan', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                            denied: ['Ditolak', 'bg-red-50 text-red-700 border-red-200'],
                            prompt: ['Belum diminta', 'bg-amber-50 text-amber-700 border-amber-200'],
                            unsupported: ['Tidak dapat dipastikan', 'bg-slate-100 text-slate-600 border-slate-200'],
                        };

                        function setGeoBadge(state) {
                            const badge = document.getElementById('geoPermissionBadge');
                            const style = geoStates[state] || geoStates.unsupported;

                            if (!badge) return;

                            badge.textContent = style[0];
                            badge.className = 'inline-flex shrink-0 items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border ' + style[1];
                        }

                        function setGeoText(text) {
                            setText(document.getElementById('geoPermissionText'), text);
                        }

                        function watchGeoPermission() {
                            if (!navigator.geolocation) {
                                setGeoBadge('unsupported');
                                setGeoText('Perangkat atau browser ini tidak mendukung geolokasi.');
                                return;
                            }

                            if (!navigator.permissions || typeof navigator.permissions.query !== 'function') {
                                setGeoBadge('unsupported');
                                setGeoText('Browser ini tidak menyediakan status izin. Gunakan tombol di bawah untuk menguji izin lokasi.');
                                return;
                            }

                            navigator.permissions.query({ name: 'geolocation' })
                                .then(function (status) {
                                    setGeoBadge(status.state);
                                    setGeoText('Status dibaca langsung dari browser dan tidak dikirim ke server.');

                                    status.onchange = function () {
                                        setGeoBadge(status.state);
                                    };
                                })
                                .catch(function () {
                                    setGeoBadge('unsupported');
                                    setGeoText('Status izin tidak dapat dibaca otomatis. Gunakan tombol di bawah untuk menguji.');
                                });
                        }

                        const geoButton = document.getElementById('geoPermissionButton');

                        if (geoButton) {
                            geoButton.addEventListener('click', function () {
                                if (!navigator.geolocation) return;

                                geoButton.disabled = true;
                                geoButton.textContent = 'Meminta izin…';

                                navigator.geolocation.getCurrentPosition(
                                    function (position) {
                                        setGeoBadge('granted');
                                        setGeoText('Izin diberikan. Akurasi lokasi saat ini ±'
                                            + Math.round(position.coords.accuracy) + ' meter.');
                                        geoButton.disabled = false;
                                        geoButton.textContent = 'Periksa ulang lokasi';
                                    },
                                    function (error) {
                                        if (error.code === 1) {
                                            setGeoBadge('denied');
                                            setGeoText('Izin ditolak. Aktifkan izin lokasi untuk situs ini di pengaturan browser.');
                                        } else {
                                            setGeoText('Izin tersedia, tetapi lokasi belum terkunci. Pindah ke area terbuka lalu coba lagi.');
                                        }

                                        geoButton.disabled = false;
                                        geoButton.textContent = 'Minta izin lokasi';
                                    },
                                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                                );
                            });
                        }

                        bindDevice();
                        watchGeoPermission();
                    })();
                </script>
            </section>

            <section class="bg-white rounded-2xl border border-[#c4c5d7]/60 shadow-sm p-4 sm:p-5">
                @include('profile.partials.update-profile-information-form')
            </section>

            <section class="bg-white rounded-2xl border border-[#c4c5d7]/60 shadow-sm p-4 sm:p-5">
                @include('profile.partials.update-password-form')
            </section>

            <section class="bg-white rounded-2xl border border-red-200 shadow-sm p-4 sm:p-5">
                @include('profile.partials.delete-user-form')
            </section>

            <section class="space-y-3 pt-1">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full h-12 rounded-xl bg-red-50 border border-red-200 text-red-700 font-semibold text-sm flex items-center justify-center gap-2 hover:bg-red-100 active:scale-[0.99] transition">
                        <span class="material-symbols-outlined text-[20px]">logout</span>
                        Keluar dari Akun
                    </button>
                </form>
                <p class="text-center text-[10px] text-[#747686]">Pengaturan akun dan keamanan akses aplikasi</p>
            </section>
        </main>
    </div>
@endsection
