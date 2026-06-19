<?php

namespace App\Exports;

use App\Models\User;
use App\Models\EmployeeShift;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CalendarMultiExport implements WithMultipleSheets
{
    protected $month;

    public function __construct($month)
    {
        $this->month = $month;
    }
    private function normalizeRole($role)
    {
        $role = strtolower($role);

        // hapus prefix pj_
        $role = str_replace('pj_', '', $role);

        // hapus suffix _ok
        $role = str_replace('_ok', '', $role);

        return $role;
    }

    public function sheets(): array
    {
        $start = Carbon::parse($this->month . '-01')->startOfMonth();
        $end   = Carbon::parse($this->month . '-01')->endOfMonth();

        $users = User::whereNotNull('role')->get();

        $grouped = [];

        foreach ($users as $user) {

            $roleKey = $this->normalizeRole($user->role);

            if (!isset($grouped[$roleKey])) {
                $grouped[$roleKey] = [
                    'users' => []
                ];
            }

            $grouped[$roleKey]['users'][] = $user;
        }

        $sheets = [];

        foreach ($grouped as $roleName => $data) {

            $sheets[] = new CalendarRoleSheet(
                strtoupper($roleName),
                collect($data['users']),
                $start,
                $end
            );
        }

        return $sheets;
    }
}
