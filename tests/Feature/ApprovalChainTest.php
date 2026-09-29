<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Permission;
use App\Models\User;
use App\Services\ApprovalFlowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Verifikasi rantai approval struktur baru:
 *
 *   YANMED    : ok/pipp/ro/rm/gizi/nurse/...     -> PJ -> YANMED (final)
 *   UMUM      : it/security/cs/ipsrs             -> PJ -> Kabag Umum -> Manager Umum
 *   MARKETING : admission/kasir/marketing        -> PJ -> Kabag Marketing -> Manager Umum
 *   FINANCE   : accounting/finance               -> PJ -> Manager Finance (final)
 *   DIREKTUR  : casemix (via PJ)/supervisor/sekre/...   -> [PJ] -> Direktur (final)
 *   HRD       : hrd                                     -> Manager Umum -> Direktur (final, tanpa PJ)
 *
 * Selain itu diuji pula pembatasan akses laporan rekap HRD (HRD & Direktur saja)
 * serta pusat approval (role approver saja).
 */
class ApprovalChainTest extends TestCase
{
    use DatabaseTransactions;

    /*
    |--------------------------------------------------------------------------
    | HELPER
    |--------------------------------------------------------------------------
    */
    private function makeUser(string $role, ?string $name = null): User
    {
        return User::create([
            'name' => $name ?? 'User ' . $role . ' ' . uniqid(),
            'email' => 'chain-' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => $role,
            'work_type' => 'office_5',
            'email_verified_at' => now(),
            'is_approved' => 1,
            // diset eksplisit: instance in-memory tidak memuat default DB
            'leave_quota' => 12,
        ]);
    }

