@extends('layouts.dashboard')
@section('title', 'Pratinjau Dokumen')
@section('content')
@php
    $mime = (string) $d->mime;
    $isPdf = str_contains($mime, 'pdf');
    $isImg = str_starts_with($mime, 'image');
    $isText = str_starts_with($mime, 'text/') || in_array($mime, ['application/json', 'application/xml']);
    $src = route('dokumen.lihat', $d);
@endphp
<div class="max-w-5xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="min-w-0">
            <h2 class="font-extrabold text-lg leading-tight truncate">{{ $d->judul }}</h2>
            <p class="text-[12.5px] text-slate-500 mt-0.5">
                {{ $d->bidang?->nama ?? 'Seluruh Dinas' }} · diunggah {{ $d->uploader?->name ?? '—' }} · {{ $d->created_at->translatedFormat('d M Y, H:i') }}
                · <span class="font-bold {{ ['publik' => 'text-emerald-600', 'bidang' => 'text-sky-600', 'rahasia' => 'text-rose-600'][$d->visibility] }}">{{ ucfirst($d->visibility) }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dokumen.unduh', $d) }}" class="rounded-lg bg-slate-900 hover:bg-slate-700 active:scale-95 text-white text-sm font-bold px-4 py-2 transition inline-flex items-center gap-1.5"><x-icon name="dokumen" class="h-4 w-4" /> Unduh</a>
            @if ($u->id === $d->uploaded_by)
            <form method="POST" action="{{ route('dokumen.akses', $d) }}" class="inline-flex items-center gap-1.5">
                @csrf @method('PATCH')
                <select name="visibility" onchange="this.form.submit()" class="rounded-lg border border-slate-300 bg-white text-sm font-semibold px-2.5 py-2 cursor-pointer outline-none hover:border-teal-400">
                    @foreach (['publik' => '🌐 Publik', 'bidang' => '🏢 Bidang', 'rahasia' => '🔒 Rahasia'] as $v => $lab)
                        <option value="{{ $v }}" @selected($d->visibility === $v)>{{ $lab }}</option>
                    @endforeach
                </select>
            </form>
            @endif
            <a href="{{ url()->previous() }}" class="rounded-lg border border-slate-300 bg-white text-sm font-semibold px-3.5 py-2 text-slate-600 hover:border-teal-400 active:scale-95 transition">← Kembali</a>
        </div>
    </div>

    <div class="card overflow-hidden">
        @if ($isPdf)
            <iframe src="{{ $src }}" class="w-full h-[78vh] bg-slate-50" title="{{ $d->judul }}"></iframe>
        @elseif ($isImg)
            <div class="p-4 grid place-items-center bg-[repeating-conic-gradient(#f1f5f9_0%_25%,#fff_0%_50%)] bg-[length:24px_24px]"><img src="{{ $src }}" alt="{{ $d->judul }}" class="max-h-[78vh] max-w-full rounded-lg shadow"></div>
        @elseif ($isText)
            <pre class="p-5 text-[13px] leading-relaxed whitespace-pre-wrap max-h-[78vh] overflow-y-auto thin-scroll font-mono text-slate-700">{{ $isiTeks }}</pre>
        @else
            <div class="p-10 text-center">
                <span class="mx-auto h-14 w-14 rounded-2xl bg-slate-100 text-slate-500 grid place-items-center mb-3 text-2xl font-black">{{ strtoupper(pathinfo($d->file_name, PATHINFO_EXTENSION) ?: '?') }}</span>
                <p class="font-bold text-slate-600">Pratinjau langsung tidak tersedia untuk format ini</p>
                <p class="text-sm text-slate-400 mt-1 max-w-md mx-auto">{{ $mime }} — gunakan tombol Unduh lalu buka di aplikasi Office/reader di perangkat Anda. Setelah diunggah ulang, file akan kembali bisa dicari di halaman universal.</p>
                <a href="{{ route('dokumen.unduh', $d) }}" class="inline-block mt-4 rounded-lg bg-slate-900 text-white text-sm font-bold px-5 py-2.5 hover:bg-slate-700 transition">Unduh {{ $d->file_name }}</a>
            </div>
        @endif
    </div>
    <p class="text-[11px] text-slate-400 text-center mt-2.5">File disajikan <i>inline</i> — menghitung sebagai “dibaca”. Pembaca: {{ $d->dibaca_count }}×</p>
</div>
@endsection
