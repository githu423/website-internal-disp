<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Notifikasi pengguna.
 *
 * - Halaman daftar notifikasi.
 * - JSON untuk badge jumlah belum dibaca + beberapa notifikasi terbaru.
 * - Penanda "baca semua".
 */
class NotifikasiController extends Controller
{
    /**
     * Halaman daftar notifikasi.
     */
    public function index(Request $request): View
    {
        // ===== DAFTAR NOTIFIKASI PENGGUNA =====
        return view('notifikasi.index', ['items' => $request->user()->notifikasi()->paginate(20)]);
    }

    /**
     * JSON notifikasi belum dibaca terbatas + jumlah total yang belum dibaca.
     */
    public function unread(Request $request): JsonResponse
    {
        // ===== NOTIFIKASI BELUM DIBACA (MAKS. 8) + TOTAL BADGE =====
        $daftar = $request->user()->notifikasi()->belum()->latest()->limit(8)->get()
            ->map(fn ($notifikasi) => ['id' => $notifikasi->id, 'judul' => $notifikasi->judul, 'isi' => $notifikasi->isi, 'tipe' => $notifikasi->tipe, 'url' => $notifikasi->url, 'waktu' => $notifikasi->created_at->diffForHumans()]);

        // Hitungan badge = SEMUA yang belum dibaca (bukan hanya 8 teratas yang dirender).
        return response()->json(['belum' => $request->user()->notifikasi()->belum()->count(), 'items' => $daftar]);
    }

    /**
     * Menandai semua notifikasi sebagai sudah dibaca.
     */
    public function bacaSemua(Request $request): RedirectResponse
    {
        // ===== TANDAI SEMUA SUDAH DIBACA =====
        $request->user()->notifikasi()->belum()->update(['dibaca_at' => now()]);

        return back();
    }
}
