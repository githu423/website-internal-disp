<?php

namespace App\Services;

use App\Models\User;

class TokenService
{
    public static function issue(User $user): string
    {
        $user->tokens()->where('name', 'sipardis')->delete();
        return $user->createToken('sipardis')->plainTextToken;
    }
}
