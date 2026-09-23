<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function issue(Request $request): JsonResponse
    {
        $d = $request->validate(['nip' => 'required|string', 'password' => 'required|string']);
        $user = User::where('nip', preg_replace('/\s+/', '', $d['nip']))->first();
        if (! $user || ! \Hash::check($d['password'], $user->password)) {
            return response()->json(['message' => 'NIP atau password salah.'], 401);
        }
        if ($user->status === 'nonaktif') {
            return response()->json(['message' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator kepegawaian.'], 403);
        }
        if ($user->status === 'pensiun') {
            return response()->json(['message' => 'Terima kasih atas pengabdian Anda. Akun dalam status pensiun tidak dapat mengakses sistem. Selamat menikmati masa purnabakti.'], 403);
        }
        if ($user->status !== 'aktif') {
            return response()->json(['message' => 'Akun Anda tidak aktif. Silakan hubungi administrator.'], 403);
        }
        return response()->json(['token' => TokenService::issue($user), 'name' => $user->name, 'role' => $user->role]);
    }

    public function me(Request $request): JsonResponse
    {
        $u = $request->user();
        return response()->json(['nip' => $u->nip, 'name' => $u->name, 'role' => $u->role, 'jabatan' => $u->jabatan, 'bidang' => $u->bidang?->nama]);
    }
}