    private function submitLeave(User $user): Leave
    {
        $this->actingAs($user)
            ->post(route('cuti.store'), [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-02',
                'leave_type' => 'cuti tahunan',
                'reason' => 'Keperluan keluarga',
                'employee_signature' => 'data:image/png;base64,AAA',
            ])
            ->assertRedirect('/dashboard');

        return Leave::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    private function submitPermission(User $user): Permission
    {
        $this->actingAs($user)
            ->post(route('izin.store'), [
                'jenis' => 'izin pribadi',
                'tanggal' => '2026-10-01',
                'tanggal_selesai' => '2026-10-01',
                'jam_mulai' => '10:00',
                'jam_selesai' => '12:00',
                'alasan' => 'Urusan pribadi',
            ])
            ->assertRedirect('/dashboard');

        return Permission::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    private function pjApprove(string $routeName, int $id): void
    {
        $this->post(route($routeName, $id), [
            'signature' => 'data:image/png;base64,AAA',
        ])->assertRedirect();
    }

    private function stageApprove(string $routeName, int $id): void
    {
        $this->post(route($routeName, $id), [
            'signature' => 'data:image/png;base64,AAA',
        ])->assertRedirect();
    }

    /*
    |--------------------------------------------------------------------------
    | 1. MAPPING GRUP & RANTAI (level service)
    |--------------------------------------------------------------------------
    */
    public function test_mapping_grup_dan_rantai_approval(): void
    {
        // YANMED
        $this->assertSame(['medical_service'], ApprovalFlowService::chainFor('nurse'));
        $this->assertSame(['medical_service'], ApprovalFlowService::chainFor('pj_nurse'));
        $this->assertSame(['medical_service'], ApprovalFlowService::chainFor('nutrition'));
        $this->assertSame(['medical_service'], ApprovalFlowService::chainFor('medical_record'));

        // UMUM
        $this->assertSame(['kabag_umum', 'manager_umum'], ApprovalFlowService::chainFor('it'));
        $this->assertSame(['kabag_umum', 'manager_umum'], ApprovalFlowService::chainFor('pj_cs'));

        // MARKETING / ADMISSION
        $this->assertSame(['kabag_marketing', 'manager_umum'], ApprovalFlowService::chainFor('admission'));
        $this->assertSame(['kabag_marketing', 'manager_umum'], ApprovalFlowService::chainFor('kasir'));

        // FINANCE
        $this->assertSame(['manager_finance'], ApprovalFlowService::chainFor('accounting'));
        $this->assertSame(['manager_finance'], ApprovalFlowService::chainFor('pj_finance'));

        // DIREKTUR
        $this->assertSame(['director'], ApprovalFlowService::chainFor('casemix'));

        // HRD: Manager Umum -> Direktur (tanpa PJ)
        $this->assertSame(['manager_umum', 'director'], ApprovalFlowService::chainFor('hrd'));
        $this->assertSame('hrd', ApprovalFlowService::groupFor('hrd'));
        $this->assertSame('waiting_manager_umum', ApprovalFlowService::initialStatus('hrd'));
        $this->assertSame('waiting_manager_umum', ApprovalFlowService::handle('hrd')['status']);

        // SEKRETARIAT (sekre): approval hanya direktur, tanpa PJ
        $this->assertSame(['director'], ApprovalFlowService::chainFor('sekre'));
        $this->assertSame(['director'], ApprovalFlowService::chainFor('sekretariat'));
        $this->assertFalse(ApprovalFlowService::needsPjApproval('sekre'));
        $this->assertFalse(ApprovalFlowService::needsPjApproval('sekretariat'));

        // SUPERVISOR: approval hanya direktur, tanpa PJ
        $this->assertSame(['director'], ApprovalFlowService::chainFor('supervisor'));
        $this->assertSame('direktur', ApprovalFlowService::groupFor('supervisor'));
        $this->assertContains('supervisor', ApprovalFlowService::rolesInGroup('direktur'));
        $this->assertFalse(ApprovalFlowService::needsPjApproval('supervisor'));
        $this->assertSame('waiting_director', ApprovalFlowService::initialStatus('supervisor'));
        $this->assertSame('waiting_director', ApprovalFlowService::handle('supervisor')['status']);
        $this->assertSame('approved', ApprovalFlowService::handle('supervisor')['pj_status']);

        $flow = ApprovalFlowService::handle('supervisor');
        $this->assertSame('pending', $flow['director_status']);
        $this->assertContains('supervisor', ApprovalFlowService::assignableRoles());
        $this->assertSame('SUPERVISOR', $this->makeUser('supervisor')->role_label);

        // PJ dilewati untuk role PJ & role atasan
        $this->assertFalse(ApprovalFlowService::needsPjApproval('pj_nurse'));
        $this->assertFalse(ApprovalFlowService::needsPjApproval('hrd'));
        $this->assertFalse(ApprovalFlowService::needsPjApproval('medical_service'));
        $this->assertTrue(ApprovalFlowService::needsPjApproval('nurse'));
        $this->assertTrue(ApprovalFlowService::needsPjApproval('security'));

        // Helper divisi PJ
        $this->assertContains('nurse', ApprovalFlowService::divisionRoles('pj_nurse'));
        $this->assertContains('nutrition', ApprovalFlowService::divisionRoles('pj_gizi'));
        $this->assertContains('medical_record', ApprovalFlowService::divisionRoles('pj_rm'));
        $this->assertContains('kasir', ApprovalFlowService::divisionRoles('pj_admission'));

        // Label status
        $this->assertSame('Waiting Kabag Umum', ApprovalFlowService::statusLabel('waiting_kabag_umum'));
        $this->assertSame('Waiting YANMED', ApprovalFlowService::statusLabel('waiting_medical_service'));
    }

    /*
    |--------------------------------------------------------------------------
    | 2. GRUP YANMED : nurse -> pj_nurse -> YANMED (final)
    |--------------------------------------------------------------------------
    */
    public function test_alur_yanmed_nurse_final_ke_yanmed(): void
    {
        $staff = $this->makeUser('nurse');
        $leave = $this->submitLeave($staff);

        $this->assertSame('pending', $leave->status);
        $this->assertSame('pending', $leave->pj_status);

        $pj = $this->makeUser('pj_nurse');
        $this->actingAs($pj);
        $this->pjApprove('pj.cuti.approve', $leave->id);

        $leave->refresh();
        $this->assertSame('approved', $leave->pj_status);
        $this->assertSame('waiting_medical_service', $leave->status);
        $this->assertSame('pending', $leave->medical_service_status);

        // YANMED melihat pengajuan di pusat approval
        $medical_service = $this->makeUser('medical_service');
        $this->actingAs($medical_service)->get('/hrd/cuti')->assertOk()->assertSee($staff->name);

        $this->stageApprove('hrd.cuti.approve', $leave->id);

        $leave->refresh();
        $this->assertSame('approved', $leave->medical_service_status);
        $this->assertSame('approved', $leave->status);
    }

    /*
    |--------------------------------------------------------------------------
    | 3. GRUP UMUM : security -> pj_security -> kabag_umum -> manager_umum
    |--------------------------------------------------------------------------
    */
    public function test_alur_umum_security_kabag_umum_lalu_manager_umum(): void
    {
        $staff = $this->makeUser('security');
        $leave = $this->submitLeave($staff);

        $this->actingAs($this->makeUser('pj_security'));
        $this->pjApprove('pj.cuti.approve', $leave->id);

        $leave->refresh();
        $this->assertSame('waiting_kabag_umum', $leave->status);

        // Kabag Umum menyetujui -> lanjut ke Manager Umum (bukan final)
        $kabag = $this->makeUser('kabag_umum');
        $this->actingAs($kabag)->get('/hrd/cuti')->assertOk()->assertSee($staff->name);
        $this->stageApprove('hrd.cuti.approve', $leave->id);

        $leave->refresh();
        $this->assertSame('approved', $leave->kabag_umum_status);
        $this->assertSame('waiting_manager_umum', $leave->status);
        $this->assertNotSame('approved', $leave->status);

        // Manager Umum menyetujui -> final
        $manager = $this->makeUser('manager_umum');
        $this->actingAs($manager)->get('/hrd/cuti')->assertOk()->assertSee($staff->name);
        $this->stageApprove('hrd.cuti.approve', $leave->id);

        $leave->refresh();
        $this->assertSame('approved', $leave->manager_umum_status);
        $this->assertSame('approved', $leave->status);
    }

    /*
    |--------------------------------------------------------------------------
    | 4. GRUP MARKETING : kasir -> pj_admission -> kabag_marketing -> manager_umum
    |--------------------------------------------------------------------------
    */
    public function test_alur_marketing_kasir_kabag_marketing_lalu_manager_umum(): void
    {
        $staff = $this->makeUser('kasir');
        $permission = $this->submitPermission($staff);

        $this->assertSame('pending', $permission->status);

        $this->actingAs($this->makeUser('pj_admission'));
        $this->pjApprove('pj.izin.approve', $permission->id);

        $permission->refresh();
        $this->assertSame('waiting_kabag_marketing', $permission->status);

        $kabag = $this->makeUser('kabag_marketing');
        $this->actingAs($kabag)->get('/hrd/izin')->assertOk()->assertSee($staff->name);
        $this->stageApprove('hrd.izin.approve', $permission->id);

        $permission->refresh();
        $this->assertSame('approved', $permission->kabag_marketing_status);
        $this->assertSame('waiting_manager_umum', $permission->status);

        $this->actingAs($this->makeUser('manager_umum'));
        $this->stageApprove('hrd.izin.approve', $permission->id);

        $permission->refresh();
        $this->assertSame('approved', $permission->manager_umum_status);
        $this->assertSame('approved', $permission->status);
    }

    /*
    |--------------------------------------------------------------------------
    | 5. GRUP FINANCE : finance -> pj_finance -> manager_finance (final)
    |--------------------------------------------------------------------------
    */
    public function test_alur_finance_final_ke_manager_finance(): void
    {
        $staff = $this->makeUser('finance');
        $leave = $this->submitLeave($staff);

        $this->actingAs($this->makeUser('pj_finance'));
        $this->pjApprove('pj.cuti.approve', $leave->id);

        $leave->refresh();
        $this->assertSame('waiting_manager_finance', $leave->status);

        $manager = $this->makeUser('manager_finance');
        $this->actingAs($manager)->get('/hrd/cuti')->assertOk()->assertSee($staff->name);
        $this->stageApprove('hrd.cuti.approve', $leave->id);

        $leave->refresh();
        $this->assertSame('approved', $leave->manager_finance_status);
        $this->assertSame('approved', $leave->status);
    }

    /*
    |--------------------------------------------------------------------------
    | 6. GRUP DIREKTUR : casemix (via PJ) -> Direktur
    |--------------------------------------------------------------------------
    */
    public function test_alur_direktur_untuk_casemix(): void
    {
        $staff = $this->makeUser('casemix');
        $leave = $this->submitLeave($staff);

        $this->actingAs($this->makeUser('pj_casemix'));
        $this->pjApprove('pj.cuti.approve', $leave->id);

        $leave->refresh();
        $this->assertSame('waiting_director', $leave->status);

        $this->actingAs($this->makeUser('director'));
        $this->stageApprove('hrd.cuti.approve', $leave->id);

        $leave->refresh();
        $this->assertSame('approved', $leave->director_status);
        $this->assertSame('approved', $leave->status);
    }

    /*
    |--------------------------------------------------------------------------
    | 6b. GRUP HRD : hrd (tanpa PJ) -> Manager Umum -> Direktur
    |--------------------------------------------------------------------------
    */
    public function test_alur_hrd_ke_manager_umum_lalu_direktur_tanpa_tahap_pj(): void
    {
        $hrd = $this->makeUser('hrd');
        $leave = $this->submitLeave($hrd);

        // HRD tidak melewati tahap PJ: langsung ke Manager Umum
        $this->assertSame('approved', $leave->pj_status);
        $this->assertSame('waiting_manager_umum', $leave->status);
        $this->assertSame('pending', $leave->manager_umum_status);

        // Manager Umum menyetujui -> lanjut ke Direktur
        $this->actingAs($this->makeUser('manager_umum'));
        $this->stageApprove('hrd.cuti.approve', $leave->id);

        $leave->refresh();
        $this->assertSame('approved', $leave->manager_umum_status);
        $this->assertSame('waiting_director', $leave->status);

        // Direktur menyetujui -> final
        $this->actingAs($this->makeUser('director'));
        $this->stageApprove('hrd.cuti.approve', $leave->id);

        $leave->refresh();
        $this->assertSame('approved', $leave->director_status);
        $this->assertSame('approved', $leave->status);
    }

    /*
    |--------------------------------------------------------------------------
    | 6c. GRUP DIREKTUR : supervisor (tanpa PJ) -> Direktur
    |--------------------------------------------------------------------------
    */
    public function test_alur_direktur_untuk_supervisor_tanpa_tahap_pj(): void
    {
        $supervisor = $this->makeUser('supervisor');
        $leave = $this->submitLeave($supervisor);

        // Supervisor tidak melewati tahap PJ: langsung ke Direktur
        $this->assertSame('approved', $leave->pj_status);
        $this->assertSame('waiting_director', $leave->status);

        // Supervisor bukan tanggung jawab PJ mana pun
        $this->actingAs($this->makeUser('pj_it'))
            ->get('/pj/dashboard')
            ->assertOk()
            ->assertDontSee($supervisor->name);

        // Direktur melihat pengajuan pada tahapnya lalu menyetujui (tahap final)
        $this->actingAs($this->makeUser('director'))
            ->get('/hrd/cuti')
            ->assertOk()
            ->assertSee($supervisor->name);

        $this->stageApprove('hrd.cuti.approve', $leave->id);

        $leave->refresh();
        $this->assertSame('approved', $leave->director_status);
        $this->assertSame('approved', $leave->status);

        // Supervisor bukan approver: pusat approval tetap tertutup
        $this->actingAs($this->makeUser('supervisor'))
            ->get('/hrd/dashboard')
            ->assertStatus(403);
    }


    /*
    |--------------------------------------------------------------------------
    | 7. KOLOM STATUS STAGE TERSIMPAN DI SEMUA TABEL PENGajuan
    |--------------------------------------------------------------------------
    */
    public function test_kolom_status_stage_tersimpan_di_semua_tabel(): void
    {
        $flow = ApprovalFlowService::handle('security');

        $leave = new Leave();
        $leave->forceFill($flow);
        $this->assertSame('pending', $leave->kabag_umum_status);

        // overtimes.status semula ENUM sempit -> harus menerima status baru
        $overtime = Overtime::create([
            'user_id' => $this->makeUser('security')->id,
            'department' => 'security',
            'day_type' => 'weekday',
            'overtime_date' => '2026-10-01',
            'start_time' => '17:00',
            'end_time' => '19:00',
            'total_hours' => 2,
            'reason' => 'Pekerjaan tambahan',
            'status' => 'waiting_kabag_umum',
            'pj_status' => 'approved',
        ]);

        $overtime->refresh();
        $this->assertSame('waiting_kabag_umum', $overtime->status);

        $overtime->update(['status' => 'waiting_manager_finance']);
        $this->assertSame('waiting_manager_finance', $overtime->fresh()->status);
    }

    /*
    |--------------------------------------------------------------------------
    | 8. AKSES LAPORAN REKAP HRD : hanya HRD & Direktur
    |--------------------------------------------------------------------------
    */
    public function test_laporan_rekap_hrd_hanya_hrd_dan_direktur(): void
    {
        $this->actingAs($this->makeUser('hrd'))
            ->get('/hrd/rekap')
            ->assertOk();

        // Direktur boleh (halaman wajib tidak 403; isi laporan tidak diuji di sini)
        $this->assertNotSame(
            403,
            $this->actingAs($this->makeUser('director'))->get('/hrd/rekap')->getStatusCode()
        );

        // Approver & role lain ditolak
        foreach (['medical_service', 'kabag_umum', 'manager_umum', 'kabag_marketing', 'manager_finance'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get('/hrd/rekap')
                ->assertStatus(403);
        }

        $this->actingAs($this->makeUser('nurse'))
            ->get('/hrd/rekap')
            ->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | 9. PUSAT APPROVAL : hanya role approver, HRD tidak lagi di dalamnya
    |--------------------------------------------------------------------------
    */
    public function test_pusat_approval_hanya_untuk_role_approver(): void
    {
        foreach (['medical_service', 'kabag_umum', 'manager_umum', 'kabag_marketing', 'manager_finance', 'director'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get('/hrd/dashboard')
                ->assertOk();
        }

        $this->actingAs($this->makeUser('hrd'))
            ->get('/hrd/dashboard')
            ->assertStatus(403);

        $this->actingAs($this->makeUser('nurse'))
            ->get('/hrd/dashboard')
            ->assertStatus(403);
    }
}
