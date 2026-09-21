<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dokumen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DokumenController extends Controller
{
    public function index(Request $request): View
    {
        $q = Dokumen::query()->with(['bidang', 'uploader', 'kategoriRel.induk.induk']);
        if ($s = trim((string) $request->query('q'))) $q->where('judul', 'like', "%$s%");
        if ($v = $request->query('visibility')) $q->where('visibility', $v);
        return view('admin.dokumen.index', [
            'dokumen' => $q->latest()->paginate(15)->withQueryString(),
            'totalUkuran' => (int) Dokumen::sum('file_size'),
        ]);
    }

    public function update(Request $request, Dokumen $dokumen): RedirectResponse
    {
        $d = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'kategori' => ['nullable', 'string', 'max:60'],
            'id_kategori' => ['nullable', 'integer', 'exists:kategori_dokumen,id'],
            'visibility' => ['required', 'in:publik,bidang,rahasia'],
        ]);
        $dokumen->update($d);
        return back()->with('sukses', 'Dokumen diperbarui.');
    }

    public function destroy(Request $request, Dokumen $dokumen): RedirectResponse
    {
        Storage::disk('public')->delete($dokumen->file_path);
        $dokumen->delete();
        return back()->with('sukses', 'Dokumen dihapus.');
    }
}
