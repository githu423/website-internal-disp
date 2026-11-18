<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\User;

/**
 * Pengiriman notifikasi ke pengguna.
 */
class NotifikasiService
{
    /**
     * Mengirim satu notifikasi ke satu pengguna.
     */
    public static function kirim(User|int $user, string $judul, ?string $isi = null, string $tipe = 'info', ?string $url = null): Notifikasi
    {
        return Notifikasi::create([
            'user_id' => $user instanceof User ? $user->id : $user,
            'judul' => $judul, 'isi' => $isi, 'tipe' => $tipe, 'url' => $url,
        ]);
    }

    /**
     * Mengirim ke semua user aktif (atau per bidang bila idBidang diisi).
     */
    public static function broadcast(string $judul, ?string $isi = null, string $tipe = 'info', ?string $url = null, ?int $idBidang = null): int
    {
        // ===== KIRIM BERURUTAN, HITUNG JUMLAHNYA =====
        $jumlah = 0;
        User::where('status', 'aktif')
            ->when($idBidang, fn ($query) => $query->where('id_bidang', $idBidang))
            ->each(function ($user) use (&$jumlah, $judul, $isi, $tipe, $url) {
                self::kirim($user, $judul, $isi, $tipe, $url);
                $jumlah++;
            });

        return $jumlah;
    }
}
