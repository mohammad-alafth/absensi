<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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

        $token = $user->createToken('auth_token')->plainTextToken;

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Login berhasil',
                'token' => $token,
                'user' => $user
            ]);
        }

        return redirect()->intended('/dashboard');
    }
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
