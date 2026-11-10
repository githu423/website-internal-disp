@extends('tamu-rapat.layout')

@section('title', 'Pratinjau Dokumen · ' . $dokumen->judul)

@section('content')
@php
    $mime = (string) $dokumen->mime;
    $isPdf = str_contains($mime, 'pdf');
    $isImg = str_starts_with($mime, 'image');
    $isText = str_starts_with($mime, 'text/') || in_array($mime, ['application/json', 'application/xml'], true);
    $ext = strtolower(pathinfo($dokumen->file_name, PATHINFO_EXTENSION));
    $isSheet = in_array($ext, ['xlsx', 'xls', 'ods'], true);
    $isDocx = in_array($ext, ['docx', 'doc'], true);
    $isPptx = in_array($ext, ['pptx', 'ppt'], true);
    $bisaRender = $isPdf || $isImg || $isText || $isSheet || ($isDocx && $ext === 'docx') || ($isPptx && $ext === 'pptx');
    $src = route('rapat.raw', ['token' => $ruangRapat->token, 'dokumen' => $dokumen->id]);
@endphp

<div class="space-y-5">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="mb-2 flex items-center text-xs font-medium text-slate-400">
                <a href="{{ route('rapat.buka', ['token' => $ruangRapat->token]) }}" class="text-purple-700 hover:underline">Ruang Rapat</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-600 truncate max-w-xs">{{ $ruangRapat->judul }}</span>
                <span class="mx-2 text-slate-300">/</span>
                <span class="font-bold text-slate-800">Pratinjau</span>
            </nav>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 flex items-center gap-2.5">
                <span class="truncate max-w-xl">{{ $dokumen->judul }}</span>
            </h1>
            <p class="mt-1 text-xs text-slate-500">
                {{ $dokumen->file_name }} &bull; {{ number_format($dokumen->file_size / 1024, 0) }} KB
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            @if ($bisaRender)
                <button type="button" id="btn-fullscreen" title="Tampilkan pratinjau memenuhi layar (tekan Esc untuk keluar)"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 text-xs font-bold transition shadow-xs">
                    <span>⛶</span> Layar Penuh
                </button>
            @endif

            @if ($bolehUnduh)
                <a href="{{ route('rapat.unduh', ['token' => $ruangRapat->token, 'dokumen' => $dokumen->id]) }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-xs font-bold transition shadow-xs">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Unduh Dokumen
                </a>
            @endif

            <a href="{{ route('rapat.buka', ['token' => $ruangRapat->token]) }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 text-xs font-bold transition shadow-xs">
                &larr; Kembali
            </a>
        </div>
    </div>

    <!-- Viewer Container -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden" id="preview-box">
        @if ($isPdf)
            <iframe src="{{ $src }}" class="w-full h-[78vh] bg-slate-50 border-0" title="{{ $dokumen->judul }}"></iframe>
        @elseif ($isImg)
            <div class="p-4 grid place-items-center bg-[repeating-conic-gradient(#f1f5f9_0%_25%,#fff_0%_50%)] bg-[length:24px_24px]">
                <img src="{{ $src }}" alt="{{ $dokumen->judul }}" class="max-h-[78vh] max-w-full rounded-lg shadow">
            </div>
        @elseif ($isText)
            <pre class="p-5 text-[13px] leading-relaxed whitespace-pre-wrap max-h-[78vh] overflow-y-auto thin-scroll font-mono text-slate-700">{{ $isiTeks }}</pre>
        @elseif ($isPptx && $ext === 'pptx')
            <div id="office-host" data-kind="pptx" data-src="{{ $src }}"
                 class="p-5 max-h-[78vh] overflow-y-auto thin-scroll">
                <p class="text-sm text-slate-400 animate-pulse">Menyiapkan pratinjau PowerPoint … <span class="font-semibold">({{ number_format($dokumen->file_size / 1024) }} KB — dirender langsung di browser, file tidak meninggalkan server)</span></p>
                <p class="text-[12px] text-slate-400 mt-1">Gunakan tombol ‹ › di pratinjau untuk pindah slide, atau «⛶ Layar penuh» untuk mode presentasi.</p>
            </div>
        @elseif (($isSheet || $isDocx) && in_array($ext, ['xlsx', 'xls', 'ods', 'docx'], true))
            <div id="office-host" data-kind="{{ $isSheet ? 'xlsx' : 'docx' }}" data-src="{{ $src }}"
                 class="p-5 max-h-[78vh] overflow-y-auto thin-scroll">
                <p class="text-sm text-slate-400 animate-pulse">Menyiapkan pratinjau {{ $isSheet ? 'spreadsheet' : 'Word' }} … <span class="font-semibold">({{ number_format($dokumen->file_size / 1024) }} KB — dirender langsung di browser, file tidak meninggalkan server)</span></p>
            </div>
        @else
            <div class="p-10 text-center">
                <span class="mx-auto h-14 w-14 rounded-2xl bg-purple-50 text-purple-600 grid place-items-center mb-3 text-2xl font-black">{{ strtoupper($ext ?: '?') }}</span>
                <p class="font-bold text-slate-700">Pratinjau langsung belum didukung untuk format .{{ $ext ?: '?' }}</p>
                <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">Pratinjau otomatis tersedia untuk PDF, gambar, teks, .xlsx, .docx, dan .pptx.</p>
                @if ($bolehUnduh)
                    <a href="{{ route('rapat.unduh', ['token' => $ruangRapat->token, 'dokumen' => $dokumen->id]) }}"
                        class="inline-flex items-center gap-2 mt-4 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-xs font-bold px-4 py-2.5 hover:from-purple-700 hover:to-indigo-700 transition shadow-sm">
                        Unduh {{ $dokumen->file_name }}
                    </a>
                @else
                    <p class="text-xs text-slate-400 mt-2 font-medium">Dokumen ini disetel untuk hanya dapat dilihat dan penyelenggara belum mengizinkan pengunduhan.</p>
                @endif
            </div>
        @endif
    </div>
</div>

<style>
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

