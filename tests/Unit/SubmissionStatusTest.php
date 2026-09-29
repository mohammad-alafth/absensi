<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\User;
use App\Support\SubmissionStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Logika pembacaan status & catatan approval (tanpa HTTP) yang menjadi dasar
 * komponen x-approval-notes, x-rejection-banner, dan x-submission-edit-form.
 */
class SubmissionStatusTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        SubmissionStatus::flushApproverNames();
    }

    private function permission(array $attributes): Permission
    {
        $permission = new Permission();
        $permission->forceFill($attributes);

        return $permission;
    }

    public function test_daftar_stage_memuat_semua_tahap_approval(): void
    {
        $stages = SubmissionStatus::stages();

        foreach ([
            'pj',
            'hrd',
            'head',
            'medical_service',
            'kabag_umum',
            'manager_umum',
            'kabag_marketing',
            'manager_finance',
            'director',
        ] as $stage) {
            $this->assertArrayHasKey($stage, $stages, "Stage {$stage} harus tersedia");
        }

        $this->assertSame('Direktur', $stages['director']);
        $this->assertSame('Penanggung Jawab (PJ)', $stages['pj']);
    }

    public function test_nama_relasi_approver_mengikuti_konvensi_stage(): void
    {
        $this->assertSame('pjApprover', SubmissionStatus::relationFor('pj'));
        $this->assertSame('directorApprover', SubmissionStatus::relationFor('director'));
        $this->assertSame('managerFinanceApprover', SubmissionStatus::relationFor('manager_finance'));
    }

    public function test_catatan_penolakan_dikenali_dari_status_stage(): void
    {
        $permission = $this->permission([
            'status' => 'rejected',
            'pj_status' => 'rejected',
            'pj_note' => 'Surat dokter belum dilampirkan',
        ]);

        $rejections = SubmissionStatus::rejections($permission);

        $this->assertCount(1, $rejections);
        $this->assertTrue($rejections[0]['rejected']);
        $this->assertSame('pj', $rejections[0]['stage']);
        $this->assertSame('Surat dokter belum dilampirkan', $rejections[0]['note']);
        $this->assertTrue(SubmissionStatus::hasRejection($permission));
    }

    public function test_penolakan_tanpa_catatan_tertulis_tetap_ditampilkan(): void
    {
        $permission = $this->permission([
            'status' => 'rejected',
            'kabag_umum_status' => 'rejected',
            'kabag_umum_note' => null,
        ]);

        $rejections = SubmissionStatus::rejections($permission);

        $this->assertCount(1, $rejections);
        $this->assertNull($rejections[0]['note']);
        $this->assertSame('Kabag Umum', $rejections[0]['label']);
    }

    public function test_catatan_setelah_kirim_ulang_ditandai_penolakan_sebelumnya(): void
    {
        // Kondisi setelah revisi: flow sudah direset (status pending) tetapi
        // catatan + approver dari siklus sebelumnya masih tersimpan.
        $permission = $this->permission([
            'status' => 'pending',
            'pj_status' => 'pending',
            'pj_note' => 'Berkas kurang lengkap',
            'pj_approved_by' => 999,
        ]);

        $notes = SubmissionStatus::notes($permission);

        $this->assertCount(1, $notes);
        $this->assertFalse($notes[0]['rejected']);
        $this->assertTrue($notes[0]['revision']);
    }

    public function test_catatan_persetujuan_biasa_bukan_penolakan(): void
    {
        $permission = $this->permission([
            'status' => 'waiting_director',
            'pj_status' => 'approved',
            'pj_note' => 'Sudah diverifikasi PJ',
            'pj_approved_by' => 999,
        ]);

        $notes = SubmissionStatus::notes($permission);

        $this->assertCount(1, $notes);
        $this->assertFalse($notes[0]['rejected']);
        $this->assertFalse($notes[0]['revision']);
        $this->assertFalse(SubmissionStatus::hasRejection($permission));
    }

    public function test_penolakan_ditampilkan_lebih_dulu_daripada_catatan_lain(): void
    {
        $permission = $this->permission([
            'status' => 'waiting_director',
            'pj_status' => 'approved',
            'pj_note' => 'Sudah diverifikasi PJ',
            'kabag_umum_status' => 'approved',
            'kabag_umum_note' => 'Diteruskan ke manager',
            'director_status' => 'rejected',
            'director_note' => 'Volume lembur tidak sesuai',
        ]);

        $notes = SubmissionStatus::notes($permission);

        $this->assertSame('director', $notes[0]['stage']);
        $this->assertTrue($notes[0]['rejected']);
        $this->assertCount(3, $notes);
    }

    public function test_status_yang_bisa_diubah_mencakup_pending_waiting_dan_rejected(): void
    {
        $this->assertTrue(SubmissionStatus::isEditable($this->permission(['status' => 'pending'])));
        $this->assertTrue(SubmissionStatus::isEditable($this->permission(['status' => 'rejected'])));

        // Status yang sedang dalam proses verifikasi (waiting_*) tetap boleh diedit
        foreach (['waiting_director', 'waiting_kabag_umum', 'waiting_medical_service'] as $status) {
            $this->assertTrue(
                SubmissionStatus::isEditable($this->permission(['status' => $status])),
                "Status {$status} seharusnya bisa diubah karena belum final disetujui"
            );
        }

        // Hanya approved yang tidak boleh diubah
        $this->assertFalse(
            SubmissionStatus::isEditable($this->permission(['status' => 'approved'])),
            "Status approved tidak boleh bisa diubah"
        );

        $this->assertFalse(SubmissionStatus::isEditable(null));
    }

    public function test_nama_approver_diambil_sekali_dan_di_cache(): void
    {
        $approver = User::create([
            'name' => 'Kabag Umum Uji',
            'email' => 'kabag-' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'kabag_umum',
            'work_type' => 'office_5',
            'email_verified_at' => now(),
            'is_approved' => 1,
            'leave_quota' => 12,
        ]);

        $permission = $this->permission([
            'status' => 'approved',
            'kabag_umum_status' => 'approved',
            'kabag_umum_note' => 'Disetujui',
            'kabag_umum_approved_by' => $approver->id,
        ]);

        SubmissionStatus::primeApproverNames([$permission]);

        $notes = SubmissionStatus::notes($permission);

        $this->assertSame('Kabag Umum Uji', $notes[0]['approver']);
        $this->assertSame('Kabag Umum Uji', SubmissionStatus::approverName($approver->id));

        SubmissionStatus::flushApproverNames();

        $this->assertSame('Kabag Umum Uji', SubmissionStatus::approverName($approver->id));
    }

    public function test_label_dan_warna_badge_status_pengajuan(): void
    {
        // Status global dikenal
        $this->assertSame('Menunggu Verifikasi', SubmissionStatus::statusLabel($this->permission(['status' => 'pending'])));
        $this->assertSame('Disetujui', SubmissionStatus::statusLabel($this->permission(['status' => 'approved'])));
        $this->assertSame('Ditolak', SubmissionStatus::statusLabel($this->permission(['status' => 'rejected'])));

        // Status per tahap (waiting_*) tetap dibaca sebagai menunggu verifikasi
        $this->assertSame('Menunggu Verifikasi', SubmissionStatus::statusLabel($this->permission(['status' => 'waiting_director'])));

        // Pengajuan tidak dikenal tetap aman
        $this->assertSame('-', SubmissionStatus::statusLabel(null));

        $this->assertStringContainsString('red', SubmissionStatus::statusTone($this->permission(['status' => 'rejected'])));
        $this->assertStringContainsString('emerald', SubmissionStatus::statusTone($this->permission(['status' => 'approved'])));
        $this->assertStringContainsString('amber', SubmissionStatus::statusTone($this->permission(['status' => 'pending'])));
        $this->assertStringContainsString('amber', SubmissionStatus::statusTone($this->permission(['status' => 'waiting_kabag_umum'])));
        $this->assertStringContainsString('amber', SubmissionStatus::statusTone(null));
    }
}
