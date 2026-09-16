@extends('layouts.dashboard')

@section('title', 'Bidang Dinas')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Struktur Organisasi & Unit Pelaksana Teknis')

@section('content')
@php 
    $u = auth()->user(); 
    
    // Pemetaan ikon & identitas visual per bidang
    $getBidangMeta = function ($b) {
        $nama = strtolower($b->nama ?? '');
        $kode = strtolower($b->kode ?? '');
        
        if (str_contains($nama, 'sekretariat') || str_contains($kode, 'sekret')) {
            return [
                'icon' => '🏛️',
                'color' => 'from-purple-600 to-indigo-600',
                'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
                'tag' => 'Administrasi & Kepegawaian',
            ];
        }
        if (str_contains($nama, 'destinasi')) {
            return [
                'icon' => '🏞️',
                'color' => 'from-emerald-600 to-teal-600',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'tag' => 'Destinasi & Daya Tarik Wisata',
            ];
        }
        if (str_contains($nama, 'pemasaran') || str_contains($nama, 'promosi')) {
            return [
                'icon' => '📢',
                'color' => 'from-blue-600 to-indigo-600',
                'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
                'tag' => 'Pemasaran & Promosi Wisata',
            ];
        }
        if (str_contains($nama, 'ekonomi kreatif') || str_contains($nama, 'ekraf')) {
            return [
                'icon' => '🎨',
                'color' => 'from-pink-600 to-purple-600',
                'badge' => 'bg-pink-50 text-pink-700 border-pink-200',
                'tag' => 'Ekonomi Kreatif & Kemitraan',
            ];
        }
        if (str_contains($nama, 'kawali')) {
            return [
                'icon' => '🏯',
                'color' => 'from-amber-600 to-orange-600',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                'tag' => 'UPTD Budaya & Sejarah Galuh',
            ];
        }
        if (str_contains($nama, 'ciamis') || str_contains($nama, 'uptd')) {
            return [
                'icon' => '🏖️',
                'color' => 'from-teal-600 to-cyan-600',
                'badge' => 'bg-teal-50 text-teal-700 border-teal-200',
                'tag' => 'UPTD Rekreasi & Objek Wisata',
            ];
        }

        return [
            'icon' => '🏢',
            'color' => 'from-purple-600 to-indigo-600',
            'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
            'tag' => 'Unit Pelaksana Teknis',
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
                    <!-- Header Kartu: Ikon Identik + Nama Bidang -->
                    <div class="flex items-start gap-4 mb-3.5">
                        <span class="h-13 w-13 rounded-2xl bg-gradient-to-br {{ $meta['color'] }} text-white text-2xl grid place-items-center shadow-md shadow-purple-500/15 shrink-0 select-none">
                            {{ $meta['icon'] }}
                        </span>
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
                            <p class="text-xs text-slate-400 mt-1">
                                Kepala / Koordinator: <span class="text-slate-700 font-semibold">{{ $b->ketua ?: '—' }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Uraian Tugas & Deskripsi -->
                    <p class="text-[13px] text-slate-600 line-clamp-2 leading-relaxed min-h-[2.6rem]">
                        {{ $b->deskripsi ?: 'Unit kerja operasional dalam lingkungan dinas pariwisata.' }}
                    </p>
                </div>

                <!-- Footer Kartu: Statistik & Quick Jump -->
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
                            <span>Komposisi: ♂ <b>{{ $b->rek->l }}</b> · ♀ <b>{{ $b->rek->p }}</b></span>
                            <span>·</span>
                            <span>PNS <b>{{ $b->rek->pns }}</b> · Non-PNS <b>{{ $b->pegawais_count - $b->rek->pns }}</b></span>
                            @if ($b->rek->blm > 0)
                                <span>·</span>
                                <span class="text-rose-600 font-bold">⚠ {{ $b->rek->blm }} data belum lengkap</span>
                            @endif
                        </div>
                    @endif
                </div>
            </a>
        @endforeach
    </div>

</div>
@endsection
