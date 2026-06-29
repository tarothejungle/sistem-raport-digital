<?php

namespace App\Services;

use App\Models\JadwalMengajar;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

final class StudentAccessService
{
    public function canManage(Siswa $siswa, ?User $actor = null): bool
    {
        $actor ??= auth()->user();

        if (! $actor instanceof User) {
            return false;
        }

        if ($actor->isAdmin()) {
            return true;
        }

        if (! $actor->isGuru() || $actor->guru === null || ! $actor->guru->can_input_nilai) {
            return false;
        }

        return JadwalMengajar::query()
            ->where('guru_id', $actor->guru->getKey())
            ->where('kelas_id', $siswa->kelas_id)
            ->exists();
    }

    public function setViewingAccess(Siswa $siswa, bool $enabled, ?User $actor = null): void
    {
        if (! $this->canManage($siswa, $actor)) {
            throw new AuthorizationException('Anda tidak memiliki akses untuk mengubah hak lihat nilai siswa ini.');
        }

        if ($enabled && $siswa->user_id === null) {
            throw ValidationException::withMessages([
                'siswa' => 'Akun siswa belum dibuat oleh admin sehingga akses lihat nilai belum dapat diaktifkan.',
            ]);
        }

        $siswa->update([
            'can_view_nilai' => $enabled,
        ]);
    }
}
