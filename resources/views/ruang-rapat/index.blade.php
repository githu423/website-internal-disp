@extends('layouts.dashboard')

@section('title', 'Ruang Rapat')
@section('header_title')
    <span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Paket dokumen rapat untuk dibagikan secara aman kepada tamu')

@section('content')
    <div class="space-y-6">
        <nav class="flex items-center text-sm font-medium text-slate-400">
            <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
            <span class="mx-2 text-slate-300">/</span>
            <span class="text-slate-700 font-semibold">Ruang Rapat</span>
        </nav>

        <div class="flex flex-col gap-4 border-b border-slate-200/60 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Ruang Rapat</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola tautan dokumen tamu yang kedaluwarsa otomatis dan dapat dinonaktifkan kapan saja.</p>
            </div>
            <a href="{{ route('ruang-rapat.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-purple-500/30 transition-all hover:from-purple-700 hover:to-indigo-700 active:scale-95">
                <x-icon name="plus" class="h-4 w-4" /> Buat Ruang Rapat
            </a>
        </div>

        @if (session('sukses'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('sukses') }}
            </div>
        @endif

        <div class="clean-card flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
            <form method="GET" action="{{ route('ruang-rapat.index') }}" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <label for="filter-status" class="text-xs font-bold uppercase tracking-wide text-slate-500">Status</label>
                <x-select name="status" :options="$statusOptions" :value="$status" autoSubmit class="w-full sm:w-56" />
            </form>
            <p class="text-xs text-slate-500">{{ $ruangRapats->total() }} ruang rapat</p>
        </div>

        @if ($ruangRapats->count())
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($ruangRapats as $ruangRapat)
                    @php
                        $kedaluwarsa = $ruangRapat->sudahKedaluwarsa();
                        $labelStatus = $ruangRapat->status === 'nonaktif' ? 'Nonaktif' : ($kedaluwarsa ? 'Kedaluwarsa' : 'Aktif');
                        $badgeStatus = $labelStatus === 'Aktif'
                            ? 'border border-emerald-200 bg-emerald-50 text-emerald-600'
                            : 'border border-slate-200 bg-slate-100 text-slate-500';
                    @endphp
                    <article class="clean-card flex h-full flex-col p-5 transition hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-purple-100 bg-purple-50 text-purple-600">
                                <x-icon name="agenda" class="h-5 w-5" />
                            </span>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-extrabold {{ $badgeStatus }}">{{ $labelStatus }}</span>
                        </div>

                        <div class="mt-4 flex-1">
                            <h2 class="text-base font-extrabold text-slate-900">{{ $ruangRapat->judul }}</h2>
                            <p class="mt-1 text-xs text-slate-500">{{ $ruangRapat->bidang?->nama ?: 'Rapat lintas bidang' }}</p>
                            <div class="mt-4 space-y-2 text-xs text-slate-600">
                                <p class="flex items-center gap-2">
                                    <x-icon name="agenda" class="h-4 w-4 shrink-0 text-slate-400" />
                                    <span>{{ $ruangRapat->tanggal_rapat?->translatedFormat('d F Y, H:i') ?: 'Waktu belum ditentukan' }}</span>
                                </p>
                                <p class="flex items-center gap-2">
                                    <x-icon name="dokumen" class="h-4 w-4 shrink-0 text-slate-400" />
                                    <span>{{ $ruangRapat->dokumen_count }} dokumen</span>
                                </p>
                                <p class="flex items-center gap-2">
                                    <x-icon name="alert" class="h-4 w-4 shrink-0 text-slate-400" />
                                    <span>Berlaku sampai {{ $ruangRapat->berlaku_sampai->translatedFormat('d F Y') }}</span>
                                </p>
                            </div>
                        </div>

                        <a href="{{ route('ruang-rapat.show', $ruangRapat) }}"
                            class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-purple-300 hover:bg-slate-50 hover:text-purple-600">
                            Kelola Ruang Rapat <x-icon name="chevron" class="h-4 w-4 rotate-180" />
                        </a>
                    </article>
                @endforeach
            </div>
            <div>{{ $ruangRapats->links() }}</div>
        @else
            <div class="clean-card overflow-hidden">
                <x-empty icon="agenda" title="Belum ada Ruang Rapat" desc="Buat satu paket berisi dokumen publik untuk dibagikan kepada peserta rapat.">
                    <a href="{{ route('ruang-rapat.create') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-purple-700 hover:to-indigo-700">
                        <x-icon name="plus" class="h-4 w-4" /> Buat Ruang Rapat
                    </a>
                </x-empty>
            </div>
        @endif
    </div>
@endsection
