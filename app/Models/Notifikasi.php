<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';
    protected $fillable = ['user_id', 'judul', 'isi', 'tipe', 'url', 'dibaca_at'];
    protected $casts = ['dibaca_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }

    public function scopeBelum($q) { return $q->whereNull('dibaca_at'); }
}
