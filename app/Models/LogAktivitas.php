<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';
    protected $fillable = ['user_id', 'nama', 'aksi', 'keterangan', 'ip'];

    public static function catat(string $aksi, ?string $keterangan = null): void
    {
        $u = Auth::user();
        static::create([
            'user_id' => $u?->id,
            'nama' => $u?->name ?? 'tamu',
            'aksi' => $aksi,
            'keterangan' => $keterangan,
            'ip' => Request::ip(),
        ]);
    }
}
