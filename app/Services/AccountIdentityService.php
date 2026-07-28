<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

final class AccountIdentityService
{
    public function normalizeUsername(mixed $value): string
    {
        $username = strtolower(trim((string) $value));

        if (! preg_match('/^[a-z0-9._-]{3,50}$/', $username)) {
            throw ValidationException::withMessages([
                'username' => 'Username harus terdiri dari 3 sampai 50 karakter: huruf, angka, titik, strip, atau underscore.',
            ]);
        }

        return $username;
    }

    public function normalizeEmail(mixed $value): string
    {
        $email = strtolower(trim((string) $value));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'Email wajib menggunakan format yang valid.',
            ]);
        }

        return $email;
    }

    public function ensureAvailable(
        string $username,
        string $email,
        ?User $ignoreUser = null,
    ): void {
        $usernameQuery = User::query()
            ->where('username', $username);

        $emailQuery = User::query()
            ->where('email', $email);

        if ($ignoreUser !== null) {
            $usernameQuery->whereKeyNot($ignoreUser->getKey());
            $emailQuery->whereKeyNot($ignoreUser->getKey());
        }

        $errors = [];

        if ($usernameQuery->exists()) {
            $errors['username'] = 'Username tersebut sudah digunakan.';
        }

        if ($emailQuery->exists()) {
            $errors['email'] = 'Email tersebut sudah digunakan.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
