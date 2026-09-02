<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CapaianIndikator;
use App\Models\CapaianSasaran;
use App\Models\CapaianTahunan;
use App\Models\LogAktivitas;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Kelola matriks Capaian Kinerja (sasaran strategis + indikator + target/realisasi per tahun).
 * Sumber berkas: CAPAIAN KINERJA.csv — impor bersifat menggantikan seluruh data.
 */
class CapaianKinerjaController extends Controller
{
    public function index(Request $request): View
    {
        $sasarans = CapaianSasaran::with(['indikator.tahunan'])->orderBy('urut')->get();
        $years = CapaianTahunan::distinct()->orderBy('tahun')->pluck('tahun')->map(fn ($t) => (int) $t)->all();
        if (!$years) $years = [(int) now()->year - 1, (int) now()->year];

        return view('admin.capaian-kinerja.index', [
            'sasarans' => $sasarans,
            'years' => $years,
            'tambahYears' => range((int) date('Y') + 3, 2015),
            'bidangs' => \App\Models\Bidang::orderBy('kode')->get(),
        ]);
    }

    // ===== Sasaran =====
    public function storeSasaran(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'nomor' => ['nullable', 'integer', 'min:1', 'max:999'],
            'judul' => ['required', 'string', 'max:500'],
            'cakupan' => ['nullable', 'string', 'max:160'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
        ]);
        $d['nomor'] = $d['nomor'] ?? ((int) CapaianSasaran::max('nomor') + 1);
        $d['urut'] = (int) CapaianSasaran::max('urut') + 1;
        CapaianSasaran::create($d);
        LogAktivitas::catat('tambah sasaran kinerja', $d['judul']);

        return back()->with('sukses', 'Sasaran ditambahkan.');
    }

    public function updateSasaran(Request $request, CapaianSasaran $sasaran): RedirectResponse
    {
        $d = $request->validate([
            'nomor' => ['nullable', 'integer', 'min:1', 'max:999'],
            'judul' => ['required', 'string', 'max:500'],
            'cakupan' => ['nullable', 'string', 'max:160'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
        ]);
        $sasaran->update($d);

        return back()->with('sukses', 'Sasaran diperbarui.');
    }

    public function destroySasaran(CapaianSasaran $sasaran): RedirectResponse
    {
        $sasaran->delete();
        LogAktivitas::catat('hapus sasaran kinerja', $sasaran->judul);

        return back()->with('sukses', 'Sasaran beserta indikatornya dihapus.');
    }

    // ===== Indikator =====
    public function storeIndikator(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'sasaran_id' => ['required', 'integer', 'exists:capaian_sasaran,id'],
            'nama' => ['required', 'string', 'max:500'],
            'satuan' => ['nullable', 'string', 'max:80'],
        ]);
        $d['urut'] = (int) CapaianIndikator::where('sasaran_id', $d['sasaran_id'])->max('urut') + 1;
        CapaianIndikator::create($d);

        return back()->with('sukses', 'Indikator ditambahkan.');
    }

    public function updateIndikator(Request $request, CapaianIndikator $indikator): RedirectResponse
    {
        $d = $request->validate([
            'nama' => ['required', 'string', 'max:500'],
            'satuan' => ['nullable', 'string', 'max:80'],
        ]);
        $indikator->update($d);

        return back()->with('sukses', 'Indikator diperbarui.');
    }

    public function destroyIndikator(CapaianIndikator $indikator): RedirectResponse
    {
        $indikator->delete();

        return back()->with('sukses', 'Indikator dihapus.');
    }

    /** Simpan/ubah nilai per tahun satu indikator; sel yang dikosongkan menghapus baris tahun tsb. */
    public function simpanNilai(Request $request, CapaianIndikator $indikator): RedirectResponse
    {
        $d = $request->validate([
            'nilai' => ['nullable', 'array'],
            'nilai.*.target' => ['nullable', 'string', 'max:120'],
            'nilai.*.realisasi' => ['nullable', 'string', 'max:120'],
            'nilai.*.persen' => ['nullable', 'string', 'max:24'],
            't_tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            't_target' => ['nullable', 'string', 'max:120'],
            't_realisasi' => ['nullable', 'string', 'max:120'],
            't_persen' => ['nullable', 'string', 'max:24'],
            'nama' => ['nullable', 'string', 'max:500'],
            'satuan' => ['nullable', 'string', 'max:80'],
        ]);
        if ($d['nama'] ?? null) $indikator->update(['nama' => $d['nama'], 'satuan' => $d['satuan'] ?? null]);

        DB::transaction(function () use ($d, $indikator) {
            foreach ($d['nilai'] ?? [] as $tahun => $n) {
                $this->simpanBaris($indikator, (int) $tahun, $n['target'] ?? null, $n['realisasi'] ?? null, $n['persen'] ?? null);
            }
            if (! empty($d['t_tahun'])) {
                $this->simpanBaris($indikator, (int) $d['t_tahun'], $d['t_target'] ?? null, $d['t_realisasi'] ?? null, $d['t_persen'] ?? null);
            }
        });

        return back()->with('sukses', "Nilai indikator \"{$indikator->nama}\" disimpan.");
    }

    private function simpanBaris(CapaianIndikator $indikator, int $tahun, ?string $t, ?string $r, ?string $p): void
    {
        $ko = fn (?string $v) => ($v = trim((string) $v)) === '' || strcasecmp($v, 'n/a') === 0 ? null : $v;
        [$t, $r, $p] = array_map($ko, [$t, $r, $p]);
        $row = CapaianTahunan::firstOrNew(['indikator_id' => $indikator->id, 'tahun' => $tahun]);
        if ($t === null && $r === null && $p === null) {
            if ($row->exists) $row->delete();
            return;
        }
        $row->target = $t; $row->realisasi = $r; $row->persen = $p; $row->save();
    }

    // ===== Impor berkas =====
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'berkas' => ['required', 'file', 'mimes:csv,xlsx,xls,ods,xlsb'],
            'mode' => ['nullable', 'in:ganti,update'],
        ]);
        $mode = $request->input('mode', 'update');

        $rows = ImportService::rowsFrom($request->file('berkas')->getRealPath());
        $data = ImportService::parseCapaian($rows);
        if (!$data) {
            return back()->with('gagal', 'Format tidak dikenali. Butuh baris header "No. | Sasaran | Indikator Kinerja | Satuan" dan baris tahun dengan kolom Target/Realisasi/% per tahun.');
        }
        $res = ImportService::applyCapaian($data, $mode);
        $pesan = $mode === 'ganti'
            ? "Data diganti seluruhnya: {$res['sasaran_baru']} sasaran, {$res['indikator_baru']} indikator, {$res['nilai_baru']} nilai tahunan."
            : "Gabar/upgrade selesai: {$res['nilai_baru']} nilai baru, {$res['nilai_ubah']} diperbarui, {$res['nilai_sama']} sudah sama (tidak diduplikat)"
              . ($res['indikator_baru'] || $res['sasaran_baru'] ? "; {$res['sasaran_baru']} sasaran & {$res['indikator_baru']} indikator baru dibuat" : '') . '.';
        LogAktivitas::catat('impor capaian kinerja (' . $mode . ')', $pesan);

        return back()->with('sukses', $pesan);
    }
}
