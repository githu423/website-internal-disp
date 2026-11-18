<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Log aktivitas pengguna (login, unggah, ubah data, dll).
 */
class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';
    protected $fillable = ['user_id', 'nama', 'aksi', 'keterangan', 'ip'];

    /**
     * Mencatat satu aktivitas; nama diambil dari akun aktif ("tamu" bila belum login).
     */
    public static function catat(string $aksi, ?string $keterangan = null): void
    {
        // ===== AMBIL AKUN AKTIF & CATAT =====
        $pengguna = Auth::user();
        static::create([
            'user_id' => $pengguna?->id,
            'nama' => $pengguna?->name ?? 'tamu',
            'aksi' => $aksi,
            'keterangan' => $keterangan,
            'ip' => Request::ip(),
        ]);
    }
}
