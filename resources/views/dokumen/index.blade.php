@extends('layouts.dashboard')
@section('title', 'Dokumen Dinas')
@section('content')
@php
    $u = auth()->user();
    $fmtSize = fn ($b) => $b > 1048576 ? round($b / 1048576, 1) . ' MB' : max(1, round($b / 1024)) . ' KB';
    $aktifBidang = (int) request('bidang', 0);
    $extOf = fn ($d) => strtolower(pathinfo($d->file_name, PATHINFO_EXTENSION));

    $kategoriOptions = ['' => 'Semua kategori'];
    foreach ($kategori as $k) { $kategoriOptions[$k] = $k; }
    $visOptions = ['publik' => '🌐 Publik', 'bidang' => '🔏 Privat bidang', 'rahasia' => '🔒 Rahasia'];
    $visOptionsLong = [
        'publik' => '🌐 Publik — semua pegawai',
        'bidang' => '🔏 Privat bidang — hanya rekan sebidang',
        'rahasia' => '🔒 Rahasia — hanya saya & admin',
    ];
@endphp

<!-- Breadcrumbs Mikro -->
<div class="mb-1.5">
    <span class="text-gray-400 text-sm font-medium">SIPARDIS <span class="mx-1">/</span> <span class="text-gray-500">Dokumen</span></span>
</div>

<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-[22px] md:text-2xl font-extrabold tracking-tight text-gray-900">Perpustakaan Dokumen</h2>
        <p class="text-sm text-slate-500 mt-1">Arsip universal lintas bidang — <b class="text-slate-700">{{ $total }}</b> dokumen dapat dicari. Dokumen <b>privat bidang</b> hanya terlihat oleh sebidang; <b>rahasia</b> hanya oleh pengunggahnya.</p>
    </div>
    @if ($u->isBisaUnggah())
        <button data-modal-open="modal-upload" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 hover:from-purple-700 hover:to-indigo-700 hover:shadow-purple-600/40 active:scale-95 transition-all duration-150">
            <x-icon name="upload" class="h-4 w-4" /> Unggah Dokumen
        </button>
    @else
        <span class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-500">Unggah dokumen hanya oleh pengelola bidang / admin — hubungi bidang Anda</span>
    @endif
</div>

