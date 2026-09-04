<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bidang (opsional) untuk sasaran strategis Capaian Kinerja — fitur usulan rekan (Ricky).
 * Dijaga hasColumn agar aman walau migration serupa sudah pernah jalan di mesin lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('capaian_sasaran', 'id_bidang')) {
            Schema::table('capaian_sasaran', function (Blueprint $table) {
                $table->unsignedBigInteger('id_bidang')->nullable()->after('cakupan');
                $table->foreign('id_bidang')->references('id')->on('bidang')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('capaian_sasaran', 'id_bidang')) {
            Schema::table('capaian_sasaran', function (Blueprint $table) {
                $table->dropForeign(['id_bidang']);
                $table->dropColumn('id_bidang');
            });
        }
    }
};
