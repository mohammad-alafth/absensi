<?php

namespace App\Http\Controllers\PJ;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
use App\Models\Leave;
use App\Models\Permission;
use App\Models\ShiftChangeRequest;
use App\Models\User;

class PJDashboardController extends Controller
{
    public static function getDivisionRolesForUser($role)
    {
        if ($role === 'pj_nurse') {
            return ['nurse', 'pj_nurse'];
        }
        if ($role === 'pj_nurse_ok') {
            return ['nurse_ok', 'pj_nurse_ok'];
        }
        if ($role === 'pj_admin') {
            return ['admin', 'administrasi', 'pj_admin'];
        }
        if ($role === 'pj_marketing') {
            return ['marketing', 'creator', 'konten_creator', 'pj_marketing'];
        }

        $baseRole = str_starts_with($role, 'pj_') ? str_replace('pj_', '', $role) : $role;
        return array_unique([$baseRole, 'pj_' . $baseRole]);
    }

    public function index()
    {
        $pjRole = auth()->user()->role;
        $divisionRoles = self::getDivisionRolesForUser($pjRole);

        $userIds = User::whereIn('role', $divisionRoles)->pluck('id');

        $pendingLeave = Leave::whereIn('user_id', $userIds)->where('pj_status', 'pending')->count();
        $pendingPermission = Permission::whereIn('user_id', $userIds)->where('pj_status', 'pending')->count();
        $pendingOvertime = Overtime::whereIn('user_id', $userIds)->where('pj_status', 'pending')->count();
        $pendingShiftChange = ShiftChangeRequest::whereIn('user_id', $userIds)->where('status', 'pending')->count();

        $approvedLeave = Leave::whereIn('user_id', $userIds)->where('pj_status', 'approved')->count()
            + Permission::whereIn('user_id', $userIds)->where('pj_status', 'approved')->count()
            + Overtime::whereIn('user_id', $userIds)->where('pj_status', 'approved')->count()
            + ShiftChangeRequest::whereIn('user_id', $userIds)->where('status', 'approved')->count();

        $rejectedLeave = Leave::whereIn('user_id', $userIds)->where('pj_status', 'rejected')->count()
            + Permission::whereIn('user_id', $userIds)->where('pj_status', 'rejected')->count()
            + Overtime::whereIn('user_id', $userIds)->where('pj_status', 'rejected')->count()
            + ShiftChangeRequest::whereIn('user_id', $userIds)->where('status', 'rejected')->count();

        $recentSubmissions = collect();

        $leaves = Leave::with('user')->whereIn('user_id', $userIds)->latest()->take(5)->get();
        foreach ($leaves as $l) {
            $recentSubmissions->push((object)[
                'user' => $l->user ?? (object)['name' => 'N/A'],
                'leave_type' => 'Cuti ' . ($l->leave_type ?? ''),
                'created_at' => $l->created_at,
                'status' => $l->pj_status ?? $l->status,
                'note' => $l->pj_note ?? $l->reason ?? null,
            ]);
        }

        $permissions = Permission::with('user')->whereIn('user_id', $userIds)->latest()->take(5)->get();
        foreach ($permissions as $p) {
            $recentSubmissions->push((object)[
                'user' => $p->user ?? (object)['name' => 'N/A'],
                'leave_type' => 'Izin: ' . ($p->permission_type ?? 'Izin'),
                'created_at' => $p->created_at,
                'status' => $p->pj_status ?? $p->status,
                'note' => $p->pj_note ?? $p->reason ?? null,
            ]);
        }

        $overtimes = Overtime::with('user')->whereIn('user_id', $userIds)->latest()->take(5)->get();
        foreach ($overtimes as $o) {
            $recentSubmissions->push((object)[
                'user' => $o->user ?? (object)['name' => 'N/A'],
                'leave_type' => 'Lembur (' . ($o->hours ?? 0) . ' Jam)',
                'created_at' => $o->created_at,
                'status' => $o->pj_status ?? $o->status,
                'note' => $o->pj_note ?? $o->reason ?? null,
            ]);
        }

        $shiftChanges = ShiftChangeRequest::with('user')->whereIn('user_id', $userIds)->latest()->take(5)->get();
        foreach ($shiftChanges as $sc) {
            $recentSubmissions->push((object)[
                'user' => $sc->user ?? (object)['name' => 'N/A'],
                'leave_type' => 'Ubah Shift (' . ($sc->target_date ?? '') . ')',
                'created_at' => $sc->created_at,
                'status' => $sc->status,
                'note' => $sc->rejection_note ?? $sc->reason ?? null,
            ]);
        }

        $recentSubmissions = $recentSubmissions->sortByDesc('created_at')->take(10);

        $divisionRole = $pjRole;

        return view('pj.dashboard', compact(
            'divisionRole',
            'pendingLeave',
            'pendingPermission',
            'pendingOvertime',
            'pendingShiftChange',
            'approvedLeave',
            'rejectedLeave',
            'recentSubmissions'
        ));
    }
}
