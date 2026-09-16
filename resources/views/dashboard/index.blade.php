@extends('layouts.dashboard')
@section('title', 'Dashboard Utama')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', '')
@section('content')
@php $u = auth()->user(); @endphp

<!-- Breadcrumbs Mikro -->
<div class="mb-1.5">
    <span class="text-gray-400 text-sm font-medium">SIPARDIS <span class="mx-1">/</span> <span class="text-gray-500">Dashboard Utama</span></span>
</div>

<!-- Toast Notification (global, muncul di pojok kanan bawah) -->
<div id="dash-toast" class="fixed bottom-6 right-6 z-[200] flex flex-col gap-2.5 pointer-events-none"></div>

<div class="flex flex-wrap items-center justify-between gap-4 mb-7">
    <div>
        <h2 class="text-[22px] md:text-2xl font-extrabold tracking-tight text-gray-900">Assalamu'alaikum, {{ $u->name }} 👋</h2>
        <p class="text-sm text-slate-500 mt-1">{{ $u->jabatan ?: 'Selamat datang di SIPARDIS' }} · {{ now()->translatedFormat('d F Y') }}</p>
    </div>
    
    <!-- Quick Actions -->
    <div class="flex items-center gap-3">
        <a href="{{ route('pad.index') }}" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:bg-slate-50 hover:border-purple-300 hover:text-purple-600 active:scale-95 transition-all duration-150">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Filter Periode
        </a>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.dashboard') }}" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 hover:shadow-purple-600/40 active:scale-95 transition-all duration-150">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Ringkasan Admin
        </a>
        @else
        <a href="{{ route('kinerja.publik') }}" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 hover:shadow-purple-600/40 active:scale-95 transition-all duration-150">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Lihat Kinerja
        </a>
        @endif
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-3 2xl:grid-cols-6 gap-3.5 mb-5">
    <x-stat :label="'PAD ' . $padTahun" :value="$padPersen !== null ? $padPersen . '%' : '—'" :sub="'Realisasi Rp ' . number_format($padSum?->realisasi ?? 0, 0, ',', '.')" tone="teal" icon="pad" :href="route('pad.index')" />
    <x-stat label="Agenda Terdekat" :value="$agendaMendatang->count() . ' / 5'" :sub="$agendaTerlewat > 0 ? $agendaTerlewat . ' terlewat belum selesai' : 'semua tepat waktu'" tone="emerald" icon="agenda" :href="route('agenda.index')" />
    <x-stat label="Dokumen" :value="$jumlahDokumen" sub="tersimpan terpusat" tone="violet" icon="dokumen" :href="route('dokumen.index')" />
    <x-stat label="Pegawai Aktif" :value="$jumlahPegawai" sub="NSPK & non-NSPK" tone="sky" icon="pegawai" dataKey="pegawai_aktif" :href="route('pegawai.index', ['status' => 'aktif'])" />
    <x-stat label="Pegawai Non-Aktif" :value="$jumlahPegawaiNonaktif" sub="cuti panjang/berhenti" tone="amber" icon="pegawai" dataKey="pegawai_nonaktif" :href="route('pegawai.index', ['status' => 'nonaktif'])" />
    <x-stat label="Pegawai Pensiun" :value="$jumlahPegawaiPensiun" sub="purna tugas" tone="slate" icon="pegawai" dataKey="pegawai_pensiun" :href="route('pegawai.index', ['status' => 'pensiun'])" />
</div>

