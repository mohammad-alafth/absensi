<?php

namespace App\Exports;

use App\Services\HRDLateReportService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HRDLateExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private Carbon $startDate,
        private Carbon $endDate,
    ) {
    }

    public function collection()
    {
        return app(HRDLateReportService::class)->rows($this->startDate, $this->endDate);
    }

    public function headings(): array
    {
        return ['Nama Karyawan', 'Total Menit', 'Total Denda Telat'];
    }

    public function map($row): array
    {
        return [$row['name'], $row['total_minutes'], $row['total_fine']];
    }
}
