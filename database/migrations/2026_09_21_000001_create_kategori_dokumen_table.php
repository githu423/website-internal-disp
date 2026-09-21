<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori dokumen bertingkat (induk → anak) yang DIPAKAI BERSAMA untuk semua
 * dokumen, terlepas dari status aksesnya (publik / privat bidang / rahasia).
 *
 * Status akses tetap melekat pada tiap dokumen (kolom `visibility`), bukan pada
 * kategori. Dokumen dengan kategori yang sama masuk ke kelompok yang sama meskipun
 * status aksesnya berbeda.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kategori_dokumen')) {
            Schema::create('kategori_dokumen', function (Blueprint $table) {
                $table->id();
                $table->string('nama', 60);
                $table->foreignId('induk_id')->nullable()->constrained('kategori_dokumen')->nullOnDelete();
                $table->timestamps();

                // Nama tidak boleh duplikat di dalam induk yang sama (jaga dari aplikasi; di sini
                // diberi index unik agar aman walau ada dua request bersamaan).
                $table->unique(['induk_id', 'nama'], 'kategori_unik_dalam_induk');
            });
        }

        if (! Schema::hasColumn('dokumen', 'id_kategori')) {
            Schema::table('dokumen', function (Blueprint $table) {
                // Pola sama dengan migrasi teruji lain di proyek ini (aman untuk SQLite).
                $table->unsignedBigInteger('id_kategori')->nullable()->after('kategori');
                $table->foreign('id_kategori')->references('id')->on('kategori_dokumen')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('dokumen', 'id_kategori')) {
            Schema::table('dokumen', function (Blueprint $table) {
                $table->dropForeign(['id_kategori']);
                $table->dropColumn('id_kategori');
            });
        }

        Schema::dropIfExists('kategori_dokumen');
    }
};
