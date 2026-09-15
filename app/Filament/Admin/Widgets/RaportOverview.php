<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Guru;
use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RaportOverview extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

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
            Stat::make('Siswa Terdaftar', Siswa::query()->aktif()->count())
                ->description('Siswa aktif yang tercatat')
                ->icon('heroicon-o-academic-cap')
                ->extraAttributes(['class' => 'raport-kpi-card'])
                ->color('primary'),

            Stat::make('Guru Terdaftar', Guru::query()->count())
                ->description('Akun guru yang tersedia')
                ->icon('heroicon-o-user-group')
                ->extraAttributes(['class' => 'raport-kpi-card'])
                ->color('info'),

            Stat::make('Kelas Terdaftar', Kelas::query()->count())
                ->description('Kelas yang tersedia')
                ->icon('heroicon-o-building-library')
                ->extraAttributes(['class' => 'raport-kpi-card'])
                ->color('info'),

            Stat::make('Jadwal Mengajar', JadwalMengajar::query()->count())
                ->description('Penugasan pengajar kelas')
                ->icon('heroicon-o-calendar-days')
                ->extraAttributes(['class' => 'raport-kpi-card'])
                ->color('warning'),

            Stat::make(
                'Nilai Final',
                Nilai::query()
                    ->where('is_submitted', true)
                    ->count(),
            )
                ->description('Nilai yang sudah difinalisasi')
                ->icon('heroicon-o-clipboard-document-check')
                ->extraAttributes(['class' => 'raport-kpi-card'])
                ->color('success'),

            Stat::make('Tahun Ajaran Aktif', $jumlahTahunAjaranAktif)
                ->description(
                    $tahunAjaranAktif?->label
                        ?? 'Belum ada tahun ajaran aktif',
                )
                ->icon('heroicon-o-calendar')
                ->extraAttributes(['class' => 'raport-kpi-card'])
                ->color(
                    $tahunAjaranAktif !== null
                        ? 'success'
                        : 'danger',
                ),
        ];
    }
}
