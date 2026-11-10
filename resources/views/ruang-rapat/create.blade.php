@extends('layouts.dashboard')

@section('title', 'Buat Ruang Rapat')
@section('header_title')
    <span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Buat tautan dokumen rapat untuk peserta dari luar dinas')

@section('content')
    @php
        $u = auth()->user();
        $kelompokOptions = [
            'agenda' => 'Agenda',
            'materi' => 'Materi',
            'pendukung' => 'Pendukung',
            'notulen' => 'Notulen',
        ];
        $unduhOptions = [
            '' => 'Ikuti pengaturan default',
            '1' => 'Izinkan unduh',
            '0' => 'Tidak boleh unduh',
        ];
        $pilihanLama = old('dokumen', []);
        $dokumenTerpilih = is_array($pilihanLama) ? array_map('strval', $pilihanLama) : [];
        $bidangUnik = $dokumen->pluck('bidang.nama')->filter()->unique()->values();
    @endphp

    <div class="space-y-6">
        {{-- Breadcrumb & Header Nav --}}
        <nav class="flex items-center text-sm font-medium text-slate-400">
            <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
            <span class="mx-2 text-slate-300">/</span>
            <a href="{{ route('ruang-rapat.index') }}" class="hover:text-purple-600 transition">Ruang Rapat</a>
            <span class="mx-2 text-slate-300">/</span>
            <span class="text-slate-700 font-semibold">Buat Ruang Rapat</span>
        </nav>

        {{-- Top Title Banner --}}
        <div class="flex flex-col gap-3 border-b border-slate-200/60 pb-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Buat Ruang Rapat Baru</h1>
                    <span class="inline-flex items-center gap-1 rounded-full border border-purple-200 bg-purple-50/80 px-2.5 py-0.5 text-[11px] font-bold text-purple-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-purple-600"></span>
                        Paket Tamu Eksternal
                    </span>
                </div>
                <p class="text-sm text-slate-500">
                    Susun berkas publik ke dalam ruang rapat online dengan tautan akses terproteksi untuk peserta tamu.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('ruang-rapat.index') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs transition hover:border-purple-300 hover:bg-slate-50 hover:text-purple-600">
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali ke daftar</span>
                </a>
            </div>
        </div>

        {{-- Error Alert --}}
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-sm text-rose-800 shadow-xs">
                <div class="flex items-start gap-3">
                    <div class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-rose-100 text-rose-600">
                        <x-icon name="alert" class="h-4 w-4" />
                    </div>
                    <div class="flex-1">
                        <p class="font-bold">Formulir belum dapat disimpan:</p>
                        <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs text-rose-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Main Form --}}
        <form method="POST" action="{{ route('ruang-rapat.store') }}" id="form-buat-rapat" class="grid gap-6 lg:grid-cols-12">
            @csrf

            {{-- ==================== KOLOM KIRI (KONTEN FORM: INFORMASI & DOKUMEN) ==================== --}}
            <div class="space-y-6 lg:col-span-8">

                {{-- 1. INFORMASI ACARA & RAPAT --}}
                <section class="clean-card space-y-5 p-5 sm:p-6 transition-all">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="grid h-8 w-8 place-items-center rounded-xl bg-purple-100 font-black text-xs text-purple-700">
                                1
                            </span>
                            <div>
                                <h2 class="text-sm md:text-base font-extrabold text-slate-900">Informasi Acara & Rapat</h2>
                                <p class="text-xs text-slate-500">Rincian ini akan ditampilkan pada header halaman tamu.</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">Langkah 1 dari 2</span>
                    </div>

                    {{-- Form Grid --}}
                    <div class="space-y-4">
                        {{-- Judul --}}
                        <div>
                            <label for="judul" class="mb-1.5 flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-600">
                                <span>Judul Rapat <span class="text-rose-500">*</span></span>
                                <span class="text-[11px] font-normal lowercase text-slate-400">maks. 180 karakter</span>
                            </label>
                            <input id="judul" name="judul" type="text" maxlength="180" required value="{{ old('judul') }}"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-400 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20"
                                placeholder="Contoh: Rapat Koordinasi Penyusunan Rencana Kerja Anggaran 2026">
                        </div>

                        {{-- Deskripsi --}}
                        <div>
                            <label for="deskripsi" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-600">
                                Deskripsi / Catatan Agenda <span class="font-normal lowercase text-slate-400">(Opsional)</span>
                            </label>
                            <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="2000"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20"
                                placeholder="Tuliskan latar belakang singkat, arahan teknis, atau poin bahasan rapat...">{{ old('deskripsi') }}</textarea>
                        </div>

                        {{-- Section Sub: Waktu, Lokasi & Tautan --}}
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 space-y-4">
                            <span class="block text-[11px] font-black uppercase tracking-wider text-slate-400">Jadwal & Tempat Pelaksanaan</span>

                            <div class="grid gap-3.5 sm:grid-cols-2">
                                {{-- Tanggal dan Waktu --}}
                                <div>
                                    <label for="tanggal_rapat" class="mb-1 block text-xs font-semibold text-slate-600">
                                        Tanggal & Waktu
                                    </label>
                                    <div class="relative">
                                        <input id="tanggal_rapat" name="tanggal_rapat" type="datetime-local" value="{{ old('tanggal_rapat') }}"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs md:text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                                    </div>
                                </div>

                                {{-- Lokasi --}}
                                <div>
                                    <label for="lokasi" class="mb-1 block text-xs font-semibold text-slate-600">
                                        Lokasi Ruang / Tempat
                                    </label>
                                    <input id="lokasi" name="lokasi" type="text" maxlength="180" value="{{ old('lokasi') }}"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs md:text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20"
                                        placeholder="Contoh: Ruang Rapat Lantai 2 Dispar">
                                </div>

                                {{-- Link Virtual --}}
                                <div class="sm:col-span-2">
                                    <label for="link_virtual" class="mb-1 block text-xs font-semibold text-slate-600">
                                        Tautan Rapat Virtual (Zoom / Google Meet)
                                    </label>
                                    <input id="link_virtual" name="link_virtual" type="url" maxlength="255" value="{{ old('link_virtual') }}"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs md:text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20"
                                        placeholder="https://meet.google.com/xxx-xxxx-xxx atau Zoom Meeting link">
                                </div>
                            </div>
                        </div>

                        {{-- Section Sub: Penanggung Jawab & Bidang --}}
                        <div class="grid gap-3.5 sm:grid-cols-2">
                            @if ($u->isAdmin() || $u->isBidang())
                                <div class="sm:col-span-2">
                                    <label for="id_bidang" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-600">
                                        Bidang Penyelenggara
                                    </label>
                                    <select id="id_bidang" name="id_bidang"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs md:text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                                        <option value="">Lintas bidang / Seluruh Dinas</option>
                                        @foreach ($bidangs as $bidang)
                                            <option value="{{ $bidang->id }}" @selected((string) old('id_bidang', $u->id_bidang) === (string) $bidang->id)>{{ $bidang->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div>
                                <label for="pic_nama" class="mb-1 block text-xs font-semibold text-slate-600">
                                    Nama Narahubung (PIC)
                                </label>
                                <input id="pic_nama" name="pic_nama" type="text" maxlength="180" value="{{ old('pic_nama') }}"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs md:text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20"
                                    placeholder="Contoh: Bpk. Ahmad Hidayat">
                            </div>

                            <div>
                                <label for="pic_kontak" class="mb-1 block text-xs font-semibold text-slate-600">
                                    Kontak PIC (No. WhatsApp / Email)
                                </label>
                                <input id="pic_kontak" name="pic_kontak" type="text" maxlength="180" value="{{ old('pic_kontak') }}"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs md:text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20"
                                    placeholder="Contoh: 0812-3456-7890">
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 2. PILIH DOKUMEN RAPAT --}}
                <section class="clean-card space-y-4 p-5 sm:p-6 transition-all">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="grid h-8 w-8 place-items-center rounded-xl bg-purple-100 font-black text-xs text-purple-700">
                                2
                            </span>
                            <div>
                                <h2 class="text-sm md:text-base font-extrabold text-slate-900">Pilih Dokumen Rapat</h2>
                                <p class="text-xs text-slate-500">Tentukan dokumen publik yang dapat dilihat oleh peserta tamu.</p>
                            </div>
                        </div>

                        {{-- Counter badges --}}
                        <div class="flex items-center gap-2 pl-11 sm:pl-0">
                            <span id="badge-terpilih" class="hidden rounded-full border border-purple-200 bg-purple-50 px-3 py-1 text-xs font-extrabold text-purple-700 shadow-xs">
                                <span id="jumlah-terpilih">0</span> Berkas Dipilih
                            </span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">
                                {{ $dokumen->count() }} Dokumen Publik
                            </span>
                        </div>
                    </div>

                    @if ($dokumen->isEmpty())
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 p-8 text-center">
                            <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-amber-50 text-amber-600">
                                <x-icon name="dokumen" class="h-6 w-6" />
                            </div>
                            <h3 class="text-sm font-extrabold text-slate-800">Belum Ada Dokumen Berstatus Publik</h3>
                            <p class="mx-auto mt-1 max-w-sm text-xs text-slate-500 leading-relaxed">
                                Ruang rapat memerlukan minimal 1 dokumen publik. Silakan ubah visibilitas dokumen ke "Publik" di menu Dokumen terlebih dahulu.
                            </p>
                            <a href="{{ route('dokumen.index') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-purple-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-purple-700 transition">
                                Kelola Dokumen Publik
                            </a>
                        </div>
                    @else
                        {{-- Interactive Search & Filter Toolbar --}}
                        <div class="space-y-2.5 rounded-2xl border border-slate-200/80 bg-slate-50/60 p-3">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                {{-- Search Bar --}}
                                <div class="relative flex-1">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                        <x-icon name="search" class="h-4 w-4" />
                                    </span>
                                    <input
                                        type="text"
                                        id="search-dokumen"
                                        placeholder="Cari judul berkas, nama file, atau nama bidang..."
                                        autocomplete="off"
                                        class="block w-full rounded-xl border border-slate-200 bg-white pl-9 pr-9 py-2 text-xs md:text-sm text-slate-800 placeholder-slate-400 outline-none transition hover:border-slate-300 focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20"
                                    >
                                    <button
                                        type="button"
                                        id="btn-clear-search"
                                        title="Bersihkan pencarian"
                                        class="hidden absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition"
                                    >
                                        <span class="grid h-5 w-5 place-items-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </span>
                                    </button>
                                </div>

                                {{-- Fast Actions & Filter Buttons --}}
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        id="btn-toggle-terpilih"
                                        class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-xs transition hover:border-purple-300 hover:text-purple-600 active:scale-95"
                                    >
                                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Hanya Dipilih</span>
                                    </button>

                                    <button
                                        type="button"
                                        id="btn-pilih-semua-tampil"
                                        title="Centang semua dokumen yang sedang tampil"
                                        class="inline-flex shrink-0 items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-xs transition hover:border-purple-300 hover:text-purple-600 active:scale-95"
                                    >
                                        <span>Pilih Semua</span>
                                    </button>

                                    <button
                                        type="button"
                                        id="btn-batal-semua-pilihan"
                                        title="Batal centang semua dokumen"
                                        class="hidden inline-flex shrink-0 items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-semibold text-rose-600 shadow-xs transition hover:border-rose-300 hover:bg-rose-50 active:scale-95"
                                    >
                                        <span>Batal</span>
                                    </button>
                                </div>
                            </div>

                            {{-- Filter Status Feedback Bar --}}
                            <div id="status-filter-dokumen" class="hidden flex-wrap items-center justify-between gap-2 px-1 pt-1 text-xs text-slate-500 border-t border-slate-200/50">
                                <span id="teks-hasil-filter" class="font-medium"></span>
                                <button type="button" id="btn-reset-semua-filter" class="font-bold text-purple-600 hover:text-purple-700 hover:underline">
                                    Reset filter
                                </button>
                            </div>
                        </div>

                        {{-- Empty Search State --}}
                        <div id="empty-search-dokumen" class="hidden rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 p-8 text-center">
                            <div class="mx-auto mb-2.5 grid h-10 w-10 place-items-center rounded-xl bg-purple-50 text-purple-600">
                                <x-icon name="search" class="h-5 w-5" />
                            </div>
                            <p class="text-sm font-bold text-slate-800">Dokumen tidak ditemukan</p>
                            <p class="mt-1 text-xs text-slate-500">Tidak ada dokumen publik yang cocok dengan kata kunci pencarian Anda.</p>
                            <button type="button" id="btn-reset-dari-empty" class="mt-3 inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-purple-600 shadow-xs transition hover:border-purple-300 hover:bg-purple-50">
                                Reset pencarian
                            </button>
                        </div>

                        {{-- List of Document Cards --}}
                        <div id="daftar-dokumen" class="space-y-3">
                            @foreach ($dokumen as $item)
                                @php
                                    $ext = strtolower(pathinfo($item->file_name, PATHINFO_EXTENSION));
                                    $bidangNama = $item->bidang?->nama ?: 'Lintas bidang';
                                    $isDipilih = in_array((string) $item->id, $dokumenTerpilih, true);
                                @endphp
                                <div class="item-dokumen rounded-2xl border transition-all duration-200 {{ $isDipilih ? 'border-purple-300 bg-purple-50/30 shadow-xs ring-1 ring-purple-400/20' : 'border-slate-200/80 bg-white hover:border-slate-300' }}"
                                    id="card-dokumen-{{ $item->id }}"
                                    data-id="{{ $item->id }}"
                                    data-title="{{ $item->judul }}"
                                    data-search="{{ strtolower($item->judul . ' ' . $item->file_name . ' ' . $bidangNama) }}">
                                    
                                    {{-- Card Top Row: Checkbox, File Icon, Title, Badge --}}
                                    <div class="flex items-start gap-3 p-4">
                                        <div class="pt-0.5">
                                            <input
                                                id="dokumen-{{ $item->id }}"
                                                type="checkbox"
                                                name="dokumen[]"
                                                value="{{ $item->id }}"
                                                @checked($isDipilih)
                                                class="checkbox-dokumen h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500/20 cursor-pointer transition"
                                            >
                                        </div>

                                        <div class="shrink-0">
                                            <x-file-icon :ext="$ext" class="h-10 w-8 rounded-md text-[9px]" />
                                        </div>

                                        <label for="dokumen-{{ $item->id }}" class="min-w-0 flex-1 cursor-pointer select-none">
                                            <span class="judul-dokumen block text-sm font-bold {{ $isDipilih ? 'text-purple-950' : 'text-slate-800' }} hover:text-purple-700 transition">
                                                {{ $item->judul }}
                                            </span>
                                            <span class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                                                <span class="truncate max-w-[200px]">{{ $item->file_name }}</span>
                                                <span class="text-slate-300">•</span>
                                                <span class="rounded-md bg-slate-100 px-1.5 py-0.2 text-[11px] font-medium text-slate-600">{{ $bidangNama }}</span>
                                            </span>
                                        </label>

                                        <div class="shrink-0 flex items-center gap-1.5">
                                            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold text-emerald-700">
                                                Publik
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Card Bottom Row: Kelompok & Unduh Config --}}
                                    <div class="config-row border-t border-slate-100/80 px-4 py-3 bg-white/60 rounded-b-2xl transition-opacity {{ $isDipilih ? 'opacity-100' : 'opacity-60' }}">
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <div>
                                                <label for="kelompok-{{ $item->id }}" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                                    Kategori Dalam Rapat
                                                </label>
                                                <select
                                                    id="kelompok-{{ $item->id }}"
                                                    name="kelompok[{{ $item->id }}]"
                                                    class="select-kelompok w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20"
                                                >
                                                    @foreach ($kelompokOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected(old('kelompok.'.$item->id, 'materi') === $value)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label for="unduh-{{ $item->id }}" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                                    Izin Akses Unduh Tamu
                                                </label>
                                                <select
                                                    id="unduh-{{ $item->id }}"
                                                    name="izinkan_unduh[{{ $item->id }}]"
                                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20"
                                                >
                                                    @foreach ($unduhOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected(old('izinkan_unduh.'.$item->id) === (string)$value)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            {{-- ==================== KOLOM KANAN (STICKY SIDEBAR: KEAMANAN & LIVE SUMMARY) ==================== --}}
            <div class="space-y-5 lg:col-span-4 lg:sticky lg:top-6 self-start">

                {{-- 3. KEAMANAN & MASA BERLAKU --}}
                <section class="clean-card space-y-4 p-5 transition-all">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="grid h-6 w-6 place-items-center rounded-lg bg-purple-50 text-purple-700">
                                <x-icon name="admin" class="h-3.5 w-3.5" />
                            </span>
                            <h2 class="text-sm font-extrabold text-slate-900">Keamanan & Masa Berlaku</h2>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">Privasi</span>
                    </div>

                    <div class="space-y-4">
                        {{-- Masa Berlaku --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="berlaku_sampai" class="text-xs font-bold uppercase tracking-wider text-slate-600">
                                    Berlaku Sampai <span class="text-rose-500">*</span>
                                </label>
                                <span id="label-sisa-hari" class="text-[11px] font-bold text-purple-600">30 hari</span>
                            </div>
                            <input
                                id="berlaku_sampai"
                                name="berlaku_sampai"
                                type="date"
                                min="{{ now()->toDateString() }}"
                                required
                                value="{{ old('berlaku_sampai', now()->addDays(30)->toDateString()) }}"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs md:text-sm font-semibold text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20"
                            >
                            
                            {{-- Presets pills --}}
                            <div class="mt-2 flex items-center gap-1.5">
                                <button type="button" class="btn-preset-hari rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-bold text-slate-600 transition hover:border-purple-300 hover:text-purple-700 active:scale-95" data-days="7">7 Hari</button>
                                <button type="button" class="btn-preset-hari rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-bold text-slate-600 transition hover:border-purple-300 hover:text-purple-700 active:scale-95" data-days="14">14 Hari</button>
                                <button type="button" class="btn-preset-hari rounded-lg border border-purple-200 bg-purple-50 px-2 py-1 text-[11px] font-bold text-purple-700 transition hover:bg-purple-100 active:scale-95" data-days="30">30 Hari</button>
                                <button type="button" class="btn-preset-hari rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-bold text-slate-600 transition hover:border-purple-300 hover:text-purple-700 active:scale-95" data-days="60">60 Hari</button>
                            </div>
                            <span class="mt-1.5 block text-[11px] text-slate-400 leading-tight">
                                Tautan otomatis terkunci saat melewati batas tanggal ini.
                            </span>
                        </div>

                        {{-- PIN Tamu (Dengan Password Reveal & Fast Generator) --}}
                        <div class="border-t border-slate-100 pt-3">
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="pin" class="text-xs font-bold uppercase tracking-wider text-slate-600">
                                    PIN Akses Tamu <span class="text-slate-400 font-normal lowercase">(Opsional)</span>
                                </label>
                                <button type="button" id="btn-generate-pin" class="text-[11px] font-bold text-purple-600 hover:text-purple-700 hover:underline">
                                    + Buat Acak (6 Digit)
                                </button>
                            </div>

                            <div class="relative">
                                <input
                                    id="pin"
                                    name="pin"
                                    type="password"
                                    minlength="4"
                                    maxlength="32"
                                    autocomplete="new-password"
                                    value="{{ old('pin') }}"
                                    class="w-full rounded-xl border border-slate-200 bg-white pl-3.5 pr-10 py-2 text-xs md:text-sm font-mono tracking-wider text-slate-800 placeholder-slate-400 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20"
                                    placeholder="Kosongkan jika bebas PIN"
                                >
                                <button
                                    type="button"
                                    id="btn-toggle-pin-eye"
                                    title="Tampilkan / sembunyikan PIN"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition"
                                >
                                    <svg id="icon-eye-open" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg id="icon-eye-closed" class="hidden h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                            <span class="mt-1.5 block text-[11px] text-slate-400 leading-tight">
                                Jika diatur, setiap tamu wajib menginput PIN sebelum dapat melihat atau mengunduh berkas.
                            </span>
                        </div>

                        {{-- Unduh Default --}}
                        <div class="border-t border-slate-100 pt-3">
                            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3 cursor-pointer hover:border-purple-200 transition">
                                <input type="hidden" name="izinkan_unduh_default" value="0">
                                <input
                                    type="checkbox"
                                    id="izinkan_unduh_default"
                                    name="izinkan_unduh_default"
                                    value="1"
                                    @checked(old('izinkan_unduh_default', '1') === '1')
                                    class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500/20 cursor-pointer"
                                >
                                <span class="min-w-0 flex-1">
                                    <span class="block text-xs font-bold text-slate-700">Izinkan Unduhan Berkas</span>
                                    <span class="mt-0.5 block text-[11px] text-slate-500 leading-tight">
                                        Tamu dapat mengunduh file asli (kecuali disetel 'Tidak boleh unduh' per dokumen).
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>
                </section>

                {{-- 4. LIVE SUMMARY WIDGET & ACTIONS --}}
                <div class="clean-card space-y-4 p-5 bg-gradient-to-b from-white via-white to-purple-50/40 border-purple-200/80 shadow-xs">
                    <div class="flex items-center justify-between border-b border-purple-100/80 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-purple-600 animate-pulse"></span>
                            <h3 class="text-xs font-black uppercase tracking-wider text-purple-900">Ringkasan Paket</h3>
                        </div>
                        <span id="badge-summary-doc" class="rounded-full bg-purple-100 px-2.5 py-0.5 text-[10px] font-black text-purple-700 transition-colors">
                            0 Dokumen
                        </span>
                    </div>

                    {{-- Dynamic Meta Preview --}}
                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="text-[11px] font-semibold text-slate-400 block mb-0.5">Judul Rapat:</span>
                            <p id="preview-judul-rapat" class="font-extrabold text-slate-800 line-clamp-2 leading-snug">
                                Belum diisi
                            </p>
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-100/80 pt-2">
                            <span class="text-slate-400">Proteksi PIN:</span>
                            <span id="preview-pin-status" class="font-bold text-slate-600 inline-flex items-center gap-1">
                                <span>🔓</span> Bebas PIN
                            </span>
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-100/80 pt-2">
                            <span class="text-slate-400">Kedaluwarsa:</span>
                            <span id="preview-expiry-date" class="font-semibold text-slate-700">
                                30 hari ke depan
                            </span>
                        </div>

                        {{-- Category Breakdown Tray --}}
                        <div id="tray-kategori-wrap" class="hidden border-t border-slate-100/80 pt-2">
                            <span class="text-[11px] font-semibold text-slate-400 block mb-1.5">Sebaran Dokumen:</span>
                            <div id="tray-kategori" class="flex flex-wrap gap-1">
                                {{-- Diisi via JS --}}
                            </div>
                        </div>

                        {{-- Selected Items List Preview (Scrollable Mini Drawer) --}}
                        <div id="drawer-terpilih-wrap" class="hidden border-t border-slate-100/80 pt-2">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[11px] font-semibold text-slate-400">Dokumen Terpilih:</span>
                                <span id="label-drawer-count" class="text-[10px] font-bold text-purple-600"></span>
                            </div>
                            <div id="drawer-list-items" class="max-h-36 overflow-y-auto space-y-1 pr-1 text-[11px]">
                                {{-- Diisi via JS --}}
                            </div>
                        </div>
                    </div>

                    {{-- Submit & Cancel Buttons --}}
                    <div class="border-t border-purple-100/80 pt-3 space-y-2">
                        <button
                            type="submit"
                            id="btn-submit-rapat"
                            @disabled($dokumen->isEmpty())
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-purple-500/25 transition hover:from-purple-700 hover:to-indigo-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <x-icon name="plus" class="h-4 w-4" />
                            <span>Simpan & Buat Tautan</span>
                        </button>

                        <a href="{{ route('ruang-rapat.index') }}"
                            class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50 hover:text-purple-600">
                            Batal
                        </a>
                    </div>

                    <p class="text-[11px] text-slate-400 text-center leading-relaxed">
                        Tautan acak aman 48-karakter akan dibuat otomatis setelah ruang rapat berhasil disimpan.
                    </p>
                </div>
            </div>
        </form>
    </div>

    @if ($dokumen->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // DOM Elements: Dokumen & Filter
                const searchInput = document.getElementById('search-dokumen');
                const clearBtn = document.getElementById('btn-clear-search');
                const toggleTerpilihBtn = document.getElementById('btn-toggle-terpilih');
                const btnPilihSemua = document.getElementById('btn-pilih-semua-tampil');
                const btnBatalSemua = document.getElementById('btn-batal-semua-pilihan');
                const badgeTerpilih = document.getElementById('badge-terpilih');
                const jumlahTerpilihSpan = document.getElementById('jumlah-terpilih');
                const statusFilter = document.getElementById('status-filter-dokumen');
                const teksHasilFilter = document.getElementById('teks-hasil-filter');
                const btnResetSemua = document.getElementById('btn-reset-semua-filter');
                const emptySearch = document.getElementById('empty-search-dokumen');
                const btnResetEmpty = document.getElementById('btn-reset-dari-empty');
                const items = Array.from(document.querySelectorAll('.item-dokumen'));
                const checkboxes = Array.from(document.querySelectorAll('.checkbox-dokumen'));
                const totalDokumen = items.length;

                // DOM Elements: Live Summary & Widgets
                const judulInput = document.getElementById('judul');
                const pinInput = document.getElementById('pin');
                const expiryInput = document.getElementById('berlaku_sampai');
                const previewJudul = document.getElementById('preview-judul-rapat');
                const previewPin = document.getElementById('preview-pin-status');
                const previewExpiry = document.getElementById('preview-expiry-date');
                const badgeSummaryDoc = document.getElementById('badge-summary-doc');
                const trayKategoriWrap = document.getElementById('tray-kategori-wrap');
                const trayKategori = document.getElementById('tray-kategori');
                const drawerWrap = document.getElementById('drawer-terpilih-wrap');
                const drawerList = document.getElementById('drawer-list-items');
                const labelDrawerCount = document.getElementById('label-drawer-count');
                const labelSisaHari = document.getElementById('label-sisa-hari');
                const btnSubmit = document.getElementById('btn-submit-rapat');

                // PIN Controls
                const btnToggleEye = document.getElementById('btn-toggle-pin-eye');
                const iconEyeOpen = document.getElementById('icon-eye-open');
                const iconEyeClosed = document.getElementById('icon-eye-closed');
                const btnGeneratePin = document.getElementById('btn-generate-pin');

                // Preset Buttons
                const presetButtons = Array.from(document.querySelectorAll('.btn-preset-hari'));

                let hanyaTerpilih = false;

                // 1. Sinkronisasi status card visual saat checkbox berubah
                function updateCardVisual(item, isChecked) {
                    const card = item;
                    const configRow = card.querySelector('.config-row');
                    const judul = card.querySelector('.judul-dokumen');

                    if (isChecked) {
                        card.classList.add('border-purple-300', 'bg-purple-50/30', 'shadow-xs', 'ring-1', 'ring-purple-400/20');
                        card.classList.remove('border-slate-200/80', 'bg-white');
                        if (configRow) configRow.classList.replace('opacity-60', 'opacity-100');
                        if (judul) {
                            judul.classList.add('text-purple-950');
                            judul.classList.remove('text-slate-800');
                        }
                    } else {
                        card.classList.remove('border-purple-300', 'bg-purple-50/30', 'shadow-xs', 'ring-1', 'ring-purple-400/20');
                        card.classList.add('border-slate-200/80', 'bg-white');
                        if (configRow) configRow.classList.replace('opacity-100', 'opacity-60');
                        if (judul) {
                            judul.classList.remove('text-purple-950');
                            judul.classList.add('text-slate-800');
                        }
                    }
                }

                // 2. Perbarui seluruh ringkasan & badge dokumen terpilih
                function perbaruiJumlahTerpilih() {
                    const checkedItems = [];
                    const categoryCounts = { agenda: 0, materi: 0, pendukung: 0, notulen: 0 };

                    items.forEach(item => {
                        const cb = item.querySelector('.checkbox-dokumen');
                        const isChecked = cb && cb.checked;
                        updateCardVisual(item, isChecked);

                        if (isChecked) {
                            const id = item.getAttribute('data-id');
                            const title = item.getAttribute('data-title') || 'Dokumen';
                            const selectCat = item.querySelector('.select-kelompok');
                            const cat = selectCat ? selectCat.value : 'materi';

                            if (categoryCounts.hasOwnProperty(cat)) {
                                categoryCounts[cat]++;
                            }
                            checkedItems.push({ id, title, cat });
                        }
                    });

                    const terpilih = checkedItems.length;
                    jumlahTerpilihSpan.textContent = terpilih;
                    badgeSummaryDoc.textContent = `${terpilih} Dokumen`;

                    if (terpilih > 0) {
                        badgeTerpilih.classList.remove('hidden');
                        badgeSummaryDoc.classList.add('bg-purple-600', 'text-white');
                        badgeSummaryDoc.classList.remove('bg-purple-100', 'text-purple-700');
                        btnBatalSemua.classList.remove('hidden');

                        // Update Sebaran Kategori Chips
                        trayKategoriWrap.classList.remove('hidden');
                        let catChips = '';
                        const catLabels = { agenda: 'Agenda', materi: 'Materi', pendukung: 'Pendukung', notulen: 'Notulen' };
                        for (const [key, count] of Object.entries(categoryCounts)) {
                            if (count > 0) {
                                catChips += `<span class="rounded-md bg-purple-100/80 px-1.5 py-0.5 text-[10px] font-bold text-purple-700">${count} ${catLabels[key]}</span>`;
                            }
                        }
                        trayKategori.innerHTML = catChips;

                        // Update Mini Drawer List
                        drawerWrap.classList.remove('hidden');
                        labelDrawerCount.textContent = `${terpilih} berkas`;
                        let drawerHtml = '';
                        checkedItems.forEach(doc => {
                            drawerHtml += `
                                <div class="flex items-center justify-between gap-1.5 rounded-lg border border-purple-100 bg-white/80 px-2 py-1 shadow-2xs">
                                    <span class="truncate text-slate-700 font-medium">${doc.title}</span>
                                    <button type="button" class="btn-remove-from-drawer text-slate-400 hover:text-rose-600 font-bold shrink-0 p-0.5" data-id="${doc.id}" title="Hapus dari pilihan">✕</button>
                                </div>
                            `;
                        });
                        drawerList.innerHTML = drawerHtml;

                        // Pasang listener uncheck dari drawer
                        drawerList.querySelectorAll('.btn-remove-from-drawer').forEach(btn => {
                            btn.addEventListener('click', function(e) {
                                e.stopPropagation();
                                const docId = this.getAttribute('data-id');
                                const targetCb = document.getElementById('dokumen-' + docId);
                                if (targetCb) {
                                    targetCb.checked = false;
                                    perbaruiJumlahTerpilih();
                                    if (hanyaTerpilih) filterDokumen();
                                }
                            });
                        });
                    } else {
                        badgeTerpilih.classList.add('hidden');
                        badgeSummaryDoc.classList.remove('bg-purple-600', 'text-white');
                        badgeSummaryDoc.classList.add('bg-purple-100', 'text-purple-700');
                        btnBatalSemua.classList.add('hidden');
                        trayKategoriWrap.classList.add('hidden');
                        drawerWrap.classList.add('hidden');
                        drawerList.innerHTML = '';
                    }
                }

                // 3. Live Preview Form Info
                function perbaruiLiveSummary() {
                    // Judul
                    if (judulInput && previewJudul) {
                        const val = judulInput.value.trim();
                        previewJudul.textContent = val || 'Belum diisi';
                    }

                    // PIN
                    if (pinInput && previewPin) {
                        const val = pinInput.value.trim();
                        if (val.length > 0) {
                            previewPin.innerHTML = '<span>🔒</span> Dilindungi PIN (' + val.length + ' karakter)';
                            previewPin.classList.add('text-purple-700');
                            previewPin.classList.remove('text-slate-600');
                        } else {
                            previewPin.innerHTML = '<span>🔓</span> Bebas PIN';
                            previewPin.classList.remove('text-purple-700');
                            previewPin.classList.add('text-slate-600');
                        }
                    }

                    // Expiry
                    if (expiryInput && previewExpiry) {
                        if (expiryInput.value) {
                            const targetDate = new Date(expiryInput.value + 'T00:00:00');
                            const today = new Date();
                            today.setHours(0,0,0,0);
                            const diffDays = Math.round((targetDate - today) / (1000 * 60 * 60 * 24));
                            
                            const formatIndo = targetDate.toLocaleDateString('id-ID', {
                                day: 'numeric',
                                month: 'short',
                                year: 'numeric'
                            });

                            previewExpiry.textContent = `${formatIndo} (${diffDays >= 0 ? diffDays + ' hari lagi' : 'Kedaluwarsa'})`;
                            if (labelSisaHari) {
                                labelSisaHari.textContent = diffDays >= 0 ? `${diffDays} hari lagi` : 'Sudah lewat';
                            }
                        }
                    }
                }

                // 4. Filter logic
                function filterDokumen() {
                    const query = (searchInput.value || '').trim().toLowerCase();
                    clearBtn.classList.toggle('hidden', query.length === 0);

                    let visibleCount = 0;

                    items.forEach(item => {
                        const searchData = item.getAttribute('data-search') || '';
                        const cb = item.querySelector('.checkbox-dokumen');
                        const isChecked = cb ? cb.checked : false;

                        const matchQuery = query === '' || searchData.includes(query);
                        const matchTerpilih = !hanyaTerpilih || isChecked;

                        if (matchQuery && matchTerpilih) {
                            item.classList.remove('hidden');
                            visibleCount++;
                        } else {
                            item.classList.add('hidden');
                        }
                    });

                    // Update UI status filter
                    const isFiltering = query.length > 0 || hanyaTerpilih;
                    if (isFiltering) {
                        statusFilter.classList.remove('hidden');
                        statusFilter.classList.add('flex');
                        
                        let pesan = `Menampilkan ${visibleCount} dari ${totalDokumen} dokumen`;
                        if (hanyaTerpilih && query.length > 0) {
                            pesan += ` (filter: terpilih & "${query}")`;
                        } else if (hanyaTerpilih) {
                            pesan += ` (filter: hanya dokumen terpilih)`;
                        } else if (query.length > 0) {
                            pesan += ` (kata kunci: "${query}")`;
                        }
                        teksHasilFilter.textContent = pesan;
                    } else {
                        statusFilter.classList.add('hidden');
                        statusFilter.classList.remove('flex');
                    }

                    // Empty search state
                    if (visibleCount === 0 && totalDokumen > 0) {
                        emptySearch.classList.remove('hidden');
                    } else {
                        emptySearch.classList.add('hidden');
                    }
                }

                function resetFilter() {
                    searchInput.value = '';
                    hanyaTerpilih = false;
                    toggleTerpilihBtn.classList.remove('bg-purple-50', 'border-purple-300', 'text-purple-700', 'font-bold');
                    toggleTerpilihBtn.classList.add('bg-white', 'border-slate-200', 'text-slate-600');
                    filterDokumen();
                    searchInput.focus();
                }

                // Event Listeners: Filter & Search
                searchInput.addEventListener('input', filterDokumen);
                searchInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') e.preventDefault();
                });

                clearBtn.addEventListener('click', function () {
                    searchInput.value = '';
                    filterDokumen();
                    searchInput.focus();
                });

                toggleTerpilihBtn.addEventListener('click', function () {
                    hanyaTerpilih = !hanyaTerpilih;
                    if (hanyaTerpilih) {
                        toggleTerpilihBtn.classList.remove('bg-white', 'border-slate-200', 'text-slate-600');
                        toggleTerpilihBtn.classList.add('bg-purple-50', 'border-purple-300', 'text-purple-700', 'font-bold');
                    } else {
                        toggleTerpilihBtn.classList.remove('bg-purple-50', 'border-purple-300', 'text-purple-700', 'font-bold');
                        toggleTerpilihBtn.classList.add('bg-white', 'border-slate-200', 'text-slate-600');
                    }
                    filterDokumen();
                });

                // Tombol "Pilih Semua yang Sedang Tampil"
                btnPilihSemua.addEventListener('click', function () {
                    items.forEach(item => {
                        if (!item.classList.contains('hidden')) {
                            const cb = item.querySelector('.checkbox-dokumen');
                            if (cb) cb.checked = true;
                        }
                    });
                    perbaruiJumlahTerpilih();
                });

                // Tombol "Batalkan Semua Pilihan"
                btnBatalSemua.addEventListener('click', function () {
                    checkboxes.forEach(cb => { cb.checked = false; });
                    perbaruiJumlahTerpilih();
                    if (hanyaTerpilih) filterDokumen();
                });

                btnResetSemua.addEventListener('click', resetFilter);
                btnResetEmpty.addEventListener('click', resetFilter);

                // Checkbox & Select Listeners
                checkboxes.forEach(cb => {
                    cb.addEventListener('change', function () {
                        perbaruiJumlahTerpilih();
                        if (hanyaTerpilih) filterDokumen();
                    });
                });

                document.querySelectorAll('.select-kelompok').forEach(sel => {
                    sel.addEventListener('change', perbaruiJumlahTerpilih);
                });

                // Preset Expiry Days Listeners
                presetButtons.forEach(btn => {
                    btn.addEventListener('click', function () {
                        const days = parseInt(this.getAttribute('data-days'), 10);
                        const futureDate = new Date();
                        futureDate.setDate(futureDate.getDate() + days);
                        
                        const yyyy = futureDate.getFullYear();
                        const mm = String(futureDate.getMonth() + 1).padStart(2, '0');
                        const dd = String(futureDate.getDate()).padStart(2, '0');
                        
                        expiryInput.value = `${yyyy}-${mm}-${dd}`;
                        
                        // Active state styling for buttons
                        presetButtons.forEach(b => {
                            b.classList.remove('bg-purple-50', 'border-purple-200', 'text-purple-700');
                            b.classList.add('bg-white', 'border-slate-200', 'text-slate-600');
                        });
                        this.classList.remove('bg-white', 'border-slate-200', 'text-slate-600');
                        this.classList.add('bg-purple-50', 'border-purple-200', 'text-purple-700');

                        perbaruiLiveSummary();
                    });
                });

                // PIN Eye Toggle
                btnToggleEye.addEventListener('click', function () {
                    if (pinInput.type === 'password') {
                        pinInput.type = 'text';
                        iconEyeOpen.classList.add('hidden');
                        iconEyeClosed.classList.remove('hidden');
                    } else {
                        pinInput.type = 'password';
                        iconEyeOpen.classList.remove('hidden');
                        iconEyeClosed.classList.add('hidden');
                    }
                });

                // Random PIN Generator
                btnGeneratePin.addEventListener('click', function () {
                    const randomPin = Math.floor(100000 + Math.random() * 900000).toString();
                    pinInput.value = randomPin;
                    pinInput.type = 'text';
                    iconEyeOpen.classList.add('hidden');
                    iconEyeClosed.classList.remove('hidden');
                    perbaruiLiveSummary();
                    pinInput.focus();
                });

                // Form live update triggers
                if (judulInput) judulInput.addEventListener('input', perbaruiLiveSummary);
                if (pinInput) pinInput.addEventListener('input', perbaruiLiveSummary);
                if (expiryInput) expiryInput.addEventListener('change', perbaruiLiveSummary);

                // Initial run
                perbaruiJumlahTerpilih();
                perbaruiLiveSummary();
            });
        </script>
    @endif
@endsection
