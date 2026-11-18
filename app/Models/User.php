<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Akun login.
 *
 * Peran: admin, bidang (pengelola), pegawai.
 * Status: aktif, nonaktif, pensiun.
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name', 'nip', 'password', 'role', 'id_bidang', 'jabatan', 'status'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'status' => 'string'];
    }

    // ===== RELASI =====
    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'id_bidang');
    }

    public function pegawai()
    {
        return $this->hasOne(Pegawai::class, 'nip', 'nip');
    }

    public function notifikasi()
    {
        return $this->hasMany(Notifikasi::class)->latest();
    }

    // ===== PENENTU PERAN =====
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isBidang(): bool
    {
        return $this->role === 'bidang';
    }

    public function isPegawai(): bool
    {
        return $this->role === 'pegawai';
    }

    /** Yang boleh mengunggah dokumen/data: admin & pengelola bidang. */
    public function isBisaUnggah(): bool
    {
        return $this->isAdmin() || $this->isBidang();
    }

    // ===== LINGKUP BIDANG UNTUK QUERY =====
    /** Untuk query scope: admin lihat semua, bidang lihat bidangnya. */
    public function idBidangTerfilter(): ?int
    {
        return $this->isAdmin() ? null : ($this->id_bidang ?: -1);
    }
}
