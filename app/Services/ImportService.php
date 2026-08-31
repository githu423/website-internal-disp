<?php

namespace App\Services;

use App\Models\CapaianBulanan;
use App\Models\CapaianProgram;
use App\Models\Pegawai;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Import otomatis dari file Excel/CSV Dinas.
 * Mendukung 3 format: Kinerja Fisik (capaian program), PAD, dan Daftar Pegawai.
 */
class ImportService
{
    /** Baca file (.csv/.xlsx/.xls) menjadi array of array of string. */
    public static function rowsFrom(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['xlsx', 'xls', 'ods', 'xlsb'])) {
            $sheet = IOFactory::load($path)->getSheet(0);
            $rows = $sheet->toArray(null, true, false, false);
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

    protected static function tgl(?string $v): ?string
    {
        if (! $v) return null;
        if (preg_match('#(\d{1,2})/(\d{1,2})/(\d{4})#', $v, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        return null;
    }

    protected static function bidangDariTeks(string $t): ?int
    {
        $t = mb_strtoupper($t);
        return match (true) {
            str_contains($t, 'PEMASARAN') => 3,
            str_contains($t, 'DAYA TAR') || str_contains($t, 'DESTINASI') => 2,
            str_contains($t, 'EKONOMI') || str_contains($t, 'SUMBER DAYA') || str_contains($t, 'KELEMBAGAAN') => 4,
            str_contains($t, 'PENUNJANG') || str_contains($t, 'SEKRETARIAT') => 1,
            default => null,
        };
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

    public static function applyKinerja(array $rows): array
    {
        $p = self::parseKinerja($rows);
        \Illuminate\Support\Facades\DB::transaction(function () use ($p) {
            foreach ($p['years'] as $y) {
                CapaianProgram::where('tahun', $y)->delete();
                CapaianBulanan::where('tahun', $y)->delete();
                $rowsY = array_values(array_filter($p['rows'], fn ($r) => (int) $r['tahun'] === (int) $y));
                CapaianProgram::insert(array_map(fn ($r) => array_merge($r, [
                    'created_at' => now(), 'updated_at' => now(),
                ]), $rowsY));
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
        return ['imported' => count($p['rows']), 'years' => $p['years']];
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
        \Illuminate\Support\Facades\DB::transaction(function () use ($p, $years) {
            foreach ($years as $y) {
                \App\Models\Pad::where('tahun', $y)->delete();
            }
            foreach ($p as $i => $row) {
                \App\Models\Pad::insert(array_merge($row, [
                    'created_at' => now(), 'updated_at' => now(),
                ]));
            }
        });
        return ['imported' => count($p), 'years' => $years];
    }

    /* ================= PEGAWAI ================= */

    public static function parsePegawai(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $r = array_pad($r, 20, '');
            if (! is_numeric(trim($r[0]))) continue;
            $nip = preg_replace('/\s+/', '', $r[2]);
            if (! $nip) continue;

            $jk = null;
            foreach ($r as $cell) {
                $u = strtoupper(trim($cell));
                if (in_array($u, ['L', 'P', 'LAKI-LAKI', 'PEREMPUAN'], true)) {
                    $jk = str_starts_with($u, 'L') ? 'L' : 'P';
                    break;
                }
            }
            $jabatan = trim($r[10]);
            $pendidikan = trim($r[12]);
            if (preg_match('/^(S-\d|D-\d|SLTA|SMA|SD)/iu', $pendidikan, $m)) {
                $pendidikan = $m[1];
            }
            $out[] = [
                'nip' => $nip,
                'nama' => trim($r[1]),
                'tempat_lahir' => trim($r[3]) ?: null,
                'tanggal_lahir' => self::tgl($r[4]),
                'jenis_kelamin' => $jk,
                'jabatan' => $jabatan ?: null,
                'pangkat_golongan' => trim($r[7]) ?: trim($r[5]) ?: null,
                'jenis_asn' => trim($r[8]) ?: null,
                'tmt_cpns' => self::tgl($r[6]),
                'tmt_sekarang' => self::tgl($r[9]),
                'tmt_jabatan' => self::tgl($r[11]),
                'pendidikan' => $pendidikan ?: null,
                'status' => 'aktif',
                'id_bidang' => self::bidangDariTeks($jabatan),
            ];
        }
        return $out;
    }

    public static function applyPegawai(array $rows): array
    {
        $p = self::parsePegawai($rows);
        $dibuat = 0; $diperbarui = 0; $akunBaru = 0;
        $pw = config('dispar.password_default_pegawai');
        foreach ($p as $data) {
            $exists = Pegawai::where('nip', $data['nip'])->exists();
            Pegawai::updateOrCreate(['nip' => $data['nip']], $data);
            $exists ? $diperbarui++ : $dibuat++;

            $user = User::where('nip', $data['nip'])->first();
            if (! $user) {
                User::create([
                    'name' => $data['nama'],
                    'nip' => $data['nip'],
                    'password' => $pw,
                    'role' => 'pegawai',
                    'id_bidang' => $data['id_bidang'],
                    'jabatan' => $data['jabatan'],
                ]);
                $akunBaru++;
            }
        }
        return ['dibuat' => $dibuat, 'diperbarui' => $diperbarui, 'akun_baru' => $akunBaru];
    }
}