@if ($prestasiTerbaru->isNotEmpty())
    <!-- ============ SLIDER PRESTASI & PENGHARGAAN DINAS (TOP SHOWCASE) ============ -->
    <div class="mb-5 rounded-2xl bg-gradient-to-br from-purple-50 to-purple-100 text-purple-900 p-3 sm:p-4 shadow-md border border-purple-200 relative overflow-hidden" id="prestasi-slider-box">
        <!-- Ambient Decorative Glow -->
        <div class="absolute -right-16 -top-16 w-60 h-60 bg-purple-200/50 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-60 h-60 bg-fuchsia-200/50 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Slider Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 relative z-10">
            <div class="flex items-center gap-3">
                <div class="h-11 w-11 rounded-xl bg-gradient-to-br from-purple-400 via-fuchsia-500 to-purple-600 flex items-center justify-center text-2xl shadow-lg shadow-purple-500/30 shrink-0 select-none">
                    🏆
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10.5px] font-extrabold uppercase tracking-wider text-purple-600">Prestasi &amp; Penghargaan Dinas</span>
                    </div>
                    <h3 class="text-base sm:text-lg font-extrabold tracking-tight text-purple-900 flex items-center gap-1.5">
                        Galeri Prestasi &amp; Capaian Unggulan
                    </h3>
                </div>
            </div>

            <div class="flex items-center gap-2 ml-auto">
                @if ($u->isAdmin())
                    <a href="{{ route('admin.prestasi.index') }}" class="hidden sm:inline-flex items-center gap-1 text-xs font-semibold text-purple-600 hover:text-purple-900 hover:underline mr-1.5 transition">
                        Kelola Prestasi →
                    </a>
                @endif

                <span id="prestasi-page-indicator" class="text-xs font-bold text-purple-700 bg-purple-200/50 px-2.5 py-1 rounded-lg border border-purple-300 select-none">
                    1 / {{ $prestasiTerbaru->count() }}
                </span>

                <div class="flex items-center gap-1">
                    <button type="button" id="prestasi-prev-btn" title="Sebelumnya" aria-label="Prestasi Sebelumnya"
                        class="h-8 w-8 rounded-lg bg-white hover:bg-purple-50 active:scale-95 text-purple-700 flex items-center justify-center transition border border-purple-200 disabled:opacity-30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" id="prestasi-next-btn" title="Selanjutnya" aria-label="Prestasi Selanjutnya"
                        class="h-8 w-8 rounded-lg bg-white hover:bg-purple-50 active:scale-95 text-purple-700 flex items-center justify-center transition border border-purple-200 disabled:opacity-30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Slider Content / Cards Track -->
        <div class="relative z-10 overflow-hidden">
            <div id="prestasi-track" class="flex gap-4 overflow-x-auto scroll-smooth snap-x snap-mandatory py-1 select-none" style="scrollbar-width: none; -ms-overflow-style: none;">
                @foreach ($prestasiTerbaru as $index => $p)
                    <div class="prestasi-slide-item snap-start shrink-0 w-full flex flex-col justify-between transition-all duration-300 relative group min-h-[150px] px-2 py-1">
                        <div>
                            <!-- Badges -->
                            <div class="flex flex-wrap items-center justify-between gap-1.5 mb-2.5">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-purple-200 text-purple-800 border border-purple-300">
                                        <span>📅</span> TA {{ $p->tahun }}
                                    </span>
                                    @if ($p->is_featured)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-extrabold bg-purple-500 text-white shadow-sm">
                                            ⭐ Pilihan Hari Ini
                                        </span>
                                    @endif
                                </div>
                                @if ($p->tingkat)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-purple-200 text-purple-800 border border-purple-300">
                                        🏛️ {{ $p->tingkat }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium text-purple-700 bg-purple-200">
                                        Kab. Ciamis
                                    </span>
                                @endif
                            </div>

                            <!-- Nama Penghargaan -->
                            <h4 class="text-lg sm:text-xl font-extrabold text-purple-950 group-hover:text-purple-700 transition-colors leading-snug line-clamp-2 mb-3" title="{{ $p->nama_penghargaan }}">
                                {{ $p->nama_penghargaan }}
                            </h4>
                        <!-- Content Details Wrapper -->
                        <div class="relative z-10">
                            <!-- Penerima & Pemberi Penghargaan -->
                            <div class="mt-4 bg-white/60 backdrop-blur-sm p-3.5 rounded-xl border border-white/80 grid grid-cols-2 gap-4 text-xs shadow-sm">
                                <div class="flex items-start gap-2.5 min-w-0">
                                    <span class="h-6 w-6 rounded-md bg-purple-100 text-purple-600 grid place-items-center shrink-0 shadow-sm border border-purple-200/50">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <span class="text-[9.5px] uppercase font-bold text-purple-500 block leading-tight">Penerima</span>
                                        <p class="font-extrabold text-slate-800 truncate text-[13px] mt-0.5" title="{{ $p->nama_penerima ?: '—' }}">
                                            {{ $p->nama_penerima ?: '—' }}
                                        </p>
                                    </div>
                                </div>

                                @if ($p->pemberi_penghargaan)
                                <div class="flex items-start gap-2.5 min-w-0">
                                    <span class="h-6 w-6 rounded-md bg-fuchsia-100 text-fuchsia-600 grid place-items-center shrink-0 shadow-sm border border-fuchsia-200/50">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <span class="text-[9.5px] uppercase font-bold text-purple-500 block leading-tight">Pemberi Penghargaan</span>
                                        <p class="font-extrabold text-slate-800 truncate text-[13px] mt-0.5" title="{{ $p->pemberi_penghargaan }}">
                                            {{ $p->pemberi_penghargaan }}
                                        </p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Placeholder / Ilustrasi -->
                        <div class="absolute right-0 bottom-0 pointer-events-none opacity-40 mix-blend-overlay">
                            <svg class="w-40 h-40 text-purple-600 translate-x-12 translate-y-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm0 3.99L19.53 19H4.47L12 5.99z"/></svg>
                        </div>
                    </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Dot Indicators -->
        <div class="flex items-center justify-center gap-1.5 mt-3.5 relative z-10" id="prestasi-dots"></div>
    </div>
@endif

{{-- Konfirmasi Dinas 10 Sep: pilihan lingkup hanya bagi user yang punya bidang --}}
@if ($punyaBidang)
<div class="flex flex-wrap items-center gap-1.5 mb-4">
    <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mr-1">Lingkup data:</span>
    <a href="{{ route('dashboard', array_merge(request()->query(), ['lingkup' => 'semua'])) }}" @class(['rounded-lg px-3 py-1.5 text-[12.5px] font-bold border transition active:scale-95', ! $lingkupSaya ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-slate-200 text-slate-600 hover:border-purple-300 hover:text-purple-600'])>🏛 Sedinas (Semua Bidang)</a>
    <a href="{{ route('dashboard', array_merge(request()->query(), ['lingkup' => 'saya'])) }}" @class(['rounded-lg px-3 py-1.5 text-[12.5px] font-bold border transition active:scale-95', $lingkupSaya ? 'bg-purple-600 text-white border-purple-600' : 'bg-white border-slate-200 text-slate-600 hover:border-purple-300 hover:text-purple-600'])>🏢 Bidang Saya</a>
</div>
@endif



<div class="grid lg:grid-cols-3 gap-4 mb-5">
    <div class="clean-card p-5 lg:col-span-2">
        <div class="flex items-center justify-between mb-2">
            @if ($ringkas)
                <h3 class="font-bold">Tingkat Capaian Keseluruhan per Tahun <span class="text-xs font-semibold text-slate-400">{{ $ringkas['labels'][0] }}–{{ $ringkas['labels'][count($ringkas['labels']) - 1] }}</span></h3>
                <a href="{{ route('kinerja.capaian', ['tampilan' => 'statistik']) }}" class="text-xs font-semibold text-purple-600 hover:underline">Statistik capaian →</a>
            @else
                <h3 class="font-bold">Capaian Kinerja per Bulan <span class="text-xs font-semibold text-slate-400">TA {{ $chart['tahun'] }}</span></h3>
                <a href="{{ route('admin.capaian.index') }}" class="text-xs font-semibold text-purple-600 hover:underline">Impor capaian →</a>
            @endif
        </div>
        @if ($ringkas)
            <p class="text-[11px] text-slate-400 mb-1">Persentase indikator yang mencapai target (≥100%) per tahun — rekap dari berkas capaian kinerja.</p>
            <div class="h-64"><canvas data-chart='@json($ringkas)'></canvas></div>
        @else
            <div class="h-64"><canvas data-chart='@json($chart)'></canvas></div>
            <p class="text-xs text-slate-400 mt-2 text-center">Belum ada berkas capaian kinerja diimpor — untuk sementara grafik garis target bulanan TA {{ $chart['tahun'] }}.</p>
        @endif
    </div>

    <div class="clean-card p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold">Pengingat Kegiatan</h3>
            <a href="{{ route('agenda.index') }}" class="text-xs font-semibold text-purple-600 hover:underline">Semua agenda →</a>
        </div>
        @forelse ($agendaMendatang as $a)
            @php $hariIni = $a->tanggal->isToday(); $lewat = $a->tanggal->lt(now()->startOfDay()); @endphp
            <div class="flex items-start gap-3 {{ $loop->last ? '' : 'pb-3 border-b border-slate-100' }}">
                <div class="h-11 w-11 shrink-0 rounded-xl grid place-items-center text-center leading-none {{ $lewat ? 'bg-rose-50 text-rose-600' : ($hariIni ? 'bg-amber-50 text-amber-600' : 'bg-purple-50 text-purple-700') }}">
                    <div><p class="text-base font-extrabold">{{ $a->tanggal->format('d') }}</p><p class="text-[9px] font-bold uppercase">{{ $a->tanggal->translatedFormat('M') }}</p></div>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[13px] font-semibold truncate">{{ $a->judul }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $a->jam_mulai ? substr((string) $a->jam_mulai, 0, 5) . ' WIB · ' : '' }}{{ $a->lokasi ?: 'Lokasi menyusul' }}@if ($a->bidang) · <span class="text-sky-600 font-semibold">{{ \Illuminate\Support\Str::limit($a->bidang->nama, 28, '…') }}</span>@endif</p>
                    @if ($lewat)<span class="inline-block mt-1 text-[10px] font-bold text-rose-600 bg-rose-50 rounded px-1.5 py-0.5">Terlewat</span>
                    @elseif ($hariIni)<span class="inline-block mt-1 text-[10px] font-bold text-amber-700 bg-amber-50 rounded px-1.5 py-0.5">Hari ini</span>@endif
                </div>
            </div>
        @empty
            <x-empty icon="agenda" title="Belum ada agenda mendatang" desc="Agenda yang dibuat admin/bidang akan muncul di sini sebagai pengingat." />
        @endforelse
    </div>
</div>

<div class="clean-card overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-2 px-5 pt-4 pb-3 border-b border-slate-100">
        <h3 class="font-bold text-[15px]">Kegiatan &amp; Kinerja Dinas <span class="text-xs font-semibold text-slate-400">TA {{ $kinerjaTahun }}</span>
            <span class="ml-1 text-[11px] font-bold bg-slate-100 text-slate-500 rounded-full px-2 py-0.5">{{ $kinerjaSelesai }}/{{ $kinerjaTotal }} tuntas</span></h3>
        <div class="flex items-center gap-3 text-xs font-semibold">
            @if ($u->isAdmin())<a href="{{ route('admin.kinerja.index') }}" class="text-purple-600 hover:underline">Kelola data →</a>@endif
            <a href="{{ route('kinerja.publik') }}" class="text-purple-600 hover:underline">Lihat semua →</a>
        </div>
    </div>
    @if ($kinerja->count())
        <div class="overflow-x-auto thin-scroll">
            <table class="data w-full min-w-[720px]">
                <thead><tr><th class="w-8">#</th><th>Kegiatan / Sub Kegiatan</th><th>Pelaksana</th><th>Target</th><th class="min-w-[190px]">Capaian</th><th>Status</th></tr></thead>
                <tbody>
                @foreach ($kinerja as $k)
                    <tr>
                        <td class="text-slate-400 text-xs">{{ $loop->iteration }}</td>
                        <td>
                            <p class="font-semibold text-[13px] leading-snug">{{ $k->nama }}</p>
                            @if ($k->catatan)<p class="text-[11px] text-amber-600 mt-0.5">⚠ {{ $k->catatan }}</p>@endif
                        </td>
                        <td class="text-xs text-slate-500">{{ $k->bidang?->nama ?? '—' }}</td>
                        <td class="text-xs font-semibold tabular-nums whitespace-nowrap">{{ rtrim(rtrim(number_format((float) $k->target, 2, ',', '.'), '0'), ',') }} <span class="font-normal text-slate-400">{{ $k->satuan }}</span></td>
                        <td><x-prog :persen="$k->persentase" /></td>
                        <td>@php $st = $k->label_status; @endphp
                            <span class="text-[11px] font-bold rounded-full px-2 py-1 whitespace-nowrap {{ $st === 'Tuntas' ? 'bg-emerald-50 text-emerald-700' : ($st === 'Berjalan' ? 'bg-sky-50 text-sky-700' : 'bg-amber-50 text-amber-700') }}">{{ $st }}</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if ($kinerjaTotal > $kinerja->count())
            <div class="px-5 py-2.5 bg-slate-50 text-xs text-slate-500">Menampilkan {{ $kinerja->count() }} dari {{ $kinerjaTotal }} kegiatan — <a class="font-semibold text-purple-600 hover:underline" href="{{ route('kinerja.publik', ['tahun' => $kinerjaTahun]) }}">lihat semuanya</a>.</div>
        @endif
    @else
        <x-empty icon="kinerja" title="Belum ada data kinerja TA {{ $kinerjaTahun }}"
            desc="{{ $u->isAdmin() ? 'Upload file Kinerja Fisik Sedinas (Excel/CSV) atau tambahkan kegiatan manual lewat menu Kelola Kinerja.' : 'Data akan tampil begitu admin memuat file kinerja.' }}">
            @if ($u->isAdmin())<a href="{{ route('admin.kinerja.index') }}" class="inline-block mt-3 rounded-lg bg-slate-900 text-white text-sm font-bold px-4 py-2 hover:bg-slate-700 transition">Import sekarang</a>@endif
        </x-empty>
    @endif
</div>

<style>
#prestasi-track::-webkit-scrollbar { display: none; }
</style>

<script>
(function () {
    var inited = false;
    function initPrestasiSlider() {
        const track = document.getElementById('prestasi-track');
        if (!track) return;

        const prevBtn = document.getElementById('prestasi-prev-btn');
        const nextBtn = document.getElementById('prestasi-next-btn');
        const indicator = document.getElementById('prestasi-page-indicator');
        const dotsContainer = document.getElementById('prestasi-dots');
        const items = track.querySelectorAll('.prestasi-slide-item');
        const total = items.length;
        if (total <= 1) return;

        let currentIndex = 0;

                if (dotsContainer) {
            dotsContainer.innerHTML = '';
            items.forEach((_, idx) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'h-1.5 rounded-full transition-all duration-300 ' + (idx === 0 ? 'w-6 bg-purple-600' : 'w-2 bg-purple-300 hover:bg-purple-400');
                dot.setAttribute('aria-label', 'Slide ' + (idx + 1));
                dot.addEventListener('click', () => goToSlide(idx));
                dotsContainer.appendChild(dot);
            });
        }

        function getCardStep() {
            if (!items[0]) return track.clientWidth;
            const gap = parseFloat(window.getComputedStyle(track).gap) || 16;
            return items[0].offsetWidth + gap;
        }

        function updateUI() {
            if (indicator) indicator.textContent = (currentIndex + 1) + ' / ' + total;
            if (dotsContainer) {
                dotsContainer.querySelectorAll('button').forEach((d, i) => {
                    d.className = 'h-1.5 rounded-full transition-all duration-300 ' + (i === currentIndex ? 'w-6 bg-purple-600' : 'w-2 bg-purple-300 hover:bg-purple-400');
                });
            }
        }

        function goToSlide(idx) {
            currentIndex = (idx + total) % total;
            track.scrollTo({ left: currentIndex * getCardStep(), behavior: 'smooth' });
            updateUI();
        }

        prevBtn?.addEventListener('click', () => { goToSlide(currentIndex - 1); restartAuto(); });
        nextBtn?.addEventListener('click', () => { goToSlide(currentIndex + 1); restartAuto(); });

        let scrollTimeout;
        track.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                const step = getCardStep();
                if (step > 0) {
                    currentIndex = Math.min(total - 1, Math.max(0, Math.round(track.scrollLeft / step)));
                    updateUI();
                }
            }, 80);
        }, { passive: true });

        // Auto slide — berjalan tiap 4 detik; berhenti sementara saat kursor menyentuh kartu
        let autoTimer = null;
        function startAuto() {
            stopAuto();
            autoTimer = setInterval(() => goToSlide(currentIndex + 1), 4000);
        }
        function stopAuto() {
            if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
        }
        function restartAuto() { stopAuto(); startAuto(); }

        const box = document.getElementById('prestasi-slider-box');
        if (box) {
            box.addEventListener('mouseenter', stopAuto);
            box.addEventListener('mouseleave', startAuto);
            box.addEventListener('touchstart', stopAuto, { passive: true });
            box.addEventListener('touchend', () => setTimeout(startAuto, 2000), { passive: true });
        }

        startAuto();
        updateUI();
    }

    // Boot tunggal (anti dobel) + dicoba ulang saat DOM & seluruh aset siap —
    // slider tetap hidup walau ada bagian lain yang error.
    function boot() {
        if (inited) return;
        inited = true;
        try { initPrestasiSlider(); } catch (e) { console.error('slider prestasi:', e); }
    }
    boot();
    document.addEventListener('DOMContentLoaded', boot);
    window.addEventListener('load', boot);
})();
</script>

