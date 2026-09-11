<?php

namespace Tests\Feature;

use App\Models\EmployeeShift;
use App\Models\Shift;
use App\Models\ShiftChangeRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ShiftChangeOffRequestTest extends TestCase
{
    use DatabaseTransactions;

    private function makeEmployee(): User
    {
        return User::create([
            'name' => 'Karyawan Shift Off Test',
            'email' => 'sc-off-' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'nurse',
            'work_type' => 'shift',
            'email_verified_at' => now(),
        ]);
    }

    private function makePj(): User
    {
        return User::create([
            'name' => 'PJ Nurse Off Test',
            'email' => 'sc-pj-' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'pj_nurse',
            'work_type' => 'office_5',
            'email_verified_at' => now(),
        ]);
    }

    public function test_employee_can_request_off_and_pj_approval_removes_schedule(): void
    {
        $employee = $this->makeEmployee();
        $pj = $this->makePj();

        $shift = Shift::create([
            'name' => 'Pagi (Off Test)',
            'start_time' => '07:00:00',
            'end_time' => '15:00:00',
            'allowed_roles' => ['nurse', 'pj_nurse'],
        ]);

        $targetDate = Carbon::tomorrow()->format('Y-m-d');

        // Karyawan punya jadwal shift di tanggal tsb
        EmployeeShift::create([
            'user_id' => $employee->id,
            'shift_id' => $shift->id,
            'shift_date' => $targetDate,
        ]);

        // 1. Opsi "Libur / Tidak Ada Shift" tampil di form pengajuan
        $this->actingAs($employee)
            ->get(route('shift-change.create'))
            ->assertOk()
            ->assertSee('Hari Libur / Tidak Ada Shift (Jadwal Kosong)');

        // 2. Karyawan mengajukan libur / tidak ada shift (value off)
        $this->actingAs($employee)
            ->post(route('shift-change.store'), [
                'shift_date' => $targetDate,
                'requested_shift_id' => 'off',
                'reason' => 'Test pengajuan libur / jadwal kosong pada tanggal tersebut.',
            ])
            ->assertRedirect(route('shift-change.history'));

        $request = ShiftChangeRequest::where('user_id', $employee->id)
            ->whereDate('shift_date', $targetDate)
            ->first();

        $this->assertNotNull($request);
        $this->assertNull($request->requested_shift_id, 'requested_shift_id harus NULL untuk pengajuan libur.');
        $this->assertEquals($shift->id, $request->current_shift_id);
        $this->assertEquals('pending', $request->status);

        // 3. Riwayat karyawan menampilkan libur / tidak ada shift
        $this->actingAs($employee)
            ->get(route('shift-change.history'))
            ->assertOk()
            ->assertSee('Hari Libur / Tidak Ada Shift');

        // 4. Halaman approval PJ tetap tampil tanpa error (requestedShift null)
        $this->actingAs($pj)
            ->get(route('pj.shift-change'))
            ->assertOk()
            ->assertSee('Hari Libur / Tidak Ada Shift');

        // 5. PJ menyetujui pengajuan libur -> jadwal tanggal tsb dihapus (jadwal kosong)
        $this->actingAs($pj)
            ->post(route('pj.shift-change.approve', $request->id), [
                'requested_shift_id' => 'off',
            ])
            ->assertRedirect();

        $request->refresh();
        $this->assertEquals('approved', $request->status);
        $this->assertNull($request->requested_shift_id);

        $this->assertDatabaseMissing('employee_shifts', [
            'user_id' => $employee->id,
            'shift_date' => $targetDate,
        ]);
    }

    public function test_normal_shift_request_flow_still_works(): void
    {
        $employee = $this->makeEmployee();
        $pj = $this->makePj();

        $shift = Shift::create([
            'name' => 'Malam (Off Test)',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'allowed_roles' => ['nurse', 'pj_nurse'],
        ]);

        $targetDate = Carbon::tomorrow()->addDay()->format('Y-m-d');

        $this->actingAs($employee)
            ->post(route('shift-change.store'), [
                'shift_date' => $targetDate,
                'requested_shift_id' => $shift->id,
                'reason' => 'Test pengajuan pindah ke shift malam.',
            ])
            ->assertRedirect(route('shift-change.history'));

        $request = ShiftChangeRequest::where('user_id', $employee->id)
            ->whereDate('shift_date', $targetDate)
            ->first();

        $this->assertNotNull($request);
        $this->assertEquals($shift->id, $request->requested_shift_id);

        $this->actingAs($pj)
            ->post(route('pj.shift-change.approve', $request->id), [
                'requested_shift_id' => $shift->id,
            ])
            ->assertRedirect();

        $request->refresh();
        $this->assertEquals('approved', $request->status);

        $this->assertDatabaseHas('employee_shifts', [
            'user_id' => $employee->id,
            'shift_date' => $targetDate,
            'shift_id' => $shift->id,
        ]);
    }

    /**
     * Regresi: kolom requested_shift_id WAJIB nullable, kalau tidak pengajuan
     * libur akan gagal dengan SQLSTATE[23000] Column 'requested_shift_id' cannot be null.
     */
    public function test_kolom_requested_shift_id_harus_nullable(): void
    {
        $column = collect(Schema::getColumns('shift_change_requests'))
            ->firstWhere('name', 'requested_shift_id');

        $this->assertNotNull($column, 'Kolom requested_shift_id tidak ditemukan.');
        $this->assertTrue(
            (bool) $column['nullable'],
            'requested_shift_id harus nullable. Jalankan `php artisan migrate` untuk memperbaiki skema.'
        );
    }

    /**
     * Karyawan yang pada tanggal tsb memang belum punya shift tetap boleh
     * mengajukan libur (current_shift_id NULL + requested_shift_id NULL).
     */
    public function test_employee_tanpa_shift_tetap_bisa_mengajukan_libur(): void
    {
        $employee = $this->makeEmployee();

        $targetDate = Carbon::tomorrow()->addDays(3)->format('Y-m-d');

        $this->assertDatabaseMissing('employee_shifts', [
            'user_id' => $employee->id,
            'shift_date' => $targetDate,
        ]);

        $this->actingAs($employee)
            ->post(route('shift-change.store'), [
                'shift_date' => $targetDate,
                'requested_shift_id' => 'off',
                'reason' => 'Ajukan libur pada tanggal tersebut (belum ada jadwal shift).',
            ])
            ->assertRedirect(route('shift-change.history'));

        $request = ShiftChangeRequest::where('user_id', $employee->id)
            ->whereDate('shift_date', $targetDate)
            ->first();

        $this->assertNotNull($request);
        $this->assertNull($request->current_shift_id);
        $this->assertNull($request->requested_shift_id);
        $this->assertEquals('pending', $request->status);
    }
}
