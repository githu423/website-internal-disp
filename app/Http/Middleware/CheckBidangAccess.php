<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckBidangAccess
{
    /** admin bebas; user bidang hanya bisa akses datanya sendiri; pegawai hanya data publik */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless($user, 403);
        if ($user->isAdmin()) {
            return $next($request);
        }
        $param = $request->route('bidang');
        $id = $param instanceof \Illuminate\Database\Eloquent\Model ? $param->getKey() : (int) $param;
        if ($user->id_bidang && $id === (int) $user->id_bidang) {
            return $next($request);
        }
        abort(403, 'Anda hanya dapat mengakses data bidang Anda sendiri.');
    }
}
