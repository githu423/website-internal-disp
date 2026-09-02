<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bidang extends Model
{
    protected $table = 'bidang';
    protected $fillable = ['kode', 'nama', 'ketua', 'deskripsi'];

    public function pegawais() { return $this->hasMany(Pegawai::class, 'id_bidang'); }
    public function users() { return $this->hasMany(User::class, 'id_bidang'); }
    public function dokumen() { return $this->hasMany(Dokumen::class, 'id_bidang'); }
    public function agenda() { return $this->hasMany(Agenda::class, 'id_bidang'); }

    /**
     * Tebak bidang dari teks jabatan/unit kerja (gabungan kata bebas urutan).
     * Skor = jumlah kata khas nama bidang yang muncul; 'PENGEMBANGAN' dsb. diabaikan
     * karena generik. NULL bila tidak ada kecocokan yang meyakinkan.
     */
    public static function tebakDariTeks(?string $teks): ?int
    {
        $T = mb_strtoupper(preg_replace('/\s+/u', ' ', trim((string) $teks)) ?? '');
        if ($T === '') return null;
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
                if ($s > 0) $skor[$b->id] = $s;
                continue; // lewati loop kata generik untuk bidang UPTD
            }

            foreach ($kata as $k) {
                if ($k === 'SEKRETARIAT') { // istilah sekretariat punya padanan khas
                    if (preg_match('/SEKRETAR(IS|IAT)|PENUNJANG|KEPEGAWAIAN|PERENCANAAN|KEUANGAN/', $T)) $s += 2;
                    continue;
                }
                if (in_array($k, $stop, true) || mb_strlen($k) < 4) continue;
                if (preg_match('/\b' . preg_quote($k, '/') . '\b/u', $T)) $s++;
            }
            if ($s > 0) $skor[$b->id] = $s;
        }
        if (!$skor) return null;
        arsort($skor);
        return (int) array_key_first($skor);
    }
}
