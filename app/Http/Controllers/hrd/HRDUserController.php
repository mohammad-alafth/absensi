<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApprovalFlowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Storage;

class HRDUserController extends Controller
{
    public function index()
    {
        $pendingUsers = User::where('is_approved', false)->get();
        $resetRequests = User::where('password_reset_request', true)->get();
        return view('hrd.users.approval', compact('pendingUsers', 'resetRequests'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'role' => ['required', 'string', 'in:' . implode(',', ApprovalFlowService::assignableRoles())],
            'work_type' => ['required', 'in:office_5,office_6,shift'],
        ]);

        // Delegated account managers must not be able to create another admin.
        // The exact admin role remains the only authority for that escalation.
        abort_unless(
            $request->user()?->role === 'admin' || $request->input('role') !== 'admin',
            403,
            'Only the exact admin role may assign the admin role.'
        );

        $user = User::findOrFail($id);
        $user->update([
            'is_approved' => true,
            'role' => $request->role,
            'work_type' => $request->work_type, // Simpan tipe kerja
        ]);

        return back()->with('success', 'Akun telah disetujui.');
    }

    public function resetPassword(Request $request, $id)
    {
        // Validasi password baru (disarankan)
        $request->validate(['new_password' => 'required|min:6']);

        $user = User::findOrFail($id);
        abort_unless(
            $request->user()?->role === 'admin' || $user->role !== 'admin',
            403,
            'Only the exact admin role may reset an admin password.'
        );
        $user->update([
            'password' => Hash::make($request->new_password),
            'password_reset_request' => false // <--- WAJIB: Agar user hilang dari list setelah di-reset
        ]);

        return back()->with('success', 'Password user berhasil direset.');
    }
}
