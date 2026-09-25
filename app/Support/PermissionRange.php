<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Helper rentang izin (permission).
 *
 * Satu pengajuan izin menempati rentang `tanggal` s/d `tanggal_selesai`
 * (`tanggal_selesai` kosong / sama dengan `tanggal` berarti izin 1 hari).
 *
 * Semua rekap/laporan yang menilai status PER TANGGAL wajib memakai helper ini.
 * Sebelumnya hanya kolom `tanggal` (hari pertama) yang dibaca, sehingga hari
 * ke-2 dan seterusnya dari izin multi-hari tidak dihitung sebagai izin
 * (muncul sebagai alpa / "-" / total izin kurang).
 */
class PermissionRange
{
    /**
     * Batasi query izin agar hanya memuat IZIN YANG BERIRISAN dengan periode.
     *
     * Zona tanggal satu izin = `tanggal` .. COALESCE(`tanggal_selesai`, `tanggal`).
     * Pengganti `whereBetween('tanggal', ...)` yang:
     *   - membuang izin yang sudah mulai sebelum periode, dan
     *   - mengabaikan hari ke-2 dan seterusnya.
     *
     * Dibuat memakai perbandingan biasa (bukan whereDate) supaya index
     * `tanggal` / `tanggal_selesai` tetap terpakai.
     */
    public static function applyOverlapsPeriod(Builder $query, $periodStart, $periodEnd): Builder
    {
        $periodStart = self::toDateString($periodStart);
        $periodEnd   = self::toDateString($periodEnd);

        return $query
            ->where('tanggal', '<=', $periodEnd)
            ->where(function (Builder $q) use ($periodStart) {
                $q->where('tanggal_selesai', '>=', $periodStart)
                    ->orWhere(function (Builder $q2) use ($periodStart) {
                        // Baris lama yang belum mengisi tanggal_selesai.
                        $q2->whereNull('tanggal_selesai')
                            ->where('tanggal', '>=', $periodStart);
                    });
            });
    }

    /**
     * Ekspansi koleksi izin menjadi daftar tanggal unik ('Y-m-d') di dalam periode.
     *
     * Contoh: izin 21/09 s/d 23/09 untuk periode September
     *         => ['2026-09-21', '2026-09-22', '2026-09-23'].
     */
    public static function datesWithin(iterable $permissions, $periodStart, $periodEnd): Collection
    {
        $rangeStart = Carbon::parse(self::toDateString($periodStart))->startOfDay();
        $rangeEnd   = Carbon::parse(self::toDateString($periodEnd))->startOfDay();

        $dates = new Collection();

        foreach ($permissions as $permission) {
            $start = Carbon::parse(self::toDateString($permission->tanggal))->startOfDay();
            $end   = Carbon::parse(self::toDateString(self::endOf($permission)))->startOfDay();

            if ($end->lt($start)) {
                $end = $start->copy();
            }

            // Potong ke periode yang diminta.
            $cursor = $start->gt($rangeStart) ? $start->copy() : $rangeStart->copy();
            $limit  = $end->lt($rangeEnd) ? $end->copy() : $rangeEnd->copy();

            while ($cursor->lte($limit)) {
                $dates->push($cursor->format('Y-m-d'));
                $cursor->addDay();
            }
        }

        return $dates->unique()->values();
    }

    /**
     * Peta 'Y-m-d' => Permission untuk menilai status satu tanggal.
     * Bila beberapa izin tumpang tindih, tanggal dipegang izin pertama.
     *
     * @return array<string, \App\Models\Permission>
     */
    public static function mapByDate(iterable $permissions, $periodStart, $periodEnd): array
    {
        $map = [];

        foreach ($permissions as $permission) {
            foreach (self::datesWithin([$permission], $periodStart, $periodEnd) as $date) {
                if (!isset($map[$date])) {
                    $map[$date] = $permission;
                }
            }
        }

        return $map;
    }

    /**
     * Tanggal selesai efektif: `tanggal_selesai` bila diisi, jika tidak `tanggal`.
     */
    public static function endOf($permission)
    {
        return $permission->tanggal_selesai ?: $permission->tanggal;
    }

    private static function toDateString($date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        return Carbon::parse($date)->format('Y-m-d');
    }
}
