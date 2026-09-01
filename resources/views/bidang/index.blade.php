@extends('layouts.dashboard')
@section('title', 'Profil Bidang')
@section('content')
<div class="grid md:grid-cols-2 gap-4">
    @foreach ($bidangList as $b)
        <a href="{{ route('bidang.show', $b) }}" class="card p-5 hover:border-teal-300 hover:shadow transition group">
            <div class="flex items-center gap-3 mb-3">
                <span class="h-11 w-11 rounded-xl bg-gradient-to-br from-sky-500 to-teal-500 text-white grid place-items-center font-black">{{ $b->kode }}</span>
                <div><p class="font-bold leading-tight">{{ $b->nama }}</p><p class="text-xs text-slate-400">Ketua: {{ $b->ketua ?: '—' }}</p></div>
            </div>
            <p class="text-[13px] text-slate-500 line-clamp-2 min-h-[2.4rem]">{{ $b->deskripsi }}</p>
            <div class="flex gap-4 mt-3.5 pt-3 border-t border-slate-100 text-xs font-semibold text-slate-500">
                <span>👥 {{ $b->pegawais_count }} pegawai</span><span>📄 {{ $b->dokumen_count }} dokumen</span><span>📅 {{ $b->agenda_count }} agenda</span>
                <span class="ml-auto text-teal-600 group-hover:translate-x-0.5 transition">Lihat →</span>
            </div>
        </a>
    @endforeach
</div>
@endsection
