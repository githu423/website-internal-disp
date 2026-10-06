@extends('tamu-rapat.layout')

@section('title', 'Tautan Tidak Aktif')

@section('content')
    <div class="mx-auto max-w-lg pt-6 sm:pt-12">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm sm:p-9">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl border border-slate-200 bg-slate-100 text-slate-500">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 8v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z" />
                </svg>
            </span>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-[.16em] text-slate-400">Ruang Rapat SIPARDIS</p>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">Tautan tidak aktif</h1>
            <p class="mt-3 text-sm leading-relaxed text-slate-500">Tautan untuk <span class="font-semibold text-slate-700">{{ $ruangRapat->judul }}</span> sudah dinonaktifkan atau melewati masa berlaku.</p>
            <p class="mt-2 text-xs leading-relaxed text-slate-400">Silakan hubungi penyelenggara rapat untuk meminta tautan baru atau informasi lebih lanjut.</p>
            <div class="mt-6 border-t border-slate-100 pt-4 text-[11px] text-slate-400">Dinas Pariwisata Kabupaten Ciamis</div>
        </section>
    </div>
@endsection
