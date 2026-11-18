{{-- ===== HALAMAN: Admin · Log Aktivitas — riwayat tindakan pengguna. ===== --}}
@extends('layouts.dashboard')

@section('title', 'Log Aktivitas')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Jejak Audit dan Rekaman Aktivitas Sistem SIPARDIS')

@section('content')
<div class="space-y-6">

    {{-- ===== BAGIAN: Breadcrumbs Mikro ===== --}}
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Admin — Audit</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Log Aktivitas</span>
    </nav>

    {{-- ===== BAGIAN: Header Halaman & Quick Actions ===== --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Jejak Aktivitas &amp; Audit Trail</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    {{ $items->total() }} Log
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Rekaman riwayat login, perubahan data, impor berkas, dan aktivitas pengguna dinas.</p>
        </div>
    </div>

    {{-- ===== BAGIAN: Filter Toolbar ===== --}}
    <form method="GET" class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <div class="relative flex-1 max-w-lg">
            <input name="q" value="{{ request('q') }}" placeholder="Cari nama pengguna, jenis aksi, atau keterangan..." 
                   class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-9 pr-3.5 py-2 text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            <span class="absolute left-3 top-2.5 text-slate-400">
                <x-icon name="search" class="h-4 w-4" />
            </span>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs md:text-sm rounded-xl shadow-sm transition">
                Cari Log
            </button>
            @if(request('q'))
                <a href="{{ route('admin.log.index') }}" class="px-3.5 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition">
                    Reset
                </a>
            @endif
        </div>
    </form>

    {{-- ===== BAGIAN: Kontainer Tabel Data Log ===== --}}
    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto thin-scroll">
            <table class="data w-full min-w-[700px]">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                        <th class="w-36 py-3 px-4">Waktu</th>
                        <th class="py-3 px-3">Pengguna</th>
                        <th class="w-32 py-3 px-3">Aksi</th>
                        <th class="py-3 px-3">Keterangan Aktivitas</th>
                        <th class="w-32 py-3 px-4 text-right">Alamat IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($items as $l)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3.5 px-4 text-xs font-mono text-slate-500 whitespace-nowrap">
                            {{ $l->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="py-3.5 px-3 font-semibold text-[13px] text-slate-900">
                            {{ $l->nama }}
                        </td>
                        <td class="py-3.5 px-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                {{ $l->aksi }}
                            </span>
                        </td>
                        <td class="py-3.5 px-3 text-xs text-slate-600 leading-relaxed">
                            {{ $l->keterangan }}
                        </td>
                        <td class="py-3.5 px-4 text-right text-[11px] font-mono text-slate-400">
                            {{ $l->ip }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center">
                            <x-empty icon="log" title="Log aktivitas masih kosong" desc="Aktivitas pengguna di aplikasi SIPARDIS akan tercatat secara otomatis di sini." />
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="p-3.5 border-t border-slate-100 bg-slate-50/50">
                {{ $items->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
