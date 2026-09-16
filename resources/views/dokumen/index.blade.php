@extends('layouts.dashboard')

@section('title', 'Dokumen Dinas')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Perpustakaan & Arsip Dokumen Digital Dinas Pariwisata')

@section('content')
@php
    $u = auth()->user();
    $fmtSize = fn ($b) => $b > 1048576 ? round($b / 1048576, 1) . ' MB' : max(1, round($b / 1024)) . ' KB';
    $aktifBidang = (int) request('bidang', 0);
    $extOf = fn ($d) => strtolower(pathinfo($d->file_name, PATHINFO_EXTENSION));

    $kategoriOptions = ['' => 'Semua Kategori'];
    foreach ($kategori as $k) { $kategoriOptions[$k] = $k; }
    $visOptions = ['publik' => '🌐 Publik', 'bidang' => '🔏 Privat Bidang', 'rahasia' => '🔒 Rahasia'];
    $visOptionsLong = [
        'publik' => '🌐 Publik — Semua Pegawai',
        'bidang' => '🔏 Privat Bidang — Hanya Rekan Sebidang',
        'rahasia' => '🔒 Rahasia — Hanya Saya & Admin',
    ];

    // Helper identitas & ikon vektor per bidang
    $getBidangMeta = function ($b) {
        if (!$b) {
            return [
                'nama_pendek' => 'Semua Bidang',
                'svg' => '<svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>',
            ];
        }

        $nama = strtolower($b->nama ?? '');
        $kode = strtolower($b->kode ?? '');

        if (str_contains($nama, 'sekretariat') || str_contains($kode, 'sekret')) {
            return [
                'nama_pendek' => 'Sekretariat',
                'svg' => '<svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>',
            ];
        }
        if (str_contains($nama, 'destinasi')) {
            return [
                'nama_pendek' => 'Destinasi Wisata',
                'svg' => '<svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>',
            ];
        }
        if (str_contains($nama, 'pemasaran') || str_contains($nama, 'promosi')) {
            return [
                'nama_pendek' => 'Pemasaran & Promosi',
                'svg' => '<svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>',
            ];
        }
        if (str_contains($nama, 'ekonomi kreatif') || str_contains($nama, 'ekraf')) {
            return [
                'nama_pendek' => 'Ekonomi Kreatif',
                'svg' => '<svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4 5 5 0 015-5c1.4 0 2 .6 3 1.5.8.8 1.6 1.5 3 1.5a5 5 0 005-5 4 4 0 00-4-4c-1.4 0-2 .6-3 1.5-.8.8-1.6 1.5-3 1.5a5 5 0 01-5-5 4 4 0 014-4c1.4 0 2 .6 3 1.5.8.8 1.6 1.5 3 1.5a5 5 0 005-5 4 4 0 00-4-4" /></svg>',
            ];
        }
        if (str_contains($nama, 'kawali')) {
            return [
                'nama_pendek' => 'UPTD Kawali',
                'svg' => '<svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>',
            ];
        }
        if (str_contains($nama, 'ciamis') || str_contains($nama, 'uptd')) {
            return [
                'nama_pendek' => 'UPTD Ciamis',
                'svg' => '<svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
            ];
        }

        return [
            'nama_pendek' => $b->nama,
            'svg' => '<svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>',
        ];
    };

    $aktifBidangModel = $aktifBidang ? $bidangs->firstWhere('id', $aktifBidang) : null;
@endphp

