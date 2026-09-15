@extends('layouts.dashboard')

@section('title', 'Notifikasi')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Pemberitahuan & Aktivitas Kedinasan')

@section('content')
@php
    $u = auth()->user();
    $unreadCount = $u->notifikasi()->belum()->count();
@endphp

<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Kantor</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Kotak Notifikasi</span>
    </nav>

    <!-- 2. Header Halaman & Quick Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Kotak Notifikasi</h1>
                @if ($unreadCount > 0)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-100 text-purple-700 border border-purple-200">
                        {{ $unreadCount }} Belum Dibaca
                    </span>
                @endif
            </div>
            <p class="text-sm text-slate-500 mt-1">Pemberitahuan agenda kedinasan, kegiatan bidang, dan info sistem.</p>
        </div>

        @if ($unreadCount > 0)
        <form method="POST" action="{{ route('notifikasi.bacaSemua') }}">
            @csrf
            <button class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:bg-slate-50 hover:border-purple-300 hover:text-purple-600 active:scale-95 transition-all duration-150">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Tandai Semua Dibaca
            </button>
        </form>
        @endif
    </div>

    <!-- 3. Daftar Notifikasi -->
    <div class="space-y-2.5">
        @forelse ($items as $n)
            @php $isBelum = ! $n->dibaca_at; @endphp
            <a href="{{ $n->url ?: '#' }}" 
               class="bg-white border rounded-xl p-4 shadow-sm flex items-start gap-4 transition-all duration-150 group {{ $isBelum ? 'border-purple-200 bg-purple-50/20 hover:border-purple-400 hover:shadow-md' : 'border-slate-200 opacity-75 hover:opacity-100 hover:border-slate-300' }}">
                
                <div class="h-11 w-11 shrink-0 rounded-xl grid place-items-center text-sm border {{ ['penting' => 'bg-rose-50 text-rose-600 border-rose-100', 'agenda' => 'bg-purple-50 text-purple-600 border-purple-100', 'import' => 'bg-emerald-50 text-emerald-600 border-emerald-100'][$n->tipe] ?? 'bg-sky-50 text-sky-600 border-sky-100' }}">
                    <x-icon :name="$n->tipe === 'agenda' ? 'agenda' : ($n->tipe === 'penting' ? 'alert' : 'bell')" class="h-5 w-5" />
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="font-bold text-[13.5px] text-slate-900 leading-snug group-hover:text-purple-700 transition">
                            {{ $n->judul }}
                        </p>
                        @if ($isBelum)
                            <span class="h-2 w-2 rounded-full bg-purple-600 shrink-0" title="Belum dibaca"></span>
                        @endif
                    </div>
                    @if ($n->isi)
                        <p class="text-[13px] text-slate-500 mt-1 leading-relaxed">{{ $n->isi }}</p>
                    @endif
                </div>

                <span class="text-[11px] text-slate-400 whitespace-nowrap shrink-0 font-medium">
                    {{ $n->created_at->diffForHumans() }}
                </span>
            </a>
        @empty
            <div class="bg-white border border-slate-200 rounded-xl p-10 text-center shadow-sm">
                <x-empty icon="bell" title="Kotak masuk kosong" desc="Notifikasi agenda, data kinerja, dan pengumuman akan muncul di sini." />
            </div>
        @endforelse
    </div>

    @if ($items->hasPages())
        <div class="mt-4">
            {{ $items->links() }}
        </div>
    @endif

</div>
@endsection

