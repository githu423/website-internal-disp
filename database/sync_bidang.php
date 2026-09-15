<?php
/**
 * sync_bidang.php — Re-assign id_bidang pegawai berdasarkan CSV
 *
 * Logika:
 *  1. Baca CSV urut dari atas ke bawah (fgetcsv agar multiline quoted field aman)
 *  2. Jika kolom unit_kerja (index 18) terisi → gunakan itu sebagai bidang
 *  3. Jika kosong → ikut bidang dari "Kepala" terakhir yang terdeteksi di kolom jabatan (index 10)
 */

chdir(dirname(__DIR__));
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

// ── Map unit_kerja → id_bidang ────────────────────────────────────────────────
$unitMap = [
    'sekre'       => 1,
    'destinasi'   => 2,
    'pemasaran'   => 3,
    'ekraf'       => 4,
    'uptd ciamis' => 5,
    'uptd kawali' => 6,
];

function unitToId(string $uk, array $map): ?int {
    $uk = strtolower(trim($uk));
    if ($uk === '') return null;
    foreach ($map as $key => $id) {
        if (str_contains($uk, $key)) return $id;
    }
    return null;
}

// ── Deteksi "Kepala" dari kolom jabatan ───────────────────────────────────────
function kepalaToId(string $jab): ?int {
    $j = strtolower($jab);
    if (str_contains($j, 'sekretaris dinas') || (str_contains($j, 'kepala') && str_contains($j, 'kepala dinas'))) return 1;
    if (str_contains($j, 'kepala') && str_contains($j, 'destinasi'))                                               return 2;
    if (str_contains($j, 'kepala') && str_contains($j, 'pemasaran'))                                              return 3;
    if (str_contains($j, 'kepala') && (str_contains($j, 'ekonomi kreatif') || str_contains($j, 'ekraf')))         return 4;
    if (str_contains($j, 'kepala') && str_contains($j, 'ciamis'))                                                 return 5;
    if (str_contains($j, 'kepala') && str_contains($j, 'kawali'))                                                 return 6;
    return null;
}

// ── Baca CSV dengan fgetcsv (aman untuk multiline quoted fields) ───────────────
$csvPath = __DIR__ . '/source/PEGAWAI DISPAR.csv';
$f = fopen($csvPath, 'r');

// Skip 2 baris header
fgetcsv($f);
fgetcsv($f);

$bidangNama = [1=>'Sekretariat',2=>'Destinasi',3=>'Pemasaran',4=>'Ekraf',5=>'UPTD Ciamis',6=>'UPTD Kawali'];

$currentBidangId = null;
$assignments     = []; // nipClean → id_bidang
$rowLog          = []; // untuk preview

while (($row = fgetcsv($f)) !== false) {
    $no       = trim($row[0] ?? '');
    $nama     = trim($row[1] ?? '');
    $nipRaw   = trim($row[2] ?? '');
    $jabatan  = trim($row[10] ?? '');
    $unitKerja = trim($row[18] ?? '');

    if ($nipRaw === '' || $no === '') continue;

    // NIP bersih = hapus semua spasi, sesuai format di DB
    $nipClean = preg_replace('/\s+/', '', $nipRaw);

    // Cek kepala → perbarui current bidang
    $kid = kepalaToId($jabatan);
    if ($kid !== null) {
        $currentBidangId = $kid;
    }

    // Tentukan bidang
    if ($unitKerja !== '') {
        $bidangId = unitToId($unitKerja, $unitMap);
        $src = "unit_kerja: '$unitKerja'";
    } else {
        $bidangId = $currentBidangId;
        $src = "(kosong → ikut kepala: " . ($bidangNama[$currentBidangId] ?? '?') . ")";
    }

    $assignments[$nipClean] = $bidangId;
    $rowLog[] = compact('no', 'nama', 'nipClean', 'src', 'bidangId');
}
fclose($f);

// ── Preview ────────────────────────────────────────────────────────────────────
echo str_pad("No.", 5) . str_pad("Nama", 38) . str_pad("Sumber", 35) . "→ Bidang\n";
echo str_repeat('-', 90) . "\n";
foreach ($rowLog as $r) {
    $bidangLabel = $r['bidangId'] ? ($bidangNama[$r['bidangId']] ?? '?') : 'NULL';
    echo str_pad($r['no'], 5)
        . str_pad(substr($r['nama'], 0, 36), 38)
        . str_pad($r['src'], 35)
        . "→ " . $r['bidangId'] . " ($bidangLabel)\n";
}

// ── Update DB ──────────────────────────────────────────────────────────────────
echo "\n" . str_repeat('=', 90) . "\n";
echo "Memulai update database (" . count($assignments) . " baris)...\n\n";

$updated  = 0;
$notFound = 0;

foreach ($assignments as $nipClean => $bidangId) {
    // DB menyimpan NIP tanpa spasi (19 digit)
    $affected = DB::table('pegawai')
        ->where('nip', $nipClean)
        ->update(['id_bidang' => $bidangId]);

    if ($affected > 0) {
        $updated++;
    } else {
        echo "  [!] Tidak ditemukan di DB: NIP $nipClean\n";
        $notFound++;
    }
}

echo "\n✓ Berhasil update: $updated pegawai\n";
echo "  Tidak ditemukan: $notFound\n\n";

// ── Rekap akhir ───────────────────────────────────────────────────────────────
$rekap = DB::table('pegawai')
    ->selectRaw('id_bidang, count(*) as total')
    ->groupBy('id_bidang')
    ->orderBy('id_bidang')
    ->get();

echo str_repeat('=', 40) . "\n";
echo "Rekap akhir per bidang:\n";
foreach ($rekap as $r) {
    $nm = $bidangNama[$r->id_bidang] ?? 'Tidak Diketahui';
    echo "  " . str_pad(($r->id_bidang ?? 'NULL'), 6) . " $nm: {$r->total} pegawai\n";
}

// Cek apakah masih ada null
$nullCount = DB::table('pegawai')->whereNull('id_bidang')->count();
echo "  NULL (belum terhubung): $nullCount pegawai\n";
