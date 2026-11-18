<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LogAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Autentikasi login/logout (guard "web").
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Menampilkan halaman login.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Memproses login lalu mengarahkan ke halaman yang dituju.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // ===== VERIFIKASI KREDENSIAL & BUAT SESI BARU =====
        $request->authenticate();
        $request->session()->regenerate();
        LogAktivitas::catat('login');

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Keluar: catat log, hapus sesi, dan arahkan ke halaman login.
     */
    public function destroy(Request $request): RedirectResponse
    {
        LogAktivitas::catat('logout');
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
