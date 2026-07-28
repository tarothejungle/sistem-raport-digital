<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class SiswaService
{
    public function __construct(
        private readonly AccountIdentityService $accountIdentityService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Siswa
    {
        $nisn = $this->normalizeNisn($data['nisn'] ?? null);
        $this->ensurePassword($data);

        $username = $this->accountIdentityService->normalizeUsername(
            $data['username'] ?? null,
        );

        $email = $this->accountIdentityService->normalizeEmail(
            $data['email'] ?? null,
        );

        $this->accountIdentityService->ensureAvailable($username, $email);

        return DB::transaction(function () use (
            $data,
            $nisn,
            $username,
            $email,
        ): Siswa {
            $user = User::query()->create([
                'name' => trim((string) $data['nama_lengkap']),
                'username' => $username,
                'email' => $email,
                'password' => Hash::make((string) $data['password']),
                'role' => User::ROLE_SISWA,
            ]);

            return Siswa::query()->create([
                'user_id' => $user->getKey(),
                'nisn' => $nisn,
                ...Arr::only($data, ['nama_lengkap', 'kelas_id']),
                'can_view_nilai' => false,
                'status' => Siswa::STATUS_AKTIF,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Siswa $siswa, array $data): Siswa
    {
        $nisn = $this->normalizeNisn($data['nisn'] ?? null);

        return DB::transaction(function () use ($siswa, $data, $nisn): Siswa {
            $siswa->loadMissing('user');

            $username = $this->accountIdentityService->normalizeUsername(
                $data['username'] ?? null,
            );

            $email = $this->accountIdentityService->normalizeEmail(
                $data['email'] ?? null,
            );

            $user = $siswa->user;

            if ($user === null) {
                $this->ensurePassword($data);
                $this->accountIdentityService->ensureAvailable($username, $email);

                $user = User::query()->create([
                    'name' => trim((string) $data['nama_lengkap']),
                    'username' => $username,
                    'email' => $email,
                    'password' => Hash::make((string) $data['password']),
                    'role' => User::ROLE_SISWA,
                ]);

                $siswa->user_id = $user->getKey();
            } else {
                $this->accountIdentityService->ensureAvailable($username, $email, $user);

                $userData = [
                    'name' => trim((string) $data['nama_lengkap']),
                    'username' => $username,
                    'email' => $email,
                ];

                if (filled($data['password'] ?? null)) {
                    $userData['password'] = Hash::make(
                        (string) $data['password'],
                    );
                }

                $user->update($userData);
            }

            $siswa->fill([
                'nisn' => $nisn,
                ...Arr::only($data, ['nama_lengkap', 'kelas_id']),
            ]);

            if (blank($siswa->status)) {
                $siswa->status = Siswa::STATUS_AKTIF;
            }

            $siswa->save();

            return $siswa->refresh();
        });
    }

    /**
     * @return array{status: 'created'|'updated', siswa: Siswa}
     */
    public function upsertFromImport(
        string $nisn,
        string $namaLengkap,
        int $kelasId,
    ): array {
        $nisn = $this->normalizeNisn($nisn);
        $namaLengkap = trim($namaLengkap);

        if ($namaLengkap === '') {
            throw ValidationException::withMessages([
                'nama_lengkap' => 'Nama lengkap wajib diisi.',
            ]);
        }

        if (! Kelas::query()->whereKey($kelasId)->exists()) {
            throw ValidationException::withMessages([
                'kelas' => 'Kelas pada file impor tidak ditemukan.',
            ]);
        }

        return DB::transaction(function () use (
            $nisn,
            $namaLengkap,
            $kelasId,
        ): array {
            $siswa = Siswa::query()
                ->with('user')
                ->where('nisn', $nisn)
                ->first();

            if ($siswa === null) {
                $account = $this->accountUntukImpor($nisn);
                $this->accountIdentityService->ensureAvailable(
                    $account['username'],
                    $account['email'],
                );

                $user = User::query()->create([
                    'name' => $namaLengkap,
                    'username' => $account['username'],
                    'email' => $account['email'],
                    'password' => Hash::make($nisn),
                    'role' => User::ROLE_SISWA,
                ]);

                $siswa = Siswa::query()->create([
                    'user_id' => $user->getKey(),
                    'nisn' => $nisn,
                    'nama_lengkap' => $namaLengkap,
                    'kelas_id' => $kelasId,
                    'can_view_nilai' => false,
                    'status' => Siswa::STATUS_AKTIF,
                ]);

                return [
                    'status' => 'created',
                    'siswa' => $siswa,
                ];
            }

            $siswa->fill([
                'nama_lengkap' => $namaLengkap,
                'kelas_id' => $kelasId,
                'status' => $siswa->status ?: Siswa::STATUS_AKTIF,
            ]);

            $siswa->save();

            if ($siswa->user === null) {
                $account = $this->accountUntukImpor($nisn);
                $this->accountIdentityService->ensureAvailable(
                    $account['username'],
                    $account['email'],
                );

                $user = User::query()->create([
                    'name' => $namaLengkap,
                    'username' => $account['username'],
                    'email' => $account['email'],
                    'password' => Hash::make($nisn),
                    'role' => User::ROLE_SISWA,
                ]);

                $siswa->update([
                    'user_id' => $user->getKey(),
                ]);
            } else {
                $siswa->user->update([
                    'name' => $namaLengkap,
                ]);
            }

            return [
                'status' => 'updated',
                'siswa' => $siswa->refresh(),
            ];
        });
    }

    public function delete(Siswa $siswa): bool
    {
        if ($siswa->nilais()->exists()) {
            throw ValidationException::withMessages([
                'siswa' => 'Siswa tidak dapat dihapus karena masih memiliki data nilai.',
            ]);
        }

        return DB::transaction(function () use ($siswa): bool {
            $user = $siswa->user;
            $deleted = (bool) $siswa->delete();

            if ($deleted && $user?->isSiswa()) {
                $user->delete();
            }

            return $deleted;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensurePassword(array $data): void
    {
        if (filled($data['password'] ?? null)) {
            return;
        }

        throw ValidationException::withMessages([
            'password' => 'Kata sandi akun siswa wajib diisi.',
        ]);
    }

    private function normalizeNisn(mixed $nisn): string
    {
        $nisn = preg_replace('/\D+/', '', (string) $nisn) ?? '';

        if (! preg_match('/^\d{8,20}$/', $nisn)) {
            throw ValidationException::withMessages([
                'nisn' => 'NISN harus terdiri dari 8 sampai 20 digit angka.',
            ]);
        }

        return $nisn;
    }

    /**
     * @return array{username: string, email: string}
     */
    private function accountUntukImpor(string $nisn): array
    {
        $username = $nisn;
        $counter = 2;

        while (
            User::query()
                ->where('username', $username)
                ->exists()
        ) {
            $username = sprintf('siswa-%s-%d', $nisn, $counter);
            $counter++;
        }

        $email = sprintf('siswa.%s@login.raport.local', $nisn);
        $counter = 2;

        while (
            User::query()
                ->where('email', $email)
                ->exists()
        ) {
            $email = sprintf(
                'siswa.%s.%d@login.raport.local',
                $nisn,
                $counter,
            );

            $counter++;
        }

        return [
            'username' => $username,
            'email' => $email,
        ];
    }
}
