<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestasi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_penghargaan', 500);
            $table->string('nama_penerima', 255)->nullable();
            $table->string('pemberi_penghargaan', 255)->nullable();
            $table->unsignedSmallInteger('tahun');
            $table->string('tingkat', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestasi');
    }
};
