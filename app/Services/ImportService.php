<?php

namespace App\Services;

use App\Models\Bidang;
use App\Models\CapaianBulanan;
use App\Models\CapaianIndikator;
use App\Models\CapaianProgram;
use App\Models\CapaianSasaran;
use App\Models\CapaianTahunan;
use App\Models\Pegawai;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Import otomatis dari file Excel/CSV Dinas.
 * Mendukung 3 format: Kinerja Fisik (capaian program), PAD, dan Daftar Pegawai.
 */
class ImportService
{
    /**
     * Baca file (.csv/.xlsx/.xls/.ods) menjadi array of array of string.
     *
     * Jenis berkas spreadsheet dideteksi dari ISI file (magic bytes), BUKAN dari
     * ekstensi: file hasil upload PHP bernama sementara "phpXXXX.tmp" tanpa .xlsx,
     * sehingga dulu Excel selalu salah dibaca sebagai CSV (perbaikan 7 Sep 2026).
     * $nama_asli (opsional) = nama asli file — dipakai memilih reader ods/xlsb.
     */
    public static function rowsFrom(string $path, ?string $nama_asli = null): array
    {
        $magic = '';
        if (is_readable($path) && ($bh = @fopen($path, 'rb'))) { $magic = (string) fread($bh, 8); fclose($bh); }
        $zip = str_starts_with($magic, "PK\x03\x04");          // xlsx / xlsb / ods (semuanya container zip)
        $ole = str_starts_with($magic, "\xD0\xCF\x11\xE0"); // xls lama
        $ext = strtolower(pathinfo($nama_asli ?: $path, PATHINFO_EXTENSION));

        if ($zip || $ole) {
            $extSheet = in_array($ext, ['xlsx', 'xls', 'ods', 'xlsb'], true) ? $ext : ($ole ? 'xls' : 'xlsx');
            $pathUse = $path;
            if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== $extSheet) {
                // IOFactory mengenali reader dari ekstensi jalur — beri file salinan berekstensi benar
                $pathUse = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dispar-import-' . bin2hex(random_bytes(5)) . '.' . $extSheet;
                copy($path, $pathUse);
            }
            try {
                $rows = IOFactory::load($pathUse)->getSheet(0)->toArray(null, true, false, false);
            } finally {
                if ($pathUse !== $path) @unlink($pathUse);
            }
        } elseif (in_array($ext, ['xlsx', 'xls', 'ods', 'xlsb'], true)) {
            $rows = IOFactory::load($path)->getSheet(0)->toArray(null, true, false, false);
        } else {
            $rows = [];
            if (($fh = fopen($path, 'r')) !== false) {
                while (($line = fgetcsv($fh)) !== false) {
                    $rows[] = $line;
                }
                fclose($fh);
            }
        }

