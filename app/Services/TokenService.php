<?php

namespace App\Services;

use App\Models\User;

/**
 * Penerbitan token API sederhana (Sanctum).
 *
 * Token lama bernama "sipardis" dihapus dulu sehingga satu akun
 * hanya punya satu token aktif.
 */
class TokenService
{
    /**
     * Terbitkan token baru untuk satu akun.
     */
    public static function issue(User $user): string
    {
        // ===== HAPUS TOKEN LAMA, TERBITKAN YANG BARU =====
        $user->tokens()->where('name', 'sipardis')->delete();

        return $user->createToken('sipardis')->plainTextToken;
    }
}
