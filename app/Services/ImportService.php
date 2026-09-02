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
        // kolom UNIT KERJA dideteksi dari header; fallback kolom ke-19 (0-based 18)
        $idxUnit = null;
        foreach ($rows as $hr) {
            foreach ($hr as $ci => $cv) {
                if (mb_strtoupper(trim((string) $cv)) === 'UNIT KERJA') { $idxUnit = $ci; break 2; }
            }
        }
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
            $unit = $idxUnit !== null ? trim((string) ($r[$idxUnit] ?? '')) : trim((string) ($r[18] ?? ''));
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
                'unit_kerja' => $unit ?: null,
                'pangkat_golongan' => trim($r[7]) ?: trim($r[5]) ?: null,
                'jenis_asn' => trim($r[8]) ?: null,
                'tmt_cpns' => self::tgl($r[6]),
                'tmt_sekarang' => self::tgl($r[9]),
                'tmt_jabatan' => self::tgl($r[11]),
                'pendidikan' => $pendidikan ?: null,
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
                $out[] = ['nomor' => (int) $no, 'judul' => $sas, 'cakupan' => $cakupan ?: null, 'id_bidang' => Bidang::tebakDariTeks($sas), 'indikator' => []];
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
                    $sasaran->update(['nomor' => $s['nomor'], 'judul' => $s['judul'], 'cakupan' => $s['cakupan'], 'id_bidang' => $s['id_bidang'], 'urut' => $us + 1]);
                } else {
                    $sasaran = CapaianSasaran::create([
                        'nomor' => $s['nomor'], 'judul' => $s['judul'], 'cakupan' => $s['cakupan'], 'id_bidang' => $s['id_bidang'], 'urut' => $us + 1,
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
}
