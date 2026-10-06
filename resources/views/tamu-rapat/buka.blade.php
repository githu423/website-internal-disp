@extends('tamu-rapat.layout')

@section('title', $ruangRapat->judul)

@section('content')
    @php
        $namaKelompok = [
            'agenda' => 'Agenda',
            'materi' => 'Materi Rapat',
            'pendukung' => 'Dokumen Pendukung',
            'notulen' => 'Notulen',
        ];
        $dokumenPerKelompok = $ruangRapat->dokumen->groupBy(fn ($dokumen) => $dokumen->pivot->kelompok);
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <nav class="mb-3 flex items-center text-xs font-medium text-slate-400">
                    <span class="text-purple-700">SIPARDIS</span>
                    <span class="mx-2 text-slate-300">/</span>
                    <span class="text-slate-600">Ruang Rapat</span>
                </nav>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wide text-emerald-600">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Tautan aktif
                </span>
                <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ $ruangRapat->judul }}</h1>
                <p class="mt-2 text-sm text-slate-500">Dokumen rapat yang dibagikan oleh Dinas Pariwisata Kabupaten Ciamis.</p>
            </div>
            <span class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-purple-100 bg-purple-50 px-3.5 py-2.5 text-xs font-bold text-purple-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18m9-9H3" /></svg>
                Akses tamu · baca saja
            </span>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-purple-100 bg-purple-50 text-purple-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" /></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-extrabold text-slate-900">Informasi rapat</h2>
                    @if ($ruangRapat->deskripsi)
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $ruangRapat->deskripsi }}</p>
                    @endif
                    <div class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2">
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-400">Tanggal dan waktu</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $ruangRapat->tanggal_rapat?->translatedFormat('d F Y, H:i') ?: 'Belum ditentukan' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-400">Lokasi</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $ruangRapat->lokasi ?: 'Belum ditentukan' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-400">Bidang penyelenggara</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $ruangRapat->bidang?->nama ?: 'Dinas Pariwisata Kabupaten Ciamis' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-400">Tautan berlaku sampai</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $ruangRapat->berlaku_sampai->translatedFormat('d F Y') }}</p>
                        </div>
                    </div>

                    @if ($ruangRapat->link_virtual)
                        <a href="{{ $ruangRapat->link_virtual }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-purple-500/25 transition hover:from-purple-700 hover:to-indigo-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3h7v7m-1-6L10 14" /><path d="M19 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6" /></svg>
                            Gabung rapat virtual
                        </a>
                    @endif

                    @if ($ruangRapat->pic_nama || $ruangRapat->pic_kontak)
                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                            <p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-400">Narahubung</p>
                            <p class="mt-1 text-sm font-bold text-slate-700">{{ $ruangRapat->pic_nama ?: 'Panitia rapat' }}</p>
                            @if ($ruangRapat->pic_kontak)
                                <p class="mt-0.5 break-all text-xs text-slate-500">{{ $ruangRapat->pic_kontak }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-extrabold tracking-tight text-slate-900">Dokumen rapat</h2>
                    <p class="mt-1 text-xs text-slate-500">Dokumen hanya dapat dilihat. Unduhan tersedia jika diizinkan penyelenggara.</p>
                </div>
                <span class="text-xs font-semibold text-slate-400">{{ $ruangRapat->dokumen->count() }} dokumen</span>
            </div>

            @if ($ruangRapat->dokumen->isEmpty())
                <x-empty icon="dokumen" title="Belum ada dokumen" desc="Penyelenggara belum menambahkan dokumen ke paket rapat ini." />
            @else
                <div class="space-y-5 pt-5">
                    @foreach ($dokumenPerKelompok as $kelompok => $items)
                        @php $namaBagian = $namaKelompok[$kelompok] ?? ucfirst($kelompok); @endphp
                        <section>
                            <h3 class="mb-2.5 text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400">{{ $namaBagian }}</h3>
                            <div class="space-y-2.5">
                                @foreach ($items as $dokumen)
                                    @php
                                        $ext = strtolower(pathinfo($dokumen->file_name, PATHINFO_EXTENSION));
                                        $bolehUnduh = $dokumen->pivot->izinkan_unduh === null
                                            ? $ruangRapat->izinkan_unduh_default
                                            : (bool) $dokumen->pivot->izinkan_unduh;
                                    @endphp
                                    <article class="flex flex-col gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-purple-200 sm:flex-row sm:items-center">
                                        <x-file-icon :ext="$ext" class="h-11 w-9 rounded-md text-[9px]" />
                                        <div class="min-w-0 flex-1">
                                            <h4 class="break-words text-sm font-bold text-slate-800">{{ $dokumen->judul }}</h4>
                                            <p class="mt-1 break-all text-xs text-slate-500">{{ $dokumen->file_name }}</p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2">
                                            <a href="{{ route('rapat.lihat', ['token' => $ruangRapat->token, 'dokumen' => $dokumen->id]) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:border-purple-300 hover:bg-slate-50 hover:text-purple-600">Lihat</a>
                                            @if ($bolehUnduh)
                                                <a href="{{ route('rapat.unduh', ['token' => $ruangRapat->token, 'dokumen' => $dokumen->id]) }}" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:from-purple-700 hover:to-indigo-700">Unduh</a>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="rounded-2xl border border-purple-100 bg-purple-50/60 px-4 py-3 text-center text-[11px] leading-relaxed text-purple-700">
            Tautan ini bersifat rahasia dan memiliki masa berlaku. Jangan teruskan kepada pihak yang tidak berkepentingan.
        </div>
    </div>
@endsection
