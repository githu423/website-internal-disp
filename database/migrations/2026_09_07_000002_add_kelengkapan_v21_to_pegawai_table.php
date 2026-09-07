<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** v21: kolom pelengkap daftar kepegawaian (pangkat awal + 3 diklat) —
     *  ikut menjadi acuan kelengkapan data sesuai file Excel resmi. */
    public function up(): void
    {
        Schema::table('pegawai', function (Blueprint $t) {
            $t->string('pangkat_awal', 100)->nullable();
            $t->string('diklat_struktural', 150)->nullable();
            $t->string('diklat_fungsional', 150)->nullable();
            $t->string('diklat_teknis', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pegawai', function (Blueprint $t) {
            $t->dropColumn(['pangkat_awal', 'diklat_struktural', 'diklat_fungsional', 'diklat_teknis']);
        });
    }
};
