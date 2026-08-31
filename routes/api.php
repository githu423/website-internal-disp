<?php

use App\Http\Controllers\Api\TokenController;
use Illuminate\Support\Facades\Route;

Route::post('auth/token', [TokenController::class, 'issue']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [TokenController::class, 'me']);
    Route::get('statistik/{tahun}', function (\Illuminate\Http\Request $r, int $tahun) {
        return response()->json(\App\Models\CapaianBulanan::where('tahun', $tahun)->orderBy('bulan')->get());
    });
});
