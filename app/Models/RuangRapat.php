<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

    public function cekPin(string $input): bool
    {
        return $this->pin_hash !== null
            && $this->pin_hash !== ''
            && Hash::check($input, $this->pin_hash);
    }
}