{{-- filter universal: pencarian + kategori + chips bidang --}}
<div class="clean-card p-4 mb-5">
    <form method="GET" class="flex flex-wrap items-center gap-2.5">
        <div class="relative flex-1 min-w-[220px]">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="search" class="h-4 w-4" /></span>
            <input name="q" value="{{ request('q') }}" placeholder="Cari judul, isi deskripsi, kategori, atau nama file…" class="w-full rounded-xl border border-slate-200 bg-white pl-9 pr-3 py-2 text-sm placeholder-slate-400 focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 outline-none transition">
        </div>
        <input type="hidden" name="bidang" value="{{ $aktifBidang ?: '' }}">

        {{-- pilihan kategori: dropdown custom bertema --}}
        <x-select name="kategori" :options="$kategoriOptions" :value="request('kategori')" class="w-48" />

        <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-500 rounded-xl border border-slate-200 px-2.5 py-2 bg-white cursor-pointer hover:border-purple-300 hover:text-purple-600 transition">
            <input type="checkbox" name="milik_saya" value="1" @checked(request('milik_saya')) class="rounded border-slate-300 text-purple-600 focus:ring-purple-500/30"> punya saya
        </label>
        <button class="rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white px-4 py-2 text-sm font-bold active:scale-95 transition shadow-sm shadow-purple-500/30">Cari</button>
    </form>

    {{-- chips bidang — selaras dengan tema (aktif = ungu) --}}
    <div class="flex flex-wrap items-center gap-1.5 mt-3.5 pt-3.5 border-t border-slate-100">
        <span class="mr-1.5 inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-slate-400">
            <x-icon name="bidang" class="h-3.5 w-3.5" /> Bidang:
        </span>
        <a href="{{ route('dokumen.index', array_diff_key(request()->query(), ['bidang'=>1])) }}" @class(['text-xs font-bold rounded-full px-3.5 py-1.5 border transition active:scale-95 inline-flex items-center gap-1.5', !$aktifBidang ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white border-transparent shadow-sm shadow-purple-500/30' : 'bg-white border-slate-200 text-slate-600 hover:border-purple-300 hover:text-purple-700'])>Semua bidang</a>
        @foreach ($bidangs as $b)
            <a href="{{ route('dokumen.index', array_merge(request()->query(), ['bidang' => $b->id])) }}" @class(['text-xs font-bold rounded-full px-3.5 py-1.5 border transition active:scale-95 inline-flex items-center gap-1.5', $aktifBidang === (int) $b->id ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white border-transparent shadow-sm shadow-purple-500/30' : 'bg-white border-slate-200 text-slate-600 hover:border-purple-300 hover:text-purple-700'])>
                {{ \Illuminate\Support\Str::limit($b->nama, 30, '…') }}
                <span class="rounded-full px-1.5 text-[10px] {{ $aktifBidang === (int) $b->id ? 'bg-white/25 text-white' : 'bg-slate-100 text-slate-500' }}">{{ $b->dokumen_count }}</span>
            </a>
        @endforeach
    </div>

<div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3.5">
    @forelse ($dokumen as $d)
        @php $mine = $u->id === $d->uploaded_by; $ext = $extOf($d); @endphp
        <div class="clean-card p-4 flex flex-col gap-3 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 {{ $d->visibility === 'rahasia' ? 'border-rose-200' : 'hover:border-purple-200' }}">
            <div class="flex items-start gap-3">
                <x-file-icon :ext="$ext" class="h-12 w-12" />
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-[13.5px] leading-snug line-clamp-2">{{ $d->judul }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $d->kategori ?: 'Umum' }} · {{ $fmtSize($d->file_size) }} · dibaca {{ $d->dibaca_count }}×</p>
                </div>
                <span class="text-[10px] font-bold rounded-full px-2 py-0.5 shrink-0 {{ ['publik' => 'bg-emerald-50 text-emerald-600', 'bidang' => 'bg-sky-50 text-sky-600', 'rahasia' => 'bg-rose-50 text-rose-600'][$d->visibility] }}">@php echo $d->visibility === 'bidang' ? 'Privat bidang' : ucfirst($d->visibility); @endphp</span>
            </div>
            @if ($d->deskripsi)<p class="text-xs text-slate-500 line-clamp-2">{{ $d->deskripsi }}</p>@endif
            <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                @if ($d->bidang)<a href="{{ route('bidang.show', $d->bidang) }}" class="font-bold text-sky-600 hover:underline truncate max-w-[60%]">{{ \Illuminate\Support\Str::limit($d->bidang->nama, 28, '…') }}</a>
                @else <span class="font-semibold text-slate-500">Seluruh Dinas</span> @endif
                <span class="truncate">· {{ $d->uploader?->name ?? '—' }}</span>
            </div>
            <div class="mt-auto flex flex-wrap items-center justify-between pt-2.5 border-t border-slate-100 gap-1.5">
                <p class="text-[11px] text-slate-400">{{ $d->created_at->translatedFormat('d M Y, H:i') }}</p>
                <div class="flex items-center gap-1.5">
                    <a href="{{ route('dokumen.show', $d) }}" class="text-[11.5px] font-bold rounded-lg bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white px-2.5 py-1.5 active:scale-95 transition">👁 Lihat</a>
                    <a href="{{ route('dokumen.unduh', $d) }}" class="text-[11.5px] font-bold rounded-lg bg-slate-900 hover:bg-slate-700 text-white px-2.5 py-1.5 active:scale-95 transition">Unduh</a>
                    @if ($mine)
                    <form method="POST" action="{{ route('dokumen.akses', $d) }}" class="inline-block">
                        @csrf @method('PATCH')
                        <x-select name="visibility" :options="$visOptions" :value="$d->visibility" autoSubmit
                            class="inline-block w-auto"
                            buttonClass="rounded-lg pl-2 pr-2 py-1.5 text-[11px] font-bold" />
                    </form>
                    <form method="POST" action="{{ route('dokumen.destroy', $d) }}" data-confirm="Hapus dokumen beserta filenya?">@csrf @method('DELETE')
                        <button class="text-[11.5px] font-bold rounded-lg border border-rose-200 text-rose-500 px-2 py-1.5 hover:bg-rose-50 active:scale-95 transition" title="Hapus">✕</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="sm:col-span-2 xl:col-span-3 clean-card">
            <x-empty icon="dokumen" title="Tidak ada dokumen yang cocok"
                desc="Coba kata kunci lain, kosongkan filter, atau unggah dokumen baru — semuanya dapat dicari di halaman universal ini.">
                @if ($u->isBisaUnggah())<button data-modal-open="modal-upload" class="mt-3 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-sm font-bold px-4 py-2 hover:from-purple-700 hover:to-indigo-700 transition">Unggah sekarang</button>@endif
            </x-empty>
        </div>
    @endforelse
</div>
@if ($dokumen->hasPages())<div class="mt-4">{{ $dokumen->links() }}</div>@endif

{{-- modal upload --}}
@if ($u->isBisaUnggah())
<div id="modal-upload" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 p-4 grid place-items-center overflow-y-auto">
    <div class="clean-card w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4"><h3 class="font-bold text-[15px] text-slate-800">Unggah Dokumen Baru</h3><button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400 grid place-items-center">✕</button></div>
        @if ($errors->any())<div class="mb-3 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-rose-700 text-[13px]">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('dokumen.store') }}" enctype="multipart/form-data" class="space-y-3 text-sm">
            @csrf
            <input name="judul" placeholder="Judul dokumen *" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 outline-none">
            <textarea name="deskripsi" rows="2" placeholder="Deskripsi singkat (memudahkan pencarian)" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 outline-none"></textarea>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <input name="kategori" placeholder="Kategori (mis. Laporan)" class="rounded-xl border border-slate-200 px-3 py-2.5 focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 outline-none">
                <x-select name="visibility" :options="$visOptionsLong" value="publik" buttonClass="rounded-xl pl-3.5 pr-3 py-2.5 text-sm" />
            </div>
            <label class="block border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:border-purple-400 transition cursor-pointer bg-slate-50">
                <input type="file" name="file" required class="block w-full text-sm text-slate-500 file:mx-auto file:mb-2 file:rounded-xl file:border-0 file:bg-purple-600 file:hover:bg-purple-700 file:px-4 file:py-2 file:text-white file:font-bold file:cursor-pointer" onchange="const n=this.parentElement.querySelector('.file-name');if(this.files[0]){n.textContent='📎 '+this.files[0].name;n.classList.remove('hidden')}else{n.classList.add('hidden')}">
                <p class="file-name hidden mt-2 text-xs font-semibold text-purple-700 truncate"></p>
                <span class="text-xs text-slate-400">PDF/Word/Excel/gambar, maks 20 MB · otomatis tercatat {{ now()->format('d/m/Y H:i') }} WIB oleh bidang Anda</span>
            </label>
            <button class="w-full rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold py-2.5 active:scale-[.98] transition shadow-sm shadow-purple-500/30">Unggah</button>
        </form>
    </div>
</div>
@endif
@endsection