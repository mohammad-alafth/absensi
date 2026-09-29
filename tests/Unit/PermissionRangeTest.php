<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Support\PermissionRange;
use PHPUnit\Framework\TestCase;

/**
 * Helper rentang izin: satu pengajuan izin menempati
 * `tanggal` s/d `tanggal_selesai` (kosong = 1 hari).
 */
class PermissionRangeTest extends TestCase
{
    private function permission(string $tanggal, ?string $selesai = null): Permission
    {
        return new Permission([
            'tanggal' => $tanggal,
            'tanggal_selesai' => $selesai,
        ]);
    }

    public function test_izin_dua_hari_diekspansi_menjadi_dua_tanggal(): void
    {
        $dates = PermissionRange::datesWithin(
            [$this->permission('2026-09-21', '2026-09-22')],
            '2026-09-01',
            '2026-09-30'
        );

        $this->assertSame(['2026-09-21', '2026-09-22'], $dates->all());
    }

    public function test_izin_tiga_hari_diekspansi_menjadi_tiga_tanggal(): void
    {
        $dates = PermissionRange::datesWithin(
            [$this->permission('2026-09-21', '2026-09-23')],
            '2026-09-01',
            '2026-09-30'
        );

        $this->assertSame(['2026-09-21', '2026-09-22', '2026-09-23'], $dates->all());
    }

    public function test_tanpa_tanggal_selesai_dianggap_satu_hari(): void
    {
        $dates = PermissionRange::datesWithin(
            [$this->permission('2026-09-21')],
            '2026-09-01',
            '2026-09-30'
        );

        $this->assertSame(['2026-09-21'], $dates->all());
    }

    public function test_rentang_dipotong_sesuai_periode_yang_diminta(): void
    {
        $dates = PermissionRange::datesWithin(
            [$this->permission('2026-08-30', '2026-09-02')],
            '2026-09-01',
            '2026-09-30'
        );

        $this->assertSame(['2026-09-01', '2026-09-02'], $dates->all());
    }

    public function test_tanggal_selesai_lebih_kecil_dari_tanggal_mulai_dianggap_satu_hari(): void
    {
        $dates = PermissionRange::datesWithin(
            [$this->permission('2026-09-21', '2026-09-19')],
            '2026-09-01',
            '2026-09-30'
        );

        $this->assertSame(['2026-09-21'], $dates->all());
    }

    public function test_map_by_date_memakai_izin_pertama_untuk_tanggal_yang_tumpang_tindih(): void
    {
        $izinA = $this->permission('2026-09-21', '2026-09-22');
        $izinB = $this->permission('2026-09-22', '2026-09-23');

        $map = PermissionRange::mapByDate([$izinA, $izinB], '2026-09-01', '2026-09-30');

        $this->assertSame($izinA, $map['2026-09-21']);
        $this->assertSame($izinA, $map['2026-09-22']);
        $this->assertSame($izinB, $map['2026-09-23']);
    }
}
