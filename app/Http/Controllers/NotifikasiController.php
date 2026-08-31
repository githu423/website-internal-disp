<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotifikasiController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifikasi.index', ['items' => $request->user()->notifikasi()->paginate(20)]);
    }

    public function unread(Request $request): JsonResponse
    {
        $items = $request->user()->notifikasi()->belum()->latest()->limit(8)->get()
            ->map(fn ($n) => ['id' => $n->id, 'judul' => $n->judul, 'isi' => $n->isi, 'tipe' => $n->tipe, 'url' => $n->url, 'waktu' => $n->created_at->diffForHumans()]);
        return response()->json(['belum' => $items->count(), 'items' => $items]);
    }

    public function bacaSemua(Request $request): RedirectResponse
    {
        $request->user()->notifikasi()->belum()->update(['dibaca_at' => now()]);
        return back();
    }
}
