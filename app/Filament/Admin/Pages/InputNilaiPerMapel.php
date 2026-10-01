<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\NilaiResource;
use App\Models\JadwalMengajar;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\KenaikanKelasService;
use App\Services\NilaiBatchService;
use App\Services\NilaiSpreadsheetService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

class InputNilaiPerMapel extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.admin.resources.InputNilaiPerMapel';

    protected static ?string $title = 'Input Nilai';

    protected static ?string $navigationLabel = 'Input Nilai';

    protected static ?string $slug = 'input-nilai/{jadwalMengajar}';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 3;

    public JadwalMengajar $jadwalMengajar;

    public static function getUrl(
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?Model $tenant = null,
        bool $shouldGuessMissingParameters = false,
        ?string $configuration = null,
    ): string {
        $parameters = (array) $parameters;

        $jadwal = $parameters['jadwalMengajar'] ?? null;

        if ($jadwal instanceof JadwalMengajar) {
            $slug = $jadwal->getSlug();
            $id = $jadwal->getKey();
            $parameters['jadwalMengajar'] = "{$slug}-{$id}";
        }

        return parent::getUrl(
            $parameters,
            $isAbsolute,
            $panel,
            $tenant,
            $shouldGuessMissingParameters,
            $configuration,
        );
    }

    public static function canAccess(): bool
    {
        return NilaiResource::canViewAny();
    }

    public function mount(JadwalMengajar $jadwalMengajar): void
    {
        abort_unless(static::canManageJadwal($jadwalMengajar), 403);

        $this->jadwalMengajar = $jadwalMengajar->loadMissing([
            'guru.user',
            'mataPelajaran',
            'kelas',
            'tahunAjaran',
        ]);
    }

    /**
     * @return array<int, NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        return [];
    }

    /**
     * @return Collection<int, JadwalMengajar>
     */
    public static function navigasiMataPelajaran(): Collection
    {
        return static::jadwalYangBolehDiakses()
            ->get()
            ->sortBy(static function (JadwalMengajar $jadwalMengajar): string {
                return sprintf(
                    '%s|%s|%s',
                    $jadwalMengajar->mataPelajaran?->kelompok ?? 'Z',
                    strtolower($jadwalMengajar->mataPelajaran?->nama_mapel ?? ''),
                    $jadwalMengajar->kelas?->nama_kelas ?? '',
                );
            })
            ->values();
    }

    public function getHeading(): string
    {
        return sprintf(
            '%s - %s',
            $this->jadwalMengajar->mataPelajaran?->nama_mapel ?? 'Mata Pelajaran',
            $this->jadwalMengajar->kelas?->nama_kelas ?? 'Kelas',
        );
    }

    public function getSubheading(): ?string
    {
        $kkm = app(NilaiBatchService::class)
            ->defaultKkm($this->jadwalMengajar);

        return sprintf(
            'Guru: %s | Tahun Ajaran: %s | KKM: %s',
            $this->jadwalMengajar->guru?->nama ?? '-',
            $this->jadwalMengajar->tahunAjaran?->label ?? '-',
            $kkm,
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'raport-mobile-full-search-table'])
            ->query(
                app(KenaikanKelasService::class)
                    ->siswaUntukKelasTahunQuery(
                        (int) $this->jadwalMengajar->kelas_id,
                        (int) $this->jadwalMengajar->tahun_ajaran_id,
                    )
                    ->with([
                        'nilais' => fn (HasMany $query) => $query
                            ->where(
                                'jadwal_mengajar_id',
                                $this->jadwalMengajar->getKey(),
                            ),
                        'catatanRapors' => fn (HasMany $query) => $query
                            ->where('tahun_ajaran_id', $this->jadwalMengajar->tahun_ajaran_id),
                    ]),
            )
            ->defaultSort('nama_lengkap')
            ->headerActions([
                Action::make('templateNilaiSiswa')
                    ->label('Template Nilai Siswa')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(route('admin.nilai.template', [
                        'jadwalMengajar' => $this->jadwalMengajar,
                    ]))
                    ->extraAttributes(['class' => 'raport-desktop-only-action']),

                Action::make('importNilaiSiswa')
                    ->label('Import Nilai Siswa')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->modalHeading('Import Nilai Siswa')
                    ->modalDescription('Gunakan template dari jadwal ini. Nilai, deskripsi, dan saran akan diperbarui berdasarkan NISN.')
                    ->modalSubmitActionLabel('Mulai Import')
                    ->schema([
                        FileUpload::make('file')
                            ->label('File Nilai Siswa')
                            ->required()
                            ->storeFiles(false)
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->maxSize(5120)
                            ->helperText('Gunakan file .xlsx dari tombol Template Nilai Siswa. Ukuran maksimal 5 MB.'),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $jumlahNilai = app(NilaiSpreadsheetService::class)
                                ->importUploadedFile(
                                    $this->jadwalMengajar,
                                    $data['file'] ?? null,
                                );
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->persistent()
                                ->title('Import nilai gagal')
                                ->body(collect($exception->errors())->flatten()->take(10)->implode(' '))
                                ->send();

                            return;
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->danger()
                                ->persistent()
                                ->title('Import nilai gagal')
                                ->body('File tidak dapat diproses. Pastikan file berasal dari Template Nilai Siswa pada jadwal ini.')
                                ->send();

                            return;
                        }

                        $this->resetTable();

                        Notification::make()
                            ->success()
                            ->title('Import nilai selesai')
                            ->body("{$jumlahNilai} nilai siswa berhasil diperbarui.")
                            ->send();
                    })
                    ->extraAttributes(['class' => 'raport-desktop-only-action']),

                Action::make('tambahNilai')
                    ->label('Tambah Nilai')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->modalHeading('Input Nilai Siswa')
                    ->modalDescription(
                        'Isi nilai seluruh siswa sekaligus. Nilai lama akan dimuat otomatis dan dapat diperbarui.'
                    )
                    ->modalWidth('7xl')
                    ->extraModalWindowAttributes([
                        'class' => 'raport-input-nilai-modal',
                    ])
                    ->modalSubmitActionLabel('Simpan Nilai')
                    ->modalCancelActionLabel('Batal')
                    ->extraAttributes(['class' => 'raport-desktop-only-action'])
                    ->fillForm(fn (): array => [
                        'kode_mapel' => $this->jadwalMengajar
                            ->mataPelajaran?->kode_mapel ?? '-',

                        'nama_mapel' => $this->jadwalMengajar
                            ->mataPelajaran?->nama_mapel ?? '-',

                        'kelas' => $this->jadwalMengajar
                            ->kelas?->nama_kelas ?? '-',

                        'kkm' => app(NilaiBatchService::class)
                            ->defaultKkm($this->jadwalMengajar),

                        'deskripsi' => $this->deskripsiLanjutanSaatIni(),

                        'nilai_siswa' => app(NilaiBatchService::class)
                            ->rowsForSchedule($this->jadwalMengajar),
                    ])
                    ->schema([
                        Section::make('Identitas Penilaian')
                            ->schema([
                                TextInput::make('kode_mapel')
                                    ->label('Kode Mata Pelajaran')
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('nama_mapel')
                                    ->label('Mata Pelajaran')
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('kelas')
                                    ->label('Kelas')
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                            ->columns(3),

                        Section::make('Pengaturan Nilai')
                            ->schema([
                                TextInput::make('kkm')
                                    ->label('KKTP')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required()
                                    ->helperText(
                                        'KKTP ini berlaku untuk guru, mapel, dan tahun ajaran yang sama.'
                                    ),

                                Textarea::make('deskripsi')
                                    ->label('Deskripsi')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->required()
                                    ->helperText('Deskripsi berlaku untuk mata pelajaran ini dan seluruh siswa.'),

                            ])
                            ->columns(2),

                        Section::make('Nilai Siswa')
                            ->description(
                                'Indeks ketercapaian dihitung otomatis berdasarkan nilai angka.'
                            )
                            ->schema([
                                Repeater::make('nilai_siswa')
                                    ->label('')
                                    ->itemLabel(static fn (array $state): ?string => filled($state['nama_lengkap'] ?? null)
                                        ? sprintf(
                                            '%s - %s',
                                            $state['nama_lengkap'],
                                            $state['nisn'] ?? '-'
                                        )
                                        : 'Siswa')
                                    ->schema([
                                        Hidden::make('siswa_id')
                                            ->required(),

                                        TextInput::make('nisn')
                                            ->label('NISN')
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('nama_lengkap')
                                            ->label('Nama Siswa')
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('nilai_angka')
                                            ->label('Nilai')
                                            ->numeric()
                                            ->integer()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->required()
                                            ->live(onBlur: true),

                                        Textarea::make('saran')
                                            ->label('Saran')
                                            ->rows(3)
                                            ->maxLength(1000)
                                            ->helperText('Saran berlaku untuk rapor siswa pada tahun ajaran ini.'),

                                        Placeholder::make(
                                            'indeks_preview',
                                        )
                                            ->label('Ketercapaian')
                                            ->content(
                                                fn (Get $get): string => static::indeksPreview(
                                                    $get('nilai_angka'),
                                                ),
                                            ),
                                    ])
                                    ->columns(3)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->columnSpanFull(),
                            ]),

                    ])
                    ->action(function (array $data): void {
                        $jumlahNilai = app(NilaiBatchService::class)->save(
                            $this->jadwalMengajar,
                            $data['kkm'] ?? null,
                            (string) ($data['deskripsi'] ?? ''),
                            is_array($data['nilai_siswa'] ?? null)
                                ? $data['nilai_siswa']
                                : [],
                        );

                        $this->resetTable();

                        Notification::make()
                            ->title('Nilai siswa berhasil disimpan.')
                            ->body(
                                sprintf(
                                    '%d nilai siswa telah diperbarui.',
                                    $jumlahNilai,
                                ),
                            )
                            ->success()
                            ->send();
                    }),
            ])
            ->columns([
                TextColumn::make('nisn')
                    ->label('NISN')
                    ->searchable(),

                TextColumn::make('nama_lengkap')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nilai')
                    ->label('Nilai')
                    ->badge()
                    ->state(fn (Siswa $record): string => (string) (
                        $this->nilaiSiswa($record)?->nilai_angka ?? 'Belum diisi'
                    ))
                    ->color(fn (Siswa $record): string => $this->nilaiSiswa($record) === null
                        ? 'gray'
                        : 'success'),

                TextColumn::make('indeks_ketercapaian')
                    ->label('Ketercapaian')
                    ->badge()
                    ->state(fn (Siswa $record): string => $this->nilaiSiswa(
                        $record,
                    )?->indeks_ketercapaian ?? 'Belum diisi')
                    ->color(fn (Siswa $record): string => static::indeksColor(
                        $this->nilaiSiswa($record)?->indeks_ketercapaian,
                    )),

                TextColumn::make('deskripsi_nilai')
                    ->label('Deskripsi')
                    ->state(fn (Siswa $record): string => $this->nilaiSiswa(
                        $record,
                    )?->deskripsi ?: 'Belum diisi')
                    ->wrap()
                    ->limit(80),

                TextColumn::make('saran_rapor')
                    ->label('Saran')
                    ->state(fn (Siswa $record): string => $record->catatanRapors->first()?->saran ?: 'Belum diisi')
                    ->wrap()
                    ->limit(80),
            ])
            ->emptyStateHeading('Belum ada siswa pada kelas ini.');
    }

    private function deskripsiLanjutanSaatIni(): ?string
    {
        $deskripsi = Nilai::query()
            ->where('jadwal_mengajar_id', $this->jadwalMengajar->getKey())
            ->orderBy('id')
            ->value('deskripsi');

        if (blank($deskripsi)) {
            return null;
        }

        return preg_replace(
            '/^Siswa memiliki keterampilan yang (?:SANGAT BAIK|BAIK|CUKUP|KURANG) dalam\s+/u',
            '',
            (string) $deskripsi,
        ) ?? (string) $deskripsi;
    }

    private static function indeksPreview(mixed $nilaiAngka): string
    {
        if (! is_numeric($nilaiAngka)) {
            return 'Belum diisi';
        }

        return match (true) {
            (int) $nilaiAngka >= 90 => 'Sangat Baik',
            (int) $nilaiAngka >= 80 => 'Baik',
            (int) $nilaiAngka >= 70 => 'Cukup',
            default => 'Kurang',
        };
    }

    private static function indeksColor(?string $indeks): string
    {
        return match ($indeks) {
            'Sangat Baik' => 'success',
            'Baik' => 'info',
            'Cukup' => 'warning',
            'Kurang' => 'danger',
            default => 'gray',
        };
    }

    private static function canManageJadwal(JadwalMengajar $jadwalMengajar): bool
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return true;
        }

        return $user?->isGuru()
            && $user->guru?->can_input_nilai === true
            && (int) $user->guru?->getKey() === (int) $jadwalMengajar->guru_id;
    }

    private static function jadwalYangBolehDiakses(): Builder
    {
        $query = JadwalMengajar::query()
            ->with([
                'guru.user',
                'mataPelajaran',
                'kelas',
                'tahunAjaran',
            ])
            ->orderBy('kelas_id')
            ->orderBy('mapel_id');

        $tahunAjaranAktifId = TahunAjaran::query()
            ->where('is_active', true)
            ->value('id');

        if ($tahunAjaranAktifId !== null) {
            $query->where('tahun_ajaran_id', $tahunAjaranAktifId);
        }

        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        // Mirror canManageJadwal(): a teacher whose input permission was
        // revoked must not see the schedule list either, otherwise the sidebar
        // advertises links that abort(403) on click.
        if (! $user?->isGuru() || $user->guru?->can_input_nilai !== true) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('guru_id', $user->guru?->getKey() ?? 0);
    }

    private function nilaiSiswa(Siswa $siswa): ?Nilai
    {
        return $siswa->nilais->first();
    }
}
