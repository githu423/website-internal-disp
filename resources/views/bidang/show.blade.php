@extends('layouts.dashboard')

@section('title', $b->nama)
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Profil Bidang & Unit Kerja Dinas')

@section('content')
{{-- Scoped styles keep native mobile controls within the bidang forms, without a Vite rebuild. --}}
<style>
    .bidang-form,
    .bidang-form .bidang-field {
        min-width: 0;
        max-width: 100%;
    }
    .bidang-form .bidang-field {
        display: block;
    }
    .bidang-form .bidang-label {
        display: block;
        margin-bottom: .375rem;
        font-size: .75rem;
        font-weight: 700;
        color: #475569;
    }
    .bidang-form .bidang-control {
        display: block;
        box-sizing: border-box;
        width: 100%;
        min-width: 0;
        max-width: 100%;
        min-height: 44px;
        padding: .625rem .75rem;
        border: 1px solid #e2e8f0;
        border-radius: .75rem;
        background-color: #fff;
        color: #0f172a;
        font-size: 16px;
        line-height: 1.5;
    }
    .bidang-form .bidang-control:focus-visible {
        outline: 2px solid #c084fc;
        outline-offset: 2px;
    }
    /* Use available form width, not the device model or simulated screen size. */
    .bidang-form .bidang-date-time {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 12rem), 1fr));
        gap: .75rem;
    }
    .bidang-form input[type="date"],
    .bidang-form input[type="time"] {
        appearance: none;
        -webkit-appearance: none;
    }
    .bidang-form input::-webkit-date-and-time-value {
        min-width: 0;
        text-align: left;
    }
    .bidang-form .bidang-file {
        overflow: hidden;
        font-size: .875rem;
    }
    .bidang-form .bidang-file::file-selector-button {
        display: block;
        max-width: 100%;
        min-height: 44px;
        margin: 0 0 .5rem;
        padding: .5rem .875rem;
        border: 0;
        border-radius: .5rem;
        background: #9333ea;
        color: #fff;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }
    .bidang-form .bidang-help,
    .bidang-form .bidang-file-name {
        margin-top: .5rem;
        font-size: .75rem;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }
    .bidang-form .bidang-submit {
        min-height: 44px;
        white-space: normal;
        overflow-wrap: anywhere;
    }
</style>

@php
    $u = auth()->user();
    $isMine = $u->id_bidang === $b->id;

    // Opsi kategori bertingkat untuk unggahan dokumen (dipakai bersama semua dokumen).
    $kategoriUploadOptions = [];
    $datalistKategori = [];
    foreach ($kategoris as $kd) {
        if ($kd->induk_id) continue;
        $kategoriUploadOptions[(string) $kd->id] = $kd->nama;
        if ($kd->anak->isEmpty()) {
            $datalistKategori[] = $kd->nama;
        }
        foreach ($kd->anak as $anak) {
            $kategoriUploadOptions[(string) $anak->id] = '└ ' . $kd->nama . ' / ' . $anak->nama;
            $datalistKategori[] = $kd->nama . ' / ' . $anak->nama;
            foreach ($anak->anak as $dkrit) {
                $kategoriUploadOptions[(string) $dkrit->id] = '└└ ' . $kd->nama . ' / ' . $anak->nama . ' / ' . $dkrit->nama;
                $datalistKategori[] = $kd->nama . ' / ' . $anak->nama . ' / ' . $dkrit->nama;
            }
        }
    }

    // Helper identifikasi icon & tema per bidang
    $getBidangMeta = function ($item) {
        $nama = strtolower($item->nama ?? '');
        $kode = strtolower($item->kode ?? '');
        
        if (str_contains($nama, 'sekretariat') || str_contains($kode, 'sekret')) {
            return [
                'box' => 'bg-purple-100/80 text-purple-700 border border-purple-200 shadow-sm shadow-purple-500/10',
                'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
                'tag' => 'Administrasi & Kepegawaian',
                'svg' => '<svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>',
                'svg_mini' => '<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>',
            ];
        }
        if (str_contains($nama, 'destinasi')) {
            return [
                'box' => 'bg-emerald-100/80 text-emerald-700 border border-emerald-200 shadow-sm shadow-emerald-500/10',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'tag' => 'Destinasi & Daya Tarik Wisata',
                'svg' => '<svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>',
                'svg_mini' => '<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>',
            ];
        }
        if (str_contains($nama, 'pemasaran') || str_contains($nama, 'promosi')) {
            return [
                'box' => 'bg-sky-100/80 text-sky-700 border border-sky-200 shadow-sm shadow-sky-500/10',
                'badge' => 'bg-sky-50 text-sky-700 border-sky-200',
                'tag' => 'Pemasaran & Promosi Pariwisata',
                'svg' => '<svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>',
                'svg_mini' => '<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>',
            ];
        }
        if (str_contains($nama, 'ekonomi kreatif') || str_contains($nama, 'ekraf')) {
            return [
                'box' => 'bg-pink-100/80 text-pink-700 border border-pink-200 shadow-sm shadow-pink-500/10',
                'badge' => 'bg-pink-50 text-pink-700 border-pink-200',
                'tag' => 'Ekonomi Kreatif & Kemitraan',
                'svg' => '<svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4 5 5 0 015-5c1.4 0 2 .6 3 1.5.8.8 1.6 1.5 3 1.5a5 5 0 005-5 4 4 0 00-4-4c-1.4 0-2 .6-3 1.5-.8.8-1.6 1.5-3 1.5a5 5 0 01-5-5 4 4 0 014-4c1.4 0 2 .6 3 1.5.8.8 1.6 1.5 3 1.5a5 5 0 005-5 4 4 0 00-4-4" /></svg>',
                'svg_mini' => '<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4 5 5 0 015-5c1.4 0 2 .6 3 1.5.8.8 1.6 1.5 3 1.5a5 5 0 005-5 4 4 0 00-4-4c-1.4 0-2 .6-3 1.5-.8.8-1.6 1.5-3 1.5a5 5 0 01-5-5 4 4 0 014-4c1.4 0 2 .6 3 1.5.8.8 1.6 1.5 3 1.5a5 5 0 005-5 4 4 0 00-4-4" /></svg>',
            ];
        }
        if (str_contains($nama, 'kawali')) {
            return [
                'box' => 'bg-amber-100/80 text-amber-700 border border-amber-200 shadow-sm shadow-amber-500/10',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                'tag' => 'UPTD Budaya & Sejarah Galuh',
                'svg' => '<svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>',
                'svg_mini' => '<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>',
            ];
        }
        if (str_contains($nama, 'ciamis') || str_contains($nama, 'uptd')) {
            return [
                'box' => 'bg-teal-100/80 text-teal-700 border border-teal-200 shadow-sm shadow-teal-500/10',
                'badge' => 'bg-teal-50 text-teal-700 border-teal-200',
                'tag' => 'UPTD Rekreasi & Objek Wisata',
                'svg' => '<svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                'svg_mini' => '<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
            ];
        }

        return [
            'box' => 'bg-purple-100/80 text-purple-700 border border-purple-200 shadow-sm shadow-purple-500/10',
            'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
            'tag' => 'Unit Pelaksana Teknis',
            'svg' => '<svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>',
            'svg_mini' => '<svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>',
        ];
    };

    $currentMeta = $getBidangMeta($b);
@endphp

<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <a href="{{ route('bidang.index') }}" class="hover:text-purple-600 transition">Kantor</a>
        <span class="mx-2 text-slate-300">/</span>
        <a href="{{ route('bidang.index') }}" class="hover:text-purple-600 transition">Bidang Dinas</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold truncate">{{ $b->nama }}</span>
    </nav>

    <!-- 2. Navigasi Switcher Antar-Bidang (Pills Responsif) -->
    <div class="flex items-center gap-2 overflow-x-auto thin-scroll pb-1">
        <a href="{{ route('bidang.index') }}" 
           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-purple-300 hover:text-purple-700 transition shadow-sm shrink-0">
            &larr; Semua Bidang
        </a>
        <div class="h-4 w-px bg-slate-200 shrink-0 mx-1"></div>
        @foreach ($semuaBidang as $sb)
            @php 
                $sbMeta = $getBidangMeta($sb);
                $isActive = $sb->id === $b->id;
                $isUserBidang = $sb->id === $u->id_bidang;
            @endphp
            <a href="{{ route('bidang.show', $sb) }}" 
               class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold transition shrink-0 {{ $isActive ? 'bg-purple-600 text-white shadow-sm shadow-purple-500/30' : 'bg-white border border-slate-200 text-slate-700 hover:border-purple-300 hover:text-purple-700 hover:bg-slate-50' }}">
                <span class="{{ $isActive ? 'text-white' : 'text-slate-500' }}">{!! $sbMeta['svg_mini'] !!}</span>
                <span>{{ $sb->nama }}</span>
                @if ($isUserBidang && !$isActive)
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" title="Bidang Anda"></span>
                @endif
            </a>
        @endforeach
    </div>

    <!-- 3. Hero Card Profil Bidang Modern -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="flex items-start gap-4">
                <div class="h-14 w-14 sm:h-16 sm:w-16 rounded-2xl {{ $currentMeta['box'] }} flex items-center justify-center shrink-0">
                    {!! $currentMeta['svg'] !!}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900">
                            {{ $b->nama }}
                        </h1>
                        @if ($isMine)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                ✓ BIDANG ANDA
                            </span>
                        @elseif ($u->isAdmin())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                MODE ADMINISTRATOR
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                Unit Kerja Eksternal
                            </span>
                        @endif
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1.5 leading-relaxed max-w-3xl">
                        {{ $b->deskripsi ?: 'Unit kerja operasional dalam lingkup Dinas Pariwisata Kabupaten Ciamis.' }}
                    </p>
                    <p class="text-xs text-slate-500 mt-2 flex items-center gap-1.5">
                        <span class="font-bold text-slate-700">Kepala / Koordinator:</span>
                        <span class="text-purple-700 font-semibold">{{ $b->ketua ?: '—' }}</span>
                    </p>
                </div>
            </div>

            @if ($bolehKelola)
                <div class="shrink-0">
                    <button data-modal-open="modal-edit-bidang" 
                            class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:border-purple-300 hover:text-purple-700 text-xs sm:text-sm font-semibold rounded-xl shadow-sm hover:bg-slate-50 active:scale-95 transition">
                        <span>✏️</span> Edit Profil Bidang
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- 4. Agenda & Dokumen di Atas, Aparatur Bidang di Bawah -->
    <div class="space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-start">
            <!-- 1. Card Agenda Bidang -->
            <div class="min-w-0 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3.5">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                        <span>📅</span> Agenda Bidang
                    </h3>
                    <a href="{{ route('agenda.index', ['bulan' => now()->format('Y-m')]) }}" class="text-xs font-bold text-purple-600 hover:underline">
                        Kalender Dinas &rarr;
                    </a>
                </div>

                <div class="space-y-2.5">
                    @forelse ($agenda as $a)
                        <div class="flex items-start gap-3 p-2.5 rounded-xl bg-slate-50/70 border border-slate-100">
                            <div class="h-10 w-10 shrink-0 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 grid place-items-center text-center">
                                <span class="text-xs font-black leading-none">{{ $a->tanggal->format('d') }}</span>
                                <span class="text-[8.5px] font-bold uppercase mt-0.5">{{ $a->tanggal->translatedFormat('M') }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-slate-900 leading-snug">{{ $a->judul }}</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    {{ $a->jam_mulai ? substr((string)$a->jam_mulai, 0, 5) . ' WIB' : 'Waktu menyusul' }}
                                    @if ($a->lokasi) · 📍 {{ $a->lokasi }} @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-3 text-center">Belum ada agenda terdaftar untuk bidang ini.</p>
                    @endforelse
                </div>

                @if ($bolehKelola)
                    <details class="group mt-2">
                        <summary class="cursor-pointer list-none rounded-xl border border-dashed border-purple-300 bg-purple-50/50 hover:bg-purple-50 px-3.5 py-2 text-xs font-bold text-purple-700 transition flex items-center justify-between">
                            <span>➕ Tambah Agenda Bidang</span>
                            <span class="transition-transform group-open:rotate-180">▾</span>
                        </summary>
                        <form method="POST" action="{{ route('bidang.agenda', $b) }}" class="bidang-form mt-2.5 space-y-3.5 text-xs bg-white p-3 rounded-xl border border-slate-200">
                            @csrf
                            <label class="bidang-field" for="bidang-agenda-judul">
                                <span class="bidang-label">Judul agenda <span aria-hidden="true">*</span></span>
                                <input id="bidang-agenda-judul" name="judul" required maxlength="180" placeholder="Contoh: Rapat evaluasi bidang"
                                       class="bidang-control">
                            </label>
                            <div class="bidang-date-time">
                                <label class="bidang-field" for="bidang-agenda-tanggal">
                                    <span class="bidang-label">Tanggal <span aria-hidden="true">*</span></span>
                                    <input id="bidang-agenda-tanggal" type="date" name="tanggal" required class="bidang-control">
                                </label>
                                <label class="bidang-field" for="bidang-agenda-jam">
                                    <span class="bidang-label">Jam mulai (WIB)</span>
                                    <input id="bidang-agenda-jam" type="time" name="jam_mulai" value="09:00" class="bidang-control">
                                </label>
                            </div>
                            <label class="bidang-field" for="bidang-agenda-lokasi">
                                <span class="bidang-label">Lokasi</span>
                                <input id="bidang-agenda-lokasi" name="lokasi" maxlength="120" placeholder="Contoh: Ruang rapat Dispar Ciamis"
                                       class="bidang-control">
                            </label>
                            <button type="submit" class="bidang-submit w-full rounded-xl bg-purple-600 hover:bg-purple-700 font-bold text-white py-2 active:scale-95 transition shadow-sm">
                                Simpan Agenda
                            </button>
                        </form>
                    </details>
                @endif
            </div>

            <!-- 2. Card Dokumen Bidang -->
            <div class="min-w-0 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3.5">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <div>
                        <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                            <span>📄</span> Dokumen Bidang
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            {{ $bolehKelola ? 'Termasuk dokumen privat sebidang.' : 'Dokumen publik unit ini.' }}
                        </p>
                    </div>
                    <a href="{{ route('dokumen.index', ['bidang' => $b->id]) }}" class="text-xs font-bold text-purple-600 hover:underline">
                        Semua &rarr;
                    </a>
                </div>

                @if ($dokumens->isNotEmpty())
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">🔍</span>
                        <input data-doc-q type="search" placeholder="Cari judul berkas…" autocomplete="off"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/70 py-1.5 pl-8 pr-8 text-xs outline-none focus:border-purple-400 focus:bg-white transition">
                        <button data-doc-clear type="button" hidden class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-1.5 py-0.5 text-[10px] font-bold text-slate-400 hover:bg-slate-100">✕</button>
                    </div>
                @endif

                <div class="divide-y divide-slate-100 max-h-60 overflow-y-auto thin-scroll">
                @forelse ($dokumens as $d)
                    <div data-doc-item data-doc-text="{{ \Illuminate\Support\Str::lower($d->judul.' '.$d->file_name.' '.($d->kategori_label ?? '')) }}" 
                         class="flex items-center gap-2.5 py-2.5">
                        <span class="shrink-0 text-base leading-none">{{ $d->visibility === 'bidang' ? '🔒' : '📄' }}</span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('dokumen.show', $d) }}" class="block truncate text-xs font-bold text-slate-800 hover:text-purple-600 transition">{{ $d->judul }}</a>
                            <span class="block truncate text-[10.5px] text-slate-400">{{ $d->file_name }} · {{ number_format((int) $d->file_size / 1024) }} KB@if (!empty($d->kategori_label)) · {{ $d->kategori_label }}@endif</span>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <a href="{{ route('dokumen.show', $d) }}" class="text-[11px] font-bold text-purple-600 hover:bg-purple-50 px-2 py-0.5 rounded transition">Lihat</a>
                            <a href="{{ route('dokumen.unduh', $d) }}" class="text-[11px] font-bold text-slate-500 hover:bg-slate-100 px-2 py-0.5 rounded transition">Unduh</a>
                        </div>
                    </div>
                @empty
                    <p class="py-3 text-xs text-slate-400 text-center">Belum ada dokumen yang diunggah.</p>
                @endforelse
                </div>

                @if ($bolehKelola)
                    <form method="POST" action="{{ route('bidang.dokumen', $b) }}" enctype="multipart/form-data"
                          class="bidang-form mt-2 space-y-3.5 rounded-xl border border-dashed border-purple-300 bg-purple-50/30 p-3 text-xs">
                        @csrf
                        <div>
                            <h4 class="text-xs font-bold text-purple-700">Unggah Dokumen Internal Bidang</h4>
                            <p class="bidang-help text-slate-500">Dokumen hanya dapat dilihat oleh rekan sebidang dan admin.</p>
                        </div>
                        <label class="bidang-field" for="bidang-dokumen-judul">
                            <span class="bidang-label">Judul dokumen <span aria-hidden="true">*</span></span>
                            <input id="bidang-dokumen-judul" name="judul" placeholder="Contoh: Notulensi rapat evaluasi" required maxlength="180"
                                   class="bidang-control">
                        </label>
                        <div class="bidang-field">
                            <span class="bidang-label" for="bidang-dokumen-kategori">Kategori / Subkategori</span>
                            <select id="bidang-dokumen-kategori" name="id_kategori" class="bidang-control">
                                <option value="">— Tanpa kategori (otomatis Arsip Bidang) —</option>
                                @foreach ($kategoriUploadOptions as $kv => $kl)
                                    <option value="{{ $kv }}">{{ $kl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="bidang-field">
                            <span class="bidang-label" for="bidang-dokumen-kategori-baru">Atau buat kategori baru (induk / subkategori)</span>
                            <input id="bidang-dokumen-kategori-baru" name="kategori" list="bidang-dokumen-kategori-list"
                                   placeholder="Contoh: Laporan / Tahun 2026 / Triwulan I"
                                   class="bidang-control">
                            <p class="bidang-help text-slate-500">Kosongkan pilihan kategori di atas bila ingin memakai kategori baru ini. Pisahkan jenjang dengan garis miring <b>/</b>.</p>
                            <datalist id="bidang-dokumen-kategori-list">
                                @foreach ($datalistKategori as $dlk)
                                    <option value="{{ $dlk }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div class="bidang-field">
                            <label class="bidang-label" for="bidang-dokumen-file">File dokumen <span aria-hidden="true">*</span></label>
                            <input id="bidang-dokumen-file" type="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg,.txt" required
                                   class="bidang-control bidang-file" aria-describedby="bidang-file-help bidang-file-name">
                            <p id="bidang-file-help" class="bidang-help text-slate-500">PDF, Word, Excel, PowerPoint, JPG, PNG, atau TXT. Maksimal 10 MB.</p>
                            <p id="bidang-file-name" class="bidang-file-name font-semibold text-purple-700" role="status" aria-live="polite">Belum ada file dipilih.</p>
                        </div>
                        <button type="submit" class="bidang-submit w-full rounded-xl bg-purple-600 hover:bg-purple-700 py-2 font-bold text-white active:scale-95 transition shadow-sm">
                            Unggah ke Bidang Ini
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Aparatur Bidang ditampilkan setelah kedua kartu -->
        <div class="min-w-0 space-y-4">
            <div class="bg-white border border-slate-200 shadow-sm rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900 flex items-center gap-2">
                            <span>👥</span> Aparatur Bidang
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $pegawais->count() }} personel aparatur bertugas di unit ini</p>
                    </div>
                </div>

                <div class="overflow-x-auto thin-scroll">
                    <table class="data w-full min-w-[620px]">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                                <th class="py-3 px-4">Nama &amp; NIP</th>
                                <th class="py-3 px-3">Jabatan</th>
                                <th class="py-3 px-3">Golongan / Pangkat</th>
                                <th class="py-3 px-4 text-right">Masa Kerja &amp; Usia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($pegawais as $p)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl {{ $currentMeta['box'] }} text-xs font-bold flex items-center justify-center shrink-0 shadow-sm">
                                            {{ strtoupper(mb_substr($p->nama, 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-[13px] text-slate-900 leading-snug">{{ $p->nama }}</p>
                                            <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $p->nip ?: 'Tanpa NIP' }} · {{ $p->jenis_asn }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-xs text-slate-700 font-medium">
                                    {{ $p->jabatan ?: '—' }}
                                </td>
                                <td class="py-3.5 px-3 text-xs text-slate-600">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px]">
                                        {{ $p->pangkat_golongan ?: '—' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-right whitespace-nowrap">
                                    <p class="font-semibold text-slate-800">{{ $p->masa_kerja['label'] }}</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        {{ $p->umur !== null ? $p->umur . ' tahun' : '—' }}
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center">
                                    <x-empty icon="pegawai" title="Belum ada pegawai di bidang ini" desc="Admin dapat mengatur penempatan pegawai melalui menu Kelola Pegawai." />
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Rekap Statistik Bawah Tabel -->
                <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-slate-500 font-medium">
                    <span class="font-extrabold text-slate-800">Komposisi:</span>
                    <span><b class="text-slate-900">{{ $statistik['semua'] }}</b> Total</span>
                    <span class="text-sky-700">♂ <b>{{ $statistik['l'] }}</b> Laki-laki</span>
                    <span class="text-pink-600">♀ <b>{{ $statistik['p'] }}</b> Perempuan</span>
                    <span class="text-purple-700"><b>{{ $statistik['pns'] }}</b> PNS</span>
                    <span class="text-indigo-700"><b>{{ $statistik['nonpns'] }}</b> Non-PNS</span>
                    <span class="text-amber-700"><b>{{ $statistik['nonaktif'] }}</b> Non-Aktif</span>
                    <span class="text-slate-500"><b>{{ $statistik['pensiun'] }}</b> Pensiun</span>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Modal Edit Profil Bidang --}}
@if ($bolehKelola)
<div id="modal-edit-bidang" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 backdrop-blur-sm p-4 grid place-items-center overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-md p-6 shadow-2xl text-sm">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-base text-slate-900">Edit Profil Bidang</h3>
                <p class="text-xs text-slate-400 mt-0.5">{{ $b->nama }}</p>
            </div>
            <button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400 grid place-items-center transition">✕</button>
        </div>
        <form method="POST" action="{{ route('bidang.update', $b) }}" class="space-y-3">
            @csrf @method('PATCH')
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nama Bidang *</label>
                <input name="nama" value="{{ $b->nama }}" required 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Kepala / Koordinator</label>
                <input name="ketua" value="{{ $b->ketua }}" placeholder="Nama kepala bidang / unit" 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Deskripsi &amp; Uraian Tugas</label>
                <textarea name="deskripsi" rows="3" placeholder="Uraian tugas fungsi pokok bidang..." 
                          class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">{{ $b->deskripsi }}</textarea>
            </div>
            <button type="submit" class="w-full rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 active:scale-[.98] transition shadow-sm shadow-purple-500/30 mt-2">
                Simpan Perubahan
            </button>
        </form>
    </div>
</div>
@endif

<script>
(function () {
    const input = document.getElementById('bidang-dokumen-file');
    const status = document.getElementById('bidang-file-name');
    if (!input || !status) return;

    const allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'txt'];
    const updateFile = () => {
        const file = input.files[0];
        input.setCustomValidity('');
        if (!file) {
            status.textContent = 'Belum ada file dipilih.';
            return;
        }

        const extension = file.name.split('.').pop().toLowerCase();
        let error = '';
        if (!allowed.includes(extension)) {
            error = 'Format file tidak didukung. Pilih PDF, Word, Excel, PowerPoint, JPG, PNG, atau TXT.';
        } else if (file.size > 10 * 1024 * 1024) {
            error = 'Ukuran file melebihi 10 MB. Pilih file yang lebih kecil.';
        }
        input.setCustomValidity(error);
        status.textContent = error || 'File dipilih: ' + file.name;
        if (error) input.reportValidity();
    };
    input.addEventListener('change', updateFile);
    input.form.addEventListener('reset', () => {
        input.setCustomValidity('');
        status.textContent = 'Belum ada file dipilih.';
    });
})();

(function(){
    const q=document.querySelector('[data-doc-q]'); if(!q) return;
    const rows=[...document.querySelectorAll('[data-doc-item]')];
    const clr=document.querySelector('[data-doc-clear]');
    const upd=()=>{
        const t=q.value.trim().toLowerCase();
        rows.forEach(r=>{const hit=!t||r.dataset.docText.includes(t); r.hidden=!hit;});
        if(clr) clr.hidden = !t;
    };
    q.addEventListener('input',upd);
    if(clr) clr.addEventListener('click',()=>{q.value='';upd();q.focus();});
    upd();
})();
</script>
@endsection