<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Kantor</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Dokumen Dinas</span>
    </nav>

    <!-- 2. Header Halaman & Quick Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Perpustakaan Dokumen</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    {{ $total }} Dokumen
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Arsip digital terpadu lintas bidang. Dokumen <b>privat bidang</b> hanya terlihat sebidang; <b>rahasia</b> hanya oleh pengunggah &amp; admin.</p>
        </div>

        <!-- Quick Actions Bar -->
        <div class="flex items-center gap-2.5">
            @if ($u->isBisaUnggah())
                <button data-modal-open="modal-upload" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-2xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 active:scale-95 transition-all duration-150">
                    <x-icon name="upload" class="h-4 w-4" /> Unggah Dokumen
                </button>
            @else
                <span class="rounded-2xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-500">
                    Unggah dokumen hanya untuk staf bidang / admin
                </span>
            @endif
        </div>
    </div>

    <!-- 3. Toolbar Filter Universal: Pencarian + Kategori + Filter Bidang Modern -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
        <!-- Form Filter Pencarian -->
        <form method="GET" class="flex flex-wrap items-center gap-2.5">
            <div class="relative flex-1 min-w-[240px]">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input name="q" value="{{ request('q') }}" placeholder="Cari judul, deskripsi, kategori, atau nama berkas…" 
                       class="w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3.5 py-2 text-xs md:text-sm placeholder-slate-400 focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            </div>
            
            <input type="hidden" name="bidang" value="{{ $aktifBidang ?: '' }}">

            <x-select name="kategori" :options="$kategoriOptions" :value="request('kategori')" autoSubmit class="w-48" />

            <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 rounded-xl border border-slate-200 px-3 py-2 bg-white cursor-pointer hover:border-purple-300 hover:text-purple-600 transition">
                <input type="checkbox" name="milik_saya" value="1" @checked(request('milik_saya')) class="rounded border-slate-300 text-purple-600 focus:ring-purple-500/20"> 
                Punya Saya
            </label>

            <button type="submit" class="rounded-xl bg-slate-900 text-white hover:bg-slate-800 px-4 py-2 text-xs md:text-sm font-semibold active:scale-95 transition shadow-sm">
                Cari
            </button>
            @if (request('q') || request('kategori') || request('milik_saya') || request('bidang'))
                <a href="{{ route('dokumen.index') }}" class="px-3 py-2 border border-slate-200 bg-white text-slate-500 hover:text-slate-800 rounded-xl text-xs md:text-sm font-semibold transition">
                    Reset
                </a>
            @endif
        </form>

        <!-- Navigasi Filter Bidang Modern (Pills Switcher Horisontal) -->
        <div class="pt-3.5 border-t border-slate-100">
            <div class="flex items-center justify-between gap-3 mb-2.5">
                <span class="inline-flex items-center gap-1.5 text-xs font-extrabold uppercase tracking-wider text-slate-400">
                    <svg class="w-3.5 h-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter Berdasarkan Unit Kerja / Bidang:
                </span>
                
                @if ($aktifBidang && $aktifBidangModel)
                    <a href="{{ route('dokumen.index', array_diff_key(request()->query(), ['bidang' => 1])) }}" 
                       class="text-xs font-bold text-purple-600 hover:text-purple-700 hover:underline inline-flex items-center gap-1 shrink-0">
                        <span>✕</span> Hapus Filter ({{ $aktifBidangModel->nama }})
                    </a>
                @endif
            </div>

            <!-- Scrollable Pills Container -->
            <div class="flex items-center gap-2 overflow-x-auto thin-scroll pb-1.5 -mx-1 px-1">
                @php $allMeta = $getBidangMeta(null); @endphp
                
                {{-- Tombol Semua Bidang --}}
                <a href="{{ route('dokumen.index', array_diff_key(request()->query(), ['bidang' => 1])) }}" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition shrink-0 active:scale-95 {{ !$aktifBidang ? 'bg-purple-600 text-white shadow-sm shadow-purple-500/30 ring-2 ring-purple-500/20' : 'bg-slate-50 border border-slate-200/80 text-slate-700 hover:bg-white hover:border-purple-300 hover:text-purple-700' }}">
                    <span class="{{ !$aktifBidang ? 'text-white' : 'text-slate-500' }}">{!! $allMeta['svg'] !!}</span>
                    <span>Semua Bidang</span>
                </a>

                {{-- Daftar Unit Kerja / Bidang --}}
                @foreach ($bidangs as $b)
                    @php 
                        $bMeta = $getBidangMeta($b);
                        $isActive = ($aktifBidang === (int) $b->id);
                        $isUserBidang = ($u->id_bidang === $b->id);
                    @endphp
                    <a href="{{ route('dokumen.index', array_merge(request()->query(), ['bidang' => $b->id])) }}" 
                       title="{{ $b->nama }}"
                       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition shrink-0 active:scale-95 {{ $isActive ? 'bg-purple-600 text-white shadow-sm shadow-purple-500/30 ring-2 ring-purple-500/20' : 'bg-slate-50 border border-slate-200/80 text-slate-700 hover:bg-white hover:border-purple-300 hover:text-purple-700' }}">
                        <span class="{{ $isActive ? 'text-white' : 'text-slate-500' }}">{!! $bMeta['svg'] !!}</span>
                        <span>{{ $bMeta['nama_pendek'] }}</span>
                        
                        <span class="rounded-lg px-2 py-0.5 text-[10.5px] font-black {{ $isActive ? 'bg-white/25 text-white' : 'bg-white border border-slate-200 text-slate-600' }}">
                            {{ $b->dokumen_count }}
                        </span>

                        @if ($isUserBidang && !$isActive)
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" title="Bidang Anda"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 4. Grid Kartu Dokumen Modern -->
    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse ($dokumen as $d)
            @php 
                $mine = $u->id === $d->uploaded_by; 
                $ext = $extOf($d); 
            @endphp
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between hover:-translate-y-1 hover:shadow-md hover:border-purple-300 transition-all duration-200 {{ $d->visibility === 'rahasia' ? 'border-rose-200' : '' }}">
                <div>
                    <div class="flex items-start gap-3">
                        <x-file-icon :ext="$ext" class="h-11 w-11 shrink-0" />
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-[13.5px] text-slate-900 leading-snug line-clamp-2">{{ $d->judul }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">{{ $d->kategori ?: 'Umum' }} · {{ $fmtSize($d->file_size) }} · dibaca {{ $d->dibaca_count }}×</p>
                        </div>
                        <span class="text-[10.5px] font-bold rounded-full px-2.5 py-0.5 shrink-0 border {{ ['publik' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'bidang' => 'bg-sky-50 text-sky-700 border-sky-200', 'rahasia' => 'bg-rose-50 text-rose-700 border-rose-200'][$d->visibility] }}">
                            {{ $d->visibility === 'bidang' ? 'Privat Bidang' : ucfirst($d->visibility) }}
                        </span>
                    </div>

                    @if ($d->deskripsi)
                        <p class="text-xs text-slate-500 line-clamp-2 mt-2.5 leading-relaxed bg-slate-50 p-2 rounded-xl border border-slate-100">{{ $d->deskripsi }}</p>
                    @endif

                    <div class="flex items-center gap-1.5 text-[11px] text-slate-400 mt-3 pt-2.5 border-t border-slate-100">
                        @if ($d->bidang)
                            <a href="{{ route('bidang.show', $d->bidang) }}" class="font-semibold text-purple-600 hover:underline truncate max-w-[60%]">
                                {{ \Illuminate\Support\Str::limit($d->bidang->nama, 28, '…') }}
                            </a>
                        @else
                            <span class="font-semibold text-slate-600">Seluruh Dinas</span>
                        @endif
                        <span class="truncate">· {{ $d->uploader?->name ?? '—' }}</span>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between pt-2.5 border-t border-slate-100 gap-1.5">
                    <p class="text-[11px] text-slate-400 font-mono">{{ $d->created_at->translatedFormat('d M Y, H:i') }}</p>
                    <div class="flex items-center gap-1.5">
                        <a href="{{ route('dokumen.show', $d) }}" class="text-xs font-bold rounded-xl bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 px-3 py-1.5 active:scale-95 transition shadow-sm">
                            👁 Lihat
                        </a>
                        <a href="{{ route('dokumen.unduh', $d) }}" class="text-xs font-bold rounded-xl bg-slate-900 hover:bg-slate-800 text-white px-3 py-1.5 active:scale-95 transition shadow-sm">
                            Unduh
                        </a>
                        @if ($mine)
                            <form method="POST" action="{{ route('dokumen.akses', $d) }}" class="inline-block">
                                @csrf @method('PATCH')
                                <x-select name="visibility" :options="$visOptions" :value="$d->visibility" autoSubmit
                                    class="inline-block w-auto"
                                    buttonClass="rounded-xl pl-2 pr-2 py-1.5 text-[11px] font-bold" />
                            </form>
                            <form method="POST" action="{{ route('dokumen.destroy', $d) }}" data-confirm="Hapus dokumen beserta filenya?">
                                @csrf @method('DELETE')
                                <button class="text-xs font-bold rounded-xl border border-rose-200 text-rose-600 px-2.5 py-1.5 hover:bg-rose-50 active:scale-95 transition" title="Hapus">
                                    ✕
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="sm:col-span-2 xl:col-span-3 bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
                <x-empty icon="dokumen" title="Tidak ada dokumen yang cocok"
                    desc="Coba kata kunci lain, kosongkan filter, atau unggah dokumen baru.">
                    @if ($u->isBisaUnggah())
                        <button data-modal-open="modal-upload" class="mt-3 rounded-xl bg-purple-600 text-white text-sm font-bold px-4 py-2 hover:bg-purple-700 transition shadow-sm">
                            Unggah Sekarang
                        </button>
                    @endif
                </x-empty>
            </div>
        @endforelse
    </div>

    @if ($dokumen->hasPages())
        <div class="mt-4">
            {{ $dokumen->links() }}
        </div>
    @endif

