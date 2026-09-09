<?php

namespace Tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ScheduleServiceOffice6Test extends TestCase
{
    /**
     * Helper: user minimal yang dibutuhkan oleh ScheduleService untuk
     * work_type office (hanya properti work_type yang dibaca).
     */
    private function officeUser(string $workType = 'office_6'): object
    {
        return (object) ['work_type' => $workType];
    }

    public function test_office6_hari_kerja_senin_jumat_jadwal_08_00_sampai_16_00(): void
    {
        // 2026-09-07 = Senin
        $schedule = \App\Services\ScheduleService::getTodaySchedule(
            $this->officeUser(),
            '2026-09-07'
        );

        $this->assertNotNull($schedule);
        $this->assertSame('08:00:00', $schedule['start_time']);
        $this->assertSame('16:00:00', $schedule['end_time']);
        $this->assertSame('2026-09-07 08:00:00', $schedule['shift_start']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-07 16:00:00', $schedule['shift_end']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-07', $schedule['shift_date']);
    }

    public function test_office6_sabtu_jadwal_setengah_hari_08_00_sampai_13_00(): void
    {
        // 2026-09-05 = Sabtu
        $schedule = \App\Services\ScheduleService::getTodaySchedule(
            $this->officeUser(),
            '2026-09-05'
        );

        $this->assertNotNull($schedule);
        $this->assertSame('08:00:00', $schedule['start_time']);
        $this->assertSame('13:00:00', $schedule['end_time']);
        $this->assertSame('2026-09-05 08:00:00', $schedule['shift_start']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-05 13:00:00', $schedule['shift_end']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-05', $schedule['shift_date']);
    }

    public function test_office6_minggu_tidak_ada_jadwal_libur(): void
    {
        // 2026-09-06 = Minggu
        $schedule = \App\Services\ScheduleService::getTodaySchedule(
            $this->officeUser(),
            '2026-09-06'
        );

        $this->assertNull($schedule);
    }

    public function test_office6_tanpa_tanggal_mengikuti_hari_ini_saat_sabtu(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-05 08:30:00')); // Sabtu

        try {
            $schedule = \App\Services\ScheduleService::getTodaySchedule($this->officeUser());

            $this->assertNotNull($schedule);
            $this->assertSame('13:00:00', $schedule['end_time']);
            $this->assertSame('2026-09-05 13:00:00', $schedule['shift_end']->format('Y-m-d H:i:s'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_office5_minggu_tetap_libur_tanpa_perubahan(): void
    {
        // Regresi: pastikan office_5 tidak ikut berubah.
        // 2026-09-07 = Senin (masuk 08.00-17.00)
        $monday = \App\Services\ScheduleService::getTodaySchedule(
            $this->officeUser('office_5'),
            '2026-09-07'
        );

        $this->assertSame('17:00:00', $monday['end_time']);

        // 2026-09-05 = Sabtu (libur office_5)
        $saturday = \App\Services\ScheduleService::getTodaySchedule(
            $this->officeUser('office_5'),
            '2026-09-05'
        );

        $this->assertNull($saturday);
    }
}
