<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use App\Services\PermissionDecision;
use App\Services\PermissionResolver;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        // 1. Authenticate user (cek email & password)
        $request->authenticate();

        // 2. Ambil user yang baru saja login
        $user = Auth::user();

        // 3. CEK STATUS APPROVAL
        if (!$user->is_approved) {
            // Logout paksa jika belum di-approve
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Lempar error kembali ke login page
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akun Anda belum disetujui oleh HRD. Mohon tunggu konfirmasi.'
                ], 403);
            }

            throw ValidationException::withMessages([
                'email' => 'Akun Anda belum disetujui oleh HRD. Mohon tunggu konfirmasi.',
            ]);
        }

        // 4. Jika sudah di-approve, lanjutkan proses normal
        $request->session()->regenerate();

        // Buat token Sanctum & simpan ID-nya di session, supaya saat logout /
        // auto-logout (idle 30 menit) token ikut di-revoke (reset token session)
        $tokenRecord = $user->createToken('auth_token');
        $request->session()->put('auth_access_token_id', $tokenRecord->accessToken->id);

        $token = $tokenRecord->plainTextToken;

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Login berhasil',
                'token' => $token,
                'user' => $user
            ]);
        }

        if ($this->shouldOpenAdminControlCenter($request, $user)) {
            return redirect()->route('admin.control-center');
        }

        return redirect()->intended('/dashboard');
    }

    private function shouldOpenAdminControlCenter(Request $request, $user): bool
    {
        if ($user->role !== 'admin'
            || $request->session()->has('url.intended')
            || !config('admin_control_center.enabled', false)
            || !Route::has('admin.control-center')) {
            return false;
        }

        $permissionKey = (string) config(
            'admin_control_center.permission_key',
            'admin.control-center.view'
        );

        try {
            return app(PermissionResolver::class)
                ->resolve('admin', $permissionKey)
                ->decision === PermissionDecision::GRANTED;
        } catch (\Throwable) {
            return false;
        }
    }
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Revoke token Sanctum yang dibuat saat login web (reset token session)
        $accessTokenId = $request->session()->pull('auth_access_token_id');

        if ($user && $accessTokenId) {
            $user->tokens()->where('id', $accessTokenId)->delete();
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