</div>

{{-- Modal Unggah Dokumen --}}
@if ($u->isBisaUnggah())
<div id="modal-upload" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 backdrop-blur-sm p-4 grid place-items-center overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-lg p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <h3 class="font-extrabold text-base text-slate-900">Unggah Dokumen Baru</h3>
            <button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400 grid place-items-center transition">✕</button>
        </div>
        @if ($errors->any())
            <div class="mb-3 rounded-xl bg-rose-50 border border-rose-200 px-3.5 py-2.5 text-rose-700 text-xs font-medium">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="POST" action="{{ route('dokumen.store') }}" enctype="multipart/form-data" class="space-y-3.5 text-sm">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Judul Dokumen *</label>
                <input name="judul" placeholder="Contoh: Laporan Kinerja TW II 2026" required 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Deskripsi Singkat</label>
                <textarea name="deskripsi" rows="2" placeholder="Catatan singkat isi dokumen untuk mempermudah pencarian..." 
                          class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Kategori</label>
                    <input name="kategori" placeholder="Misal: SK, Laporan, Pedoman" 
                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Akses Visibilitas</label>
                    <x-select name="visibility" :options="$visOptionsLong" value="publik" buttonClass="rounded-xl pl-3.5 pr-3 py-2 text-sm" />
                </div>
            </div>

            <label class="block border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center hover:border-purple-400 transition cursor-pointer bg-slate-50/50">
                <input type="file" name="file" required class="block w-full text-sm text-slate-500 file:mx-auto file:mb-2.5 file:rounded-xl file:border-0 file:bg-purple-600 file:hover:bg-purple-700 file:px-4 file:py-2 file:text-white file:font-bold file:cursor-pointer transition" 
                       onchange="const n=this.parentElement.querySelector('.file-name');if(this.files[0]){n.textContent='📎 '+this.files[0].name;n.classList.remove('hidden')}else{n.classList.add('hidden')}">
                <p class="file-name hidden mt-2 text-xs font-semibold text-purple-700 truncate"></p>
                <span class="text-xs text-slate-400 block mt-1">PDF, Word, Excel, Gambar (Maks. 20 MB). Otomatis tercatat di bidang Anda.</span>
            </label>

            <button class="w-full rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 active:scale-[.98] transition shadow-sm shadow-purple-500/30 mt-2">
                Unggah Dokumen
            </button>
        </form>
    </div>
</div>
@endif

@endsection
