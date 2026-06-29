<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Guru;
use App\Models\JadwalMengajar;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RaportOverview extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
     * @return int|array<string, int>
     */
    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $tahunAjaranAktif = TahunAjaran::query()
            ->where('is_active', true)
            ->first();

        $jumlahTahunAjaranAktif = TahunAjaran::query()
            ->where('is_active', true)
            ->count();

        return [
            Stat::make('Siswa Terdaftar', Siswa::query()->count())
                ->description('Seluruh siswa yang tercatat')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->icon('heroicon-o-academic-cap')
                ->color('primary'),

            Stat::make('Guru Terdaftar', Guru::query()->count())
                ->description('Akun guru yang tersedia')
                ->descriptionIcon('heroicon-m-user-group')
                ->icon('heroicon-o-user-group')
                ->color('info'),

            Stat::make('Kelas Terdaftar', Kelas::query()->count())
                ->description('Kelas yang tersedia')
                ->descriptionIcon('heroicon-m-building-library')
                ->icon('heroicon-o-building-library')
                ->color('info'),

            Stat::make('Jadwal Mengajar', JadwalMengajar::query()->count())
                ->description('Penugasan pengajar kelas')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->icon('heroicon-o-calendar-days')
                ->color('warning'),

            Stat::make(
                'Nilai Final',
                Nilai::query()
                    ->where('is_submitted', true)
                    ->count(),
            )
                ->description('Nilai yang sudah difinalisasi')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('success'),

            Stat::make('Tahun Ajaran Aktif', $jumlahTahunAjaranAktif)
                ->description(
                    $tahunAjaranAktif?->label
                        ?? 'Belum ada tahun ajaran aktif',
                )
                ->descriptionIcon(
                    $tahunAjaranAktif !== null
                        ? 'heroicon-m-check-circle'
                        : 'heroicon-m-exclamation-triangle',
                )
                ->icon('heroicon-o-calendar')
                ->color(
                    $tahunAjaranAktif !== null
                        ? 'success'
                        : 'danger',
                ),
        ];
    }
}