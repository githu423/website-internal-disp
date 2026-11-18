<?php

namespace App\Models;

use App\Services\MasaKerjaService;
use Illuminate\Database\Eloquent\Model;

/**
 * Data kepegawaian.
 *
 * Kelengkapan data diukur dari KOLOM_LENGKAP (sama dengan checklist halaman /profil).
 */
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

    // ===== RELASI =====
    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'id_bidang');
    }

    // ===== MASA KERJA (REAL-TIME) =====
    public function getMasaKerjaAttribute(): array
    {
        return MasaKerjaService::hitung($this->tmt_cpns ?: $this->tmt_sekarang);
    }

    // ===== UMUR (REAL-TIME) =====
    /**
     * Umur dalam TAHUN, dihitung real-time dari tanggal lahir (konfirmasi Dinas
     * 11 Sep 2026 — identitas umur tampil di semua halaman data pegawai).
     * NULL bila tanggal lahir belum terisi atau salah (tertanggal di masa depan).
     */
    public function getUmurAttribute(): ?int
    {
        if (! $this->tanggal_lahir || $this->tanggal_lahir->isFuture()) {
            return null;
        }

        return (int) $this->tanggal_lahir->diffInYears(now());
    }

    // ===== DAFTAR KOLOM PENENTU "DATA LENGKAP" =====
    /**
     * v21: acuan "lengkap" = 13 kolom yang ADA di file Excel kepegawaian resmi
     * (urutan mengikuti file). Tempat/tanggal lahir tidak diwajibkan karena
     * tidak selalu ada di file. Kolom diklat ikut dipantau tp tetap terlihat.
     */
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

    /** Kolom yang bertipe DATE — MySQL strict menolak `= ''` untuk tipe ini. */
    public const KOLOM_DATE = ['tmt_cpns', 'tmt_sekarang', 'tmt_jabatan'];

    // ===== SCOPE: YANG DATANYA BELUM LENGKAP =====
    public function scopeBelumLengkap($query)
    {
        return $query->where(function ($queryKurang) {
            foreach (array_keys(self::KOLOM_LENGKAP) as $kolom) {
                if (in_array($kolom, self::KOLOM_DATE, true)) {
                    $queryKurang->orWhereNull($kolom);
                } else {
                    $queryKurang->orWhereNull($kolom)->orWhere($kolom, '');
                }
            }
        });
    }

    // ===== DAFTAR KOLOM YANG MASIH KOSONG =====
    /** Label kolom kelengkapan yang masih kosong — utk badge per baris (v18). */
    public function kolomKurang(): array
    {
        $daftarKurang = [];
        foreach (self::KOLOM_LENGKAP as $kolom => $label) {
            $nilai = $this->$kolom;
            // Kolom DATE hanya cek NULL (MySQL tidak pernah menyimpan '' untuk DATE).
            if (in_array($kolom, self::KOLOM_DATE, true)) {
                if ($nilai === null) {
                    $daftarKurang[] = $label;
                }
            } else {
                if ($nilai === null || $nilai === '') {
                    $daftarKurang[] = $label;
                }
            }
        }

        return $daftarKurang;
    }

    // ===== RINGKASAN "BELUM LENGKAP" =====
    /**
     * Catatan singkat "belum lengkap: ..." utk baris tabel (v24): hanya beberapa
     * kolom pertama yang disebut, sisanya diringkas "+N kolom lain" supaya baris
     * tidak melebar. Daftar lengkapnya tetap bisa dilihat lewat halaman /profil.
     */
    public function ringkasKurang(int $batas = 3): string
    {
        $daftarKurang = $this->kolomKurang();
        if (count($daftarKurang) <= $batas) {
            return implode(', ', $daftarKurang);
        }

        return implode(', ', array_slice($daftarKurang, 0, $batas)).' +'.(count($daftarKurang) - $batas).' kolom lain';
    }

    // ===== PENCARIAN MENYELURUH =====
    /**
     * Pencarian menyeluruh (konfirmasi Dinas 11 Sep 2026): kata kunci dicocokkan ke
     * SEMUA data yang tampil di halaman pegawai — nama, NIP, jabatan, pangkat,
     * pendidikan, unit kerja, status, tempat lahir — TERMASUK nama bidang tempat
     * orang itu ditempatkan, sehingga mencari "destinasi" menampilkan SELURUH
     * anggota Bidang Destinasi (bukan hanya yang jabatannya menyebut destinasi).
     * Singkatan sehari-hari dikenali (ekrab/ekraf → ekonomi kreatif). NIP boleh
     * diketik dengan spasi; angka 4 digit juga dicocokkan ke tahun TMT/tgl lahir.
     */
    public function scopeCari($query, ?string $kataKunci)
    {
        if ($kataKunci) {
            // Padanan singkatan sehari-hari → nama resmi (mis. "ekrab"/"ekraf" → "ekonomi kreatif").
            $varianKata = [$kataKunci];
            foreach (['ekrab' => 'ekonomi kreatif', 'ekraf' => 'ekonomi kreatif'] as $singkatan => $namaResmi) {
                if (stripos($kataKunci, $singkatan) !== false) {
                    $varianKata[] = $namaResmi;
                }
            }
            $nipRapi = preg_replace('/\s+/', '', $kataKunci);

            $query->where(function ($queryCari) use ($varianKata, $kataKunci, $nipRapi) {
                foreach ($varianKata as $indeks => $kata) {
                    $grupKondisi = fn ($queryGrup) => $queryGrup->where('nama', 'like', "%$kata%")
                        ->orWhere('nip', 'like', "%$kata%")
                        ->orWhere('jabatan', 'like', "%$kata%")
                        ->orWhere('unit_kerja', 'like', "%$kata%")
                        ->orWhere('pangkat_golongan', 'like', "%$kata%")
                        ->orWhere('pangkat_awal', 'like', "%$kata%")
                        ->orWhere('pendidikan', 'like', "%$kata%")
                        ->orWhere('jenis_asn', 'like', "%$kata%")
                        ->orWhere('tempat_lahir', 'like', "%$kata%")
                        ->orWhere('status', 'like', "%$kata%")
                        ->orWhereHas('bidang', fn ($queryBidang) => $queryBidang->where('nama', 'like', "%$kata%"));
                    $indeks === 0 ? $queryCari->where($grupKondisi) : $queryCari->orWhere($grupKondisi);
                }

                // NIP yang diketik dengan spasi tetap dicocokkan.
                if ($nipRapi !== '' && $nipRapi !== $kataKunci) {
                    $queryCari->orWhereRaw("REPLACE(nip, ' ', '') LIKE ?", ["%$nipRapi%"]);
                }

                // Angka 4 digit juga dicocokkan ke tahun TMT / tanggal lahir.
                if (preg_match('/^\d{4}$/', $nipRapi)) {
                    $tahun = (int) $nipRapi;
                    $queryCari->orWhere(fn ($queryTahun) => $queryTahun->whereYear('tmt_cpns', $tahun)
                        ->orWhereYear('tmt_sekarang', $tahun)
                        ->orWhereYear('tmt_jabatan', $tahun)
                        ->orWhereYear('tanggal_lahir', $tahun));
                }
            });
        }

        return $query;
    }
}
