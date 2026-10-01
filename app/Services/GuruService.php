<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class GuruService
{
    public function __construct(
        private readonly AccountIdentityService $accountIdentityService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Guru
    {
        $username = $this->accountIdentityService->normalizeUsername(
            $data['username'] ?? null,
        );

        $email = $this->accountIdentityService->normalizeEmail(
            $data['email'] ?? null,
        );

        $this->accountIdentityService->ensureAvailable($username, $email);

        return DB::transaction(function () use (
            $data,
            $username,
            $email,
        ): Guru {
            $user = User::query()->create([
                'name' => trim((string) $data['name']),
                'username' => $username,
                'email' => $email,
                'password' => Hash::make((string) $data['password']),
                'role' => User::ROLE_GURU,
            ]);

            return Guru::query()->create([
                'user_id' => $user->getKey(),
                ...Arr::only($data, [
                    'no_telp',
                    'jenis_kelamin',
                    'tempat_lahir',
                    'tanggal_lahir',
                    'pendidikan_terakhir',
                ]),
                'can_input_nilai' => false,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Guru $guru, array $data): Guru
    {
        return DB::transaction(function () use ($guru, $data): Guru {
            $guru->loadMissing('user');

            $user = $guru->user;

            if ($user === null) {
                throw ValidationException::withMessages([
                    'username' => 'Akun untuk guru ini tidak ditemukan.',
                ]);
            }

            $username = $this->accountIdentityService->normalizeUsername(
                $data['username'] ?? null,
            );

            $email = $this->accountIdentityService->normalizeEmail(
                $data['email'] ?? null,
            );

            $this->accountIdentityService->ensureAvailable(
                $username,
                $email,
                $user,
            );

            $userData = [
                'name' => trim((string) $data['name']),
                'username' => $username,
                'email' => $email,
            ];

            if (filled($data['password'] ?? null)) {
                $userData['password'] = Hash::make(
                    (string) $data['password'],
                );
            }

            $user->update($userData);

            $guru->update([
                ...Arr::only($data, [
                    'no_telp',
                    'jenis_kelamin',
                    'tempat_lahir',
                    'tanggal_lahir',
                    'pendidikan_terakhir',
                ]),
            ]);

            return $guru->refresh();
        });
    }

    public function delete(Guru $guru): bool
    {
        if ($guru->jadwalMengajars()->exists()) {
            throw ValidationException::withMessages([
                'guru' => 'Guru tidak dapat dihapus karena masih memiliki penugasan mengajar.',
            ]);
        }

        return DB::transaction(function () use ($guru): bool {
            $user = $guru->user;
            $deleted = (bool) $guru->delete();

            if ($deleted && $user?->isGuru()) {
                $user->delete();
            }

            return $deleted;
        });
    }
}
