@extends('layouts.dashboard')
@section('title', 'Dokumen Dinas')
@section('content')
@php
    $u = auth()->user();
    $fmtSize = fn ($b) => $b > 1048576 ? round($b / 1048576, 1) . ' MB' : max(1, round($b / 1024)) . ' KB';
    $aktifBidang = (int) request('bidang', 0);
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h2 class="text-lg font-extrabold">Perpustakaan Dokumen</h2>
        <p class="text-sm text-slate-500">Arsip universal lintas bidang — {{ $total }} dokumen dapat dicari. Dokumen <b>rahasia</b> hanya terlihat oleh pengunggahnya.</p>
    </div>
    @if ($u->isBisaUnggah())
        <button data-modal-open="modal-upload" class="rounded-lg bg-slate-900 text-white text-sm font-bold px-4 py-2.5 hover:bg-slate-700 active:scale-[.98] transition inline-flex items-center gap-2"><x-icon name="upload" class="h-4 w-4" /> Unggah Dokumen</button>
    @else
        <span class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-500">Unggah dokumen hanya oleh pengelola bidang / admin — hubungi bidang Anda</span>
    @endif
</div>

{{-- filter universal: chips bidang --}}
<div class="card p-3.5 mb-4">
    <form method="GET" class="flex flex-wrap gap-2.5 items-center">
        <div class="relative flex-1 min-w-[220px]">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="search" class="h-4 w-4" /></span>
            <input name="q" value="{{ request('q') }}" placeholder="Cari judul, isi deskripsi, kategori, atau nama file…" class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/30 outline-none">
        </div>
        <input type="hidden" name="bidang" value="{{ $aktifBidang ?: '' }}">
        <select name="kategori" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            <option value="">Semua kategori</option>
            @foreach ($kategori as $k)<option value="{{ $k }}" @selected(request('kategori') === $k)>{{ $k }}</option>@endforeach
        </select>
        <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-500 rounded-lg border border-slate-200 px-2.5 py-2 bg-white cursor-pointer hover:border-teal-400 transition">
            <input type="checkbox" name="milik_saya" value="1" @checked(request('milik_saya')) class="rounded border-slate-300 text-teal-600"> punya saya
        </label>
        <button class="rounded-lg bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 text-sm font-bold active:scale-95 transition">Cari</button>
    </form>
    <div class="flex flex-wrap gap-1.5 mt-3 pt-3 border-t border-slate-100">
        <a href="{{ route('dokumen.index', array_diff_key(request()->query(), ['bidang'=>1])) }}" @class(['text-xs font-bold rounded-full px-3 py-1.5 border transition active:scale-95', !$aktifBidang ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-slate-200 text-slate-500 hover:border-teal-400'])>Semua bidang</a>
        @foreach ($bidangs as $b)
            <a href="{{ route('dokumen.index', array_merge(request()->query(), ['bidang' => $b->id])) }}" @class(['text-xs font-bold rounded-full px-3 py-1.5 border transition active:scale-95 inline-flex items-center gap-1.5', $aktifBidang === (int) $b->id ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-slate-200 text-slate-500 hover:border-teal-400'])>
                {{ \Illuminate\Support\Str::limit($b->nama, 30, '…') }}
                <span class="{{ $aktifBidang === (int) $b->id ? 'bg-white/20' : 'bg-slate-100 text-slate-500' }} rounded-full px-1.5 text-[10px]">{{ $b->dokumen_count }}</span>
            </a>
        @endforeach
    </div>
</div>

<div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3.5">
    @forelse ($dokumen as $d)
        @php $mine = $u->id === $d->uploaded_by; @endphp
        <div class="card p-4 flex flex-col gap-3 transition {{ $d->visibility === 'rahasia' ? 'border-rose-200' : 'hover:border-teal-300 hover:shadow' }}">
            <div class="flex items-start gap-3">
                <span class="h-10 w-10 shrink-0 rounded-xl grid place-items-center text-[10px] font-black uppercase {{ str_contains((string) $d->mime, 'pdf') ? 'bg-rose-50 text-rose-600' : (str_starts_with((string) $d->mime, 'image') ? 'bg-violet-50 text-violet-600' : 'bg-sky-50 text-sky-600') }}">{{ pathinfo($d->file_name, PATHINFO_EXTENSION) ?: 'FILE' }}</span>
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-[13.5px] leading-snug line-clamp-2">{{ $d->judul }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $d->kategori ?: 'Umum' }} · {{ $fmtSize($d->file_size) }} · dibaca {{ $d->dibaca_count }}×</p>
                </div>
                <span class="text-[10px] font-bold rounded-full px-2 py-0.5 shrink-0 {{ ['publik' => 'bg-emerald-50 text-emerald-600', 'bidang' => 'bg-sky-50 text-sky-600', 'rahasia' => 'bg-rose-50 text-rose-600'][$d->visibility] }}">{{ ucfirst($d->visibility) }}</span>
            </div>
            @if ($d->deskripsi)<p class="text-xs text-slate-500 line-clamp-2">{{ $d->deskripsi }}</p>@endif
            <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                @if ($d->bidang)<a href="{{ route('bidang.show', $d->bidang) }}" class="font-bold text-sky-600 hover:underline truncate max-w-[60%]">{{ \Illuminate\Support\Str::limit($d->bidang->nama, 28, '…') }}</a>
                @else <span class="font-semibold text-slate-500">Seluruh Dinas</span> @endif
                <span class="truncate">· {{ $d->uploader?->name ?? '—' }}</span>
            </div>
            <div class="mt-auto flex flex-wrap items-center justify-between pt-2.5 border-t border-slate-100 gap-1.5">
                <p class="text-[11px] text-slate-400">{{ $d->created_at->translatedFormat('d M Y, H:i') }}</p>
                <div class="flex gap-1.5">
                    <a href="{{ route('dokumen.show', $d) }}" class="text-[11.5px] font-bold rounded-lg bg-teal-600 hover:bg-teal-700 text-white px-2.5 py-1.5 active:scale-95 transition">👁 Lihat</a>
                    <a href="{{ route('dokumen.unduh', $d) }}" class="text-[11.5px] font-bold rounded-lg bg-slate-900 hover:bg-slate-700 text-white px-2.5 py-1.5 active:scale-95 transition">Unduh</a>
                    @if ($mine)
                    <form method="POST" action="{{ route('dokumen.akses', $d) }}" class="inline">
                        @csrf @method('PATCH')
                        <select name="visibility" onchange="this.form.submit()" title="Ubah akses (hanya Anda sebagai pengunggah)"
                            class="rounded-lg border border-slate-300 bg-white text-[11px] font-bold px-1.5 py-1.5 text-slate-600 cursor-pointer hover:border-teal-400 outline-none">
                            @foreach (['publik' => '🌐 Publik', 'bidang' => '🏢 Bidang', 'rahasia' => '🔒 Rahasia'] as $vv => $lab)
                                <option value="{{ $vv }}" @selected($d->visibility === $vv)>{{ $lab }}</option>
                            @endforeach
                        </select>
                    </form>
                    <form method="POST" action="{{ route('dokumen.destroy', $d) }}" data-confirm="Hapus dokumen beserta filenya?">@csrf @method('DELETE')
                        <button class="text-[11.5px] font-bold rounded-lg border border-rose-200 text-rose-500 px-2 py-1.5 hover:bg-rose-50 active:scale-95 transition" title="Hapus">✕</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="sm:col-span-2 xl:col-span-3 card">
            <x-empty icon="dokumen" title="Tidak ada dokumen yang cocok"
                desc="Coba kata kunci lain, kosongkan filter, atau unggah dokumen baru — semuanya dapat dicari di halaman universal ini.">
                @if ($u->isBisaUnggah())<button data-modal-open="modal-upload" class="mt-3 rounded-lg bg-slate-900 text-white text-sm font-bold px-4 py-2 hover:bg-slate-700 transition">Unggah sekarang</button>@endif
            </x-empty>
        </div>
    @endforelse
</div>
@if ($dokumen->hasPages())<div class="mt-4">{{ $dokumen->links() }}</div>@endif

{{-- modal upload --}}
@if ($u->isBisaUnggah())
<div id="modal-upload" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 p-4 grid place-items-center overflow-y-auto">
    <div class="card w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4"><h3 class="font-bold text-[15px]">Unggah Dokumen Baru</h3><button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400 grid place-items-center">✕</button></div>
        @if ($errors->any())<div class="mb-3 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-rose-700 text-[13px]">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('dokumen.store') }}" enctype="multipart/form-data" class="space-y-3 text-sm">
            @csrf
            <input name="judul" placeholder="Judul dokumen *" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-teal-500 outline-none">
            <textarea name="deskripsi" rows="2" placeholder="Deskripsi singkat (memudahkan pencarian)" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-teal-500 outline-none"></textarea>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <input name="kategori" placeholder="Kategori (mis. Laporan)" class="rounded-lg border border-slate-300 px-3 py-2.5 focus:border-teal-500 outline-none">
                <select name="visibility" class="rounded-lg border border-slate-300 px-3 py-2.5 bg-white">
                    <option value="publik">🌐 Publik — semua pegawai</option>
                    <option value="bidang">🏢 Arsip bidang — terlabel bidang Anda</option>
                    <option value="rahasia">🔒 Rahasia — hanya saya &amp; admin</option>
                </select>
            </div>
            <label class="block border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:border-teal-400 transition cursor-pointer bg-slate-50">
                <input type="file" name="file" required class="block w-full text-sm text-slate-500 file:mx-auto file:mb-2 file:rounded-lg file:border-0 file:bg-teal-600 file:px-4 file:py-2 file:text-white file:font-bold file:cursor-pointer">
                <span class="text-xs text-slate-400">PDF/Word/Excel/gambar, maks 20 MB · otomatis tercatat {{ now()->format('d/m/Y H:i') }} WIB oleh bidang Anda</span>
            </label>
            <button class="w-full rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 active:scale-[.98] transition">Unggah</button>
        </form>
    </div>
</div>
@endif
@endsection
