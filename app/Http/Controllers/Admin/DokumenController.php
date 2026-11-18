<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dokumen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Pengelolaan dokumen oleh admin (tanpa batas visibility).
 */
class DokumenController extends Controller
{
    /**
     * Daftar dokumen seluruh bidang + total ukuran berkas.
     */
    public function index(Request $request): View
    {
        // ===== FILTER =====
        $query = Dokumen::query()->with(['bidang', 'uploader', 'kategoriRel.induk.induk']);
        if ($kataKunci = trim((string) $request->query('q'))) {
            $query->where('judul', 'like', "%$kataKunci%");
        }
        if ($visibility = $request->query('visibility')) {
            $query->where('visibility', $visibility);
        }

        // ===== RESPONS =====
        return view('admin.dokumen.index', [
            'dokumen' => $query->latest()->paginate(15)->withQueryString(),
            'totalUkuran' => (int) Dokumen::sum('file_size'),
        ]);
    }

    /**
     * Memperbarui judul/kategori/visibility dokumen.
     */
    public function update(Request $request, Dokumen $dokumen): RedirectResponse
    {
        // ===== VALIDASI & SIMPAN =====
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'kategori' => ['nullable', 'string', 'max:60'],
            'id_kategori' => ['nullable', 'integer', 'exists:kategori_dokumen,id'],
            'visibility' => ['required', 'in:publik,bidang,rahasia'],
        ]);
        $dokumen->update($data);

        return back()->with('sukses', 'Dokumen diperbarui.');
    }

    /**
     * Menghapus berkas dan data dokumen.
     */
    public function destroy(Request $request, Dokumen $dokumen): RedirectResponse
    {
        // ===== HAPUS BERKAS & DATA =====
        Storage::disk('public')->delete($dokumen->file_path);
        $dokumen->delete();

        return back()->with('sukses', 'Dokumen dihapus.');
    }
}
