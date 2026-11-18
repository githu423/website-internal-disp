<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Perhitungan masa kerja pegawai.
 *
 * Satuannya tahun + bulan + HARI (permintaan Ricky, 7 Sep 2026) —
 * ikut bertambah setiap hari, bukan hanya per bulan.
 */
class MasaKerjaService
{
    /**
     * Hitung masa kerja real-time dari tanggal TMT sampai HARI INI.
     *
     * @return array{tahun:int,bulan:int,hari:int,label:string}
     */
    public static function hitung(Carbon|string|null $sejak): array
    {
        // ===== TANPA TANGGAL TMT: MASA KERJA KOSONG =====
        if (! $sejak) {
            return ['tahun' => 0, 'bulan' => 0, 'hari' => 0, 'label' => '—'];
        }

        // ===== HITUNG SELISIH HINGGA HARI INI =====
        $sejakCarbon = $sejak instanceof Carbon ? $sejak : Carbon::parse($sejak);
        $now = now();
        if ($sejakCarbon->gt($now)) {
            return ['tahun' => 0, 'bulan' => 0, 'hari' => 0, 'label' => '—'];
        }

        $diff = $sejakCarbon->diff($now);
        $tahun = (int) $diff->y;
        $bulan = (int) $diff->m;
        $hari = (int) $diff->d;

        // ===== SUSUN LABEL "X tahun Y bulan Z hari" =====
        $labelBagian = [];
        if ($tahun > 0) {
            $labelBagian[] = $tahun.' tahun';
        }
        if ($tahun > 0 || $bulan > 0) {
            $labelBagian[] = $bulan.' bulan';
        }
        $labelBagian[] = $hari.' hari';

        return ['tahun' => $tahun, 'bulan' => $bulan, 'hari' => $hari, 'label' => implode(' ', $labelBagian)];
    }
}
