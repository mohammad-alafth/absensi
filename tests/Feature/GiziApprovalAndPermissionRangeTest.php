<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * 1. Alur approval role gizi (nutrition): pj_nurse -> hrd.
 * 2. Form izin: tanggal mulai & tanggal selesai; jam wajib hanya bila izin 1 hari,
 *    izin lebih dari 1 hari tidak menyimpan jam.
 */
class GiziApprovalAndPermissionRangeTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'User Test ' . uniqid(),
            'email' => 'test-' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'nutrition',
            'work_type' => 'office_6',
            'email_verified_at' => now(),
        ], $overrides));
    }

    private function makePermission(User $user): Permission
    {
        return Permission::create([
            'user_id' => $user->id,
            'tanggal' => '2026-09-20',
            'jenis' => 'izin pribadi',
            'status' => 'pending',
            'pj_status' => 'pending',
            'hrd_status' => 'pending',
            'alasan' => 'Urusan pribadi',
        ]);
    }

    public function test_gizi_izin_muncul_di_daftar_pj_nurse(): void
    {
        $gizi = $this->makeUser();
        $permission = $this->makePermission($gizi);

        $pj = $this->makeUser([
            'role' => 'pj_nurse',
            'name' => 'PJ Nurse ' . uniqid(),
        ]);

        $this->actingAs($pj)
            ->get(route('pj.izin'))
            ->assertOk()
            ->assertSee($gizi->name);
    }

    public function test_alur_approval_gizi_pj_nurse_lalu_hrd(): void
    {
        $gizi = $this->makeUser();
        $permission = $this->makePermission($gizi);

        // Langkah 1: PJ Nurse menyetujui
        $pj = $this->makeUser([
            'role' => 'pj_nurse',
            'name' => 'PJ Nurse ' . uniqid(),
        ]);

        $this->actingAs($pj)
            ->post(route('pj.izin.approve', $permission->id), [
                'signature' => 'data:image/png;base64,AAA',
            ])
            ->assertRedirect();

        $permission->refresh();
        $this->assertSame('approved', $permission->pj_status);
        $this->assertSame('waiting_hrd', $permission->status);
        $this->assertSame('pending', $permission->hrd_status);

        // Izin gizi muncul di daftar menunggu HRD
        // (route 'izin' ambigu dengan form user, pakai path HRD eksplisit)
        $hrd = $this->makeUser([
            'role' => 'hrd',
            'name' => 'HRD Approver ' . uniqid(),
        ]);

        $this->actingAs($hrd)
            ->get('/hrd/izin')
            ->assertOk()
            ->assertSee($gizi->name);

        // Langkah 2: HRD menyetujui final
        $this->actingAs($hrd)
            ->post(route('hrd.izin.approve', $permission->id), [
                'signature' => 'data:image/png;base64,AAA',
            ])
            ->assertRedirect();

        $permission->refresh();
        $this->assertSame('approved', $permission->hrd_status);
        $this->assertSame('approved', $permission->status);
    }

    public function test_izin_lebih_dari_satu_hari_tidak_menyimpan_jam(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('izin.store'), [
                'jenis' => 'keperluan keluarga',
                'tanggal' => '2026-09-21',
                'tanggal_selesai' => '2026-09-23',
                'alasan' => 'Acara keluarga di luar kota',
            ])
            ->assertRedirect('/dashboard');

        $permission = Permission::where('user_id', $user->id)->latest()->first();

        $this->assertNotNull($permission);
        $this->assertSame(
            '2026-09-21',
            Carbon::parse($permission->tanggal)->format('Y-m-d')
        );
        $this->assertSame(
            '2026-09-23',
            Carbon::parse($permission->tanggal_selesai)->format('Y-m-d')
        );
        $this->assertNull($permission->jam_mulai);
        $this->assertNull($permission->jam_selesai);
    }

    public function test_izin_satu_hari_tanpa_jam_gagal_validasi(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('izin.store'), [
                'jenis' => 'izin pribadi',
                'tanggal' => '2026-09-21',
                'alasan' => 'Urusan pribadi',
            ])
            ->assertSessionHasErrors(['jam_mulai', 'jam_selesai']);
    }

    public function test_izin_satu_hari_dengan_jam_tersimpan(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('izin.store'), [
                'jenis' => 'pulang lebih awal',
                'tanggal' => '2026-09-21',
                'tanggal_selesai' => '2026-09-21',
                'jam_mulai' => '13:00',
                'jam_selesai' => '17:00',
                'alasan' => 'Mengantar keluarga',
            ])
            ->assertRedirect('/dashboard');

        $permission = Permission::where('user_id', $user->id)->latest()->first();

        $this->assertNotNull($permission);
        $this->assertNotNull($permission->jam_mulai);
        $this->assertNotNull($permission->jam_selesai);
        $this->assertSame(
            '2026-09-21',
            Carbon::parse($permission->tanggal_selesai)->format('Y-m-d')
        );
    }
}
