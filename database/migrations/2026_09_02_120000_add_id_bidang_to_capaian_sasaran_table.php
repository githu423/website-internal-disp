<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capaian_sasaran', function (Blueprint $table) {
            $table->unsignedBigInteger('id_bidang')->nullable()->after('cakupan');
            $table->foreign('id_bidang')->references('id')->on('bidang')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('capaian_sasaran', function (Blueprint $table) {
            $table->dropForeign(['id_bidang']);
            $table->dropColumn('id_bidang');
        });
    }
};
