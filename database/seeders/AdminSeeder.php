<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['nip' => config('dispar.admin.nip')],
            [
                'name' => config('dispar.admin.name'),
                'password' => config('dispar.admin.password'),
                'role' => 'admin',
            ]
        );
    }
}
