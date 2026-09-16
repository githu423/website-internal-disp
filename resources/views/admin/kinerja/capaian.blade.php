@extends('layouts.dashboard')

@section('title', 'Capaian Kinerja')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Capaian Kinerja Sasaran Strategis Dinas')

@section('content')
<div class="space-y-6">

    <!-- Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Admin — Data</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Capaian Kinerja</span>
    </nav>

    <!-- Header Halaman & Quick Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Capaian Kinerja Sasaran Strategis</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    Tahun {{ $last }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Tahun {{ $last }}: {{ $tercapai }} dari {{ $dinilai }} indikator tercapai (&ge;100%).
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <div class="inline-flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-sm">
                <a href="{{ route('kinerja.capaian', ['tampilan' => 'matriks']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $tampilan === 'matriks' ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 hover:text-purple-600 hover:bg-slate-50' }}">
                    Matriks
                </a>
                <a href="{{ route('kinerja.capaian', ['tampilan' => 'statistik']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $tampilan === 'statistik' ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 hover:text-purple-600 hover:bg-slate-50' }}">
                    Statistik
                </a>
            </div>
            <a href="{{ route('admin.capaian.index') }}" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 active:scale-95 transition-all duration-150">
                Kelola Capaian
            </a>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-8 shadow-sm text-center">
        <p class="text-sm text-slate-500">
            Halaman publik capaian kinerja dapat dilihat di <a href="{{ route('kinerja.capaian') }}" class="text-purple-600 font-semibold hover:underline">Capaian Kinerja Publik</a> atau kelola melalui <a href="{{ route('admin.capaian.index') }}" class="text-purple-600 font-semibold hover:underline">Kelola Capaian Kinerja</a>.
        </p>
    </div>

</div>
@endsection
