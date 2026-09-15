<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class CreateSuperAdmin extends Command
{
    protected $signature = 'admin:create-super';

    protected $description = 'Membuat akun administrator dengan akses penuh';

    public function handle(): int
    {
        $data = [
            'name' => trim((string) $this->ask('Nama lengkap')),
            'username' => strtolower(trim((string) $this->ask('Username'))),
            'email' => strtolower(trim((string) $this->ask('Email'))),
            'password' => (string) $this->secret('Kata sandi'),
            'password_confirmation' => (string) $this->secret('Konfirmasi kata sandi'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique(User::class, 'username'),
            ],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'username.regex' => 'Username hanya boleh berisi huruf kecil, angka, titik, garis bawah, dan tanda hubung.',
        ]);

        if ($validator->fails()) {
            $this->components->error('Akun administrator gagal dibuat.');

            foreach ($validator->errors()->all() as $error) {
                $this->line("- {$error}");
            }

            return self::FAILURE;
        }

        $user = new User;
        $user->forceFill([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ])->save();

        $this->components->info("Administrator {$user->username} berhasil dibuat.");

        return self::SUCCESS;
    }
}
