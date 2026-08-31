<?php

namespace App\Services;

use Carbon\Carbon;

class MasaKerjaService
{
    /** @return array{tahun:int,bulan:int,label:string} */
    public static function hitung(Carbon|string|null $sejak): array
    {
        if (! $sejak) return ['tahun' => 0, 'bulan' => 0, 'label' => '—'];
        $s = $sejak instanceof Carbon ? $sejak : Carbon::parse($sejak);
        $now = now();
        if ($s->gt($now)) return ['tahun' => 0, 'bulan' => 0, 'label' => '—'];
        $diff = $s->diff($now);
        $t = $diff->y; $b = $diff->m;
        return ['tahun' => $t, 'bulan' => $b, 'label' => $t.' tahun '.$b.' bulan'];
    }
}
