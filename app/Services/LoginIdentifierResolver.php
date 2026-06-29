<?php

namespace App\Services;

use App\Models\User;

final class LoginIdentifierResolver
{
    public function resolve(string $identifier): ?User
    {
        $username = strtolower(trim($identifier));

        if ($username === '') {
            return null;
        }

        return User::query()
            ->where('username', $username)
            ->first();
    }
}