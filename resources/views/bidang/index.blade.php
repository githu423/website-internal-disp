@extends('layouts.dashboard')

@section('title', 'Bidang Dinas')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Struktur Organisasi & Unit Pelaksana Teknis')

@section('content')
@php 
    $u = auth()->user(); 
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
                    {{ count($bidangList) }} Unit
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Struktur organisasi Dinas Pariwisata Kabupaten Ciamis. Buka unit untuk melihat aparatur, agenda, dan arsip dokumennya.</p>
        </div>
    </div>

    <!-- 3. Grid Kartu Bidang -->
    <div class="grid md:grid-cols-2 gap-4">
        @foreach ($bidangList as $b)
            @php $isMine = $u->id_bidang === $b->id; @endphp
            <a href="{{ route('bidang.show', $b) }}" 
               class="bg-white border rounded-xl p-5 shadow-sm hover:border-purple-300 hover:shadow-md transition-all duration-200 flex flex-col justify-between group {{ $isMine ? 'border-purple-300 ring-2 ring-purple-500/15 bg-purple-50/15' : 'border-slate-200' }}">
                <div>
                    <div class="flex items-start gap-3.5 mb-3">
                        <span class="h-12 w-12 rounded-xl bg-gradient-to-br from-purple-600 to-indigo-600 text-white font-black text-sm grid place-items-center shadow-sm shadow-purple-500/20 shrink-0">
                            {{ $b->kode }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-extrabold text-[15px] text-slate-900 leading-snug group-hover:text-purple-700 transition">
                                    {{ $b->nama }}
                                </h3>
                                @if ($isMine)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-0.5 text-[10px] font-extrabold">
                                        ✓ BIDANG ANDA
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">Kepala / Koordinator: <span class="text-slate-600 font-medium">{{ $b->ketua ?: '—' }}</span></p>
                        </div>
                    </div>

                    <p class="text-[13px] text-slate-500 line-clamp-2 leading-relaxed min-h-[2.4rem]">
                        {{ $b->deskripsi ?: 'Tidak ada deskripsi profil untuk unit ini.' }}
                    </p>
                </div>

                <div class="mt-4 pt-3.5 border-t border-slate-100">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-slate-600">
                        <span class="px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200/70">👥 {{ $b->pegawais_count }} Pegawai</span>
                        <span class="px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200/70">📄 {{ $b->dokumen_count }} Dokumen</span>
                        <span class="px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200/70">📅 {{ $b->agenda_count }} Agenda</span>
                        <span class="ml-auto text-purple-600 font-bold text-xs group-hover:translate-x-0.5 transition flex items-center gap-1">
                            Buka Detail →
                        </span>
                    </div>

                    @if ($b->rek)
                        <p class="mt-2 text-[11px] text-slate-400 font-medium">
                            Komposisi: ♂ {{ $b->rek->l }} · ♀ {{ $b->rek->p }} · PNS {{ $b->rek->pns }} · Non-PNS {{ $b->pegawais_count - $b->rek->pns }}
                            @if ($b->rek->blm > 0)
                                · <span class="text-rose-600 font-bold">⚠ {{ $b->rek->blm }} data belum lengkap</span>
                            @endif
                        </p>
                    @endif
                </div>
            </a>
        @endforeach
    </div>

</div>
@endsection

