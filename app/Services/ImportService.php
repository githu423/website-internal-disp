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
        if (is_readable($path) && ($berkas = @fopen($path, 'rb'))) { $magic = (string) fread($berkas, 8); fclose($berkas); }
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
                $spreadsheet = IOFactory::load($pathUse);
                // 9 Sep 2026: sheet 0 kadang berupa sampul kosong — pakai sheet pertama yang benar-benar berisi data
                $sheet = null;
                for ($i = 0; $i < $spreadsheet->getSheetCount(); $i++) {
                    $kandidat = $spreadsheet->getSheet($i);
                    foreach ($kandidat->toArray(null, true, false, false) as $barisUji) {
                        if (is_array($barisUji) && trim(implode('', array_map(fn ($sel) => (string) $sel, $barisUji))) !== '') { $sheet = $kandidat; break 2; }
                    }
                }
                $rows = ($sheet ?? $spreadsheet->getSheet(0))->toArray(null, true, false, false);
                $spreadsheet->disconnectWorksheets();
            } finally {
                if ($pathUse !== $path) @unlink($pathUse);
            }
        } elseif (in_array($ext, ['xlsx', 'xls', 'ods', 'xlsb'], true)) {
            $rows = IOFactory::load($path)->getSheet(0)->toArray(null, true, false, false);
        } else {
            $rows = [];
            if (($berkas = fopen($path, 'r')) !== false) {
                while (($line = fgetcsv($berkas)) !== false) {
                    $rows[] = $line;
                }
                fclose($berkas);
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
        return array_values(array_filter($rows, fn ($baris) => implode('', $baris) !== ''));
    }

    /**
     * Angka dari teks sel: koma & spasi dibuang; teks non-angka dianggap 0.
     */
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

    /**
     * Menemukan id bidang dari teks nama unit (mis. "Kepala Bidang Kebudayaan").
     */
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

    /**
     * Konfirmasi Dinas 10 Sep 2026 — BATAS KEPALA untuk penempatan bidang.
     * Baris berjabatan "Kepala Bidang X" menandai kepala bidang X; semua baris di
     * bawahnya adalah staf X sampai "Kepala Bidang" berikutnya. Baris di atas batas
     * pertama (Sekretaris Dinas, Kepala Sub Bagian, dst.) = Sekretariat.
     * Mengembalikan KODE bidang yang dipimpin, atau null bila baris bukan batas kepala.
     * ("Kepala Sub Bagian" BUKAN batas — dia tetap staf sekretariat.)
     */
    public static function kepalaBidangDariJabatan(?string $jabatan): ?string
    {
        $T = mb_strtoupper(preg_replace('/\s+/u', ' ', trim((string) $jabatan)) ?? '');
        if ($T === '') return null;

        // Sekretaris Dinas = kepala Sekretariat (bukan batas pergantian bagian)
        if (preg_match('/SEKRETARIS\s+DINAS/u', $T)) return '1';

        // Kepala UPTD / Pengelola Objek Wisata + wilayah → batas UPTD bersangkutan
        if (preg_match('/\b(UPTD|PENGELOLA\s+OBJEK\s+WISATA)\b/u', $T)) {
            if (preg_match('/\bKAWALI\b/u', $T)) return 'UPTD-KAW';
            if (preg_match('/\bCIAMIS\b/u', $T)) return 'UPTD-CIA';
        }

        // Kepala Bidang <X> → batas bidang X; kata kunci yang muncul PERTAMA di teks menang
        if (preg_match('/KEPALA\s+BIDANG/u', $T)) {
            $pos = null; $kode = null;
            foreach (['DESTINASI' => '2', 'PEMASARAN' => '3', 'EKONOMI' => '4'] as $kw => $kd) {
                $p = mb_strpos($T, $kw);
                if ($p !== false && ($pos === null || $p < $pos)) { $pos = $p; $kode = $kd; }
            }
            return $kode; // null bila "Kepala Bidang" tanpa nama bidang yang dikenal
        }
        return null;
    }

    /**
     * Konfirmasi Dinas 11 Sep 2026 — baca kolom UNIT KERJA (kolom paling kanan file
     * Excel) → kode bidang. Bila sel pada suatu baris menyebut bidang secara eksplisit,
     * itulah penempatan baris tersebut (menang atas urutan batas kepala, karena kolom
     * ini memang kolom penempatan resmi). NULL bila kosong / generik (mis. hanya
     * "Dinas Pariwisata") → baris mengikuti urutan batas kepala dari kolom jabatan.
     */
    public static function kodeDariUnitKerja(?string $unit): ?string
    {
        $T = mb_strtoupper(preg_replace('/\s+/u', ' ', trim((string) $unit)) ?? '');
        if ($T === '') return null;

        // UPTD / Pengelola Objek Wisata + wilayah → UPTD bersangkutan
        if (preg_match('/\bUPTD?\b/u', $T) || preg_match('/OBJEK\s+WISATA/u', $T)) {
            if (preg_match('/\bKAWALI\b/u', $T)) return 'UPTD-KAW';
            if (preg_match('/\bCIAMIS\b/u', $T)) return 'UPTD-CIA';
        }
        if (preg_match('/EKONOMI\s+KREATIF|\bEKRAB\b/u', $T)) return '4';
        if (preg_match('/PEMASARAN/u', $T)) return '3';
        if (preg_match('/DESTINASI|DAYA\s+TARIK/u', $T)) return '2';
        if (preg_match('/SEKRETARIAT|\bSEKRE\b/u', $T)) return '1';
        return null; // generik — ikut urutan batas kepala
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
            // 9 Sep 2026: baris "TAHUN <tahun>" bisa saja rata-tengah (merged cell) sehingga tidak lagi
            // persis di kolom 0, atau ditulis "TAHUN ANGGARAN 2026" — cek kolom 0 dulu (format lama),
            // lalu toleran: baris nyaris kosong yang mengandung pola TAHUN <tahun> di sel mana pun.
            if (preg_match('/^TAHUN\s*(?:ANGGARAN\s*)?(\d{4})/i', trim($r[0]), $m)) {
                $tahun = (int) $m[1];
                $years[$tahun] = true;
                $layoutB = false; $idb = null; $urutan = 0;
                continue;
            }
            $isiSel = array_filter(array_slice($r, 0, 8), fn ($c) => trim((string) $c) !== '');
            if (count($isiSel) <= 2) {
                $ketemuTahun = false;
                foreach ($isiSel as $cell) {
                    if (preg_match('/^TAHUN\s*(?:ANGGARAN\s*)?(\d{4})/i', trim((string) $cell), $m)) { $ketemuTahun = true; break; }
                }
                if ($ketemuTahun) {
                    $tahun = (int) $m[1];
                    $years[$tahun] = true;
                    $layoutB = false; $idb = null; $urutan = 0;
                    continue;
                }
            }
            if (! $tahun) continue;
            $jml = strtoupper(trim($r[0]));
            if (str_starts_with($jml, 'JUMLAH')) { $tahun = $tahun; continue; }
            // header? kenali dari kata SUMBER DANA / Indikator
            $joined = mb_strtoupper(implode('|', $r));
            if (str_contains($joined, 'SUMBER DANA')) { $layoutB = str_contains($joined, 'INDIKATOR'); continue; }
            if (str_contains($joined, ' JAN ') || preg_match('/\bJan(uari)?\b/u', $joined)) continue;

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

    /**
     * Menyimpan hasil parseKinerja ke tabel capaian_program & capaian_bulanan.
     *
     * @param  array  $rows  Baris mentah hasil rowsFrom()
     * @param  bool  $gantiTahun  true = hapus semua data tahun terkait dulu (mode "ganti")
     * @return array{imported:int, years:int[], dibuat:int, diperbarui:int, mode:string}
     */
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

    /**
     * Membaca layout PAD: baris TAHUN, header No./No. Rekening, kategori A/B/C,
     * lalu baris objek penerimaan beserta 12 kolom realisasi bulanan.
     *
     * @return array<int, array<string, mixed>>
     */
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
            $bulanIni     = now()->month;                                   // 1-12
            $idxBulanIni  = $bulanIni - 1;                                  // 0-based index array
            $idxBulanLalu = $idxBulanIni - 1 >= 0 ? $idxBulanIni - 1 : 11; // Desember tahun lalu jika Januari
            $out[] = [
                'tahun'                => $tahun,
                'kategori'             => $kategori,
                'no_rekening'          => trim($r[1]) ?: null,
                'objek'                => trim($r[2]),
                'target_tahunan'       => self::num($r[3]),
                'target_perubahan'     => self::num($r[4]),
                'realisasi_bulan_lalu' => $months[$idxBulanLalu] ?? 0,
                'realisasi_bulan_ini'  => $months[$idxBulanIni]  ?? 0,
                'kumulatif'            => self::num($r[17]) ?: array_sum($months),
                'persentase'           => self::num($r[19]),
                'sisa_target'          => self::num($r[18]),
            ];
        }
        return $out;
    }

    /**
     * Menyimpan hasil parsePad ke tabel pads (upsert per tahun + no_rekening).
     *
     * @return array{imported:int, years:int[], dibuat:int, diperbarui:int, mode:string}
     */
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

    /**
     * Membaca daftar pegawai: kolom dikenali dari teks header (baris 1-3),
     * dengan fallback ke posisi template Dinas lama.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function parsePegawai(array $rows): array
    {
        $out = []; $batas = [];
        // v21: kolom dikenali dari TEKS HEADER (baris 1-3), bukan posisi tetap — susunan
        // file user apa pun terbaca. Fallback ke posisi template Dinas lama bila header absen.
        $petaKolom = []; $kolomPangkat = [];
        foreach (array_slice($rows, 0, 3) as $barisHeader) {
            if (! is_array($barisHeader)) continue;
            foreach ($barisHeader as $indeksKolom => $nilaiHeader) {
                $teksHeader = str_replace([' ', '.'], '', strtoupper(trim((string) $nilaiHeader)));
                if ($teksHeader === '') continue;
                if (str_contains($teksHeader, 'PANGKAT') && str_contains($teksHeader, 'GOL')) { if (! in_array($indeksKolom, $kolomPangkat, true)) $kolomPangkat[] = $indeksKolom; continue; }
                if (str_starts_with($teksHeader, 'ASN')) $petaKolom['jenis_asn'] ??= $indeksKolom;
                foreach ([
                    'TEMPATLAHIR' => 'tempat_lahir', 'TANGGALLAHIR' => 'tanggal_lahir',
                    'TMTCPNS' => 'tmt_cpns', 'TMTSEKARANG' => 'tmt_sekarang', 'TMTJABATAN' => 'tmt_jabatan',
                    'JABATANSEKARANG' => 'jabatan', 'PENDIDIKANUMUM' => 'pendidikan',
                    'DIKLATSTRUKTURAL' => 'diklat_struktural', 'DIKLATFUNGSIONAL' => 'diklat_fungsional',
                    'DIKLATTEKNIS' => 'diklat_teknis', 'JENISKELAMIN' => 'jenis_kelamin', 'UNITKERJA' => 'unit_kerja',
                ] as $kunci => $field) { if ($teksHeader === $kunci) $petaKolom[$field] ??= $indeksKolom; }
            }
        }
        $sel = fn (array $r, ?int $i): string => $i === null ? '' : trim((string) ($r[$i] ?? ''));
        $kolom = fn (string $field, int $lama): int => $petaKolom[$field] ?? $lama;

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
                    $teksSel = strtoupper(trim((string) $cell));
                    if (in_array($teksSel, ['L', 'P', 'LAKI-LAKI', 'PEREMPUAN'], true)) { $jk = str_starts_with($teksSel, 'L') ? 'L' : 'P'; break; }
                }
            }
            // dua header "PANGKAT/GOL" -> kolom pertama = pangkat AWAL, terakhir = pangkat TERAKHIR
            $pgAwal = count($kolomPangkat) >= 2 ? $kolomPangkat[0] : null;
            $pgAkhir = $kolomPangkat ? end($kolomPangkat) : 7;
            $jabatan = $sel($r, $kolom('jabatan', 10));
            $unit = $sel($r, $kolom('unit_kerja', 18));
            $pendidikan = $sel($r, $kolom('pendidikan', 12));
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
                'id_bidang' => null, // diisi setelah seluruh baris terkumpul (penempatan berurutan, lihat bawah)
            ];
            $batas[] = self::kepalaBidangDariJabatan($jabatan);
        }

        /* Konfirmasi Dinas 10 Sep 2026 — penempatan bidang mengikuti URUTAN baris file:
         * baris "Kepala Bidang X" = batas (dia kepala X), baris di bawahnya staf X sampai
         * batas berikutnya; di atas batas pertama (Sekretaris, Kasubbag, dst.) = Sekretariat.
         * Jabatan tetap tertulis sesuai SK. Bila file tidak memuat satu pun batas kepala →
         * fallback menebak per-baris dari teks jabatan/unit kerja (perilaku lama). */
        $adaBatas = false;
        foreach ($batas as $k) if ($k !== null && $k !== '1') { $adaBatas = true; break; }

        if ($adaBatas) {
            foreach ($batas as $k) if (str_starts_with((string) $k, 'UPTD')) { Bidang::pastikanUptd(); break; }
            foreach ($out as $row) if (str_starts_with((string) self::kodeDariUnitKerja($row['unit_kerja'] ?? null), 'UPTD')) { Bidang::pastikanUptd(); break; }
            $idDariKode = [];
            foreach (Bidang::all() as $bidang) $idDariKode[$bidang->kode] = (int) $bidang->id;
            $current = $idDariKode['1'] ?? null; // di atas batas pertama = Sekretariat
            foreach ($out as $i => $row) {
                $k = $batas[$i] ?? null;
                if ($k !== null && $k !== '1' && ($idDariKode[$k] ?? null)) $current = $idDariKode[$k];

                // Konfirmasi Dinas 11 Sep: kolom UNIT KERJA (paling kanan di file) ikut
                // dibaca — bila sel baris ini menyebut bidang secara eksplisit, itulah
                // yang MENANG untuk baris tersebut; selain itu mengikuti batas kepala
                // (urutan jabatan).
                $uk = self::kodeDariUnitKerja($row['unit_kerja'] ?? null);
                $out[$i]['id_bidang'] = ($uk !== null && isset($idDariKode[$uk])) ? $idDariKode[$uk] : $current;
            }
        } else {
            foreach ($out as $i => $row) {
                $out[$i]['id_bidang'] = Bidang::tebakDariTeks(trim((string) $row['jabatan'] . ' ' . (string) $row['unit_kerja']));
            }
        }
        return $out;
    }

    /**
     * Menyimpan hasil parsePegawai: upsert pegawai per NIP, catat ketua bidang,
     * lalu sinkronkan akun login + peran/bidangnya.
     *
     * @return array{dibuat:int, diperbarui:int, akun_baru:int, akun_disinkron:int}
     */
    public static function applyPegawai(array $rows): array
    {
        $p = self::parsePegawai($rows);
        $dibuat = 0; $diperbarui = 0; $akunBaru = 0; $akunSinkron = 0;
        $pw = config('dispar.password_default_pegawai');
        foreach ($p as $data) {
            $existingPeg = Pegawai::where('nip', $data['nip'])->first();
            $exists = (bool) $existingPeg;
            // Jangan timpa status pensiun/nonaktif yang sudah diatur manual — file absensi tidak memuat kolom status.
            if ($exists && $existingPeg->status !== 'aktif') {
                unset($data['status']);
            }
            Pegawai::updateOrCreate(['nip' => $data['nip']], $data);
            $exists ? $diperbarui++ : $dibuat++;

            // Konfirmasi Dinas 10 Sep: baris batas kepala ("Kepala Bidang/UPTD/Sekretaris
            // Dinas") → catat namanya sebagai ketua bidang, bila kolom ketua masih kosong.
            $kepalaKode = self::kepalaBidangDariJabatan($data['jabatan']);
            if ($kepalaKode) {
                $bidangKepala = Bidang::where('kode', $kepalaKode)->first();
                if ($bidangKepala && ! trim((string) $bidangKepala->ketua)) $bidangKepala->update(['ketua' => $data['nama']]);
            }

            $bidang = $data['id_bidang'];
            $peran = self::peranDariJabatan($data['jabatan']);
            // status terkini pegawai (setelah preservasi di atas) untuk sinkron akun
            $statusPeg = $existingPeg ? $existingPeg->status : ($data['status'] ?? 'aktif');
            // jika baru dibuat, ambil dari DB
            if ($exists) {
                $freshPeg = Pegawai::where('nip', $data['nip'])->first();
                $statusPeg = $freshPeg->status ?? $statusPeg;
            }
            $user = User::where('nip', $data['nip'])->first();
            if (! $user) {
                User::create([
                    'name' => $data['nama'],
                    'nip' => $data['nip'],
                    'password' => $pw,
                    'role' => $peran,
                    'id_bidang' => $bidang,
                    'jabatan' => $data['jabatan'],
                    'status' => $statusPeg,
                ]);
                $akunBaru++;
            } else {
                $ubah = [];
                // sinkron status selalu (aktif/nonaktif/pensiun)
                if ($user->status !== $statusPeg) $ubah['status'] = $statusPeg;
                if ($user->role !== 'admin') {
                    if ($bidang && (int) $user->id_bidang !== (int) $bidang) $ubah['id_bidang'] = $bidang;
                    if ($user->role !== $peran) $ubah['role'] = $peran;
                    if ($data['jabatan'] && $user->jabatan !== $data['jabatan']) $ubah['jabatan'] = $data['jabatan'];
                }
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
        $sel = fn (array $r, int $i): string => trim((string) ($r[$i] ?? ''));

        $barisHeader = null;
        foreach ($rows as $i => $baris) {
            if (preg_match('/^no\.?$/i', $sel($baris, 0))) { $barisHeader = $i; break; }
        }
        if ($barisHeader === null) return [];

        // deteksi kolom tahun (baris setelah header), tiap tahun = awal triplet (target, realisasi, persen)
        $years = [];
        for ($i = $barisHeader + 1; $i <= $barisHeader + 3 && $i < count($rows); $i++) {
            foreach ($rows[$i] as $indeksKolom => $cell) {
                if (preg_match('/^(20\d{2})$/', trim((string) $cell), $match)) {
                    $years[(int) $match[1]] = ['t' => $indeksKolom];
                }
            }
            if ($years) break;
        }
        if (!$years) return [];

        $bersihkanTeks = function (string $v): ?string {
            if ($v === '' || strcasecmp($v, 'n/a') === 0 || $v === '-') return null;
            return $v;
        };

        $out = [];
        $induk = -1;
        for ($i = $barisHeader + 1; $i < count($rows); $i++) {
            $baris = array_values($rows[$i]);
            $nomor = $sel($baris, 0); $teksSasaran = $sel($baris, 1); $teksIndikator = $sel($baris, 2); $teksSatuan = $sel($baris, 3);

            if ($nomor !== '' && $teksSasaran !== '' && preg_match('/^\d+$/', $nomor)) {
                $cakupan = null;
                if (preg_match('/Sasaran\s*Tahun\s*:?\s*(.+)/i', $teksSasaran, $match)) {
                    $cakupan = trim($match[1]);
                    $teksSasaran = trim(preg_replace('/\n?Sasaran\s*Tahun.*/is', '', $teksSasaran));
                }
                $teksSasaran = trim(preg_replace('/\s*\n\s*/', ' ', $teksSasaran));
                $out[] = ['nomor' => (int) $nomor, 'judul' => $teksSasaran, 'cakupan' => $cakupan ?: null,
                    'id_bidang' => \App\Models\Bidang::tebakDariTeks($teksSasaran), 'indikator' => []];
                $induk = count($out) - 1;
            }

            if ($induk < 0 || $teksIndikator === '' || mb_stripos($teksIndikator, 'indikator kinerja') !== false) continue;

            $nilai = [];
            foreach ($years as $tahun => $posisi) {
                $target = $bersihkanTeks($sel($baris, $posisi['t']));
                $realisasi = $bersihkanTeks($sel($baris, $posisi['t'] + 1));
                $persen = $bersihkanTeks($sel($baris, $posisi['t'] + 2));
                if ($target !== null || $realisasi !== null || $persen !== null) {
                    $nilai[$tahun] = ['target' => $target, 'realisasi' => $realisasi, 'persen' => $persen];
                }
            }
            $out[$induk]['indikator'][] = ['nama' => $teksIndikator, 'satuan' => $teksSatuan ?: null, 'nilai' => $nilai];
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
     * Selaraskan peran, bidang, status, dan jabatan seluruh akun dengan tabel pegawai (dua arah).
     * - Pegawai -> User untuk peran/bidang/jabatan (non-admin)
     * - Status diselaraskan dua arah: bila salah satu pensiun/nonaktif maka kedua jadi itu (pensiun menang).
     * - Untuk akun nonaktif/pensiun tanpa baris pegawai → buat baris minimal agar muncul di Data Pegawai.
     * Aman dipanggil berulang (idempoten).
     */
    public static function sinkronAkunPegawai(): int
    {
        $adaUnit = \Illuminate\Support\Facades\Schema::hasColumn('pegawai', 'unit_kerja');
        $ubah = 0;
        // 1) sinkron User yang punya Pegawai (dua arah status)
        foreach (User::with('pegawai')->get() as $user) {
            $p = $user->pegawai ?: Pegawai::where('nip', $user->nip)->first();
            if ($p) {
                $perubahan = [];
                // peran/bidang/jabatan hanya untuk non-admin (admin tetap admin)
                if ($user->role !== 'admin') {
                    $idBidang = $p->id_bidang ?? Bidang::tebakDariTeks(trim(((string) $p->jabatan) . ' ' . ($adaUnit ? (string) ($p->unit_kerja ?? '') : '')));
                    if ($idBidang && (int) $user->id_bidang !== (int) $idBidang) $perubahan['id_bidang'] = $idBidang;
                    $peran = self::peranDariJabatan($p->jabatan);
                    if ($user->role !== $peran) $perubahan['role'] = $peran;
                    if ($p->jabatan && $user->jabatan !== $p->jabatan) $perubahan['jabatan'] = $p->jabatan;
                }
                // status: dua arah, pensiun > nonaktif > aktif
                $desired = 'aktif';
                if ($user->status === 'pensiun' || $p->status === 'pensiun') $desired = 'pensiun';
                elseif ($user->status === 'nonaktif' || $p->status === 'nonaktif') $desired = 'nonaktif';
                if ($user->status !== $desired) $perubahan['status'] = $desired;
                if ($perubahan) { $user->update($perubahan); $ubah++; }
                if ($p->status !== $desired) { $p->update(['status' => $desired]); $ubah++; }
            } else {
                // 2) akun nonaktif/pensiun tanpa pegawai → buat pegawai minimal agar filter Data Pegawai ada isinya
                if (in_array($user->status, ['nonaktif', 'pensiun'], true)) {
                    Pegawai::create(['nip' => $user->nip, 'nama' => $user->name, 'status' => $user->status, 'id_bidang' => $user->id_bidang, 'jabatan' => $user->jabatan]);
                    $ubah++;
                }
            }
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
        $barisHeader = null;
        foreach ($rows as $i => $baris) {
            foreach ($baris as $nilaiSel) {
                if (mb_strtoupper(trim((string) $nilaiSel)) === 'INDIKATOR') { $barisHeader = $i; break 2; }
            }
        }
        if ($barisHeader === null) return [];

        $noCol = $indCol = $satCol = $ketCol = null;
        foreach ($rows[$barisHeader] as $indeksKolom => $nilai) {
            $teks = mb_strtoupper(trim((string) $nilai));
            if ($noCol === null && preg_match('/^NO\.?$/u', $teks)) $noCol = $indeksKolom;
            elseif ($teks === 'INDIKATOR') $indCol = $indeksKolom;
            elseif ($teks === 'SATUAN') $satCol = $indeksKolom;
            elseif ($teks === 'KETERANGAN') $ketCol = $indeksKolom;
        }
        $noCol ??= 0; $indCol ??= $noCol + 1; $satCol ??= $indCol + 1;

        // tahun = sel 4 digit di baris header / 2 baris berikutnya, di kolom kanan SATUAN
        $tahunCols = [];
        $kandidatTahun = array_slice($rows, $barisHeader, 3);
        $lebar = 0; foreach ($kandidatTahun as $barisTahun) $lebar = max($lebar, count($barisTahun));
        for ($indeksKolom = 0; $indeksKolom < $lebar; $indeksKolom++) {
            if ($indeksKolom <= $satCol) continue;
            foreach ($kandidatTahun as $barisTahun) {
                if (preg_match('/^(19|20)\d{2}$/', trim((string) ($barisTahun[$indeksKolom] ?? '')), $match)) { $tahunCols[$indeksKolom] = (int) $match[0]; break; }
            }
        }
        if (!$tahunCols) return [];

        $out = [];
        for ($indeks = $barisHeader + 1; $indeks < count($rows); $indeks++) {
            $baris = $rows[$indeks];
            $indikator = trim((string) ($baris[$indCol] ?? ''));
            if ($indikator === '' || mb_strtoupper($indikator) === 'INDIKATOR') continue; // baris sub-header/tahun
            $nilai = [];
            foreach ($tahunCols as $indeksKolom => $tahun) {
                $angka = self::angkaSel((string) ($baris[$indeksKolom] ?? ''));
                if ($angka !== null) $nilai[$tahun] = $angka;
            }
            $nomor = trim((string) ($baris[$noCol] ?? ''));
            $out[] = [
                'no' => ctype_digit($nomor) ? (int) $nomor : null,
                'indikator' => preg_replace('/\s+/', ' ', $indikator),
                'satuan' => ($satuan = trim((string) ($baris[$satCol] ?? ''))) !== '' ? $satuan : null,
                'keterangan' => $ketCol !== null ? (trim((string) ($baris[$ketCol] ?? '')) ?: null) : null,
                'nilai' => $nilai,
            ];
        }
        return $out;
    }

    /** Upsert per nama indikator (toleran besar/kecil & spasi ganda); tahun yang tidak disebut file dibiarkan. */
    public static function applyStatistik(array $p): array
    {
        $dibuat = 0; $diperbarui = 0; $jumlahSel = 0;
        foreach ($data as $baris) {
            $key = mb_strtoupper(preg_replace('/\s+/', ' ', trim($baris['indikator'])));
            $statistik = \App\Models\Statistik::whereRaw('upper(indikator) = ?', [$key])->first();
            if ($statistik) { $diperbarui++; } else { $statistik = new \App\Models\Statistik(); $dibuat++; }
            $statistik->indikator = trim($baris['indikator']);
            $statistik->no_urut = $baris['no']; $statistik->satuan = $baris['satuan']; $statistik->keterangan = $baris['keterangan'];
            $statistik->save();
            foreach ($baris['nilai'] as $tahun => $nilai) {
                \App\Models\StatistikNilai::updateOrCreate(
                    ['id_statistik' => $statistik->id, 'tahun' => (int) $tahun],
                    ['nilai' => $nilai],
                );
                $jumlahSel++;
            }
        }
        return ['dibuat' => $dibuat, 'diperbarui' => $diperbarui, 'sel' => $jumlahSel];
    }

    /* ================= PRESTASI ================= */

    /**
     * Parsing file Daftar Penghargaan / Prestasi Dinas.
     *
     * Format file: blok berulang diawali baris "TAHUN 2021" (atau tahun lain),
     * lalu baris header kolom, lalu baris data bernomor (angka di kolom 0).
     * Kolom "Tingkat" kadang tidak ada di blok tahun lama — ditangani dengan
     * deteksi jumlah kolom dan posisi header yang dinamis.
     *
     * Kolom yang diharapkan (0-based):
     *   0 = No
     *   1 = Nama Penghargaan
     *   2 = Nama Penerima
     *   3 = Pemberi Penghargaan
     *   4 = Tahun Penghargaan
     *   5 = Perangkat Daerah Leading Sektor (dilewati)
     *   6 = Tingkat  (opsional — mungkin tidak ada)
     */
    public static function parsePrestasi(array $rows): array
    {
        $out = [];
        $tahun = null;
        $colNo = $colNama = $colPenerima = $colPemberi = $colTahun = $colTingkat = null;

        foreach ($rows as $r) {
            $rowStr = mb_strtolower(implode(' ', array_map('strval', $r)));

            if (preg_match('/tahun\s*(\d{4})/i', $rowStr, $m) && ! str_contains($rowStr, 'penghargaan')) {
                $tahun = (int) $m[1];
                continue;
            }

            if (str_contains($rowStr, 'penghargaan') && str_contains($rowStr, 'penerima')) {
                $colNo = $colNama = $colPenerima = $colPemberi = $colTahun = $colTingkat = null;
                foreach ($r as $indeksKolom => $nilaiHeader) {
                    $teks = mb_strtolower(trim((string) $nilaiHeader));
                    if ($teks === 'no') $colNo = $indeksKolom;
                    elseif ($teks === 'nama penghargaan') $colNama = $indeksKolom;
                    elseif ($teks === 'nama penerima') $colPenerima = $indeksKolom;
                    elseif ($teks === 'pemberi penghargaan') $colPemberi = $indeksKolom;
                    elseif (str_starts_with($teks, 'tahun')) $colTahun = $indeksKolom;
                    elseif ($teks === 'tingkat') $colTingkat = $indeksKolom;
                }
                continue;
            }

            if (! $tahun || $colNo === null || $colNama === null) continue;

            $noVal = trim((string) ($r[$colNo] ?? ''));
            if (! is_numeric($noVal)) continue;

            $namaPenghargaan = trim((string) ($r[$colNama] ?? ''));
            if ($namaPenghargaan === '') continue;

            $tahunPenghargaan = $colTahun !== null && is_numeric(trim((string) ($r[$colTahun] ?? '')))
                ? (int) trim((string) $r[$colTahun]) : $tahun;

            $out[] = [
                'nama_penghargaan' => $namaPenghargaan,
                'nama_penerima' => $colPenerima !== null ? (trim((string) ($r[$colPenerima] ?? '')) ?: null) : null,
                'pemberi_penghargaan' => $colPemberi !== null ? (trim((string) ($r[$colPemberi] ?? '')) ?: null) : null,
                'tahun' => $tahunPenghargaan,
                'tingkat' => $colTingkat !== null ? (trim((string) ($r[$colTingkat] ?? '')) ?: null) : null,
            ];
        }

        return $out;
    }

    /**
     * Hapus data prestasi per tahun yang terlibat, lalu insert ulang.
     * Menerima array data yang sudah di-parse maupun raw baris spreadsheet.
     */
    public static function applyPrestasi(array $rows): array
    {
        // Jika belum di-parse (masih raw baris spreadsheet), jalankan parsePrestasi
        $p = (!empty($rows) && isset($rows[0]['nama_penghargaan']))
            ? $rows
            : self::parsePrestasi($rows);

        $years = array_values(array_unique(array_column($p, 'tahun')));

        \Illuminate\Support\Facades\DB::transaction(function () use ($p, $years) {
            // hapus semua baris untuk tahun-tahun yang terlibat
            \App\Models\Prestasi::whereIn('tahun', $years)->delete();
            // insert baru
            foreach ($p as $row) {
                \App\Models\Prestasi::create($row);
            }
        });

        return ['imported' => count($p), 'years' => $years];
    }
}
