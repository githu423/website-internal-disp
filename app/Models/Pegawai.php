<?php

namespace App\Models;

use App\Services\MasaKerjaService;
use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    protected $table = 'pegawai';
    protected $fillable = [
        'nip', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'jabatan',
        'pangkat_golongan', 'jenis_asn', 'tmt_cpns', 'tmt_sekarang', 'tmt_jabatan',
        'pendidikan', 'status', 'id_bidang',
    ];
    protected $casts = ['tanggal_lahir' => 'date', 'tmt_cpns' => 'date', 'tmt_sekarang' => 'date', 'tmt_jabatan' => 'date'];

    public function bidang() { return $this->belongsTo(Bidang::class, 'id_bidang'); }

    public function getMasaKerjaAttribute(): array
    {
        return MasaKerjaService::hitung($this->tmt_cpns ?: $this->tmt_sekarang);
    }

    public function scopeCari($q, ?string $s)
    {
        if ($s) {
            $q->where(fn ($w) => $w->where('nama', 'like', "%$s%")
                ->orWhere('nip', 'like', "%$s%")
                ->orWhere('jabatan', 'like', "%$s%"));
        }
        return $q;
    }
}
