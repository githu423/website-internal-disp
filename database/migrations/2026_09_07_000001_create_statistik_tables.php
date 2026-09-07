<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Statistik Pariwisata format "indikator × tahun" (file Ricky, 7 Sep 2026).
     * Ganti pemakaian capaian-bulanan di modul Statistik — tabel lama TIDAK dihapus
     * (masih dipakai modul Capaian Kinerja).
     */
    public function up(): void
    {
        Schema::create('statistik', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('no_urut')->nullable();
            $t->string('indikator', 180)->unique();
            $t->string('satuan', 40)->nullable();
            $t->string('keterangan', 255)->nullable();
            $t->timestamps();
        });

        Schema::create('statistik_nilai', function (Blueprint $t) {
            $t->id();
            $t->foreignId('id_statistik')->constrained('statistik')->cascadeOnDelete();
            $t->unsignedSmallInteger('tahun');
            $t->decimal('nilai', 20, 4)->nullable();
            $t->unique(['id_statistik', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statistik_nilai');
        Schema::dropIfExists('statistik');
    }
};
