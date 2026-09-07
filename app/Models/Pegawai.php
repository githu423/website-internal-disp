<?php

namespace App\Models;

use App\Services\MasaKerjaService;
use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    protected $table = 'pegawai';
    protected $fillable = [
        'nip', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'jabatan',
        'unit_kerja',
        'pangkat_golongan', 'jenis_asn', 'tmt_cpns', 'tmt_sekarang', 'tmt_jabatan',
        'pendidikan', 'status', 'id_bidang',
        'pangkat_awal', 'diklat_struktural', 'diklat_fungsional', 'diklat_teknis',
    ];
    protected $casts = ['tanggal_lahir' => 'date', 'tmt_cpns' => 'date', 'tmt_sekarang' => 'date', 'tmt_jabatan' => 'date'];

    public function bidang() { return $this->belongsTo(Bidang::class, 'id_bidang'); }

    public function getMasaKerjaAttribute(): array
    {
        return MasaKerjaService::hitung($this->tmt_cpns ?: $this->tmt_sekarang);
    }

    /**
     * Pegawai yang datanya belum lengkap — kriteria SAMA dengan checklist
     * halaman /profil (permintaan Ricky 7 Sep 2026, dipakai chip merah & filter).
     */
    /** v18: kolom INTI penentu "data lengkap" — disamakan dengan yang memang terisi
     *  dari file Excel kepegawaian. unit_kerja & TMT jadi opsional (dulu membuat
     *  hampir semua orang tercatat "belum lengkap" walau filenya tidak memuatnya). */
    /** v21: acuan "lengkap" = 13 kolom yang ADA di file Excel kepegawaian resmi
     *  (urutan mengikuti file). Tempat/tanggal lahir tidak diwajibkan karena
     *  tidak selalu ada di file. Kolom diklat ikut dipantau tp tetap terlihat. */
    public const KOLOM_LENGKAP = [
        'pangkat_awal' => 'Pangkat/golongan awal',
        'tmt_cpns' => 'TMT CPNS',
        'pangkat_golongan' => 'Pangkat/golongan terakhir',
        'jenis_asn' => 'Status ASN',
        'tmt_sekarang' => 'TMT sekarang',
        'jabatan' => 'Jabatan sekarang',
        'tmt_jabatan' => 'TMT jabatan',
        'pendidikan' => 'Pendidikan umum',
        'diklat_struktural' => 'Diklat struktural',
        'diklat_fungsional' => 'Diklat fungsional',
        'diklat_teknis' => 'Diklat teknis',
        'jenis_kelamin' => 'Jenis kelamin',
        'unit_kerja' => 'Unit kerja',
    ];

    public function scopeBelumLengkap($q)
    {
        return $q->where(function ($w) {
            foreach (array_keys(self::KOLOM_LENGKAP) as $c) {
                $w->orWhereNull($c)->orWhere($c, '');
            }
        });
    }

    /** Label kolom kelengkapan yang masih kosong — utk badge per baris (v18). */
    public function kolomKurang(): array
    {
        $kurang = [];
        foreach (self::KOLOM_LENGKAP as $c => $label) {
            $v = $this->$c;
            if ($v === null || $v === '') $kurang[] = $label;
        }
        return $kurang;
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
