<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\Statistik;
use App\Models\StatistikNilai;
use App\Services\ImportService;
use App\Services\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kelola Statistik Pariwisata — format file "DATA STATISTIK PARIWISATA
 * KABUPATEN CIAMIS" (indikator × tahun, permintaan Ricky 7 Sep 2026).
 *
 * Format lama "target vs capaian per bulan" DICABUT dari modul ini karena
 * dobel dengan halaman Kegiatan & Kinerja; tabel capaian_bulanan tetap utuh
 * untuk modul Capaian Kinerja.
 */
class StatistikController extends Controller
{
    public function index(Request $request): View
    {
        $rows = Statistik::with('nilais')->orderByRaw('COALESCE(no_urut, 99999)')->orderBy('indikator')->get();
        $tahunList = StatistikNilai::distinct()->orderBy('tahun')->pluck('tahun')->map(fn ($t) => (int) $t)->all();
        if (!$tahunList) $tahunList = [(int) now()->year];

        return view('admin.statistik.index', [
            'rows' => $rows->map(fn ($r) => [
                'id' => $r->id, 'no' => $r->no_urut, 'indikator' => $r->indikator,
                'satuan' => $r->satuan, 'ket' => $r->keterangan,
                'nilai' => $r->nilais->pluck('nilai', 'tahun')->map(fn ($v) => (string) $v)->all(),
            ])->values(),
            'tahunList' => $tahunList,
            'total' => Statistik::count(),
        ]);
    }

    /** Simpan seluruh grid sekaligus; baris ber-id dgn indikator dikosongkan/cek hapus = dihapus. */
    public function store(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'tahunList' => ['required', 'array'],
            'rows' => ['nullable', 'array'],
            'rows.*.id' => ['nullable', 'integer', 'exists:statistik,id'],
            'rows.*.no_urut' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'rows.*.indikator' => ['nullable', 'string', 'max:180'],
            'rows.*.satuan' => ['nullable', 'string', 'max:40'],
            'rows.*.keterangan' => ['nullable', 'string', 'max:255'],
            'rows.*.nilai' => ['nullable', 'array'],
            'rows.*.hapus' => ['nullable', 'boolean'],
        ]);

        $years = array_values(array_unique(array_map('intval', (array) $d['tahunList'])));
        $tersimpan = 0; $terhapus = 0;

        foreach ((array) ($d['rows'] ?? []) as $r) {
            $id = $r['id'] ?? null;
            if ($id && !empty($r['hapus'])) { Statistik::destroy((int) $id); $terhapus++; continue; }
            $nama = trim((string) ($r['indikator'] ?? ''));
            if ($nama === '') continue; // baris baru kosong -> abaikan
            $st = $id ? Statistik::find((int) $id) : null;
            if (!$st) { // cari berdasarkan nama agar tidak dobel dgn hasil import
                $st = Statistik::whereRaw('upper(indikator) = ?', [mb_strtoupper(preg_replace('/\s+/', ' ', $nama))])->first()
                    ?? new Statistik();
            }
            $st->indikator = preg_replace('/\s+/', ' ', $nama);
            $st->no_urut = ($n = trim((string) ($r['no_urut'] ?? ''))) !== '' ? (int) $n : null;
            $st->satuan = trim((string) ($r['satuan'] ?? '')) ?: null;
            $st->keterangan = trim((string) ($r['keterangan'] ?? '')) ?: null;
            $st->save();

            foreach ($years as $t) {
                $raw = trim((string) (($r['nilai'][$t] ?? '')));
                if ($raw === '') { StatistikNilai::where('id_statistik', $st->id)->where('tahun', $t)->delete(); continue; }
                $v = ImportService::angkaSel($raw);
                if ($v === null) continue; // tidak bisa dibaca -> jangan menimpa data lama
                StatistikNilai::updateOrCreate(['id_statistik' => $st->id, 'tahun' => $t], ['nilai' => $v]);
            }
            $tersimpan++;
        }

        LogAktivitas::catat('ubah statistik pariwisata', "grid manual: $tersimpan baris disimpan, $terhapus dihapus");
        return back()->with('sukses', 'Statistik tersimpan' . ($terhapus ? " — $tersimpan baris diperbarui, $terhapus dihapus." : '.'));
    }

    /** Impor file Excel/CSV format Data Statistik (judul & baris tahun deteksi otomatis). */
    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls,ods', 'max:20480']]);
        $f = $request->file('file');
        try {
            $rows = ImportService::rowsFrom($f->getRealPath(), $f->getClientOriginalName());
        } catch (\Throwable $e) {
            return back()->with('gagal', 'File gagal dibaca: ' . $e->getMessage());
        }
        $p = ImportService::parseStatistik($rows);
        if (!$p) {
            return back()->with('gagal', 'Baris indikator tidak dikenali. Gunakan format file "Data Statistik Pariwisata": baris judul bebas, lalu header "No. | Indikator | Satuan | Tahun | Keterangan", baris daftar tahun (mis. 2017..2025), kemudian datanya.');
        }
        $h = ImportService::applyStatistik($p);
        NotifikasiService::broadcast('Statistik pariwisata diperbarui',
            "Import statistik: {$h['dibuat']} indikator baru, {$h['diperbarui']} diperbarui, {$h['sel']} sel nilai terisi.",
            'import', route('statistik.index'));
        LogAktivitas::catat('impor statistik pariwisata', json_encode($h));
        return back()->with('sukses', "Import statistik selesai: {$h['dibuat']} indikator baru, {$h['diperbarui']} diperbarui, {$h['sel']} sel nilai terisi.");
    }

    public function destroy(Statistik $statistik): RedirectResponse
    {
        $statistik->delete();
        LogAktivitas::catat('hapus indikator statistik', $statistik->indikator);
        return back()->with('sukses', 'Indikator "' . $statistik->indikator . '" dihapus.');
    }
}
