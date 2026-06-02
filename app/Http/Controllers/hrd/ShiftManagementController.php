<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;
use App\Models\User;

class ShiftManagementController extends Controller
{

    public function store(Request $request)
    {
        $request->validate(['name' => 'required', 'start_time' => 'required', 'end_time' => 'required']);
        Shift::create($request->all());
        return back()->with('success', 'Shift baru berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $request->validate(['start_time' => 'required', 'end_time' => 'required']);
        $shift = Shift::findOrFail($id);

        // Update data termasuk allowed_roles (array dari checkbox)
        $shift->update([
            'start_time'   => $request->start_time,
            'end_time'     => $request->end_time,
            'is_overnight' => $request->has('is_overnight'),
            'allowed_roles' => $request->roles ?? []
        ]);

        return back()->with('success', 'Shift berhasil diupdate');
    }
    public function index()
    {
        $shifts = Shift::all();
        $roles = user::where('work_type', 'shift')
            ->where('role', 'like', 'pj_%')
            ->distinct()
            ->pluck('role')
            ->toArray();

        return view(
            'hrd.shifts.index',
            compact('shifts','roles')
        );
    }
}
