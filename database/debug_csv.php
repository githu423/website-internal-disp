<?php
chdir(dirname(__DIR__));
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

// ── Cek format NIP di DB ──────────────────────────────────────────────────────
echo "=== Sample NIP di database ===\n";
$samples = DB::table('pegawai')->whereNotNull('nip')->limit(6)->get(['nip','nama']);
foreach ($samples as $p) {
    echo "  DB NIP: [" . $p->nip . "] " . $p->nama . "\n";
}

// ── Cek kolom CSV ─────────────────────────────────────────────────────────────
echo "\n=== Struktur kolom CSV (baris data ke-1) ===\n";
$f = fopen(__DIR__ . '/source/PEGAWAI DISPAR.csv', 'r');
$row0 = fgetcsv($f); // header baris 1
$row1 = fgetcsv($f); // header baris 2
$row2 = fgetcsv($f); // data pertama
fclose($f);

foreach ($row2 as $idx => $val) {
    $v = trim($val);
    if ($v !== '') echo "  [$idx] " . substr($v, 0, 70) . "\n";
}
