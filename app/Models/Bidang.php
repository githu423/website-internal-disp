<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bidang extends Model
{
    protected $table = 'bidang';
    protected $fillable = ['kode', 'nama', 'ketua', 'deskripsi'];

    /**
     * Struktur organisasi resmi Dispar (konfirmasi Dinas, 10 Sep 2026).
     * KODE tetap abadi agar relasi data lama (pegawai/agenda/dokumen) tidak bergeser;
     * urutan kode = urutan resmi (1 Sekretariat, 2 Destinasi, 3 Pemasaran, 4 Ekrab, lalu UPTD).
     * Nomor/huruf resmi (a/b/c) & logo per bidang menyusul memakai urutan ini.
     */
    public static function daftarResmi(): array
    {
        return [
            ['kode' => '1', 'nama' => 'Sekretariat', 'ketua' => null, 'deskripsi' => 'Uraian tugas: pembinaan administrasi umum, kepegawaian, perencanaan, keuangan dan pelaporan.'],
            ['kode' => '2', 'nama' => 'Bidang Destinasi', 'ketua' => 'Rio Andri, S.Pt., MP.', 'deskripsi' => 'Uraian tugas: perencanaan dan pengembangan daya tarik wisata, destinasi, dan kelembagaan pariwisata.'],
            ['kode' => '3', 'nama' => 'Bidang Pemasaran', 'ketua' => 'Asep Didi Herdiawan, S.Pd., M.M.', 'deskripsi' => 'Uraian tugas: promosi, publikasi, kerja sama pemasaran, dan penyelenggaraan event pariwisata.'],
            ['kode' => '4', 'nama' => 'Bidang Ekonomi Kreatif', 'ketua' => 'Ginanjar Rahayu, S.IP., M.M.', 'deskripsi' => 'Uraian tugas: pengembangan ekonomi kreatif, SDM pariwisata, dan kemitraan pelaku usaha.'],
            ['kode' => 'UPTD-CIA', 'nama' => 'UPTD Pengelolaan OW Wil. Ciamis', 'ketua' => 'Darojatuna A. Pernanda, S.E., M.M.', 'deskripsi' => 'Unit Pelaksana Teknis Daerah — pengelolaan objek wisata wilayah Ciamis.'],
            ['kode' => 'UPTD-KAW', 'nama' => 'UPTD Pengelolaan OW Wil. Kawali', 'ketua' => 'Aan Kurniawan, S.IP', 'deskripsi' => 'Unit Pelaksana Teknis Daerah — pengelolaan objek wisata wilayah Kawali.'],
        ];
    }

    public function pegawais()
    {
        return $this->hasMany(Pegawai::class, 'id_bidang');
    }
    public function users()
    {
        return $this->hasMany(User::class, 'id_bidang');
    }
    public function dokumen()
    {
        return $this->hasMany(Dokumen::class, 'id_bidang');
    }
    public function agenda()
    {
        return $this->hasMany(Agenda::class, 'id_bidang');
    }

    /** Pastikan entri bidang UPTD Ciamis & Kawali ada (idempoten; dipakai ulang oleh seeder & dispar:sinkron-akun). */
    public static function pastikanUptd(): int
    {
        $n = 0;
        foreach ([
            ['UPTD-CIA', 'UPTD CIAMIS', 'UPTD Pengelolaan Objek Wisata Wilayah Ciamis'],
            ['UPTD-KAW', 'UPTD KAWALI', 'UPTD Pengelolaan Objek Wisata Wilayah Kawali']
        ] as [$kode, $nama, $display]) {
            $row = static::where('kode', $kode)->first();
            if (!$row) {
                static::create(['kode' => $kode, 'nama' => $nama, 'deskripsi' => 'Unit Pelaksana Teknis Daerah — pengelolaan objek wisata.']);
                $n++;
                continue;
            }
            // sudah ada tapi dengan nama display seeder — biarkan; hanya rapikan bila nama kosong
            if (trim((string) $row->nama) === '') {
                $row->update(['nama' => $nama]);
            }
        }
        return $n;
    }

    /**
     * Tebak bidang dari teks jabatan/unit kerja (gabungan kata bebas urutan).
     * Skor = jumlah kata khas nama bidang yang muncul; 'PENGEMBANGAN' dsb. diabaikan
     * karena generik. NULL bila tidak ada kecocokan yang meyakinkan.
     */
    public static function tebakDariTeks(?string $teks): ?int
    {
        $T = mb_strtoupper(preg_replace('/\s+/u', ' ', trim((string) $teks)) ?? '');
        if ($T === '')
            return null;
        $stop = ['BIDANG', 'DINAS', 'PARIWISATA', 'PENGEMBANGAN', 'SEKRETARIAT', 'PARIWISATA'];
        $skor = [];
        foreach (static::all() as $b) {
            $kata = preg_split('/[^\p{L}]+/u', mb_strtoupper($b->nama), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $s = 0;

            // —— Penanganan khusus UPTD: hanya cocokkan jika teks mengandung
            //    "UPTD" DAN nama wilayah (kata terakhir) bersama-sama.
            if (in_array('UPTD', $kata, true)) {
                $wilayah = end($kata); // misal "CIAMIS", "KAWALI"
                if (
                    preg_match('/\bUPTD\b/u', $T)
                    && preg_match('/\b' . preg_quote($wilayah, '/') . '\b/u', $T)
                ) {
                    $s += 2;
                }
                if ($s > 0)
                    $skor[$b->id] = $s;
                continue; // lewati loop kata generik untuk bidang UPTD
            }
            foreach ($kata as $k) {
                if ($k === 'SEKRETARIAT') { // istilah sekretariat punya padanan khas
                    if (preg_match('/SEKRETAR(IS|IAT)|PENUNJANG|KEPEGAWAIAN|PERENCANAAN|KEUANGAN/', $T))
                        $s += 2;
                    continue;
                }
                if (in_array($k, $stop, true) || mb_strlen($k) < 4)
                    continue;
                if (preg_match('/\b' . preg_quote($k, '/') . '\b/u', $T))
                    $s++;
            }
            if ($s > 0)
                $skor[$b->id] = $s;
        }
        if (!$skor)
            return null;
        arsort($skor);
        return (int) array_key_first($skor);
    }
}
