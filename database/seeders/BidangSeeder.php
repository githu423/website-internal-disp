<?php

namespace Database\Seeders;

use App\Models\Bidang;
use Illuminate\Database\Seeder;

class BidangSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['1', 'SEKRETARIAT', 'Sekretariat', 'Uraian tugas: pembinaan administrasi umum, kepegawaian, perencanaan, keuangan dan pelaporan.'],
            ['2', 'BIDANG PENGEMBANGAN DESTINASI', 'Bidang Pengembangan Daya Tarik & Destinasi Pariwisata', 'Uraian tugas: perencanaan dan pengembangan daya tarik wisata, destinasi, dan kelembagaan pariwisata.'],
            ['3', 'BIDANG PEMASARAN', 'Bidang Pemasaran Pariwisata', 'Uraian tugas: promosi, publikasi, kerja sama pemasaran, dan penyelenggaraan event pariwisata.'],
            ['4', 'BIDANG EKONOMI KREATIF', 'Bidang Pengembangan Ekonomi Kreatif & Sumber Daya Pariwisata', 'Uraian tugas: pengembangan ekonomi kreatif, SDM pariwisata, dan kemitraan pelaku usaha.'],
            // UPTD kini entri "bidang" sendiri agar halaman & datanya tampil (permintaan Ricky, 2 Sep)
            ['UPTD-CIA', 'UPTD CIAMIS', 'UPTD Pengelolaan Objek Wisata Wilayah Ciamis', 'Unit Pelaksana Teknis Daerah — pengelolaan objek wisata wilayah Ciamis.'],
            ['UPTD-KAW', 'UPTD KAWALI', 'UPTD Pengelolaan Objek Wisata Wilayah Kawali', 'Unit Pelaksana Teknis Daerah — pengelolaan objek wisata wilayah Kawali.'],
        ];
        foreach ($data as [$kode, $nama, $display, $deskripsi]) {
            Bidang::updateOrCreate(['kode' => $kode], ['nama' => $display, 'deskripsi' => $deskripsi]);
        }
    }
}
