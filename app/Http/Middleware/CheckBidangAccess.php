<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Pembatas akses data bidang.
 *
 * - admin: bebas.
 * - user bidang: hanya data bidangnya sendiri.
 * - pegawai: hanya data publik (ditolak di sini).
 */
class CheckBidangAccess
{
    public function handle(Request $request, Closure $next)
    {
        // ===== PASTIKAN LOGIN =====
        $user = $request->user();
        abort_unless($user, 403);

        // ===== ADMIN SELALU BOLEH =====
        if ($user->isAdmin()) {
            return $next($request);
        }

        // ===== BANDINGKAN BIDANG AKUN DENGAN BIDANG DI RUTE =====
        $parameterBidang = $request->route('bidang');
        $idBidang = $parameterBidang instanceof \Illuminate\Database\Eloquent\Model ? $parameterBidang->getKey() : (int) $parameterBidang;
        if ($user->id_bidang && $idBidang === (int) $user->id_bidang) {
            return $next($request);
        }

        abort(403, 'Anda hanya dapat mengakses data bidang Anda sendiri.');
    }
}
