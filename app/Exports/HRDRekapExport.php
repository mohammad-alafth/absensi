<?php

namespace App\Exports;

use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Export Rekap HRD - SATU LEMBAR (SHEET) PER UNIT/ROLE.
 *
 * Setiap sheet dibuat oleh HRDRekapSheet dan memuat kolom:
 *   A NAMA PEGAWAI | B UNIT/ROLE | C TOTAL HADIR | D TOTAL TELAT
 *   E TOTAL WAKTU TERLAMBAT | F TOTAL JAM KERJA | G DENDA KETERLAMBATAN (formula)
 *   H TANGGAL IZIN | I TANGGAL CUTI | J JAM LEMBUR (format surat lembur: "X Jam")
 */
class HRDRekapExport implements WithMultipleSheets
{
    protected $month, $role, $startDate, $endDate;

    /**
     * @param string|null          $month     'Y-m' - dipakai bila rentang tidak diberikan
     * @param string               $role      'all' | role tertentu
     * @param string|Carbon|null   $startDate batas awal periode (opsional)
     * @param string|Carbon|null   $endDate   batas akhir periode (opsional)
     */
    public function __construct($month = null, $role = 'all', $startDate = null, $endDate = null)
    {
        $this->month = $month ?: now()->format('Y-m');
        $this->role = $role;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Daftar unit/role yang memiliki pegawai pada periode ini.
     */
    private function rolesWithEmployees(): array
    {
        $roles = User::whereNotIn('role', ['admin'])
            ->select('role')
            ->distinct()
            ->pluck('role');

        if ($this->role != 'all') {
            $roles = $roles->filter(function ($r) {
                return HRDRekapSheet::normalizeRole($r)
                    == HRDRekapSheet::normalizeRole($this->role);
            });
        }

        return $roles
            ->map(fn($r) => HRDRekapSheet::normalizeRole($r))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Sheet per unit/role.
     * role 'all' => satu sheet untuk setiap unit yang punya pegawai.
     */
    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->rolesWithEmployees() as $role) {
            $sheets[] = new HRDRekapSheet(
                $this->month,
                $role,
                $this->startDate,
                $this->endDate
            );
        }

        // Bila tidak ada pegawai sama sekali, tetap buat satu sheet
        // agar file Excel tidak kosong.
        if (empty($sheets)) {
            $sheets[] = new HRDRekapSheet(
                $this->month,
                $this->role,
                $this->startDate,
                $this->endDate
            );
        }

        return $sheets;
    }
}
