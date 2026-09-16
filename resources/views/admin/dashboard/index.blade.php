@extends('layouts.dashboard')

@section('title', 'Ringkasan Admin')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Panel Kontrol & Pusat Manajemen Administrasi Sistem SIPARDIS')

@section('content')
<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Admin</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Ringkasan Panel Admin</span>
    </nav>

    <!-- 2. Header Halaman & Welcome Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Pusat Kendali Administrasi</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    Administrator SIPARDIS
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Ringkasan status basis data, berkas terunggah, dan log aktivitas operasional terkini.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('dashboard') }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:bg-slate-50 hover:border-purple-300 hover:text-purple-600 active:scale-95 transition-all duration-150">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard Utama
            </a>
        </div>
    </div>

    <!-- 3. Grid Statistik Admin -->
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <x-stat label="Akun Pengguna" :value="$stats['users']" tone="purple" icon="akun" :href="route('admin.akun.index')" />
        <x-stat label="Data Pegawai" :value="$stats['pegawai']" tone="sky" icon="pegawai" :href="route('admin.pegawai.index')" />
        <x-stat label="Dokumen & Arsip" :value="$stats['dokumen']" tone="violet" icon="dokumen" :href="route('admin.dokumen.index')" />
        <x-stat label="Agenda Aktif" :value="$stats['agenda']" tone="amber" icon="agenda" :href="route('admin.agenda.index')" />
        <x-stat label="Sub-Kegiatan Kinerja" :value="$stats['kinerja']" tone="emerald" icon="kinerja" :href="route('admin.kinerja.index')" />
        <x-stat label="Objek Retribusi PAD" :value="$stats['pad']" tone="rose" icon="pad" :href="route('admin.pad.index')" />
    </div>

    <!-- 4. Tabel Log Aktivitas Terakhir -->
    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <h3 class="font-bold text-base text-slate-900">Aktivitas Sistem Terakhir</h3>
            </div>
            <a href="{{ route('admin.log.index') }}" class="text-xs font-bold text-purple-700 hover:text-purple-800 hover:underline inline-flex items-center gap-1">
                Lihat Seluruh Log Audit &rarr;
            </a>
        </div>

        <div class="overflow-x-auto thin-scroll">
            <table class="data w-full min-w-[620px]">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                        <th class="w-36 py-3 px-4">Waktu</th>
                        <th class="w-48 py-3 px-3">Pengguna</th>
                        <th class="w-32 py-3 px-3">Aksi</th>
                        <th class="py-3 px-4">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $l)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3 px-4 whitespace-nowrap text-xs text-slate-400">
                            {{ $l->created_at->diffForHumans() }}
                        </td>
                        <td class="py-3 px-3 font-semibold text-[13px] text-slate-900">
                            {{ $l->nama }}
                        </td>
                        <td class="py-3 px-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                {{ $l->aksi }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-xs text-slate-600">
                            {{ $l->keterangan }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-10 text-center">
                            <x-empty icon="log" title="Belum ada aktivitas tercatat" />
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
