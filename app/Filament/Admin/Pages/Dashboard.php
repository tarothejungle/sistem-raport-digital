<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getHeading(): string
    {
        return match (auth()->user()?->role) {
            'admin' => 'Ringkasan Akademik',
            'guru' => 'Ruang Kerja Guru',
            'siswa' => 'Rapor Saya',
            default => 'Dashboard',
        };
    }

    public function getSubheading(): ?string
    {
        $user = auth()->user();

        return match ($user?->role) {
            'admin' => 'Pantau kesiapan nilai, kelas, dan rapor pada periode aktif.',
            'guru' => 'Lanjutkan input nilai dan pantau status kelas yang Anda ampu.',
            'siswa' => 'Lihat nilai final dan informasi akademik Anda.',
            default => 'Selamat datang di Sistem Rapor Digital.',
        };
    }
}
