<?php

use App\Http\Controllers\Api\TokenController;
use Illuminate\Support\Facades\Route;

// ===== API TOKEN (TANPA LOGIN) =====
Route::post('auth/token', [TokenController::class, 'issue']);

// ===== API DENGAN TOKEN SANCTUM =====
Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [TokenController::class, 'me']);

    // Data capaian bulanan untuk satu tahun.
    Route::get('statistik/{tahun}', function (\Illuminate\Http\Request $r, int $tahun) {
        return response()->json(\App\Models\CapaianBulanan::where('tahun', $tahun)->orderBy('bulan')->get());
    });
});
