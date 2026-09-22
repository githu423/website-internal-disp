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
     * Umur dalam TAHUN, dihitung real-time dari tanggal lahir (konfirmasi Dinas
     * 11 Sep 2026 — identitas umur tampil di semua halaman data pegawai).
     * NULL bila tanggal lahir belum terisi atau salah (tertanggal di masa depan).
     */
    public function getUmurAttribute(): ?int
    {
        if (! $this->tanggal_lahir || $this->tanggal_lahir->isFuture()) return null;
        return (int) $this->tanggal_lahir->diffInYears(now());
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

    /**
     * Catatan singkat "belum lengkap: ..." utk baris tabel (v24): hanya beberapa
     * kolom pertama yang disebut, sisanya diringkas "+N kolom lain" supaya baris
     * tidak melebar. Daftar lengkapnya tetap bisa dilihat lewat halaman /profil.
     */
    public function ringkasKurang(int $batas = 3): string
    {
        $semua = $this->kolomKurang();
        if (count($semua) <= $batas) return implode(', ', $semua);
        return implode(', ', array_slice($semua, 0, $batas)) . ' +' . (count($semua) - $batas) . ' kolom lain';
    }

        /**
     * Pencarian menyeluruh (konfirmasi Dinas 11 Sep 2026): kata kunci dicocokkan ke
     * SEMUA data yang tampil di halaman pegawai — nama, NIP, jabatan, pangkat,
     * pendidikan, unit kerja, status, tempat lahir — TERMASUK nama bidang tempat
     * orang itu ditempatkan, sehingga mencari "destinasi" menampilkan SELURUH
     * anggota Bidang Destinasi (bukan hanya yang jabatannya menyebut destinasi).
     * Singkatan sehari-hari dikenali (ekrab/ekraf → ekonomi kreatif). NIP boleh
     * diketik dengan spasi; angka 4 digit juga dicocokkan ke tahun TMT/tgl lahir.
     */
    public function scopeCari($q, ?string $s)
    {
        if ($s) {
            // padanan singkatan sehari-hari → nama resmi (mis. "ekrab"/"ekraf" → "ekonomi kreatif")
            $varian = [$s];
            foreach (['ekrab' => 'ekonomi kreatif', 'ekraf' => 'ekonomi kreatif'] as $singk => $resmi) {
                if (stripos($s, $singk) !== false) $varian[] = $resmi;
            }
            $nipRapat = preg_replace('/\s+/', '', $s);
            $q->where(function ($w) use ($varian, $s, $nipRapat) {
                foreach ($varian as $i => $k) {
                    $grp = fn ($g) => $g->where('nama', 'like', "%$k%")
                        ->orWhere('nip', 'like', "%$k%")
                        ->orWhere('jabatan', 'like', "%$k%")
                        ->orWhere('unit_kerja', 'like', "%$k%")
                        ->orWhere('pangkat_golongan', 'like', "%$k%")
                        ->orWhere('pangkat_awal', 'like', "%$k%")
                        ->orWhere('pendidikan', 'like', "%$k%")
                        ->orWhere('jenis_asn', 'like', "%$k%")
                        ->orWhere('tempat_lahir', 'like', "%$k%")
                        ->orWhere('status', 'like', "%$k%")
                        ->orWhereHas('bidang', fn ($b) => $b->where('nama', 'like', "%$k%"));
                    $i === 0 ? $w->where($grp) : $w->orWhere($grp);
                }
                if ($nipRapat !== '' && $nipRapat !== $s) {
                    $w->orWhereRaw("REPLACE(nip, ' ', '') LIKE ?", ["%$nipRapat%"]);
                }
                if (preg_match('/^\d{4}$/', $nipRapat)) {
                    $t = (int) $nipRapat;
                    $w->orWhere(fn ($y) => $y->whereYear('tmt_cpns', $t)
                        ->orWhereYear('tmt_sekarang', $t)
                        ->orWhereYear('tmt_jabatan', $t)
                        ->orWhereYear('tanggal_lahir', $t));
                }
            });
        }
        return $q;
    }
}
