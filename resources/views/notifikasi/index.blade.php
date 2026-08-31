@extends('layouts.dashboard')
@section('title', 'Notifikasi')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-extrabold">Kotak Notifikasi</h2>
    <form method="POST" action="{{ route('notifikasi.bacaSemua') }}">@csrf
        <button class="rounded-lg border border-slate-300 bg-white text-sm font-semibold px-3.5 py-2 hover:border-teal-400 active:scale-95 transition">Tandai semua dibaca</button>
    </form>
</div>
<div class="space-y-2">
    @forelse ($items as $n)
        <a href="{{ $n->url ?: '#' }}" class="card p-4 flex items-start gap-3.5 hover:border-teal-300 transition {{ $n->dibaca_at ? 'opacity-60' : '' }}">
            <span class="h-9 w-9 shrink-0 rounded-xl grid place-items-center {{ ['penting' => 'bg-rose-50 text-rose-500', 'agenda' => 'bg-amber-50 text-amber-600', 'import' => 'bg-emerald-50 text-emerald-600'][$n->tipe] ?? 'bg-sky-50 text-sky-600' }}">
                <x-icon :name="$n->tipe === 'agenda' ? 'agenda' : ($n->tipe === 'penting' ? 'alert' : 'bell')" class="h-4 w-4" /></span>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-[13.5px]">{{ $n->judul }} @if (!$n->dibaca_at)<span class="inline-block h-2 w-2 rounded-full bg-teal-500 align-middle ml-1"></span>@endif</p>
                @if ($n->isi)<p class="text-[13px] text-slate-500 mt-0.5">{{ $n->isi }}</p>@endif
            </div>
            <span class="text-[11px] text-slate-400 whitespace-nowrap">{{ $n->created_at->diffForHumans() }}</span>
        </a>
    @empty
        <div class="card"><x-empty icon="bell" title="Kotak masuk kosong" desc="Notifikasi agenda, import data, dan pengumuman akan muncul di sini." /></div>
    @endforelse
</div>
@if ($items->hasPages())<div class="mt-4">{{ $items->links() }}</div>@endif
@endsection
