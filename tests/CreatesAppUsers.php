<?php

namespace Tests;

use App\Models\Bidang;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait CreatesAppUsers
{
    protected function buatKerangka(): void
    {
        foreach ([['1', 'SEKRETARIAT', 'Sekretariat'], ['2', 'BIDANG PENGEMBANGAN DESTINASI', 'Bidang Destinasi'], ['3', 'BIDANG PEMASARAN', 'Bidang Pemasaran']] as [$k, $n]) {
            Bidang::create(['kode' => $k, 'nama' => $n]);
        }
    }

    protected function userAdmin(): User
    {
        return User::create(['name' => 'Si Admin', 'nip' => 'ADMIN0000', 'password' => 'admin123', 'role' => 'admin']);
    }

    protected static $seq = 0;

    protected function userPegawai(?int $bidang = 1, string $nip = 'PEG9'): User
    {
        $nip = $nip === 'PEG9' ? 'PEG9-' . (++static::$seq) : $nip;
        return User::create(['name' => 'Staf Uji', 'nip' => $nip, 'password' => 'rahasia123', 'role' => 'pegawai', 'id_bidang' => $bidang]);
    }
}
