<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Ruang Rapat: tautan publik berbasis token untuk tamu eksternal.
 *
 * - token dibuat otomatis saat ruang rapat dibuat.
 * - pin_hash opsional (PIN tamu).
 * - berlaku_sampai menentukan kedaluwarsa.
 * - status: aktif / nonaktif.
 */
class RuangRapat extends Model
{
    protected $table = 'ruang_rapat';

    protected $fillable = [
        'judul', 'deskripsi', 'tanggal_rapat', 'lokasi', 'link_virtual',
        'id_bidang', 'pic_nama', 'pic_kontak', 'izinkan_unduh_default',
        'berlaku_sampai', 'status', 'dibuat_oleh', 'dibuka_count',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_rapat' => 'datetime',
            'berlaku_sampai' => 'date',
            'izinkan_unduh_default' => 'boolean',
            'dibuka_count' => 'integer',
        ];
    }

    // ===== TOKEN OTOMATIS SAAT DIBUAT =====
    protected static function booted(): void
    {
        static::creating(function (self $ruangRapat): void {
            if ($ruangRapat->token) {
                return;
            }

            do {
                $token = Str::random(48);
            } while (static::where('token', $token)->exists());

            $ruangRapat->token = $token;
        });
    }

    // ===== RELASI =====
    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'id_bidang');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function dokumen()
    {
        return $this->belongsToMany(Dokumen::class, 'ruang_rapat_dokumen', 'id_ruang_rapat', 'id_dokumen')
            ->withPivot('kelompok', 'izinkan_unduh')
            ->withTimestamps();
    }

    public function kehadiran()
    {
        return $this->hasMany(RuangRapatKehadiran::class, 'id_ruang_rapat');
    }

    // ===== KEDALUWARSA =====
    public function sudahKedaluwarsa(): bool
    {
        if ($this->status === 'nonaktif') {
            return true;
        }

        if (! $this->berlaku_sampai) {
            return true;
        }

        // Tanggal berlaku mencakup seluruh hari yang tercantum.
        return $this->berlaku_sampai->copy()->endOfDay()->isPast();
    }

    // ===== VERIFIKASI PIN =====
    public function cekPin(string $input): bool
    {
        return $this->pin_hash !== null
            && $this->pin_hash !== ''
            && Hash::check($input, $this->pin_hash);
    }
}
