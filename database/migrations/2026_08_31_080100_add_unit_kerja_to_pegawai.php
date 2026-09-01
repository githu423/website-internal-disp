<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unit kerja dari berkas pegawai — dipakai untuk pengelompokan bidang otomatis
        Schema::table('pegawai', function (Blueprint $table) {
            $table->string('unit_kerja', 120)->nullable()->after('jabatan');
        });
    }

    public function down(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            $table->dropColumn('unit_kerja');
        });
    }
};
