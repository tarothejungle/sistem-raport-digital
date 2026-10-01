<?php

namespace App\Filament\Admin\Pages;

use App\Models\Kelas;
use App\Models\RiwayatKelasSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\KenaikanKelasService;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class KenaikanKelas extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'filament.admin.pages.kenaikan-kelas';

    protected static ?string $slug = 'kenaikan-kelas';

    protected static ?string $title = 'Kenaikan Kelas';

    protected static ?string $navigationLabel = 'Kenaikan Kelas';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 2;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $activeTahunAjaran = TahunAjaran::query()
            ->where('is_active', true)
            ->first();

        $sourceTahunAjaran = TahunAjaran::query()
            ->when(
                $activeTahunAjaran !== null,
                static fn (Builder $query): Builder => $query->whereKeyNot($activeTahunAjaran->getKey()),
            )
            ->orderByDesc('nama')
            ->orderByDesc('semester')
            ->first();

        $this->form->fill([
            'tahun_ajaran_asal_id' => $sourceTahunAjaran?->getKey(),
            'tahun_ajaran_tujuan_id' => $activeTahunAjaran?->getKey(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Periode Kenaikan')
                    ->description('Pilih tahun ajaran asal dan tujuan. Siswa kelas 1-5 dinaikkan ke tingkat berikutnya, sedangkan kelas 6 diluluskan ke Data Alumni.')
                    ->schema([
                        Select::make('tahun_ajaran_asal_id')
                            ->label('Tahun Ajaran Asal')
                            ->options(fn (): array => $this->tahunAjaranOptions())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->placeholder('Pilih tahun ajaran asal'),

                        Select::make('tahun_ajaran_tujuan_id')
                            ->label('Tahun Ajaran Tujuan')
                            ->options(fn (): array => $this->tahunAjaranOptions())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->different('tahun_ajaran_asal_id')
                            ->placeholder('Pilih tahun ajaran tujuan'),

                        Placeholder::make('ringkasan')
                            ->label('Ringkasan Alur')
                            ->content(fn (): string => $this->ringkasanAlur())
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function updatedDataTahunAjaranAsalId(): void
    {
        $this->resetTable();
    }

    public function updatedDataTahunAjaranTujuanId(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'raport-mobile-full-search-table'])
            ->query(fn (): Builder => $this->siswaQuery())
            ->defaultSort('kelas_id')
            ->columns([
                TextColumn::make('nisn')
                    ->label('NISN')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('nama_lengkap')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kelas_asal')
                    ->label('Kelas Asal')
                    ->state(fn (Siswa $record): string => $this->kelasAsal($record)?->nama_kelas ?? '-')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('arah_kenaikan')
                    ->label('Arah Proses')
                    ->state(fn (Siswa $record): string => $this->arahProses($record))
                    ->badge()
                    ->color(fn (Siswa $record): string => $this->warnaArahProses($record)),

                TextColumn::make('status_proses')
                    ->label('Status')
                    ->state(fn (Siswa $record): string => $this->statusProses($record))
                    ->badge()
                    ->color(fn (Siswa $record): string => $this->warnaStatusProses($record)),
            ])
            ->filters([
                SelectFilter::make('kelas_id')
                    ->label('Kelas Aktif')
                    ->relationship('kelas', 'nama_kelas')
                    ->searchable()
                    ->preload(),
            ])
            ->toolbarActions([
                BulkAction::make('promoteSelected')
                    ->label('Naikkan Terpilih')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Naikkan siswa terpilih?')
                    ->modalDescription('Siswa kelas 1-5 akan dibuatkan riwayat pada tahun ajaran tujuan dan kelas aktifnya dipindahkan ke tingkat berikutnya.')
                    ->modalSubmitActionLabel('Ya, naikkan')
                    ->action(function ($records): void {
                        $tahunAjaranAsalId = $this->tahunAjaranAsalId();
                        $tahunAjaranTujuanId = $this->tahunAjaranTujuanId();

                        if ($tahunAjaranAsalId === null || $tahunAjaranTujuanId === null) {
                            $this->notifyPeriodMissing();

                            return;
                        }

                        try {
                            $summary = app(KenaikanKelasService::class)->promote(
                                $records,
                                $tahunAjaranAsalId,
                                $tahunAjaranTujuanId,
                            );
                        } catch (ValidationException $exception) {
                            $this->notifyValidationError('Kenaikan kelas gagal', $exception);

                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title('Kenaikan kelas selesai')
                            ->body("{$summary['promoted']} siswa berhasil dinaikkan ke {$summary['target_year']}.")
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('graduateSelected')
                    ->label('Luluskan Terpilih')
                    ->icon('heroicon-o-academic-cap')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Luluskan siswa kelas 6?')
                    ->modalDescription('Siswa kelas 6 terpilih akan dipindahkan ke menu Data Alumni. Data nilai dan rapor tetap tersimpan.')
                    ->modalSubmitActionLabel('Ya, luluskan')
                    ->schema([
                        DatePicker::make('tanggal_lulus')
                            ->label('Tanggal Lulus')
                            ->default(now())
                            ->required(),
                        Textarea::make('keterangan_alumni')
                            ->label('Keterangan')
                            ->rows(3)
                            ->maxLength(255)
                            ->placeholder('Opsional, misalnya: Lulus reguler.'),
                    ])
                    ->action(function ($records, array $data): void {
                        $tahunAjaranAsalId = $this->tahunAjaranAsalId();

                        if ($tahunAjaranAsalId === null) {
                            $this->notifyPeriodMissing();

                            return;
                        }

                        try {
                            $summary = app(KenaikanKelasService::class)->graduate(
                                $records,
                                $tahunAjaranAsalId,
                                $data,
                            );
                        } catch (ValidationException $exception) {
                            $this->notifyValidationError('Kelulusan gagal', $exception);

                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title('Siswa berhasil diluluskan')
                            ->body("{$summary['graduated']} siswa dipindahkan ke Data Alumni dari {$summary['source_year']}.")
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('Belum ada siswa aktif')
            ->emptyStateDescription('Pastikan data siswa sudah tersedia dan pilih periode kenaikan kelas terlebih dahulu.');
    }

    private function siswaQuery(): Builder
    {
        if ($this->tahunAjaranAsalId() === null) {
            return Siswa::query()->whereKey(0);
        }

        return Siswa::query()
            ->aktif()
            ->with([
                'kelas',
                'riwayatKelasSiswas' => fn ($query) => $query
                    ->where('tahun_ajaran_id', $this->tahunAjaranAsalId())
                    ->with('kelas'),
            ]);
    }

    private function kelasAsal(Siswa $siswa): ?Kelas
    {
        return app(KenaikanKelasService::class)->kelasUntukTahunAjaran(
            $siswa,
            $this->tahunAjaranAsalId(),
        );
    }

    private function arahProses(Siswa $siswa): string
    {
        $kelasAsal = $this->kelasAsal($siswa);

        if ($kelasAsal === null) {
            return 'Kelas asal belum ada';
        }

        if ($kelasAsal->tingkat >= 6) {
            return 'Lulus ke Alumni';
        }

        $kelasTujuan = app(KenaikanKelasService::class)->targetKelasUntuk($kelasAsal);

        return $kelasTujuan !== null
            ? "Naik ke {$kelasTujuan->nama_kelas}"
            : 'Target belum tersedia';
    }

    private function warnaArahProses(Siswa $siswa): string
    {
        $kelasAsal = $this->kelasAsal($siswa);

        if ($kelasAsal === null) {
            return 'danger';
        }

        if ($kelasAsal->tingkat >= 6) {
            return 'warning';
        }

        return app(KenaikanKelasService::class)->targetKelasUntuk($kelasAsal) !== null
            ? 'success'
            : 'danger';
    }

    private function statusProses(Siswa $siswa): string
    {
        $targetId = $this->tahunAjaranTujuanId();

        if (
            $targetId !== null
            && RiwayatKelasSiswa::query()
                ->where('siswa_id', $siswa->getKey())
                ->where('tahun_ajaran_id', $targetId)
                ->exists()
        ) {
            return 'Sudah di tahun tujuan';
        }

        $kelasAsal = $this->kelasAsal($siswa);

        if ($kelasAsal?->tingkat >= 6) {
            return 'Siap diluluskan';
        }

        return 'Siap diproses';
    }

    private function warnaStatusProses(Siswa $siswa): string
    {
        $targetId = $this->tahunAjaranTujuanId();

        if (
            $targetId !== null
            && RiwayatKelasSiswa::query()
                ->where('siswa_id', $siswa->getKey())
                ->where('tahun_ajaran_id', $targetId)
                ->exists()
        ) {
            return 'info';
        }

        return $this->kelasAsal($siswa)?->tingkat >= 6
            ? 'warning'
            : 'success';
    }

    /**
     * @return array<int, string>
     */
    private function tahunAjaranOptions(): array
    {
        return TahunAjaran::query()
            ->orderByDesc('nama')
            ->orderByDesc('semester')
            ->get()
            ->mapWithKeys(static fn (TahunAjaran $tahunAjaran): array => [
                $tahunAjaran->getKey() => $tahunAjaran->label,
            ])
            ->all();
    }

    private function ringkasanAlur(): string
    {
        $asal = TahunAjaran::query()->find($this->tahunAjaranAsalId());
        $tujuan = TahunAjaran::query()->find($this->tahunAjaranTujuanId());

        if ($asal === null || $tujuan === null) {
            return 'Pilih tahun ajaran asal dan tujuan terlebih dahulu.';
        }

        return "Dari {$asal->label} menuju {$tujuan->label}. Kelas 1-5 dinaikkan, kelas 6 diluluskan ke alumni.";
    }

    private function tahunAjaranAsalId(): ?int
    {
        $id = $this->data['tahun_ajaran_asal_id'] ?? null;

        return filled($id) ? (int) $id : null;
    }

    private function tahunAjaranTujuanId(): ?int
    {
        $id = $this->data['tahun_ajaran_tujuan_id'] ?? null;

        return filled($id) ? (int) $id : null;
    }

    private function notifyValidationError(string $title, ValidationException $exception): void
    {
        Notification::make()
            ->danger()
            ->persistent()
            ->title($title)
            ->body(collect($exception->errors())->flatten()->take(8)->implode(' '))
            ->send();
    }

    private function notifyPeriodMissing(): void
    {
        Notification::make()
            ->warning()
            ->title('Periode belum lengkap')
            ->body('Pilih tahun ajaran asal dan tujuan terlebih dahulu.')
            ->send();
    }
}
