<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Pembatas akses berdasarkan peran.
 *
 * Pemakaian: ->middleware('role:admin') atau 'role:admin,bidang'.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        // ===== CEK APAKAH PERAN AKUN ADA DI DAFTAR YANG DIIZINKAN =====
        $user = $request->user();
        abort_unless($user && in_array($user->role, $roles, true), 403, 'Anda tidak memiliki akses ke halaman ini.');

        return $next($request);
    }
}
