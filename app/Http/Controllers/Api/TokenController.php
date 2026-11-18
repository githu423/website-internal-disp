<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * API sederhana untuk aplikasi pihak ketiga: terbitkan token dan baca profil.
 */
class TokenController extends Controller
{
    /**
     * Terbitkan token API setelah NIP + password diverifikasi.
     */
    public function issue(Request $request): JsonResponse
    {
        // ===== 1. VALIDASI INPUT =====
        $data = $request->validate(['nip' => 'required|string', 'password' => 'required|string']);

        // ===== 2. VERIFIKASI KREDENSIAL =====
        $akun = User::where('nip', preg_replace('/\s+/', '', $data['nip']))->first();
        if (! $akun || ! Hash::check($data['password'], $akun->password)) {
            return response()->json(['message' => 'NIP atau password salah.'], 401);
        }

        // ===== 3. CEK STATUS AKUN =====
        if ($akun->status === 'nonaktif') {
            return response()->json(['message' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator kepegawaian.'], 403);
        }
        if ($akun->status === 'pensiun') {
            return response()->json(['message' => 'Terima kasih atas pengabdian Anda. Akun dalam status pensiun tidak dapat mengakses sistem. Selamat menikmati masa purnabakti.'], 403);
        }
        if ($akun->status !== 'aktif') {
            return response()->json(['message' => 'Akun Anda tidak aktif. Silakan hubungi administrator.'], 403);
        }

        // ===== 4. TERBITKAN TOKEN =====
        return response()->json(['token' => TokenService::issue($akun), 'name' => $akun->name, 'role' => $akun->role]);
    }

    /**
     * Profil pengguna berdasarkan token yang dikirim.
     */
    public function me(Request $request): JsonResponse
    {
        $pengguna = $request->user();

        return response()->json(['nip' => $pengguna->nip, 'name' => $pengguna->name, 'role' => $pengguna->role, 'jabatan' => $pengguna->jabatan, 'bidang' => $pengguna->bidang?->nama]);
    }
}
