@extends('layouts.dashboard')
@section('title', 'Agenda & Kegiatan')
@section('content')
@php $u = auth()->user(); @endphp
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="flex items-center gap-2">
        <a href="{{ route('agenda.index', ['bulan' => $bulanLalu]) }}" class="h-9 w-9 grid place-items-center rounded-lg border border-slate-200 bg-white hover:border-teal-400 active:scale-95 transition"><x-icon name="chevron" class="h-4 w-4" /></a>
        <h2 class="text-lg font-extrabold capitalize">{{ \Carbon\Carbon::parse($bulanIni . '-01')->translatedFormat('F Y') }}</h2>
        <a href="{{ route('agenda.index', ['bulan' => $bulanDepan]) }}" class="h-9 w-9 grid place-items-center rounded-lg border border-slate-200 bg-white hover:border-teal-400 active:scale-95 transition rotate-180"><x-icon name="chevron" class="h-4 w-4" /></a>
    </div>
    <a href="{{ route('agenda.index') }}" class="text-sm font-semibold text-slate-500 hover:text-teal-600">Hari ini</a>
</div>

<div class="grid lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-2.5">
        @php
            // Konfirmasi Dinas 10 Sep: agenda dinas (umum) di atas, lalu kelompok per bidang — semua terlihat semua user
            $dinas = $items->whereNull('id_bidang')->values();
            $perBidang = $items->whereNotNull('id_bidang')->groupBy(fn ($a) => $a->bidang?->nama ?? 'Bidang')->sortKeys();
        @endphp
        @if ($items->isEmpty())
            <div class="card"><x-empty icon="agenda" title="Tidak ada agenda di bulan ini" desc="Agenda dinas dibuat admin; agenda bidang dibuat lewat halaman bidang masing-masing." /></div>
        @endif

        @if ($dinas->isNotEmpty())
            <div class="flex items-center gap-3 pt-2 pb-1">
                <span class="h-8 w-8 rounded-[10px] bg-slate-800 text-white grid place-items-center shadow-sm text-sm">🏛</span>
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-[15px] leading-tight text-slate-800">Agenda Dinas</h3>
                    <p class="text-[11.5px] font-medium text-slate-500">Umum · {{ $dinas->count() }} kegiatan</p>
                </div>
            </div>
            @foreach ($dinas as $a)
                @include('agenda._kartu', ['a' => $a])
            @endforeach
        @endif

        @foreach ($perBidang as $nama => $grup)
            <div class="flex items-center gap-3 pt-4 pb-1 {{ $loop->first && $dinas->isEmpty() ? '' : 'mt-2 border-t border-slate-200/60' }}">
                <span class="h-8 w-8 rounded-[10px] bg-sky-100 text-sky-700 grid place-items-center shadow-sm border border-sky-200 text-sm">🏢</span>
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-[15px] leading-tight text-slate-800">{{ $nama }}</h3>
                    <p class="text-[11.5px] font-medium text-slate-500">{{ $grup->count() }} kegiatan</p>
                </div>
            </div>
            @foreach ($grup as $a)
                @include('agenda._kartu', ['a' => $a])
            @endforeach
        @endforeach
    </div>

    <div class="space-y-4">
        @if ($u->isAdmin())
        <div class="clean-card p-5">
            <h3 class="font-bold mb-4 flex items-center gap-2.5"><span class="h-8 w-8 rounded-lg bg-teal-50 border border-teal-100 text-teal-600 grid place-items-center"><x-icon name="plus" class="h-4 w-4" /></span> <span class="text-[15px]">Tambah Agenda</span></h3>
            <form method="POST" action="{{ route('agenda.store') }}" class="space-y-3 text-sm">
                @csrf
                <input name="judul" placeholder="Judul kegiatan *" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input type="date" name="tanggal" value="{{ $bulanIni }}-15" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                    <input type="time" name="jam_mulai" value="09:00" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                </div>
                <input name="lokasi" placeholder="Lokasi" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition">
                <select name="kategori" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm bg-white focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition"><option value="rapat">Rapat</option><option value="kegiatan">Kegiatan</option><option value="kunjungan">Kunjungan</option><option value="lainnya">Lainnya</option></select>
                @if ($u->isAdmin())<select name="id_bidang" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm bg-white focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition"><option value="">Seluruh dinas</option>@foreach (\App\Models\Bidang::orderBy('kode')->get() as $b)<option value="{{ $b->id }}">{{ $b->nama }}</option>@endforeach</select>@endif
                <textarea name="deskripsi" rows="2" placeholder="Catatan (opsional)" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition"></textarea>
                <label class="flex items-center gap-2 text-[13px] text-slate-600 mt-1"><input type="checkbox" name="beri_tahu" value="1" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" checked> Kirim notifikasi ke seluruh pegawai</label>
                <button class="w-full rounded-xl bg-teal-600 hover:bg-teal-700 active:scale-[.98] text-white font-bold py-2.5 transition mt-2 shadow-sm">Simpan Agenda</button>
            </form>
        </div>
        @endif
        <div class="clean-card p-5">
            <h3 class="font-bold mb-3 text-[15px]">14 Hari ke Depan</h3>
            <div class="space-y-1">
                @forelse ($terdekat as $a)
                    <div class="flex gap-3 py-2.5 {{ $loop->last ? '' : 'border-b border-slate-100' }}">
                        <span class="text-xs font-bold text-teal-600 whitespace-nowrap w-14 shrink-0">{{ $a->tanggal->translatedFormat('d M') }}</span>
                        <p class="text-[13px] font-medium leading-snug">{{ $a->judul }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 py-2">Tidak ada agenda 14 hari ke depan.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
