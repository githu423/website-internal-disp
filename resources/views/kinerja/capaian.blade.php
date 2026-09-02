@extends('layouts.dashboard')
@section('title', 'Capaian Kinerja')
@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h2 class="text-lg font-extrabold">Capaian Kinerja Sasaran Strategis</h2>
        <p class="text-sm text-slate-500">
            Tahun {{ $last }}: {{ $tercapai }} dari {{ $dinilai }} indikator tercapai (≥100%).
        </p>
    </div>
    <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1 text-sm font-bold">
        <a href="{{ route('kinerja.capaian', ['tampilan' => 'matriks']) }}"
           @class(['rounded-md px-3 py-1.5 transition', 'bg-slate-900 text-white' => $tampilan === 'matriks', 'text-slate-500 hover:bg-slate-50' => $tampilan !== 'matriks'])>
            Matriks
        </a>
        <a href="{{ route('kinerja.capaian', ['tampilan' => 'statistik']) }}"
           @class(['rounded-md px-3 py-1.5 transition', 'bg-slate-900 text-white' => $tampilan === 'statistik', 'text-slate-500 hover:bg-slate-50' => $tampilan !== 'statistik'])>
            Statistik
        </a>
    </div>
</div>

@php
    // badge kecil nama bidang; dipakai di kedua tampilan (Matriks & Statistik)
    $badgeBidang = function (?string $nama) {
        $ada = (bool) $nama;
        $cls = $ada ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-500';
        $label = $nama ?: 'Lintas Dinas';
        return '<span class="ml-1 align-middle rounded-full px-2 py-0.5 text-[10px] font-bold ' . $cls . '">' . e($label) . '</span>';
    };

    // siapkan data chart sebagai array PHP biasa (dihitung di sini, bukan inline di dalam <script>,
    // supaya Blade tidak salah parse tanda kurung/kurawal saat menyusun @json() bertingkat)
    $ringkasChartJs = null;
    if (!empty($ringkasChart)) {
        $ringkasChartJs = [
            'labels' => $ringkasChart['labels'],
            'datasets' => array_map(function ($d) {
                return [
                    'label' => $d['label'],
                    'data' => $d['data'],
                    'borderColor' => $d['color'],
                    'backgroundColor' => $d['color'] . '22',
                    'fill' => $d['fill'] ?? false,
                    'tension' => 0.3,
                ];
            }, $ringkasChart['datasets']),
        ];
    }

    $chartsJs = [];
    foreach (($charts ?? []) as $idx => $c) {
        if (empty($c['chart'])) continue;
        $chartsJs[$idx] = [
            'labels' => $c['chart']['labels'],
            'datasets' => array_map(function ($d) {
                return [
                    'type' => $d['type'],
                    'label' => $d['label'],
                    'data' => $d['data'],
                    'backgroundColor' => $d['color'],
                    'borderColor' => $d['color'],
                    'yAxisID' => (($d['axis'] ?? 'y') === 'y1') ? 'y1' : 'y',
                ];
            }, $c['chart']['datasets']),
        ];
    }
@endphp

@if ($tampilan === 'matriks')

    @forelse ($sasarans as $s)
        <div class="card mb-4 overflow-hidden">
            <div class="px-4 py-3 flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50/60">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-slate-900 text-sm font-extrabold text-white">{{ $s->nomor }}</span>
                <div class="flex-1 min-w-[200px]">
                    <div class="font-bold leading-snug">
                        {{ $s->nomor }}. {{ $s->judul }} {!! $badgeBidang($s->bidang?->nama) !!}
                    </div>
                    @if ($s->cakupan)
                        <div class="text-[11px] text-slate-400 font-medium">Sasaran Tahun : {{ $s->cakupan }}</div>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto thin-scroll">
                <table class="data w-full min-w-[720px]">
                    <thead>
                        <tr class="text-left">
                            <th class="w-8">#</th>
                            <th>Indikator</th>
                            <th class="w-20">Satuan</th>
                            @foreach ($years as $y)
                                <th class="w-40 text-center">{{ $y }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($s->indikator as $i => $ind)
                            @php $by = $ind->tahunan->keyBy('tahun'); @endphp
                            <tr class="border-t border-slate-100">
                                <td class="text-slate-400">{{ $i + 1 }}</td>
                                <td class="font-medium">{{ $ind->nama }}</td>
                                <td class="text-slate-500">{{ $ind->satuan ?: '—' }}</td>
                                @foreach ($years as $y)
                                    @php
                                        $n = $by->get($y);
                                        $num = \App\Models\CapaianIndikator::persenNum($n?->persen);
                                    @endphp
                                    <td class="text-center text-xs">
                                        @if ($n && ($n->target || $n->realisasi || $n->persen))
                                            <span class="text-slate-500">T: {{ $n->target ?? '—' }}</span><br>
                                            <span class="font-bold">R: {{ $n->realisasi ?? '—' }}</span>
                                            @if ($num !== null)
                                                <span class="ml-1 rounded px-1.5 py-0.5 text-[10px] font-bold {{ $num >= 100 ? 'bg-emerald-100 text-emerald-800' : ($num >= 80 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-700') }}">{{ $n->persen }}</span>
                                            @endif
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr class="border-t border-slate-100">
                                <td colspan="{{ 3 + count($years) }}" class="py-2 text-slate-400 text-sm">Belum ada indikator.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card p-10 text-center text-slate-400">Belum ada data capaian kinerja.</div>
    @endforelse

@else {{-- tampilan statistik --}}

    @if ($ringkasChartJs)
        <div class="card p-4 mb-4">
            <h3 class="font-bold mb-2 text-sm">% Indikator Tercapai per Tahun</h3>
            <canvas id="chart-ringkas" height="90"></canvas>
        </div>
    @endif

    <div class="grid md:grid-cols-2 gap-4">
        @forelse ($charts as $idx => $c)
            <div class="card p-4">
                <div class="text-xs text-slate-400 font-semibold mb-0.5">
                    {{ $c['sasaran'] }} {!! $badgeBidang($c['bidang'] ?? null) !!}
                </div>
                <h3 class="font-bold text-sm mb-2">{{ $c['nama'] }} @if($c['satuan'])<span class="text-slate-400 font-normal">({{ $c['satuan'] }})</span>@endif</h3>

                @if ($c['chart'])
                    <canvas id="chart-{{ $idx }}" height="160"></canvas>
                @else
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($c['chips'] as $chip)
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $chip['ok'] === true ? 'bg-emerald-100 text-emerald-800' : ($chip['ok'] === false ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-500') }}">
                                {{ $chip['tahun'] }}: {{ $chip['teks'] }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="card p-10 text-center text-slate-400 md:col-span-2">Belum ada data untuk ditampilkan.</div>
        @endforelse
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            var ringkasData = @json($ringkasChartJs);
            var chartsData = @json($chartsJs);

            if (ringkasData) {
                new Chart(document.getElementById('chart-ringkas'), {
                    type: 'line',
                    data: ringkasData,
                    options: {
                        plugins: { legend: { display: false } },
                        scales: { y: { suggestedMax: 100 } },
                    },
                });
            }

            Object.keys(chartsData).forEach(function (idx) {
                var el = document.getElementById('chart-' + idx);
                if (!el) return;
                new Chart(el, {
                    data: chartsData[idx],
                    options: {
                        responsive: true,
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } },
                        scales: {
                            y: { beginAtZero: true },
                            y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, suggestedMax: 100 },
                        },
                    },
                });
            });
        });
    </script>

@endif
@endsection
