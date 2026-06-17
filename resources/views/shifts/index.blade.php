<x-app-layout>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9ff;
        }

        .fc-theme-standard td,
        .fc-theme-standard th {
            border-color: #e2e8f0;
        }

        .fc-col-header-cell {
            background-color: #1E40AF !important;
            color: white !important;
            padding: 10px 0 !important;
            font-size: 13px !important;
            text-transform: uppercase;
        }

        .fc-col-header-cell a {
            color: white !important;
            text-decoration: none;
        }

        .fc .fc-button-primary {
            background-color: #1E40AF !important;
            border-color: #1E40AF !important;
            text-transform: capitalize !important;
            font-weight: 600 !important;
            font-size: 12px !important;
        }

        .fc .fc-button-primary:not(:disabled):active,
        .fc .fc-button-primary:not(:disabled).fc-button-active {
            background-color: #152c7a !important;
            border-color: #152c7a !important;
        }

        .fc-daygrid-day-number {
            font-size: 12px;
            font-weight: bold;
            color: #475569;
        }

        .fc-event {
            cursor: pointer;
            padding: 3px 5px;
            font-size: 11px;
            font-weight: 600;
            border: none;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        #external-events .fc-event {
            cursor: grab;
            margin-bottom: 8px;
            padding: 10px;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-left: 4px solid #1E40AF;
            color: #1e293b;
            font-size: 12px;
        }

        #external-events .fc-event:active {
            cursor: grabbing;
        }
    </style>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="min-h-screen p-3 sm:p-4 pb-20">
        <div class="w-full max-w-[98%] mx-auto">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-[#1E40AF] font-bold text-xs hover:underline transition">← Kembali</a>
                <div class="bg-gradient-to-r from-[#1E40AF] to-blue-500 text-white px-4 py-1.5 rounded-xl shadow-xs text-xs font-bold">
                    Penjadwalan Kalender
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-4">
                <div class="w-full lg:w-1/4 xl:w-1/5 bg-white rounded-2xl shadow-sm border border-slate-200 p-4 h-fit sticky top-4">
                    <h3 class="font-bold text-sm mb-3 text-slate-800 border-b pb-2">👥 Daftar Pegawai</h3>
                    <p class="text-[10px] text-gray-500 mb-4">Tarik nama ke tanggal untuk mengatur jadwal.</p>
                    <div id="external-events" class="max-h-[60vh] overflow-y-auto pr-1">
                        @foreach($employees as $employee)
                        <div class="fc-event rounded-xl flex items-center justify-between" data-user="{{ $employee->id }}" data-name="{{ $employee->name }}">
                            <span class="font-bold truncate">{{ $employee->name }}</span>
                            <span class="text-gray-400 text-[10px]">Seret ☷</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="w-full lg:w-3/4 xl:w-4/5 bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const availableShifts = @json($shifts);
        const csrfToken = "{{ csrf_token() }}";
        const assignUrl = "{{ route('shift.assign') }}";
        const eventsUrl = "{{ route('shift.calendar') }}";
    </script>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const calendarEl = document.getElementById('calendar');
            const containerEl = document.getElementById('external-events');

            /*
            |--------------------------------------------------------------------------
            | DRAGGABLE EMPLOYEE LIST
            |--------------------------------------------------------------------------
            */

            new FullCalendar.Draggable(containerEl, {
                itemSelector: '.fc-event',
                eventData: function(eventEl) {
                    return {
                        title: eventEl.dataset.name
                    };
                }
            });

            /*
            |--------------------------------------------------------------------------
            | CALENDAR
            |--------------------------------------------------------------------------
            */

            const calendar = new FullCalendar.Calendar(calendarEl, {

                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },

                initialView: 'dayGridMonth',
                editable: true,
                droppable: true,

                events: eventsUrl,

                /*
                |--------------------------------------------------------------------------
                | CREATE SHIFT (DRAG EMPLOYEE TO DATE)
                |--------------------------------------------------------------------------
                */

                eventReceive: function(info) {

                    const userId = info.draggedEl.dataset.user;
                    const userName = info.draggedEl.dataset.name;
                    const dropDate = info.event.startStr.split('T')[0];

                    Swal.fire({
                            title: 'Atur Jadwal Shift',
                            width: 700,

                            html: `
        <div style="text-align:left">

            <div style="
                background:#f8fafc;
                border:1px solid #e5e7eb;
                border-radius:12px;
                padding:14px;
                margin-bottom:20px;
            ">
                <div style="font-size:12px;color:#6b7280">
                    Karyawan
                </div>

                <div style="
                    font-size:18px;
                    font-weight:700;
                    color:#111827;
                ">
                    ${userName}
                </div>

                <div style="
                    margin-top:5px;
                    font-size:13px;
                    color:#6b7280;
                ">
                    📅 ${dropDate}
                </div>
            </div>

            <div style="
                font-size:15px;
                font-weight:600;
                margin-bottom:12px;
            ">
                Pilih Shift
            </div>

            <div id="shift-list">
                ${availableShifts.map(shift => `
                    <div
                        class="shift-card"
                        data-id="${shift.id}"
                        data-start="${shift.start_time}"
                        data-end="${shift.end_time}"
                        data-overnight="${shift.is_overnight}"
                        style="
                            cursor:pointer;
                            border:1px solid #e5e7eb;
                            border-radius:12px;
                            padding:14px;
                            margin-bottom:10px;
                            transition:.2s;
                        "
                    >
                        <div style="
                            display:flex;
                            justify-content:space-between;
                            align-items:center;
                        ">
                            <div>
                                <div style="
                                    font-weight:700;
                                    color:#111827;
                                ">
                                    ${shift.name}
                                </div>

                                <div style="
                                    color:#6b7280;
                                    margin-top:4px;
                                    font-size:13px;
                                ">
                                    🕒 ${shift.start_time.substring(0,5)}
                                    -
                                    ${shift.end_time.substring(0,5)}
                                </div>
                            </div>

                            ${
                                shift.is_overnight
                                ?
                                `
                                    <span style="
                                        background:#fef3c7;
                                        color:#92400e;
                                        padding:4px 10px;
                                        border-radius:999px;
                                        font-size:12px;
                                        font-weight:600;
                                    ">
                                        🌙 Malam
                                    </span>
                                `
                                :
                                `
                                    <span style="
                                        background:#dcfce7;
                                        color:#166534;
                                        padding:4px 10px;
                                        border-radius:999px;
                                        font-size:12px;
                                        font-weight:600;
                                    ">
                                        ☀ Siang
                                    </span>
                                `
                            }
                        </div>
                    </div>
                `).join('')}
            </div>

        </div>
    `,

                            showCancelButton: true,
                            confirmButtonText: 'Simpan Jadwal',
                            cancelButtonText: 'Batal',

                            didOpen: () => {

                                let selectedShift = null;

                                document
                                    .querySelectorAll('.shift-card')
                                    .forEach(card => {

                                        card.addEventListener('click', function() {

                                            document
                                                .querySelectorAll('.shift-card')
                                                .forEach(c => {

                                                    c.style.border =
                                                        '1px solid #e5e7eb';

                                                    c.style.background =
                                                        '#ffffff';
                                                });

                                            this.style.border =
                                                '2px solid #2563eb';

                                            this.style.background =
                                                '#eff6ff';

                                            selectedShift = {
                                                shift_id: this.dataset.id,
                                                start_time: this.dataset.start,
                                                end_time: this.dataset.end,
                                                is_overnight: this.dataset.overnight == '1'
                                            };
                                        });
                                    });

                                window.selectedShift = () => selectedShift;
                            },

                            preConfirm: () => {

                                const shift = window.selectedShift();

                                if (!shift) {

                                    Swal.showValidationMessage(
                                        'Silakan pilih shift terlebih dahulu'
                                    );

                                    return false;
                                }

                                return {
                                    user_id: userId,
                                    shift_date: dropDate,
                                    shift_id: shift.shift_id,
                                    start_time: shift.start_time,
                                    end_time: shift.end_time,
                                    is_overnight: shift.is_overnight
                                };
                            }
                        })
                        .then((result) => {

                            if (result.isConfirmed) {

                                saveAssignment(result.value, function(response) {

                                    if (response.success) {

                                        info.event.remove();

                                        setTimeout(() => {
                                            calendar.refetchEvents();
                                        }, 500);

                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Jadwal Berhasil Disimpan',
                                            text: `${userName} telah dijadwalkan pada ${dropDate}`
                                        });

                                    } else {

                                        info.event.remove();

                                        Swal.fire(
                                            'Gagal',
                                            response.message,
                                            'error'
                                        );
                                    }
                                });

                            } else {

                                info.event.remove();
                            }
                        });
                },

                /*
                |--------------------------------------------------------------------------
                | MOVE EXISTING SHIFT
                |--------------------------------------------------------------------------
                */

                eventDrop: function(info) {

                    console.log('FULL EVENT');
                    console.log(info.event.toPlainObject());

                    console.log('EXTENDED');
                    console.log(info.event.extendedProps);

                    const props = info.event.extendedProps;

                    const payload = {
                        event_id: info.event.id,
                        user_id: props.user_id,
                        shift_id: props.shift_id,
                        shift_date: info.event.startStr.split('T')[0],
                        start_time: props.start_time,
                        end_time: props.end_time,
                        is_overnight: props.is_overnight ? 1 : 0
                    };

                    console.log('PAYLOAD');
                    console.log(payload);

                    saveAssignment(payload, function(response) {

                        if (!response.success) {

                            info.revert();

                            Swal.fire(
                                'Gagal',
                                response.message,
                                'error'
                            );

                        } else {

                            calendar.refetchEvents();
                        }
                    });
                },

                /*
                |--------------------------------------------------------------------------
                | EDIT SHIFT
                |--------------------------------------------------------------------------
                */

                eventClick: function(info) {

                    const props = info.event.extendedProps;

                    Swal.fire({

                            title: 'Edit Jadwal',

                            html: `
                    <label>Tanggal</label>

                    <input
                        type="date"
                        id="edit-date"
                        class="swal2-input"
                        value="${info.event.startStr.split('T')[0]}">

                    <label>Jam Masuk</label>

                    <input
                        type="time"
                        id="edit-start"
                        class="swal2-input"
                        value="${props.start_time}">

                    <label>Jam Keluar</label>

                    <input
                        type="time"
                        id="edit-end"
                        class="swal2-input"
                        value="${props.end_time}">
                `,

                            showCancelButton: true,
                            showDenyButton: true,

                            confirmButtonText: 'Simpan',
                            denyButtonText: 'Hapus',

                            preConfirm: () => {

                                return {
                                    event_id: info.event.id,
                                    user_id: props.user_id,
                                    shift_id: props.shift_id,
                                    shift_date: document.getElementById('edit-date').value,
                                    start_time: document.getElementById('edit-start').value,
                                    end_time: document.getElementById('edit-end').value,
                                    is_overnight: props.is_overnight ? 1 : 0
                                };
                            }
                        })
                        .then((result) => {

                            /*
                            |--------------------------------------------------------------------------
                            | UPDATE
                            |--------------------------------------------------------------------------
                            */

                            if (result.isConfirmed) {

                                saveAssignment(result.value, function(response) {

                                    if (response.success) {

                                        calendar.refetchEvents();

                                        Swal.fire(
                                            'Berhasil',
                                            'Jadwal diperbarui',
                                            'success'
                                        );
                                    }
                                });
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | DELETE
                            |--------------------------------------------------------------------------
                            */

                            if (result.isDenied) {

                                saveAssignment({

                                    user_id: props.user_id,
                                    shift_date: info.event.startStr.split('T')[0],
                                    is_delete: true

                                }, function(response) {

                                    if (response.success) {

                                        info.event.remove();
                                    }
                                });
                            }
                        });
                }
            });

            calendar.render();

            /*
            |--------------------------------------------------------------------------
            | AJAX SAVE
            |--------------------------------------------------------------------------
            */

            function saveAssignment(payload, callback) {

                fetch(assignUrl, {

                        method: 'POST',

                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },

                        body: JSON.stringify(payload)

                    })
                    .then(async response => {

                        const data = await response.json();

                        if (!response.ok) {

                            let errorMsg = data.message;

                            if (data.errors) {

                                errorMsg = Object.values(data.errors)
                                    .map(e => e.join(', '))
                                    .join('<br>');
                            }

                            callback({
                                success: false,
                                message: errorMsg
                            });

                        } else {

                            callback(data);
                        }
                    })
                    .catch(err => {

                        console.error(err);

                        callback({
                            success: false,
                            message: 'Kesalahan Sistem/Jaringan!'
                        });
                    });
            }

        });
    </script>
</x-app-layout>