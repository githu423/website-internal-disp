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
<div class="max-w-6xl mx-auto space-y-5">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                <span>SIPARDIS</span>
                <span class="text-slate-300">/</span>
                <a href="{{ route('dokumen.index') }}" class="text-slate-500 hover:text-purple-600 transition">Kantor</a>
                <span class="text-slate-300">/</span>
                <a href="{{ route('dokumen.index') }}" class="text-slate-500 hover:text-purple-600 transition">Dokumen</a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold">Pratinjau</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                <span class="truncate max-w-xl">{{ $d->judul }}</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ ['publik' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'bidang' => 'bg-sky-50 text-sky-700 border-sky-200', 'rahasia' => 'bg-rose-50 text-rose-700 border-rose-200'][$d->visibility] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                    {{ ucfirst($d->visibility) }}
                </span>
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $d->bidang?->nama ?? 'Seluruh Dinas' }} · Diunggah oleh <span class="font-medium text-slate-700">{{ $d->uploader?->name ?? '—' }}</span> · {{ $d->created_at->translatedFormat('d M Y, H:i') }}
            </p>
        </div>

        <!-- Quick Actions -->
        <div class="flex items-center gap-2.5 flex-wrap">
            @if ($bisaRender)
            <button type="button" id="btn-fullscreen" title="Tampilkan pratinjau memenuhi layar (tekan Esc untuk keluar)"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 text-sm font-semibold transition shadow-sm">
                <span>⛶</span> Layar Penuh
            </button>
            @endif

            <a href="{{ route('dokumen.unduh', $d) }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold transition shadow-sm shadow-purple-200">
                <x-icon name="dokumen" class="h-4 w-4" /> Unduh Dokumen
            </a>

            @if ($u->id === $d->uploaded_by)
            <form method="POST" action="{{ route('dokumen.akses', $d) }}" class="inline-flex items-center">
                @csrf @method('PATCH')
                <select name="visibility" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white text-sm font-semibold px-3 py-2 cursor-pointer outline-none hover:border-purple-300 focus:border-purple-400 text-slate-700 shadow-sm transition">
                    @foreach (['publik' => '🌐 Publik', 'bidang' => '🏢 Bidang', 'rahasia' => '🔒 Rahasia'] as $v => $lab)
                        <option value="{{ $v }}" @selected($d->visibility === $v)>{{ $lab }}</option>
                    @endforeach
                </select>
            </form>
            @endif

            <a href="{{ route('dokumen.index') }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 text-sm font-semibold transition shadow-sm">
                ← Kembali
            </a>
        </div>
    </div>

    <!-- Viewer Container -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden" id="preview-box">
        @if ($isPdf)
            <iframe src="{{ $src }}" class="w-full h-[78vh] bg-slate-50" title="{{ $d->judul }}"></iframe>
        @elseif ($isImg)
            <div class="preview-checker p-4 grid place-items-center bg-[repeating-conic-gradient(#f1f5f9_0%_25%,#fff_0%_50%)] bg-[length:24px_24px]"><img src="{{ $src }}" alt="{{ $d->judul }}" class="max-h-[78vh] max-w-full rounded-lg shadow"></div>
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