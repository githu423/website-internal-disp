@extends('layouts.dashboard')
@section('title', 'Pratinjau Dokumen')
@section('content')
@php
    $mime = (string) $d->mime;
    $isPdf = str_contains($mime, 'pdf');
    $isImg = str_starts_with($mime, 'image');
    $isText = str_starts_with($mime, 'text/') || in_array($mime, ['application/json', 'application/xml']);
    $ext = strtolower(pathinfo($d->file_name, PATHINFO_EXTENSION));
    $isSheet = in_array($ext, ['xlsx', 'xls', 'ods'], true);
    $isDocx = $ext === 'docx';
    $isPptx = $ext === 'pptx';
    $bisaRender = $isPdf || $isImg || $isText || $isSheet || $isDocx || $isPptx;
    $src = route('dokumen.lihat', $d);
@endphp
<div class="max-w-5xl mx-auto">
    <!-- Breadcrumbs Mikro -->
    <div class="mb-1.5">
        <span class="text-gray-400 text-sm font-medium">SIPARDIS <span class="mx-1">/</span> <a href="{{ route('dokumen.index') }}" class="text-gray-500 hover:text-purple-600 transition">Dokumen</a> <span class="mx-1">/</span> <span class="text-gray-500">Pratinjau</span></span>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="min-w-0">
            <h2 class="font-extrabold text-[22px] leading-tight truncate">{{ $d->judul }}</h2>
            <p class="text-[12.5px] text-slate-500 mt-0.5">
                {{ $d->bidang?->nama ?? 'Seluruh Dinas' }} · diunggah {{ $d->uploader?->name ?? '—' }} · {{ $d->created_at->translatedFormat('d M Y, H:i') }}
                · <span class="font-bold {{ ['publik' => 'text-emerald-600', 'bidang' => 'text-sky-600', 'rahasia' => 'text-rose-600'][$d->visibility] }}">{{ ucfirst($d->visibility) }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if ($bisaRender)
            <button type="button" id="btn-fullscreen" title="Tampilkan pratinjau memenuhi layar (tekan Esc untuk keluar)"
                class="rounded-xl border border-slate-200 bg-white text-sm font-semibold px-3.5 py-2 text-slate-600 hover:border-purple-300 hover:text-purple-700 active:scale-95 transition inline-flex items-center gap-1.5">⛶ Layar penuh</button>
            @endif
            <a href="{{ route('dokumen.unduh', $d) }}" class="rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 shadow-sm shadow-purple-500/30 active:scale-95 text-white text-sm font-bold px-4 py-2 transition inline-flex items-center gap-1.5"><x-icon name="dokumen" class="h-4 w-4" /> Unduh</a>
            @if ($u->id === $d->uploaded_by)
            <form method="POST" action="{{ route('dokumen.akses', $d) }}" class="inline-flex items-center gap-1.5">
                @csrf @method('PATCH')
                <select name="visibility" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white text-sm font-semibold px-2.5 py-2 cursor-pointer outline-none hover:border-purple-300 focus:border-purple-400">
                    @foreach (['publik' => '🌐 Publik', 'bidang' => '🏢 Bidang', 'rahasia' => '🔒 Rahasia'] as $v => $lab)
                        <option value="{{ $v }}" @selected($d->visibility === $v)>{{ $lab }}</option>
                    @endforeach
                </select>
            </form>
            @endif
            <a href="{{ url()->previous() }}" class="rounded-xl border border-slate-200 bg-white text-sm font-semibold px-3.5 py-2 text-slate-600 hover:border-purple-300 active:scale-95 transition">← Kembali</a>
        </div>
    </div>

    <div class="clean-card overflow-hidden" id="preview-box">
        @if ($isPdf)
            <iframe src="{{ $src }}" class="w-full h-[78vh] bg-slate-50" title="{{ $d->judul }}"></iframe>
        @elseif ($isImg)
            <div class="p-4 grid place-items-center bg-[repeating-conic-gradient(#f1f5f9_0%_25%,#fff_0%_50%)] bg-[length:24px_24px]"><img src="{{ $src }}" alt="{{ $d->judul }}" class="max-h-[78vh] max-w-full rounded-lg shadow"></div>
        @elseif ($isText)
            <pre class="p-5 text-[13px] leading-relaxed whitespace-pre-wrap max-h-[78vh] overflow-y-auto thin-scroll font-mono text-slate-700">{{ $isiTeks }}</pre>
        @elseif ($isPptx)
            <div id="office-host" data-kind="pptx" data-src="{{ $src }}"
                 class="p-5 max-h-[78vh] overflow-y-auto thin-scroll">
                <p class="text-sm text-slate-400 animate-pulse">Menyiapkan pratinjau PowerPoint … <span class="font-semibold">({{ number_format($d->file_size / 1024) }} KB — dirender langsung di browser, file tidak meninggalkan server)</span></p>
                <p class="text-[12px] text-slate-400 mt-1">Gunakan tombol ‹ › di pratinjau untuk pindah slide, atau «⛶ Layar penuh» untuk mode presentasi.</p>
            </div>
        @elseif ($isSheet || $isDocx)
            <div id="office-host" data-kind="{{ $isSheet ? 'xlsx' : 'docx' }}" data-src="{{ $src }}"
                 class="p-5 max-h-[78vh] overflow-y-auto thin-scroll">
                <p class="text-sm text-slate-400 animate-pulse">Menyiapkan pratinjau {{ $isSheet ? 'spreadsheet' : 'Word' }} … <span class="font-semibold">({{ number_format($d->file_size / 1024) }} KB — dirender langsung di browser, file tidak meninggalkan server)</span></p>
            </div>
        @else
            <div class="p-10 text-center">
                <span class="mx-auto h-14 w-14 rounded-2xl bg-slate-100 text-slate-500 grid place-items-center mb-3 text-2xl font-black">{{ strtoupper(pathinfo($d->file_name, PATHINFO_EXTENSION) ?: '?') }}</span>
                <p class="font-bold text-slate-600">Pratinjau langsung belum tersedia untuk format .{{ $ext ?: '?' }} ini</p>
                <p class="text-sm text-slate-400 mt-1 max-w-md mx-auto">{{ $mime }} — pratinjau otomatis tersedia untuk PDF, gambar, teks, .xlsx, .docx, dan .pptx. Untuk file PowerPoint versi lama (.ppt), simpan ulang sebagai <b>.pptx</b> atau <b>PDF</b> di aplikasi Office lalu unggah ulang agar bisa dilihat langsung.</p>
                <a href="{{ route('dokumen.unduh', $d) }}" class="inline-block mt-4 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-sm font-bold px-5 py-2.5 hover:from-purple-700 hover:to-indigo-700 transition">Unduh {{ $d->file_name }}</a>
            </div>
        @endif
    </div>
    <p class="text-[11px] text-slate-400 text-center mt-2.5">File disajikan <i>inline</i> — menghitung sebagai “dibaca”. Pembaca: {{ $d->dibaca_count }}×</p>
</div>

<style>
    /* mode layar penuh: pratinjau memenuhi layar */
    #preview-box:fullscreen { border-radius: 0; border: none; background: #fff; overflow: auto; }
    #preview-box:-webkit-full-screen { border-radius: 0; border: none; background: #fff; overflow: auto; }
    #preview-box:fullscreen iframe { height: 100vh; }
    #preview-box:-webkit-full-screen iframe { height: 100vh; }
    #preview-box:fullscreen img { max-height: 96vh; }
    #preview-box:fullscreen pre { max-height: none; min-height: 100vh; }
    #preview-box:fullscreen #office-host { max-height: none; }
</style>
<script>
    (function () {
        const btn = document.getElementById('btn-fullscreen');
        const box = document.getElementById('preview-box');
        if (!btn || !box) return;
        const fs = () => box.requestFullscreen ? box.requestFullscreen()
            : (box.webkitRequestFullscreen ? box.webkitRequestFullscreen() : alert('Browser ini tidak mendukung layar penuh.'));
        btn.addEventListener('click', fs);
    })();
</script>
@endsection