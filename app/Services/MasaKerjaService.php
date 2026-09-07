<?php

namespace App\Services;

use Carbon\Carbon;

class MasaKerjaService
{
    /**
     * Hitung masa kerja real-time dari tanggal TMT sampai HARI INI.
     * Satuannya tahun + bulan + HARI (permintaan Ricky, 7 Sep 2026) —
     * ikut bertambah setiap hari, bukan hanya per bulan.
     *
     * @return array{tahun:int,bulan:int,hari:int,label:string}
     */
    public static function hitung(Carbon|string|null $sejak): array
    {
        if (! $sejak) return ['tahun' => 0, 'bulan' => 0, 'hari' => 0, 'label' => '—'];
        $s = $sejak instanceof Carbon ? $sejak : Carbon::parse($sejak);
        $now = now();
        if ($s->gt($now)) return ['tahun' => 0, 'bulan' => 0, 'hari' => 0, 'label' => '—'];
        $diff = $s->diff($now);
        $t = (int) $diff->y; $b = (int) $diff->m; $d = (int) $diff->d;
        $bagian = [];
        if ($t > 0) $bagian[] = $t.' tahun';
        if ($t > 0 || $b > 0) $bagian[] = $b.' bulan';
        $bagian[] = $d.' hari';
        return ['tahun' => $t, 'bulan' => $b, 'hari' => $d, 'label' => implode(' ', $bagian)];
    }
}