        // normalisasi: string, trim, dan konversi encoding cp1252 -> utf8 bila perlu
        foreach ($rows as &$row) {
            foreach ($row as &$cell) {
                $cell = trim((string) $cell);
                if ($cell !== '' && ! mb_check_encoding($cell, 'UTF-8')) {
                    $cell = mb_convert_encoding($cell, 'UTF-8', 'Windows-1252');
                }
                $cell = str_replace(["\r", "\n"], ' ', $cell);
            }
            unset($cell);
        }
        return array_values(array_filter($rows, fn ($r) => implode('', $r) !== ''));
    }

    protected static function num(?string $v): float
    {
        $v = str_replace([',', ' '], '', (string) $v);
        return is_numeric($v) ? (float) $v : 0.0;
    }

    /**
     * Tanggal dari sel: teks dd/mm/yyyy (csv/ketik manual), yyyy-mm-dd, dd-mm-yyyy,
     * ATAU angka serial Excel (xlsx menyimpan tanggal sebagai angka, mis. 27468).
     */
    protected static function tgl(?string $v): ?string
    {
        if (! $v) return null;
        $v = trim($v);
        if (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})#', $v, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('#^(\d{4})[/.-](\d{1,2})[/.-](\d{1,2})#', $v, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        if (is_numeric($v) && (float) $v > 20000 && (float) $v < 80000) { // rentang serial ~1955-2118
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $v)->format('Y-m-d');
        }
        return null;
    }

    protected static function bidangDariTeks(string $t): ?int
    {
        return Bidang::tebakDariTeks($t);
    }

    /** Pimpinan unit (eselon IV & koordinator) → peran 'bidang'; lainnya 'pegawai'. */
    public static function peranDariJabatan(?string $j): string
    {
        $t = mb_strtoupper(trim((string) $j));
        return preg_match('/\b(KABID|KEPALA\s+(BIDANG|SEKSI|SUBBAGIAN|SUB\s+BAGIAN|UPTD?)|KASI|KASUBBAG|SEKRETARIS|KOORDINATOR)\b/u', $t)
            ? 'bidang' : 'pegawai';
    }

    /* ================= KINERJA FISIK (capaian program/kegiatan/sub) ================= */

    /**
     * Cari file sumber data: path dari .env (relatif/absolut, toleran beda spasi/underscore),
     * lalu cadangan di database/source/.
     */
    public static function cariFileSumber(string $kunciConfig, string $namaBaku): ?string
    {
        $dipilih = trim((string) config('dispar.sumber.' . $kunciConfig));
        $cands = [];
        if ($dipilih !== '') {
            $abs = $dipilih;
            if (! preg_match('#^([A-Za-z]:[\\\\/]|/|\\\\\\\\)#', $abs)) $abs = base_path($dipilih);
            $cands[] = $abs;
            // varian nama file: spasi <-> underscore, tanpa/dengan folder storage/app/private
            $base = basename($abs);
            $dir = dirname($abs);
            foreach ([preg_replace('/[_]+/', ' ', $base), preg_replace('/[ ]+/', '_', $base)] as $varian) {
                if ($varian !== '' && $varian !== $base) $cands[] = $dir . DIRECTORY_SEPARATOR . $varian;
            }
            $cands[] = storage_path('app/private/' . $base);
            $cands[] = storage_path('app/private/' . $namaBaku);
        }
        $cands[] = database_path('source/' . $namaBaku);
        $cands[] = storage_path('app/private/' . $namaBaku);
        foreach ($cands as $c) {
            if (is_file($c)) return $c;
        }
        return null;
    }

    /** @return array{rows: array<int, array>, years: int[], bulanan: array<int,array<int,array>>} */
    public static function parseKinerja(array $rows): array
    {
        $out = []; $bulanan = []; $years = [];
        $tahun = null; $layoutB = false; $idb = null; $urutan = 0;

        foreach ($rows as $r) {
            $r = array_pad($r, 31, '');
            if (preg_match('/^TAHUN\s*(\d{4})/i', trim($r[0]), $m)) {
                $tahun = (int) $m[1];
                $years[$tahun] = true;
                $layoutB = false; $idb = null; $urutan = 0;
                continue;
            }
            if (! $tahun) continue;
            $jml = strtoupper(trim($r[0]));
            if (str_starts_with($jml, 'JUMLAH')) { $tahun = $tahun; continue; }
            // header? kenali dari kata SUMBER DANA / Indikator
            $joined = mb_strtoupper(implode('|', $r));
            if (str_contains($joined, 'SUMBER DANA')) { $layoutB = str_contains($joined, 'INDIKATOR'); continue; }
            if (str_contains($joined, ' JAN ') || preg_match('/\bJan\b/u', $joined)) continue;

            $level = null; $nama = null;
            if (trim($r[1]) !== '') { $level = 'program'; $nama = trim($r[1]); $idb = self::bidangDariTeks($nama); }
            elseif (trim($r[2]) !== '') { $level = 'kegiatan'; $nama = trim($r[2]); }
            elseif (trim($r[3]) !== '') { $level = 'sub_kegiatan'; $nama = trim($r[3]); }
            if (! $nama) continue;

            if ($layoutB) {
                [$sumber, $pagu, $indikator, $target, $satuan, $total, $persen] =
                    [$r[4], $r[5], $r[6], $r[7], $r[8], $r[21] ?: $r[20], $r[22] ?: $r[21]];
                $mCols = range(9, 20);
            } else {
                [$sumber, $pagu, $indikator, $target, $satuan, $total, $persen] =
                    [$r[4], $r[5], null, $r[6], $r[7], $r[20], $r[21]];
                $mCols = range(8, 19);
            }
            $catatan = trim($r[23] ?? '');
            if (is_numeric($catatan) || $catatan === 'Permasalahan') $catatan = '';

            $row = [
                'tahun' => $tahun, 'level' => $level, 'nama' => $nama,
                'sumber_dana' => $sumber ?: null, 'pagu_anggaran' => self::num($pagu),
                'indikator' => $indikator ?: null, 'target' => self::num($target),
                'satuan' => $satuan ?: null, 'capaian' => self::num($total),
                'persentase' => self::num($persen), 'catatan' => $catatan ?: null,
                'id_bidang' => $idb, 'urutan' => ++$urutan,
            ];
            $out[] = $row;

            // data bulanan hanya untuk baris sub yang punya angka per bulan
            if ($level === 'sub_kegiatan') {
                foreach ($mCols as $i => $col) {
                    $v = self::num($r[$col]);
                    if ($v != 0.0 || trim((string) $r[$col]) !== '') {
                        $bulanan[$tahun][$i + 1] = ($bulanan[$tahun][$i + 1] ?? 0) + $v;
                    }
                }
                if ($row['target'] > 0) {
                    for ($m = 1; $m <= 12; $m++) {
                        $bulanan[$tahun]['t' . $m] = ($bulanan[$tahun]['t' . $m] ?? 0) + $row['target'] / 12;
                    }
                }
            }
        }

        return ['rows' => $out, 'years' => array_keys($years), 'bulanan' => $bulanan];
    }

    public static function applyKinerja(array $rows, bool $gantiTahun = false): array
    {
        $p = self::parseKinerja($rows);
        $dibuat = 0; $diperbarui = 0;
        \Illuminate\Support\Facades\DB::transaction(function () use ($p, $gantiTahun, &$dibuat, &$diperbarui) {
            if ($gantiTahun) {
                foreach ($p['years'] as $y) {
                    CapaianProgram::where('tahun', $y)->delete();
                    CapaianBulanan::where('tahun', $y)->delete();
                }
            }
            foreach ($p['rows'] as $r) { // v20: upsert seperti sistem pegawai — tanpa centang "ganti", data manual & tahun lain aman
                $key = ['tahun' => $r['tahun'], 'level' => $r['level'], 'nama' => $r['nama']];
                $sebelum = CapaianProgram::where($key)->exists();
                CapaianProgram::updateOrCreate($key, array_diff_key($r, $key));
                $sebelum ? $diperbarui++ : $dibuat++;
            }
            foreach ($p['bulanan'] as $y => $data) {
                $adaCapaian = false;
                for ($m = 1; $m <= 12; $m++) {
                    $c = $data[$m] ?? 0; $t = $data['t'.$m] ?? 0;
                    if ($c != 0 || $t != 0) { $adaCapaian = true; break; }
                }
                if (! $adaCapaian) continue;
                for ($m = 1; $m <= 12; $m++) {
                    CapaianBulanan::updateOrCreate(
                        ['tahun' => $y, 'bulan' => $m],
                        ['target' => round($data['t'.$m] ?? 0, 2), 'capaian' => round($data[$m] ?? 0, 2)]
                    );
                }
            }
        });
        return ['imported' => count($p['rows']), 'years' => $p['years'],
                'dibuat' => $dibuat, 'diperbarui' => $diperbarui, 'mode' => $gantiTahun ? 'ganti' : 'upsert'];
    }

    /* ================= PAD (pendapatan retribusi daerah) ================= */

    public static function parsePad(array $rows): array
    {
        $out = []; $tahun = null; $kategori = null; $targetHeader = null;

        foreach ($rows as $r) {
            $r = array_pad($r, 20, '');
            if (preg_match('/^TAHUN\s*(\d{4})/i', trim($r[0]), $m)) {
                $tahun = (int) $m[1]; $kategori = null; $targetHeader = null;
                continue;
            }
            if (! $tahun) continue;

            // baris header: No. | No. Rekening | Uraian | Target ...
            if (trim($r[0]) === 'No.' || str_contains($r[0], 'No.')) {
                $targetHeader = 'T' . (int) $tahun;
                continue;
            }
            $c0 = trim($r[0]);
            // baris kategori: A. | B. | C.
            if (preg_match('/^[A-Z]\.?$/', $c0) && trim($r[1]) !== '' && ! is_numeric($c0)) {
                $kategori = trim($r[1]);
                continue;
            }
            // baris jumlah: lewati (agregat dihitung ulang oleh app)
            if (str_starts_with(mb_strtoupper($c0), 'JUMLAH')) continue;
            if (! is_numeric($c0) || trim($r[2]) === '') continue;

            $months = array_map(fn ($i) => self::num($r[5 + $i]), range(0, 11));
            $out[] = [
                'tahun' => $tahun,
                'kategori' => $kategori,
                'no_rekening' => trim($r[1]) ?: null,
                'objek' => trim($r[2]),
                'target_tahunan' => self::num($r[3]),
                'target_perubahan' => self::num($r[4]),
                'realisasi_bulan_lalu' => $months[10],
                'realisasi_bulan_ini' => $months[11],
                'kumulatif' => self::num($r[17]) ?: array_sum($months),
                'persentase' => self::num($r[19]),
                'sisa_target' => self::num($r[18]),
            ];
        }
        return $out;
    }

    public static function applyPad(array $rows): array
    {
        $p = self::parsePad($rows);
        $years = array_values(array_unique(array_column($p, 'tahun')));
        $dibuat = 0; $diperbarui = 0;
        \Illuminate\Support\Facades\DB::transaction(function () use ($p, &$dibuat, &$diperbarui) {
            foreach ($p as $row) { // v20: upsert seperti sistem pegawai (dulu hapus-then-insert per tahun)
                $key = !empty($row['no_rekening'])
                    ? ['tahun' => $row['tahun'], 'no_rekening' => $row['no_rekening']]
                    : ['tahun' => $row['tahun'], 'objek' => $row['objek']];
                $sebelum = \App\Models\Pad::where($key)->exists();
                \App\Models\Pad::updateOrCreate($key, array_diff_key($row, $key));
                $sebelum ? $diperbarui++ : $dibuat++;
            }
        });
        return ['imported' => count($p), 'years' => $years, 'dibuat' => $dibuat, 'diperbarui' => $diperbarui];
    }

    /* ================= PEGAWAI ================= */

    public static function parsePegawai(array $rows): array
    {
        $out = [];
        // v21: kolom dikenali dari TEKS HEADER (baris 1-3), bukan posisi tetap — susunan
        // file user apa pun terbaca. Fallback ke posisi template Dinas lama bila header absen.
        $hx = []; $pangkatCols = [];
        foreach (array_slice($rows, 0, 3) as $hr) {
            if (! is_array($hr)) continue;
            foreach ($hr as $ci => $cv) {
                $t = str_replace([' ', '.'], '', strtoupper(trim((string) $cv)));
                if ($t === '') continue;
                if (str_contains($t, 'PANGKAT') && str_contains($t, 'GOL')) { if (! in_array($ci, $pangkatCols, true)) $pangkatCols[] = $ci; continue; }
                if (str_starts_with($t, 'ASN')) $hx['jenis_asn'] ??= $ci;
                foreach ([
                    'TEMPATLAHIR' => 'tempat_lahir', 'TANGGALLAHIR' => 'tanggal_lahir',
                    'TMTCPNS' => 'tmt_cpns', 'TMTSEKARANG' => 'tmt_sekarang', 'TMTJABATAN' => 'tmt_jabatan',
                    'JABATANSEKARANG' => 'jabatan', 'PENDIDIKANUMUM' => 'pendidikan',
                    'DIKLATSTRUKTURAL' => 'diklat_struktural', 'DIKLATFUNGSIONAL' => 'diklat_fungsional',
                    'DIKLATTEKNIS' => 'diklat_teknis', 'JENISKELAMIN' => 'jenis_kelamin', 'UNITKERJA' => 'unit_kerja',
                ] as $k => $f) { if ($t === $k) $hx[$f] ??= $ci; }
            }
        }
        $at = fn (array $r, ?int $i): string => $i === null ? '' : trim((string) ($r[$i] ?? ''));
        $col = fn (string $f, int $lama): int => $hx[$f] ?? $lama;

        foreach ($rows as $r) {
            if (! is_array($r)) continue;
            $r = array_pad($r, 20, '');
            if (! is_numeric(trim($r[0]))) continue;
            $nip = preg_replace('/\s+/', '', $r[2]);
            if (! $nip) continue;

            $jk = null;
            $jkv = strtoupper($at($r, $col('jenis_kelamin', -1)));
            if ($jkv !== '') $jk = str_starts_with($jkv, 'L') ? 'L' : (str_starts_with($jkv, 'P') ? 'P' : null);
            if (! $jk) {
                foreach ($r as $cell) {
                    $u = strtoupper(trim((string) $cell));
                    if (in_array($u, ['L', 'P', 'LAKI-LAKI', 'PEREMPUAN'], true)) { $jk = str_starts_with($u, 'L') ? 'L' : 'P'; break; }
                }
            }
            // dua header "PANGKAT/GOL" -> kolom pertama = pangkat AWAL, terakhir = pangkat TERAKHIR
            $pgAwal = count($pangkatCols) >= 2 ? $pangkatCols[0] : null;
            $pgAkhir = $pangkatCols ? end($pangkatCols) : 7;
            $jabatan = $at($r, $col('jabatan', 10));
            $unit = $at($r, $col('unit_kerja', 18));
            $pendidikan = $at($r, $col('pendidikan', 12));
            if (preg_match('/^(S-\d|D-\d|SLTA|SMA|SD)/iu', $pendidikan, $mm)) $pendidikan = $mm[1];

            $out[] = [
                'nip' => $nip,
                'nama' => trim($r[1]),
                'tempat_lahir' => $at($r, $col('tempat_lahir', 3)) ?: null,
                'tanggal_lahir' => self::tgl((string) ($r[$col('tanggal_lahir', 4)] ?? '')),
                'jenis_kelamin' => $jk,
                'jabatan' => $jabatan ?: null,
                'unit_kerja' => $unit ?: null,
                'pangkat_awal' => $pgAwal !== null ? ($at($r, $pgAwal) ?: null) : (trim($r[5]) ?: null),
                'pangkat_golongan' => $at($r, $pgAkhir) ?: trim($r[7]) ?: null,
                'jenis_asn' => $at($r, $col('jenis_asn', 8)) ?: null,
                'tmt_cpns' => self::tgl((string) ($r[$col('tmt_cpns', 6)] ?? '')),
                'tmt_sekarang' => self::tgl((string) ($r[$col('tmt_sekarang', 9)] ?? '')),
                'tmt_jabatan' => self::tgl((string) ($r[$col('tmt_jabatan', 11)] ?? '')),
                'pendidikan' => $pendidikan ?: null,
                'diklat_struktural' => $at($r, $col('diklat_struktural', -1)) ?: null,
                'diklat_fungsional' => $at($r, $col('diklat_fungsional', -1)) ?: null,
                'diklat_teknis' => $at($r, $col('diklat_teknis', -1)) ?: null,
                'status' => 'aktif',
                'id_bidang' => Bidang::tebakDariTeks(trim($jabatan . ' ' . $unit)),
            ];
        }
        return $out;
    }

    public static function applyPegawai(array $rows): array
    {
        $p = self::parsePegawai($rows);
        $dibuat = 0; $diperbarui = 0; $akunBaru = 0; $akunSinkron = 0;
        $pw = config('dispar.password_default_pegawai');
        foreach ($p as $data) {
            $exists = Pegawai::where('nip', $data['nip'])->exists();
            Pegawai::updateOrCreate(['nip' => $data['nip']], $data);
            $exists ? $diperbarui++ : $dibuat++;

            $bidang = $data['id_bidang'];
            $peran = self::peranDariJabatan($data['jabatan']);
            $user = User::where('nip', $data['nip'])->first();
            if (! $user) {
                User::create([
                    'name' => $data['nama'],
                    'nip' => $data['nip'],
                    'password' => $pw,
                    'role' => $peran,
                    'id_bidang' => $bidang,
                    'jabatan' => $data['jabatan'],
                ]);
                $akunBaru++;
            } elseif ($user->role !== 'admin') {
                $ubah = [];
                if ($bidang && (int) $user->id_bidang !== (int) $bidang) $ubah['id_bidang'] = $bidang;
                if ($user->role !== $peran) $ubah['role'] = $peran;
                if ($data['jabatan'] && $user->jabatan !== $data['jabatan']) $ubah['jabatan'] = $data['jabatan'];
                if ($ubah) { $user->update($ubah); $akunSinkron++; }
            }
        }
        return ['dibuat' => $dibuat, 'diperbarui' => $diperbarui, 'akun_baru' => $akunBaru, 'akun_disinkron' => $akunSinkron];
    }

    /**
     * Parse matriks Capaian Kinerja: header "No. | Sasaran | Indikator Kinerja | Satuan | <tahun...>"
     * lalu tiap tahun punya triplet kolom Target | Realisasi | %.
     * Sel "n/a"/kosong diperlakukan sebagai tidak ada data; sasaran menyusul tanpa nomor digabung ke atasnya.
     */
    public static function parseCapaian(array $rows): array
    {
        $g = fn (array $r, int $i): string => trim((string) ($r[$i] ?? ''));

        $h = null;
        foreach ($rows as $i => $r) {
            if (preg_match('/^no\.?$/i', $g($r, 0))) { $h = $i; break; }
        }
        if ($h === null) return [];

        // deteksi kolom tahun (baris setelah header), tiap tahun = awal triplet (t, t+1, t+2)
        $years = [];
        for ($i = $h + 1; $i <= $h + 3 && $i < count($rows); $i++) {
            foreach ($rows[$i] as $c => $cell) {
                if (preg_match('/^(20\d{2})$/', trim((string) $cell), $m)) {
                    $years[(int) $m[1]] = ['t' => $c];
                }
            }
            if ($years) break;
        }
        if (!$years) return [];

        $bersih = function (string $v): ?string {
            if ($v === '' || strcasecmp($v, 'n/a') === 0 || $v === '-') return null;
            return $v;
        };

        $out = [];
        $cur = -1;
        for ($i = $h + 1; $i < count($rows); $i++) {
            $r = array_values($rows[$i]);
            $no = $g($r, 0); $sas = $g($r, 1); $ind = $g($r, 2); $sat = $g($r, 3);

            if ($no !== '' && $sas !== '' && preg_match('/^\d+$/', $no)) {
                $cakupan = null;
                if (preg_match('/Sasaran\s*Tahun\s*:?\s*(.+)/i', $sas, $m)) {
                    $cakupan = trim($m[1]);
                    $sas = trim(preg_replace('/\n?Sasaran\s*Tahun.*/is', '', $sas));
                }
                $sas = trim(preg_replace('/\s*\n\s*/', ' ', $sas));
                $out[] = ['nomor' => (int) $no, 'judul' => $sas, 'cakupan' => $cakupan ?: null,
                    'id_bidang' => \App\Models\Bidang::tebakDariTeks($sas), 'indikator' => []];
                $cur = count($out) - 1;
            }

            if ($cur < 0 || $ind === '' || mb_stripos($ind, 'indikator kinerja') !== false) continue;

            $nilai = [];
            foreach ($years as $y => $pos) {
                $t = $bersih($g($r, $pos['t']));
                $rl = $bersih($g($r, $pos['t'] + 1));
                $p = $bersih($g($r, $pos['t'] + 2));
                if ($t !== null || $rl !== null || $p !== null) {
                    $nilai[$y] = ['target' => $t, 'realisasi' => $rl, 'persen' => $p];
                }
            }
            $out[$cur]['indikator'][] = ['nama' => $ind, 'satuan' => $sat ?: null, 'nilai' => $nilai];
        }

        return $out;
    }

    /** Simpan hasil parseCapaian(). Mode 'update' = gabung/upgrade otomatis tanpa duplikat (default, aman untuk file lengkap yang diunggah berulang kali); mode 'ganti' = hapus semua lalu isi ulang. */
    public static function applyCapaian(array $data, string $mode = 'update'): array
    {
        $stat = ['sasaran_baru' => 0, 'indikator_baru' => 0, 'nilai_baru' => 0, 'nilai_ubah' => 0, 'nilai_sama' => 0];

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $mode, &$stat) {
            if ($mode === 'ganti') {
                CapaianTahunan::query()->delete();
                CapaianIndikator::query()->delete();
                CapaianSasaran::query()->delete();
            }
            $slug = fn (?string $x) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $x) ?: ''));
            $semuaSasaran = $mode === 'update' ? CapaianSasaran::with('indikator')->get() : collect();

            foreach (array_values($data) as $us => $s) {
                $sasaran = null;
                foreach ($semuaSasaran as $cand) {
                    if ($slug($cand->judul) === $slug($s['judul']) || $cand->nomor === $s['nomor']) { $sasaran = $cand; break; }
                }
                if ($sasaran) {
                    $sasaran->update(['nomor' => $s['nomor'], 'judul' => $s['judul'], 'cakupan' => $s['cakupan'],
                        'urut' => $us + 1, 'id_bidang' => $s['id_bidang'] ?? $sasaran->id_bidang]); // impor tak menghapus pilihan manual bila judul tak terbaca
                } else {
                    $sasaran = CapaianSasaran::create([
                        'nomor' => $s['nomor'], 'judul' => $s['judul'], 'cakupan' => $s['cakupan'], 'urut' => $us + 1,
                        'id_bidang' => $s['id_bidang'] ?? null,
                    ]);
                    $stat['sasaran_baru']++;
                }
                foreach (array_values($s['indikator']) as $ui => $i) {
                    $ind = null;
                    foreach ($sasaran->indikator as $cand) {
                        if ($slug($cand->nama) === $slug($i['nama'])) { $ind = $cand; break; }
                    }
                    if ($ind) {
                        $ind->update(['nama' => $i['nama'], 'satuan' => $i['satuan'], 'urut' => $ui + 1]);
                    } else {
                        $ind = CapaianIndikator::create([
                            'sasaran_id' => $sasaran->id, 'nama' => $i['nama'], 'satuan' => $i['satuan'], 'urut' => $ui + 1,
                        ]);
                        $stat['indikator_baru']++;
                    }
                    foreach ($i['nilai'] as $tahun => $n) {
                        $row = CapaianTahunan::firstOrNew(['indikator_id' => $ind->id, 'tahun' => (int) $tahun]);
                        if (!$row->exists) {
                            $row->fill(['target' => $n['target'], 'realisasi' => $n['realisasi'], 'persen' => $n['persen']])->save();
                            $stat['nilai_baru']++;
                            continue;
                        }
                        $ubah = false;
                        foreach (['target', 'realisasi', 'persen'] as $k) {
                            // sel kosong/n-a di file tidak menghapus nilai lama (mode merge)
                            if ($n[$k] === null) { if ($mode === 'ganti') { $row->$k = null; $ubah = true; } continue; }
                            if ($row->$k !== $n[$k]) { $row->$k = $n[$k]; $ubah = true; }
                        }
                        if ($ubah) { $row->save(); $stat['nilai_ubah']++; } else { $stat['nilai_sama']++; }
                    }
                }
                $semuaSasaran->push($sasaran);
                $sasaran->load('indikator');
            }
        });

        return $stat;
    }

    /**
     * Isi pegawai.id_bidang yang masih kosong berdasarkan jabatan/unit kerja
     * (mis. staf UPTD yang datanya belum tertaut bidang). Idempoten.
     */
    public static function sinkronBidangPegawai(): int
    {
        $adaUnit = \Illuminate\Support\Facades\Schema::hasColumn('pegawai', 'unit_kerja');
        $n = 0;
        foreach (Pegawai::whereNull('id_bidang')->get() as $p) {
            $bid = Bidang::tebakDariTeks(trim(((string) $p->jabatan) . ' ' . ($adaUnit ? (string) ($p->unit_kerja ?? '') : '')));
            if ($bid) { $p->id_bidang = $bid; $p->save(); $n++; }
        }
        return $n;
    }

    /**
     * Selaraskan peran & bidang seluruh akun non-admin dengan tabel pegawai.
     * Aman dipanggil berulang (idempoten) dan tetap jalan walau kolom unit_kerja belum dimigrasi.
     */
    public static function sinkronAkunPegawai(): int
    {
        $adaUnit = \Illuminate\Support\Facades\Schema::hasColumn('pegawai', 'unit_kerja');
        $ubah = 0;
        foreach (User::where('role', '!=', 'admin')->with('pegawai')->get() as $u) {
            $p = $u->pegawai ?: Pegawai::where('nip', $u->nip)->first();
            if (!$p) continue;
            $d = [];
            $bid = $p->id_bidang ?? Bidang::tebakDariTeks(trim(((string) $p->jabatan) . ' ' . ($adaUnit ? (string) ($p->unit_kerja ?? '') : '')));
            if ($bid && (int) $u->id_bidang !== (int) $bid) $d['id_bidang'] = $bid;
            $peran = self::peranDariJabatan($p->jabatan);
            if ($u->role !== $peran) $d['role'] = $peran;
            if ($d) { $u->update($d); $ubah++; }
        }
        return $ubah;
    }
    /* ================= STATISTIK PARIWISATA (indikator x tahun) ================= */

    /**
     * Angka dari sel spreadsheet: "1,173,275,000" & "1.173.275.000" & "22.07%" → float;
     * "", "n/a", "-", "—" → null (BEDA dengan 0!). Heuristik: pemisah ribuan vs desimal.
     */
    public static function angkaSel(?string $v): ?float
    {
        $v = trim(str_replace(["\u{00A0}", ' '], '', (string) $v));
        if ($v === '' || preg_match('/^(n\/?a|[-–—]+|\.+)$/iu', $v)) return null;
        $v = rtrim(trim($v), '%');
        $neg = str_starts_with($v, '-');
        $v = ltrim($v, '-+');
        $hasDot = str_contains($v, '.'); $hasCom = str_contains($v, ',');
        if ($hasDot && $hasCom) {
            if (strrpos($v, ',') > strrpos($v, '.')) { $v = str_replace('.', '', $v); $v = str_replace(',', '.', $v); }
            else { $v = str_replace(',', '', $v); }
        } elseif ($hasCom) {
            $v = preg_match('/^\d{1,3}(,\d{3})+$/', $v) ? str_replace(',', '', $v) : str_replace(',', '.', $v);
        } elseif ($hasDot) {
            if (preg_match('/^\d{1,3}(\.\d{3})+$/', $v)) $v = str_replace('.', '', $v);
        }
        if (!is_numeric($v)) return null;
        return $neg ? -(float) $v : (float) $v;
    }

    /**
     * Parse matriks "DATA STATISTIK PARIWISATA": baris judul bebas di atas,
     * satu baris header (No. | Indikator | Satuan | Tahun | ... | Keterangan),
     * satu baris daftar tahun (boleh di header itu sendiri), lalu baris data.
     * Kolom dipetakan dari posisi 2017..20xx — jadi geser kolom/extra spasi aman.
     *
     * @return array<int, array{no:?int,indikator:string,satuan:?string,keterangan:?string,nilai:array<int,float>}>
     */
    public static function parseStatistik(array $rows): array
    {
        $h = null;
        foreach ($rows as $i => $r) {
            foreach ($r as $cv) {
                if (mb_strtoupper(trim((string) $cv)) === 'INDIKATOR') { $h = $i; break 2; }
            }
        }
        if ($h === null) return [];

        $noCol = $indCol = $satCol = $ketCol = null;
        foreach ($rows[$h] as $c => $v) {
            $u = mb_strtoupper(trim((string) $v));
            if ($noCol === null && preg_match('/^NO\.?$/u', $u)) $noCol = $c;
            elseif ($u === 'INDIKATOR') $indCol = $c;
            elseif ($u === 'SATUAN') $satCol = $c;
            elseif ($u === 'KETERANGAN') $ketCol = $c;
        }
        $noCol ??= 0; $indCol ??= $noCol + 1; $satCol ??= $indCol + 1;

        // tahun = sel 4 digit di baris header / 2 baris berikutnya, di kolom kanan SATUAN
        $tahunCols = [];
        $cands = array_slice($rows, $h, 3);
        $width = 0; foreach ($cands as $rr) $width = max($width, count($rr));
        for ($c = 0; $c < $width; $c++) {
            if ($c <= $satCol) continue;
            foreach ($cands as $rr) {
                if (preg_match('/^(19|20)\d{2}$/', trim((string) ($rr[$c] ?? '')), $m)) { $tahunCols[$c] = (int) $m[0]; break; }
            }
        }
        if (!$tahunCols) return [];

        $out = [];
        for ($i = $h + 1; $i < count($rows); $i++) {
            $r = $rows[$i];
            $ind = trim((string) ($r[$indCol] ?? ''));
            if ($ind === '' || mb_strtoupper($ind) === 'INDIKATOR') continue; // baris sub-header/tahun
            $nilai = [];
            foreach ($tahunCols as $c => $t) {
                $val = self::angkaSel((string) ($r[$c] ?? ''));
                if ($val !== null) $nilai[$t] = $val;
            }
            $no = trim((string) ($r[$noCol] ?? ''));
            $out[] = [
                'no' => ctype_digit($no) ? (int) $no : null,
                'indikator' => preg_replace('/\s+/', ' ', $ind),
                'satuan' => ($sat = trim((string) ($r[$satCol] ?? ''))) !== '' ? $sat : null,
                'keterangan' => $ketCol !== null ? (trim((string) ($r[$ketCol] ?? '')) ?: null) : null,
                'nilai' => $nilai,
            ];
        }
        return $out;
    }

    /** Upsert per nama indikator (toleran besar/kecil & spasi ganda); tahun yang tidak disebut file dibiarkan. */
    public static function applyStatistik(array $p): array
    {
        $dibuat = 0; $diperbarui = 0; $sel = 0;
        foreach ($p as $baris) {
            $key = mb_strtoupper(preg_replace('/\s+/', ' ', trim($baris['indikator'])));
            $st = \App\Models\Statistik::whereRaw('upper(indikator) = ?', [$key])->first();
            if ($st) { $diperbarui++; } else { $st = new \App\Models\Statistik(); $dibuat++; }
            $st->indikator = trim($baris['indikator']);
            $st->no_urut = $baris['no']; $st->satuan = $baris['satuan']; $st->keterangan = $baris['keterangan'];
            $st->save();
            foreach ($baris['nilai'] as $t => $v) {
                \App\Models\StatistikNilai::updateOrCreate(
                    ['id_statistik' => $st->id, 'tahun' => (int) $t],
                    ['nilai' => $v],
                );
                $sel++;
            }
        }
        return ['dibuat' => $dibuat, 'diperbarui' => $diperbarui, 'sel' => $sel];
    }
}
