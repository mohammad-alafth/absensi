<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Permission;
use App\Models\User;
use App\Support\SubmissionStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Menu Riwayat harus menampilkan ALASAN PENOLAKAN dari approver, dan pengajuan
 * yang ditolak (atau masih pending) dapat direvisi pemiliknya lewat tombol
 * "Edit Pengajuan". Revisi dari pengajuan yang ditolak otomatis dikirim ulang
 * ke tahap approval awal, sementara catatan penolakan lama tetap tersimpan.
 *
 * Sejak revisi terakhir, tombol edit juga tersedia langsung di halaman
 * pengajuan sisi user (panel "Pengajuan Saya" pada /izin, /cuti, /lembur)
 * sehingga pengaju tidak perlu membuka menu Riwayat.
 */
class SubmissionRejectionAndEditTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        SubmissionStatus::flushApproverNames();
    }

    /* --------------------------------------------------------------------- */
    /* HELPER                                                                */
    /* --------------------------------------------------------------------- */

    private function makeUser(string $role, ?string $name = null): User
    {
        return User::create([
            'name' => $name ?? 'User ' . $role . ' ' . uniqid(),
            'email' => 'riwayat-' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => $role,
            'work_type' => 'office_5',
            'email_verified_at' => now(),
            'is_approved' => 1,
            'leave_quota' => 12,
        ]);
    }

    private function submitPermission(User $user, array $overrides = []): Permission
    {
        $this->actingAs($user)
            ->post(route('izin.store'), array_merge([
                'jenis' => 'izin pribadi',
                'tanggal' => '2026-10-01',
                'tanggal_selesai' => '2026-10-01',
                'jam_mulai' => '10:00',
                'jam_selesai' => '12:00',
                'alasan' => 'Urusan pribadi',
            ], $overrides))
            ->assertRedirect('/dashboard');

        return Permission::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    private function submitLeave(User $user): Leave
    {
        $this->actingAs($user)
            ->post(route('cuti.store'), [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-02',
                'leave_type' => 'Tahunan',
                'reason' => 'Keperluan keluarga',
                'employee_signature' => 'data:image/png;base64,AAA',
            ])
            ->assertRedirect('/dashboard');

        return Leave::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    private function submitOvertime(User $user): Overtime
    {
        $this->actingAs($user)
            ->post(route('lembur.store'), [
                'overtime_date' => '2026-10-01',
                'start_time' => '17:00',
                'end_time' => '19:00',
                'reason' => 'Penyelesaian pekerjaan',
                'day_type' => 'hari_kerja',
                'employee_signature' => 'data:image/png;base64,AAA',
            ])
            ->assertRedirect('/dashboard');

        return Overtime::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    private function rejectAsPj(string $routeName, int $id, string $note): void
    {
        $this->post(route($routeName, $id), ['note' => $note])
            ->assertRedirect();
    }

    /* --------------------------------------------------------------------- */
    /* 1. ALASAN PENOLAKAN TAMPIL DI MENU RIWAYAT                            */
    /* --------------------------------------------------------------------- */

    public function test_alasan_penolakan_pj_tampil_di_menu_riwayat(): void
    {
        $employee = $this->makeUser('ipsrs');
        $permission = $this->submitPermission($employee);

        $this->actingAs($this->makeUser('pj_ipsrs'));
        $this->rejectAsPj('pj.izin.reject', $permission->id, 'Surat keterangan belum dilampirkan');

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'status' => 'rejected',
            'pj_note' => 'Surat keterangan belum dilampirkan',
        ]);

        // Riwayat gabungan (cuti / izin / lembur)
        $this->actingAs($employee)
            ->get(route('history'))
            ->assertOk()
            ->assertSee('Surat keterangan belum dilampirkan')
            ->assertSee('Ditolak Penanggung Jawab (PJ)');

        // Riwayat izin khusus
        $this->actingAs($employee)
            ->get(route('izin.history'))
            ->assertOk()
            ->assertSee('Surat keterangan belum dilampirkan')
            ->assertSee('Alasan Penolakan');
    }

    /* --------------------------------------------------------------------- */
    /* 2. REVISI IZIN SETELAH DITOLAK -> DIKIRIM ULANG KE PJ                 */
    /* --------------------------------------------------------------------- */

    public function test_revisi_izin_yang_ditolak_dikirim_ulang_ke_pj(): void
    {
        $employee = $this->makeUser('ipsrs');
        $permission = $this->submitPermission($employee);
        $pj = $this->makeUser('pj_ipsrs', 'PJ IPSRS');

        $this->actingAs($pj);
        $this->rejectAsPj('pj.izin.reject', $permission->id, 'Lampiran kurang jelas');

        $this->actingAs($employee)
            ->put(route('izin.update', $permission->id), [
                'jenis' => 'izin pribadi',
                'tanggal' => '2026-10-02',
                'tanggal_selesai' => '2026-10-02',
                'jam_mulai' => '08:00',
                'jam_selesai' => '10:00',
                'alasan' => 'Urusan pribadi (revisi)',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $permission->refresh();

        $this->assertSame('pending', $permission->status);
        $this->assertSame('pending', $permission->pj_status);
        $this->assertSame('2026-10-02', (string) $permission->tanggal);
        $this->assertSame('08:00', substr((string) $permission->jam_mulai, 0, 5));
        $this->assertSame('Urusan pribadi (revisi)', $permission->alasan);

        // Catatan penolakan lama tetap tersimpan sebagai riwayat.
        $this->assertSame('Lampiran kurang jelas', $permission->pj_note);

        // Kembali muncul di antrean PJ.
        $inbox = $this->actingAs($pj)->get(route('pj.izin'))->assertOk();
        $this->assertTrue(
            collect($inbox->viewData('permissions'))->contains('id', $permission->id),
            'Pengajuan revisi harus muncul lagi di antrean PJ.'
        );

        // Di menu Riwayat catatan lama ditandai sebagai penolakan sebelumnya.
        $this->actingAs($employee)
            ->get(route('history'))
            ->assertOk()
            ->assertSee('Catatan Penolakan Sebelumnya')
            ->assertSee('Lampiran kurang jelas');
    }

    /* --------------------------------------------------------------------- */
    /* 3. PENGAJUAN YANG SUDAH DISETUJUI TIDAK BOLEH DIUBAH                  */
    /* --------------------------------------------------------------------- */

    public function test_pengajuan_yang_sudah_disetujui_tidak_dapat_diubah(): void
    {
        $employee = $this->makeUser('ipsrs');
        $permission = $this->submitPermission($employee);

        $permission->update(['status' => 'approved', 'pj_status' => 'approved']);

        $this->actingAs($employee)
            ->put(route('izin.update', $permission->id), [
                'jenis' => 'izin pribadi',
                'tanggal' => '2026-10-03',
                'tanggal_selesai' => '2026-10-03',
                'jam_mulai' => '08:00',
                'jam_selesai' => '09:00',
                'alasan' => 'Diubah diam-diam',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $permission->refresh();

        $this->assertSame('approved', $permission->status);
        $this->assertSame('Urusan pribadi', $permission->alasan);
    }

    /* --------------------------------------------------------------------- */
    /* 4. KEAMANAN: HANYA PEMILIK PENGAJUAN                                  */
    /* --------------------------------------------------------------------- */

    public function test_pengguna_lain_tidak_dapat_mengubah_pengajuan(): void
    {
        $employee = $this->makeUser('ipsrs');
        $permission = $this->submitPermission($employee);

        $this->actingAs($this->makeUser('ipsrs'))
            ->put(route('izin.update', $permission->id), [
                'jenis' => 'izin pribadi',
                'tanggal' => '2026-10-04',
                'tanggal_selesai' => '2026-10-04',
                'jam_mulai' => '08:00',
                'jam_selesai' => '09:00',
                'alasan' => 'Bukan milik saya',
            ])
            ->assertStatus(403);

        $this->assertSame('Urusan pribadi', $permission->fresh()->alasan);
    }

    /* --------------------------------------------------------------------- */
    /* 5. REVISI CUTI SETELAH DITOLAK                                        */
    /* --------------------------------------------------------------------- */

    public function test_revisi_cuti_yang_ditolak_dikirim_ulang(): void
    {
        $employee = $this->makeUser('ipsrs');
        $leave = $this->submitLeave($employee);
        $pj = $this->makeUser('pj_ipsrs', 'PJ IPSRS');

        $this->actingAs($pj);
        $this->rejectAsPj('pj.cuti.reject', $leave->id, 'Delegasi tugas belum diisi');

        $this->actingAs($employee)
            ->get(route('cuti.history'))
            ->assertOk()
            ->assertSee('Delegasi tugas belum diisi');

        $this->actingAs($employee)
            ->put(route('cuti.update', $leave->id), [
                'start_date' => '2026-10-05',
                'end_date' => '2026-10-06',
                'leave_type' => 'Tahunan',
                'reason' => 'Keperluan keluarga (revisi)',
                'delegate_name' => 'Rekan Kerja',
                'delegate_nik' => '12345',
                'emergency_contact' => '081200000000',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $leave->refresh();

        $this->assertSame('pending', $leave->status);
        $this->assertSame('pending', $leave->pj_status);
        $this->assertSame(2, (int) $leave->total_days);
        $this->assertSame('2026-10-06', (string) $leave->end_date);
        $this->assertSame('Keperluan keluarga (revisi)', $leave->reason);
        $this->assertSame('Delegasi tugas belum diisi', $leave->pj_note);
    }

    /* --------------------------------------------------------------------- */
    /* 6. REVISI LEMBUR SETELAH DITOLAK (VOLUME JAM DIHITUNG ULANG)          */
    /* --------------------------------------------------------------------- */

    public function test_revisi_lembur_yang_ditolak_dikirim_ulang(): void
    {
        $employee = $this->makeUser('ipsrs');
        $overtime = $this->submitOvertime($employee);
        $pj = $this->makeUser('pj_ipsrs', 'PJ IPSRS');

        $this->actingAs($pj);
        $this->rejectAsPj('pj.lembur.reject', $overtime->id, 'Volume jam tidak sesuai');

        $this->actingAs($employee)
            ->get(route('lembur.history'))
            ->assertOk()
            ->assertSee('Volume jam tidak sesuai');

        $this->actingAs($employee)
            ->put(route('lembur.update', $overtime->id), [
                'overtime_date' => '2026-10-02',
                'start_time' => '18:00',
                'end_time' => '21:00',
                'day_type' => 'hari_kerja',
                'reason' => 'Penyelesaian laporan (revisi)',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $overtime->refresh();

        $this->assertSame('pending', $overtime->status);
        $this->assertSame('pending', $overtime->pj_status);
        // Kolom `total_hours` bertipe integer, jadi 3 jam tersimpan sebagai 3
        // (sebelum revisi nilainya 2 jam dari 17:00-19:00).
        $this->assertSame(3, (int) $overtime->total_hours);
        $this->assertSame('Penyelesaian laporan (revisi)', $overtime->reason);
        $this->assertSame('Volume jam tidak sesuai', $overtime->pj_note);
    }

    /* --------------------------------------------------------------------- */
    /* 7. PENOLAKAN DI TAHAP LANJUT (YANMED) JUGA TAMPIL DI RIWAYAT          */
    /* --------------------------------------------------------------------- */

    public function test_penolakan_tahap_yanmed_tampil_di_menu_riwayat(): void
    {
        $employee = $this->makeUser('gizi');
        $permission = $this->submitPermission($employee);

        // PJ menyetujui -> pengajuan naik ke tahap YANMED.
        $this->actingAs($this->makeUser('pj_gizi'))
            ->post(route('pj.izin.approve', $permission->id), [
                'signature' => 'data:image/png;base64,AAA',
            ])
            ->assertRedirect();

        $this->assertSame('waiting_medical_service', $permission->fresh()->status);

        // Approver YANMED menolak dengan catatan.
        $this->actingAs($this->makeUser('medical_service', 'Kepala YANMED'))
            ->post(route('hrd.izin.reject', $permission->id), [
                'note' => 'Bukan kewenangan unit penunjang',
            ])
            ->assertRedirect();

        $permission->refresh();

        $this->assertSame('rejected', $permission->status);
        $this->assertSame('rejected', $permission->medical_service_status);
        $this->assertSame('Bukan kewenangan unit penunjang', $permission->medical_service_note);

        $this->actingAs($employee)
            ->get(route('history'))
            ->assertOk()
            ->assertSee('Bukan kewenangan unit penunjang')
            ->assertSee('Ditolak YANMED (Penunjang Medis)');

        // Revisi tetap bisa dikirim ulang walaupun ditolak di tahap lanjut.
        $this->actingAs($employee)
            ->put(route('izin.update', $permission->id), [
                'jenis' => 'sakit',
                'tanggal' => '2026-10-07',
                'tanggal_selesai' => '2026-10-07',
                'jam_mulai' => '07:00',
                'jam_selesai' => '09:00',
                'alasan' => 'Kontrol kesehatan (revisi)',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('pending', $permission->fresh()->status);
    }

    /* --------------------------------------------------------------------- */
    /* 8. TOMBOL EDIT HANYA UNTUK PENGAJUAN YANG MASIH BISA DIUBAH           */
    /* --------------------------------------------------------------------- */

    public function test_tombol_edit_hanya_muncul_untuk_status_yang_bisa_diubah(): void
    {
        $employee = $this->makeUser('ipsrs');
        $permission = $this->submitPermission($employee);

        // Status pending -> boleh direvisi.
        $this->actingAs($employee)
            ->get(route('history'))
            ->assertOk()
            ->assertSee('Edit Pengajuan Izin');

        // Sudah disetujui -> form edit tidak dirender.
        $permission->update(['status' => 'approved', 'pj_status' => 'approved']);

        $this->actingAs($employee)
            ->get(route('history'))
            ->assertOk()
            ->assertDontSee('Edit Pengajuan Izin');
    }

    /* --------------------------------------------------------------------- */
    /* 9. PANEL "PENGAJUAN SAYA" DI HALAMAN PENGAJUAN (SISI PENGAJU)         */
    /* --------------------------------------------------------------------- */

    public function test_panel_pengajuan_saya_tampil_di_halaman_izin_cuti_dan_lembur(): void
    {
        $employee = $this->makeUser('ipsrs');

        $permission = $this->submitPermission($employee);
        $leave = $this->submitLeave($employee);
        $overtime = $this->submitOvertime($employee);

        $this->actingAs($this->makeUser('pj_ipsrs', 'PJ IPSRS'));
        $this->rejectAsPj('pj.izin.reject', $permission->id, 'Lampiran izin belum lengkap');
        $this->rejectAsPj('pj.cuti.reject', $leave->id, 'Tanggal cuti bentrok jadwal');
        $this->rejectAsPj('pj.lembur.reject', $overtime->id, 'Lembur belum disetujui kepala ruang');

        // --- Halaman pengajuan izin --------------------------------------
        $this->actingAs($employee)
            ->get(route('izin'))
            ->assertOk()
            ->assertSee('Pengajuan Izin Saya')
            ->assertSee('Edit Pengajuan Izin')
            ->assertSee('Lampiran izin belum lengkap')
            ->assertSee('dapat direvisi');

        // --- Halaman pengajuan cuti --------------------------------------
        $this->actingAs($employee)
            ->get(route('cuti'))
            ->assertOk()
            ->assertSee('Pengajuan Cuti Saya')
            ->assertSee('Edit Pengajuan Cuti')
            ->assertSee('Tanggal cuti bentrok jadwal');

        // --- Halaman pengajuan lembur ------------------------------------
        $this->actingAs($employee)
            ->get(route('lembur'))
            ->assertOk()
            ->assertSee('Pengajuan Lembur Saya')
            ->assertSee('Edit Pengajuan Lembur')
            ->assertSee('Lembur belum disetujui kepala ruang');
    }

    /* --------------------------------------------------------------------- */
    /* 10. KIRIM ULANG DARI HALAMAN PENGAJUAN TANPA LEWAT MENU RIWAYAT       */
    /* --------------------------------------------------------------------- */

    public function test_kirim_ulang_izin_dari_halaman_pengajuan_kembali_ke_halaman_izin(): void
    {
        $employee = $this->makeUser('ipsrs');
        $permission = $this->submitPermission($employee);

        $this->actingAs($this->makeUser('pj_ipsrs', 'PJ IPSRS'));
        $this->rejectAsPj('pj.izin.reject', $permission->id, 'Alasan kurang jelas');

        $this->actingAs($employee)
            ->from(route('izin'))
            ->put(route('izin.update', $permission->id), [
                'jenis' => 'izin pribadi',
                'tanggal' => '2026-10-08',
                'tanggal_selesai' => '2026-10-08',
                'jam_mulai' => '09:00',
                'jam_selesai' => '11:00',
                'alasan' => 'Urusan pribadi (revisi dari halaman izin)',
            ])
            ->assertRedirect(route('izin'))
            ->assertSessionHas('success');

        $permission->refresh();

        $this->assertSame('pending', $permission->status);
        $this->assertSame('2026-10-08', (string) $permission->tanggal);
        // Catatan penolakan lama tidak hilang setelah dikirim ulang.
        $this->assertSame('Alasan kurang jelas', $permission->pj_note);

        // Pesan sukses tampil di halaman pengajuan (tanpa perlu buka Riwayat).
        $this->actingAs($employee)
            ->get(route('izin'))
            ->assertOk()
            ->assertSee('dikirim ulang untuk persetujuan');
    }

    public function test_kirim_ulang_cuti_dan_lembur_dari_halaman_pengajuan(): void
    {
        $employee = $this->makeUser('ipsrs');

        $leave = $this->submitLeave($employee);
        $overtime = $this->submitOvertime($employee);

        $this->actingAs($this->makeUser('pj_ipsrs', 'PJ IPSRS'));
        $this->rejectAsPj('pj.cuti.reject', $leave->id, 'Perlu surat tugas');
        $this->rejectAsPj('pj.lembur.reject', $overtime->id, 'Volume jam berlebih');

        // --- Cuti ---------------------------------------------------------
        $this->actingAs($employee)
            ->from(route('cuti'))
            ->put(route('cuti.update', $leave->id), [
                'start_date' => '2026-10-09',
                'end_date' => '2026-10-10',
                'leave_type' => 'Tahunan',
                'reason' => 'Keperluan keluarga (revisi)',
                'delegate_name' => 'Rekan Kerja',
                'delegate_nik' => '12345',
            ])
            ->assertRedirect(route('cuti'))
            ->assertSessionHas('success');

        $leave->refresh();

        $this->assertSame('pending', $leave->status);
        $this->assertSame('2026-10-10', (string) $leave->end_date);
        $this->assertSame('Perlu surat tugas', $leave->pj_note);

        // --- Lembur -------------------------------------------------------
        $this->actingAs($employee)
            ->from(route('lembur'))
            ->put(route('lembur.update', $overtime->id), [
                'overtime_date' => '2026-10-03',
                'start_time' => '17:00',
                'end_time' => '20:00',
                'day_type' => 'hari_kerja',
                'reason' => 'Penyelesaian pekerjaan (revisi)',
            ])
            ->assertRedirect(route('lembur'))
            ->assertSessionHas('success');

        $overtime->refresh();

        $this->assertSame('pending', $overtime->status);
        $this->assertSame('Volume jam berlebih', $overtime->pj_note);
    }

    /* --------------------------------------------------------------------- */
    /* 11. PANEL PENGAJUAN SAYA TIDAK BOCOR & HORMATI ATURAN EDIT            */
    /* --------------------------------------------------------------------- */

    public function test_panel_pengajuan_saya_tidak_menampilkan_pengajuan_user_lain(): void
    {
        $other = $this->makeUser('ipsrs');
        $this->submitPermission($other, ['alasan' => 'Keperluan pribadi user lain']);

        $this->actingAs($this->makeUser('ipsrs'))
            ->get(route('izin'))
            ->assertOk()
            ->assertSee('Belum ada pengajuan izin')
            ->assertDontSee('Keperluan pribadi user lain')
            ->assertDontSee('Edit Pengajuan Izin');
    }

    public function test_panel_pengajuan_saya_tidak_menawarkan_edit_untuk_status_disetujui(): void
    {
        $employee = $this->makeUser('ipsrs');
        $permission = $this->submitPermission($employee);

        // Status pending -> boleh direvisi dari halaman pengajuan.
        $this->actingAs($employee)
            ->get(route('izin'))
            ->assertOk()
            ->assertSee('Menunggu Verifikasi')
            ->assertSee('Edit Pengajuan Izin');

        // Sudah disetujui -> hanya menampilkan status, tanpa form edit.
        $permission->update(['status' => 'approved', 'pj_status' => 'approved']);

        $this->actingAs($employee)
            ->get(route('izin'))
            ->assertOk()
            ->assertSee('Pengajuan Izin Saya')
            ->assertSee('Disetujui')
            ->assertDontSee('Edit Pengajuan Izin');
    }
}

