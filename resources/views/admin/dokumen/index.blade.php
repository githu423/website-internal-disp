{{-- ===== HALAMAN: Admin · Dokumen — daftar dokumen semua bidang. ===== --}}
@extends('layouts.dashboard')

@section('title', 'Kelola Dokumen')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Arsip Digital, Regulasi, dan Penyimpanan Berkas Dinas Pariwisata')

@section('content')
<div class="space-y-6">

    {{-- ===== BAGIAN: Breadcrumbs Mikro ===== --}}
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Admin — Berkas</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Kelola Dokumen</span>
    </nav>

    {{-- ===== BAGIAN: Header Halaman & Quick Actions ===== --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Arsip &amp; Repositori Dokumen</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    {{ $dokumen->total() }} Berkas
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                    💾 {{ round($totalUkuran / 1048576, 2) }} MB terpakai
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Kelola hak akses visibilitas, unduhan, dan retensi arsip dokumen dinas.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('dokumen.index') }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:bg-slate-50 hover:border-purple-300 hover:text-purple-600 active:scale-95 transition-all duration-150">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Lihat Repositori Publik
            </a>
        </div>
    </div>

    {{-- ===== BAGIAN: Filter Toolbar ===== --}}
    <form method="GET" class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <div class="flex flex-1 flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
            <div class="relative flex-1">
                <input name="q" value="{{ request('q') }}" placeholder="Cari judul dokumen atau kata kunci..." 
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-9 pr-3.5 py-2 text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                <span class="absolute left-3 top-2.5 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
            </div>

            <x-select name="visibility" :options="['' => 'Semua Visibilitas', 'publik' => '🌐 Publik (Semua)', 'bidang' => '🔏 Internal Bidang', 'rahasia' => '🔒 Rahasia / Terbatas']" :value="request('visibility')" autoSubmit class="w-full sm:w-56" />
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs md:text-sm rounded-xl shadow-sm transition">
                Saring Dokumen
            </button>
            @if(request('q') || request('visibility'))
                <a href="{{ route('admin.dokumen.index') }}" class="px-3.5 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition">
                    Reset
                </a>
            @endif
        </div>
    </form>

    {{-- ===== BAGIAN: Kontainer Tabel Data Dokumen ===== --}}
    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto thin-scroll">
            <table class="data w-full min-w-[760px]">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                        <th class="py-3 px-4">Judul &amp; Pengunggah</th>
                        <th class="py-3 px-3">Bidang Terkait</th>
                        <th class="py-3 px-3">Visibilitas</th>
                        <th class="py-3 px-3 text-right">Ukuran</th>
                        <th class="py-3 px-3 text-center">Diunduh</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($dokumen as $d)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3.5 px-4">
                            <p class="font-bold text-[13px] text-slate-900 leading-snug">{{ $d->judul }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                <span class="font-medium text-slate-500">{{ $d->kategori_label }}</span> · 
                                Diunggah oleh {{ $d->uploader?->name ?? '—' }} · 
                                {{ $d->created_at->translatedFormat('d M Y, H:i') }}
                            </p>
                        </td>
                        <td class="py-3.5 px-3">
                            @if($d->bidang)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-purple-50 text-purple-700 border border-purple-100">
                                    {{ $d->bidang->nama }}
                                </span>
                            @else
                                <span class="text-xs text-slate-400 italic">Dinas (Umum)</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ ['publik' => 'bg-emerald-50 text-emerald-700 border border-emerald-200', 'bidang' => 'bg-sky-50 text-sky-700 border border-sky-200', 'rahasia' => 'bg-rose-50 text-rose-700 border border-rose-200'][$d->visibility] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ ucfirst($d->visibility) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-3 text-right text-xs tabular-nums text-slate-600">
                            {{ $d->file_size > 1048576 ? round($d->file_size / 1048576, 1) . ' MB' : max(1, round($d->file_size / 1024)) . ' KB' }}
                        </td>
                        <td class="py-3.5 px-3 text-center text-xs font-bold text-slate-800">
                            {{ $d->dibaca_count }}×
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('dokumen.show', $d) }}" 
                                   class="px-2.5 py-1 text-xs font-bold rounded-lg text-purple-700 hover:bg-purple-50 transition">
                                    Lihat
                                </a>
                                <a href="{{ route('dokumen.unduh', $d) }}" 
                                   class="px-2.5 py-1 text-xs font-bold rounded-lg text-slate-600 hover:text-purple-700 hover:bg-slate-100 transition">
                                    Unduh
                                </a>
                                <form method="POST" action="{{ route('admin.dokumen.destroy', $d) }}" data-confirm="Hapus permanen dokumen ini?" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="px-2 py-1 text-xs font-bold rounded-lg text-rose-600 hover:bg-rose-50 transition">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center">
                            <x-empty icon="dokumen" title="Belum ada arsip dokumen" desc="Dokumen yang diunggah oleh pengguna atau admin akan tercatat di sini." />
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($dokumen->hasPages())
            <div class="p-3.5 border-t border-slate-100 bg-slate-50/50">
                {{ $dokumen->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
