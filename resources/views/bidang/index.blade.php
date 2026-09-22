@extends('layouts.dashboard')

@section('title', 'Bidang Dinas')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Struktur Organisasi & Unit Pelaksana Teknis')

@section('content')
@php 
    $u = auth()->user(); 
    
    // Pemetaan ikon vektor profesional & identitas visual per bidang
    $getBidangMeta = function ($b) {
        $nama = strtolower($b->nama ?? '');
        $kode = strtolower($b->kode ?? '');
        
        if (str_contains($nama, 'sekretariat') || str_contains($kode, 'sekret')) {
            return [
                'box' => 'bg-purple-100/80 text-purple-700 border border-purple-200 shadow-sm shadow-purple-500/10',
                'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
                'tag' => 'Administrasi & Kepegawaian',
                'svg' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>',
            ];
        }
        if (str_contains($nama, 'destinasi')) {
            return [
                'box' => 'bg-emerald-100/80 text-emerald-700 border border-emerald-200 shadow-sm shadow-emerald-500/10',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'tag' => 'Destinasi & Daya Tarik Wisata',
                'svg' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>',
            ];
        }
        if (str_contains($nama, 'pemasaran') || str_contains($nama, 'promosi')) {
            return [
                'box' => 'bg-sky-100/80 text-sky-700 border border-sky-200 shadow-sm shadow-sky-500/10',
                'badge' => 'bg-sky-50 text-sky-700 border-sky-200',
                'tag' => 'Pemasaran & Promosi Pariwisata',
                'svg' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>',
            ];
        }
        if (str_contains($nama, 'ekonomi kreatif') || str_contains($nama, 'ekraf')) {
            return [
                'box' => 'bg-pink-100/80 text-pink-700 border border-pink-200 shadow-sm shadow-pink-500/10',
                'badge' => 'bg-pink-50 text-pink-700 border-pink-200',
                'tag' => 'Ekonomi Kreatif & Kemitraan',
                'svg' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4 5 5 0 015-5c1.4 0 2 .6 3 1.5.8.8 1.6 1.5 3 1.5a5 5 0 005-5 4 4 0 00-4-4c-1.4 0-2 .6-3 1.5-.8.8-1.6 1.5-3 1.5a5 5 0 01-5-5 4 4 0 014-4c1.4 0 2 .6 3 1.5.8.8 1.6 1.5 3 1.5a5 5 0 005-5 4 4 0 00-4-4" /></svg>',
            ];
        }
        if (str_contains($nama, 'kawali')) {
            return [
                'box' => 'bg-amber-100/80 text-amber-700 border border-amber-200 shadow-sm shadow-amber-500/10',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                'tag' => 'UPTD Budaya & Sejarah Galuh',
                'svg' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>',
            ];
        }
        if (str_contains($nama, 'ciamis') || str_contains($nama, 'uptd')) {
            return [
                'box' => 'bg-teal-100/80 text-teal-700 border border-teal-200 shadow-sm shadow-teal-500/10',
                'badge' => 'bg-teal-50 text-teal-700 border-teal-200',
                'tag' => 'UPTD Rekreasi & Objek Wisata',
                'svg' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
            ];
        }

        return [
            'box' => 'bg-purple-100/80 text-purple-700 border border-purple-200 shadow-sm shadow-purple-500/10',
            'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
            'tag' => 'Unit Pelaksana Teknis',
            'svg' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>',
        ];
    };
@endphp

<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Kantor</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Bidang Dinas</span>
    </nav>

    <!-- 2. Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Bidang &amp; Unit Kerja Dinas</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    {{ count($bidangList) }} Unit Kerja
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Struktur organisasi Dinas Pariwisata Kabupaten Ciamis. Buka unit untuk mengelola aparatur, agenda, dan berkas arsip bidang.</p>
        </div>
    </div>

    <!-- 3. Grid Kartu Bidang Modern -->
    <div class="grid md:grid-cols-2 gap-5">
        @foreach ($bidangList as $b)
            @php 
                $isMine = $u->id_bidang === $b->id; 
                $meta = $getBidangMeta($b);
            @endphp
            <a href="{{ route('bidang.show', $b) }}" 
               class="bg-white border rounded-2xl p-5 sm:p-6 shadow-sm hover:border-purple-300 hover:shadow-md transition-all duration-200 flex flex-col justify-between group {{ $isMine ? 'border-purple-300 ring-2 ring-purple-500/15 bg-purple-50/10' : 'border-slate-200' }}">
                <div>
                    <!-- Header Kartu: Ikon Vektor Presisi + Nama Bidang -->
                    <div class="flex items-start gap-3.5 mb-3.5">
                        <div class="h-12 w-12 rounded-2xl {{ $meta['box'] }} flex items-center justify-center shrink-0 transition-transform duration-200 group-hover:scale-105">
                            {!! $meta['svg'] !!}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-extrabold text-base text-slate-900 leading-snug group-hover:text-purple-700 transition">
                                    {{ $b->nama }}
                                </h3>
                                @if ($isMine)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-0.5 text-[10.5px] font-extrabold">
                                        ✓ BIDANG ANDA
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Kepala / Koordinator: <span class="text-slate-700 font-semibold">{{ $b->ketua ?: '—' }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Uraian Tugas & Deskripsi -->
                    <p class="text-[13px] text-slate-600 line-clamp-2 leading-relaxed min-h-[2.6rem]">
                        {{ $b->deskripsi ?: 'Unit kerja operasional dalam lingkungan Dinas Pariwisata Kabupaten Ciamis.' }}
                    </p>
                </div>

                <!-- Footer Kartu: Statistik & Link Buka -->
                <div class="mt-4 pt-3.5 border-t border-slate-100">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-600">
                        <span class="px-2.5 py-1 rounded-xl bg-slate-50 border border-slate-200/80">👥 {{ $b->pegawais_count }} Pegawai</span>
                        <span class="px-2.5 py-1 rounded-xl bg-slate-50 border border-slate-200/80">📄 {{ $b->dokumen_count }} Dokumen</span>
                        <span class="px-2.5 py-1 rounded-xl bg-slate-50 border border-slate-200/80">📅 {{ $b->agenda_count }} Agenda</span>
                        <span class="ml-auto text-purple-600 font-bold text-xs group-hover:translate-x-1 transition-transform flex items-center gap-1">
                            Buka Detail Unit &rarr;
                        </span>
                    </div>

                    @if ($b->rek)
                        <div class="mt-2.5 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[11px] text-slate-400">
                            <span class="font-semibold text-slate-500">Komposisi:</span>
                            <span>♂ {{ (int) $b->rek->l }} · ♀ {{ (int) $b->rek->p }} · PNS {{ (int) $b->rek->pns }} · non-PNS {{ max(0, (int) $b->pegawais_count - (int) $b->rek->pns) }}</span>
                            @if ((int) $b->rek->blm > 0)
                                <span>·</span>
                                <span class="text-rose-600 font-bold">⚠ {{ (int) $b->rek->blm }} data belum lengkap</span>
                            @endif
                        </div>
                    @endif
                </div>
            </a>
        @endforeach
    </div>

</div>
@endsection
