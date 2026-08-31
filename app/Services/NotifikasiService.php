<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\User;

class NotifikasiService
{
    public static function kirim(User|int $user, string $judul, ?string $isi = null, string $tipe = 'info', ?string $url = null): Notifikasi
    {
        return Notifikasi::create([
            'user_id' => $user instanceof User ? $user->id : $user,
            'judul' => $judul, 'isi' => $isi, 'tipe' => $tipe, 'url' => $url,
        ]);
    }

    /** ke semua user aktif (atau per bidang bila idBidang diisi) */
    public static function broadcast(string $judul, ?string $isi = null, string $tipe = 'info', ?string $url = null, ?int $idBidang = null): int
    {
        $n = 0;
        User::where('status', 'aktif')
            ->when($idBidang, fn ($q) => $q->where('id_bidang', $idBidang))
            ->each(function ($u) use (&$n, $judul, $isi, $tipe, $url) {
                self::kirim($u, $judul, $isi, $tipe, $url);
                $n++;
            });
        return $n;
    }
}
