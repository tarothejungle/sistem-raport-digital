<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class AbsensiGuruSyncService
{
    public function __construct(
        private readonly AccountIdentityService $accountIdentityService,
    ) {}

    /**
     * @return array{created: int, updated: int}
     */
    public function sync(): array
    {
        $gurus = $this->fetchGurus();

        return DB::transaction(function () use ($gurus): array {
            $created = 0;
            $updated = 0;

            foreach ($gurus as $guru) {
                if ($this->upsertGuru($guru)) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            return ['created' => $created, 'updated' => $updated];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchGurus(): array
    {
        $url = rtrim((string) config('services.absensi.url', ''), '/');
        $apiKey = (string) config('services.absensi.api_key', '');

        if ($url === '' || $apiKey === '') {
            throw new RuntimeException('Konfigurasi integrasi Absensi belum lengkap.');
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-API-KEY' => $apiKey,
            ])
                ->connectTimeout((int) config('services.absensi.connect_timeout', 5))
                ->timeout((int) config('services.absensi.timeout', 30))
                ->get($url);
        } catch (ConnectionException $exception) {
            report($exception);

            throw new RuntimeException('Tidak dapat terhubung ke server Absensi.');
        }

        $payload = $response->json();

        if (! $response->successful()) {
            $detail = is_array($payload)
                ? trim((string) ($payload['error_detail'] ?? ''))
                : '';

            throw new RuntimeException($detail !== '' ? $detail : 'Server Absensi menolak permintaan data guru.');
        }

        if (! is_array($payload)
            || ($payload['status'] ?? '') !== 'success'
            || ! is_array($payload['data'] ?? null)
        ) {
            $detail = is_array($payload)
                ? trim((string) ($payload['error_detail'] ?? ''))
                : '';

            throw new RuntimeException($detail !== '' ? $detail : 'Format respon dari server Absensi tidak valid.');
        }

        return array_values($payload['data']);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function upsertGuru(array $item): bool
    {
        try {
            $username = $this->accountIdentityService->normalizeUsername(
                $item['username'] ?? null,
            );

            $email = $this->accountIdentityService->normalizeEmail(
                $item['email'] ?? null,
            );
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();

            throw new RuntimeException('Data guru tidak valid: '.(string) $message);
        }

        $name = trim((string) ($item['nama'] ?? ''));
        $phone = trim((string) ($item['no_telepon'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('Data guru dari server Absensi tidak lengkap.');
        }

        $user = User::query()
            ->where(function ($query) use ($username, $email): void {
                $query->where('username', $username)->orWhere('email', $email);
            })
            ->first();

        if ($user === null) {
            $user = User::query()->create([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => Str::password(16),
                'role' => User::ROLE_GURU,
            ]);

            $user->guru()->create([
                'no_telp' => $phone !== '' ? $phone : null,
            ]);

            return true;
        }

        $user->update([
            'name' => $name,
            'email' => $email,
        ]);

        $user->guru()->updateOrCreate([], [
            'no_telp' => $phone !== '' ? $phone : null,
        ]);

        return false;
    }
}
