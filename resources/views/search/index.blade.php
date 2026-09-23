@extends('layouts.dashboard')

@section('title', $q ? 'Hasil Pencarian: ' . $q : 'Pencarian Global')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Pencarian Terpadu Pegawai, Agenda, dan Dokumen Dinas')

@section('content')
<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Pencarian</span>
        @if($q)
            <span class="mx-2 text-slate-300">/</span>
            <span class="text-slate-700 font-semibold">{{ $q }}</span>
        @endif
    </nav>

    <!-- 2. Header Halaman & Search Form -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
                    @if($q) Hasil untuk "<span class="text-purple-700">{{ $q }}</span>" @else Pencarian Terpadu SIPARDIS @endif
                </h1>
                @if($q)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                        {{ $total }} Hasil
                    </span>
                @endif
            </div>
            <p class="text-sm text-slate-500 mt-1">
                @if($q) Menelusuri basis data pegawai, agenda kegiatan, dan berkas arsip dinas. @else Ketik kata kunci pencarian pada kolom berikut untuk mulai menemukan data. @endif
            </p>
        </div>

        <form action="{{ route('search') }}" method="GET" class="flex-1 max-w-md">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </span>
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari pegawai, agenda, dokumen..."
                    autofocus
                    class="block w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl bg-white text-sm placeholder-slate-400 focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 outline-none transition shadow-sm">
            </div>
        </form>
    </div>

    @if(!$q)
        <!-- State Kosong Belum Input Query -->
        <div class="bg-white border border-slate-200 rounded-2xl p-12 flex flex-col items-center justify-center text-center shadow-sm">
            <div class="h-16 w-16 rounded-2xl bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center mb-4 text-2xl shadow-sm">
                <x-icon name="search" class="h-8 w-8" />
            </div>
            <h3 class="font-bold text-lg text-slate-900">Ketik kata kunci di atas</h3>
            <p class="text-sm text-slate-500 mt-1 max-w-sm">Pencarian berjalan di seluruh data pegawai, agenda, dan dokumen secara simultan dengan dukungan pencocokan fonetik cerdas.</p>
            <div class="mt-5 flex flex-wrap gap-2 justify-center">
                @foreach(['Kepala Dinas', 'Rapat Koordinasi', 'SOP', 'Pemasaran', 'PAD', 'Renstra'] as $contoh)
                    <a href="{{ route('search', ['q' => $contoh]) }}" 
                       class="text-xs px-3.5 py-1.5 bg-slate-50 hover:bg-purple-50 hover:text-purple-700 text-slate-600 rounded-full border border-slate-200 hover:border-purple-200 transition font-medium">
                        {{ $contoh }}
                    </a>
                @endforeach
            </div>
        </div>

    @elseif($total === 0)
        <!-- Tidak Ada Hasil -->
        <div class="bg-white border border-slate-200 rounded-2xl p-12 flex flex-col items-center justify-center text-center shadow-sm">
            <div class="h-16 w-16 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center mb-4 text-2xl shadow-sm">
                🔍
            </div>
            <h3 class="font-bold text-lg text-slate-900">Tidak ada hasil untuk "<span class="text-purple-700">{{ $q }}</span>"</h3>
            <p class="text-sm text-slate-500 mt-1 max-w-sm">Coba periksa kembali ejaan, gunakan kata yang lebih umum, atau coba kata kunci lain.</p>
            <p class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-3.5 py-1 mt-4 font-semibold">
                💡 Tip: Sistem secara otomatis mendeteksi kata yang bunyinya serupa (toleransi typo fonetik aktif).
            </p>
        </div>

    @else
        <!-- Ada Hasil Pencarian -->
        <div class="space-y-6">

            {{-- 1. HASIL PEGAWAI --}}
            @if($pegawai->isNotEmpty())
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-3.5">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="h-8 w-8 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 grid place-items-center">
                            <x-icon name="pegawai" class="h-4 w-4" />
                        </span>
                        <div>
                            <h3 class="font-bold text-[15px] text-slate-900">Aparatur &amp; Pegawai</h3>
                            <p class="text-xs text-slate-400">{{ $pegawai->count() }} hasil ditemukan</p>
                        </div>
                    </div>
                    @if($pegawai->count() === 5)
                        <a href="{{ route('pegawai.index', ['q' => $q]) }}" class="text-xs font-bold text-purple-700 hover:underline">
                            Lihat Semua Pegawai &rarr;
                        </a>
                    @endif
                </div>

                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach($pegawai as $p)
                    <a href="{{ route('pegawai.index', ['q' => $p->nama]) }}" class="bg-slate-50/60 hover:bg-white border border-slate-200/80 hover:border-purple-200 rounded-xl p-3.5 flex items-center gap-3 hover:shadow-sm transition group">
                        <span class="h-10 w-10 shrink-0 rounded-xl bg-gradient-to-br from-purple-600 to-indigo-600 text-white font-bold text-xs grid place-items-center shadow-sm shadow-purple-500/20">
                            {{ strtoupper(mb_substr($p->nama, 0, 2)) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-[13px] text-slate-900 truncate group-hover:text-purple-700 transition">{{ $p->nama }}</p>
                            <p class="text-[11px] text-slate-500 truncate mt-0.5">
                                {{ $p->jabatan ?: '—' }} 
                                @if($p->bidang) · <span class="text-purple-600 font-medium">{{ $p->bidang->nama }}</span>@endif
                            </p>
                        </div>
                        @php
                            $stMapSearch = [
                                'aktif'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'nonaktif' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'pensiun'  => 'bg-slate-100 text-slate-600 border-slate-200',
                            ];
                        @endphp
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0 border {{ $stMapSearch[$p->status] ?? 'bg-slate-100 text-slate-600 border-slate-200' }}">
                            {{ ucfirst($p->status) }}
                        </span>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- 2. HASIL AGENDA --}}
            @if($agenda->isNotEmpty())
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-3.5">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="h-8 w-8 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 grid place-items-center">
                            <x-icon name="agenda" class="h-4 w-4" />
                        </span>
                        <div>
                            <h3 class="font-bold text-[15px] text-slate-900">Jadwal &amp; Agenda</h3>
                            <p class="text-xs text-slate-400">{{ $agenda->count() }} hasil ditemukan</p>
                        </div>
                    </div>
                    @if($agenda->count() === 5)
                        <a href="{{ route('agenda.index', ['q' => $q]) }}" class="text-xs font-bold text-purple-700 hover:underline">
                            Lihat Semua Agenda &rarr;
                        </a>
                    @endif
                </div>

                <div class="space-y-2.5">
                    @foreach($agenda as $a)
                    @php $lewat = $a->status === 'belum' && $a->tanggal->lt(now()->startOfDay()); @endphp
                    <a href="{{ route('agenda.index') }}" class="bg-slate-50/60 hover:bg-white border border-slate-200/80 hover:border-purple-200 rounded-xl p-3.5 flex items-start gap-3.5 hover:shadow-sm transition group">
                        <div class="h-11 w-11 shrink-0 rounded-xl grid place-items-center text-center border {{ $lewat ? 'bg-rose-50 border-rose-200 text-rose-600' : 'bg-purple-50 border-purple-200 text-purple-700' }}">
                            <div>
                                <p class="text-base font-black leading-none">{{ $a->tanggal->format('d') }}</p>
                                <p class="text-[9px] font-bold uppercase mt-0.5">{{ $a->tanggal->translatedFormat('M') }}</p>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-[13px] text-slate-900 group-hover:text-purple-700 transition {{ $a->status === 'selesai' ? 'line-through text-slate-400' : '' }}">{{ $a->judul }}</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                {{ $a->jam_mulai ? substr((string)$a->jam_mulai, 0, 5) . ' WIB' : 'Waktu menyusul' }} ·
                                {{ $a->lokasi ?: 'Dinas Pariwisata Ciamis' }}
                                @if($a->bidang) · <span class="text-purple-600 font-medium">{{ $a->bidang->nama }}</span>@endif
                            </p>
                        </div>
                        @if($lewat)
                            <span class="text-[10px] font-bold bg-rose-50 border border-rose-200 text-rose-700 rounded-full px-2 py-0.5 shrink-0">TERLEWAT</span>
                        @endif
                        @if($a->status === 'selesai')
                            <span class="text-[10px] font-bold bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-full px-2 py-0.5 shrink-0">SELESAI</span>
                        @endif
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- 3. HASIL DOKUMEN --}}
            @if($dokumen->isNotEmpty())
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-3.5">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="h-8 w-8 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 grid place-items-center">
                            <x-icon name="dokumen" class="h-4 w-4" />
                        </span>
                        <div>
                            <h3 class="font-bold text-[15px] text-slate-900">Arsip &amp; Dokumen</h3>
                            <p class="text-xs text-slate-400">{{ $dokumen->count() }} hasil ditemukan</p>
                        </div>
                    </div>
                    @if($dokumen->count() === 5)
                        <a href="{{ route('dokumen.index', ['q' => $q]) }}" class="text-xs font-bold text-purple-700 hover:underline">
                            Lihat Semua Dokumen &rarr;
                        </a>
                    @endif
                </div>

                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach($dokumen as $d)
                    <a href="{{ route('dokumen.show', $d) }}" class="bg-slate-50/60 hover:bg-white border border-slate-200/80 hover:border-purple-200 rounded-xl p-3.5 flex items-start gap-3 hover:shadow-sm transition group">
                        <span class="h-10 w-10 shrink-0 rounded-xl bg-purple-50 text-purple-600 grid place-items-center border border-purple-100">
                            <x-icon name="dokumen" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-[13px] text-slate-900 truncate group-hover:text-purple-700 transition">{{ $d->judul }}</p>
                            <p class="text-[11px] text-slate-500 truncate mt-0.5">
                                {{ $d->kategori_label }}
                                @if($d->bidang) · <span class="text-purple-600 font-medium">{{ $d->bidang->nama }}</span>@endif
                                · {{ $d->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    @endif

</div>
@endsection