<script>
/* kartu pegawai realtime — polling 10 detik, dipisah sendiri agar slider tak ikut mati bila error */
(function () {
    var els = document.querySelectorAll('[data-kartu]');
    if (!els.length) return;
    var url = {{ json_encode(route('dashboard.kartu')) }};
    setInterval(function () {
        fetch(url, { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d) return;
                els.forEach(function (el) {
                    var v = d[el.dataset.kartu];
                    if (v !== undefined && el.textContent !== String(v)) el.textContent = v;
                });
            })
            .catch(function () { /* offline: biarkan angka terakhir */ });
    }, 10000);
})();

/* ============ DASHBOARD TOAST SYSTEM ============ */
window.dashToast = function (msg, type) {
    var container = document.getElementById('dash-toast');
    if (!container) return;
    var styles = {
        info:  { bg: 'bg-white border border-blue-100 text-blue-800 shadow-lg shadow-blue-500/10',  icon: '<svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
        warn:  { bg: 'bg-white border border-amber-100 text-amber-800 shadow-lg shadow-amber-500/10', icon: '<svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>' },
        error: { bg: 'bg-white border border-rose-100 text-rose-800 shadow-lg shadow-rose-500/10',   icon: '<svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
        ok:    { bg: 'bg-white border border-emerald-100 text-emerald-800 shadow-lg shadow-emerald-500/10', icon: '<svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' }
    };
    var s = styles[type] || styles.info;
    var t = document.createElement('div');
    t.className = 'pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-xl max-w-sm ' + s.bg;
    t.style.cssText = 'opacity:0;transform:translateX(16px);transition:all .25s cubic-bezier(.4,0,.2,1)';
    t.innerHTML = s.icon + '<p class="text-[13px] font-semibold leading-snug flex-1">' + msg + '</p><button onclick="this.closest(\'div\').remove()" class="text-current opacity-40 hover:opacity-70 transition ml-1 shrink-0 mt-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg></button>';
    container.appendChild(t);
    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            t.style.opacity = '1';
            t.style.transform = 'translateX(0)';
        });
    });
    setTimeout(function () {
        t.style.opacity = '0';
        t.style.transform = 'translateX(16px)';
        setTimeout(function () { t.remove(); }, 250);
    }, 4500);
};
</script>
@endsection