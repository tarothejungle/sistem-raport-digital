<?php

namespace App\Filament\Admin\Pages;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\AbsensiSiswaService;
use App\Services\KenaikanKelasService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbsenSiswa extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'filament.admin.pages.absen-siswa';

    protected static ?string $slug = 'absen-siswa';

    protected static ?string $title = 'Absen Siswa';

    protected static ?string $navigationLabel = 'Absen Siswa';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 4;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin()
            || ($user?->isGuru() && $user->guru?->can_input_nilai === true);
    }

    public function mount(): void
    {
        $this->form->fill([
            'tahun_ajaran_id' => TahunAjaran::query()->where('is_active', true)->value('id'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pilih Kelas')
                    ->description('Absensi disimpan satu kali per kelas dan otomatis dipakai pada seluruh mata pelajaran.')
                    ->schema([
                        Select::make('tahun_ajaran_id')
                            ->label('Tahun Ajaran')
                            ->options(TahunAjaran::query()
                                ->when(! auth()->user()?->isAdmin(), static fn (Builder $query): Builder => $query->where('is_active', true))
                                ->orderByDesc('is_active')
                                ->orderByDesc('nama')
                                ->get()
                                ->mapWithKeys(static fn (TahunAjaran $tahun): array => [$tahun->getKey() => $tahun->label]))
                            ->native(true)
                            ->live()
                            ->required(),
                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->options(fn (): array => $this->kelasQuery()->pluck('nama_kelas', 'id')->all())
                            ->native(true)
                            ->live()
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function updatedDataTahunAjaranId(): void
    {
        $this->data['kelas_id'] = null;
        $this->resetTable();
    }

    public function updatedDataKelasId(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'raport-mobile-full-search-table raport-absen-siswa-table'])
            ->query(fn (): Builder => $this->siswaQuery())
            ->defaultSort('nama_lengkap')
            ->toolbarActions([
                Action::make('isiAbsensi')
                    ->label('Isi Absen Siswa')
                    ->icon('heroicon-o-pencil-square')
                    ->extraAttributes([
                        'id' => 'raport-isi-absensi-action',
                        'class' => 'raport-desktop-only-action',
                        'x-on:click' => '$store.sidebar.close()',
                    ])
                    ->disabled(fn (): bool => $this->kelas() === null || $this->tahunAjaran() === null)
                    ->modalHeading('Absen Siswa per Kelas')
                    ->modalDescription('Simpan sekali. Data yang sama akan tampil pada seluruh mata pelajaran dan rapor.')
                    ->modalWidth('7xl')
                    ->extraModalWindowAttributes([
                        'class' => 'raport-absen-siswa-modal',
                    ])
                    ->modalSubmitActionLabel('Simpan Absensi')
                    ->fillForm(function (): array {
                        $kelas = $this->kelas();
                        $tahun = $this->tahunAjaran();

                        return [
                            'absensi_siswa' => $kelas !== null && $tahun !== null
                                ? app(AbsensiSiswaService::class)->rows($kelas, $tahun)
                                : [],
                        ];
                    })
                    ->schema([
                        Repeater::make('absensi_siswa')
                            ->label('')
                            ->itemLabel(static fn (array $state): string => sprintf(
                                '%s - %s',
                                $state['nama_lengkap'] ?? 'Siswa',
                                $state['nisn'] ?? '-',
                            ))
                            ->schema([
                                Hidden::make('siswa_id')->required(),
                                TextInput::make('nama_lengkap')->label('Nama Siswa')->disabled()->dehydrated(false),
                                TextInput::make('sakit')->label('Sakit')->numeric()->integer()->minValue(0)->maxValue(365)->required(),
                                TextInput::make('izin')->label('Izin')->numeric()->integer()->minValue(0)->maxValue(365)->required(),
                                TextInput::make('alpa')->label('Alpa')->numeric()->integer()->minValue(0)->maxValue(365)->required(),
                            ])
                            ->columns(4)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ])
                    ->action(function (array $data): void {
                        $kelas = $this->kelas();
                        $tahun = $this->tahunAjaran();

                        abort_unless($kelas !== null && $tahun !== null, 422);

                        $jumlah = app(AbsensiSiswaService::class)->save(
                            $kelas,
                            $tahun,
                            is_array($data['absensi_siswa'] ?? null) ? $data['absensi_siswa'] : [],
                        );

                        $this->resetTable();

                        Notification::make()
                            ->success()
                            ->title('Absensi siswa berhasil disimpan.')
                            ->body("{$jumlah} data siswa telah diperbarui.")
                            ->send();
                    }),
            ])
            ->columns([
                TextColumn::make('nisn')->label('NISN')->searchable(),
                TextColumn::make('nama_lengkap')->label('Nama Siswa')->searchable()->sortable(),
                TextColumn::make('sakit')->label('Sakit')->state(fn (Siswa $record): int => $record->absensiSiswas->first()?->sakit ?? 0)->badge(),
                TextColumn::make('izin')->label('Izin')->state(fn (Siswa $record): int => $record->absensiSiswas->first()?->izin ?? 0)->badge(),
                TextColumn::make('alpa')->label('Alpa')->state(fn (Siswa $record): int => $record->absensiSiswas->first()?->alpa ?? 0)->badge(),
            ])
            ->emptyStateHeading($this->kelasId() === null ? 'Pilih kelas terlebih dahulu.' : 'Belum ada siswa pada kelas ini.');
    }

    private function siswaQuery(): Builder
    {
        $kelas = $this->kelas();
        $tahun = $this->tahunAjaran();

        if ($kelas === null || $tahun === null) {
            return Siswa::query()->whereKey(0);
        }

        try {
            app(AbsensiSiswaService::class)->ensureActorCanManageClass($kelas, $tahun);
        } catch (\Throwable) {
            return Siswa::query()->whereKey(0);
        }

        return app(KenaikanKelasService::class)
            ->siswaUntukKelasTahunQuery($kelas->getKey(), $tahun->getKey())
            ->with(['absensiSiswas' => fn (HasMany $query): HasMany => $query->where('tahun_ajaran_id', $tahun->getKey())]);
    }

    private function kelasQuery(): Builder
    {
        $query = Kelas::query()->orderBy('tingkat')->orderBy('nama_kelas');
        $tahunId = $this->tahunAjaranId();

        if (auth()->user()?->isAdmin()) {
            return $query;
        }

        return $query->whereHas('jadwalMengajars', fn (Builder $jadwal): Builder => $jadwal
            ->where('guru_id', auth()->user()?->guru?->getKey() ?? 0)
            ->when($tahunId !== null, fn (Builder $builder): Builder => $builder->where('tahun_ajaran_id', $tahunId)));
    }

    private function kelas(): ?Kelas
    {
        return $this->kelasId() === null ? null : Kelas::query()->find($this->kelasId());
    }

    private function tahunAjaran(): ?TahunAjaran
    {
        return $this->tahunAjaranId() === null ? null : TahunAjaran::query()->find($this->tahunAjaranId());
    }

    private function kelasId(): ?int
    {
        return filled($this->data['kelas_id'] ?? null) ? (int) $this->data['kelas_id'] : null;
    }

    private function tahunAjaranId(): ?int
    {
        return filled($this->data['tahun_ajaran_id'] ?? null) ? (int) $this->data['tahun_ajaran_id'] : null;
    }
}
