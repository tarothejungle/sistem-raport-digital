<?php

namespace App\Filament\Admin\Pages;

use App\Models\CatatanRapor;
use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CetakRaporSiswa extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $view = 'filament.admin.resources.CetakRaporSiswa';

    protected static ?string $slug = 'cetak-rapor-siswa';

    protected static ?string $title = 'Cetak Rapor Siswa';

    protected static ?string $navigationLabel = 'Cetak Rapor Siswa';

    protected static ?string $navigationIcon = 'heroicon-o-printer';

    protected static ?string $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 4;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return true;
        }

        return $user?->isGuru()
            && $user->guru !== null
            && Kelas::query()
                ->where('wali_kelas_id', $user->guru->getKey())
                ->exists();
    }

    public function mount(): void
    {
        $this->form->fill([
            'tahun_ajaran_id' => TahunAjaran::query()
                ->where('is_active', true)
                ->value('id'),
        ]);
    }

    public function form(Form $form): Form
    {
        $tahunAjaranOptions = TahunAjaran::query()
            ->orderByDesc('is_active')
            ->orderByDesc('nama')
            ->get()
            ->mapWithKeys(static fn (TahunAjaran $tahunAjaran): array => [
                $tahunAjaran->getKey() => $tahunAjaran->label,
            ])
            ->all();

        $kelasOptions = $this->kelasYangBolehDicetakQuery()
            ->pluck('nama_kelas', 'id')
            ->all();

        $form
            ->schema([
                Forms\Components\Section::make('Pilih Periode Rapor')
                    ->description(
                        'Pilih tahun ajaran dan kelas untuk menampilkan siswa yang akan dicetak raportnya.'
                    )
                    ->schema([
                        Forms\Components\Select::make('tahun_ajaran_id')
                            ->label('Tahun Ajaran')
                            ->options($tahunAjaranOptions)
                            // ->searchable()
                            ->preload()
                            ->native(true)
                            ->live()
                            ->required()
                            ->placeholder('Pilih Tahun Ajaran'),

                        Forms\Components\Select::make('kelas_id')
                            ->label('Kelas')
                            ->options($kelasOptions)
                            // ->searchable()
                            ->preload()
                            ->native(true)
                            ->optionsLimit(100)
                            ->live()
                            ->required()
                            ->placeholder('Pilih kelas'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');

        return $form;
    }

    public function updatedDataTahunAjaranId(): void
    {
        $this->resetTable();
    }

    public function updatedDataKelasId(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->siswaQuery())
            ->defaultSort('nama_lengkap')
            ->columns([
                Tables\Columns\TextColumn::make('nisn')
                    ->label('NISN')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nilai_final_count')
                    ->label('Nilai Final')
                    ->state(function (Siswa $record): string {
                        $jumlahMapel = $this->jumlahMapel();

                        if ($jumlahMapel === 0) {
                            return '-';
                        }

                        return sprintf(
                            '%d / %d',
                            $record->nilai_final_count,
                            $jumlahMapel,
                        );
                    })
                    ->badge()
                    ->color(function (Siswa $record): string {
                        return $this->raporSiapDicetak($record)
                            ? 'success'
                            : 'warning';
                    }),

                Tables\Columns\TextColumn::make('status_rapor')
                    ->label('Status Rapor')
                    ->state(function (Siswa $record): string {
                        if ($this->jumlahMapel() === 0) {
                            return 'Belum ada pengajar kelas';
                        }

                        return $this->raporSiapDicetak($record)
                            ? 'Siap dicetak'
                            : 'Nilai belum lengkap';
                    })
                    ->badge()
                    ->color(function (Siswa $record): string {
                        return $this->raporSiapDicetak($record)
                            ? 'success'
                            : 'warning';
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('pratinjauRapor')
                    ->label('Pratinjau PDF')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (Siswa $record): bool => $this->raporSiapDicetak($record))
                    ->url(fn (Siswa $record): string => route('admin.rapor.preview', [
                        'siswa' => $record->getKey(),
                        'tahunAjaran' => $this->tahunAjaranId(),
                    ]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('unduhRapor')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Siswa $record): bool => $this->raporSiapDicetak($record))
                    ->url(fn (Siswa $record): string => route('admin.rapor.download', [
                        'siswa' => $record->getKey(),
                        'tahunAjaran' => $this->tahunAjaranId(),
                    ])),    

                Tables\Actions\Action::make('isiSaranRapor')
                    ->label('Isi Saran')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->modalHeading(fn (Siswa $record): string => 'Saran Rapor: '.$record->nama_lengkap)
                    ->form([
                        Forms\Components\Textarea::make('saran')
                            ->label('Saran-Saran')
                            ->rows(5)
                            ->maxLength(1000)
                            ->helperText('Saran ini hanya berlaku untuk siswa dan tahun ajaran yang dipilih.'),
                    ])
                    ->fillForm(function (Siswa $record): array {
                        return [
                            'saran' => CatatanRapor::query()
                                ->where('siswa_id', $record->getKey())
                                ->where('tahun_ajaran_id', $this->tahunAjaranId())
                                ->value('saran'),
                        ];
                    })
                    ->action(function (Siswa $record, array $data): void {
                        CatatanRapor::query()->updateOrCreate(
                            [
                                'siswa_id' => $record->getKey(),
                                'tahun_ajaran_id' => $this->tahunAjaranId(),
                            ],
                            [
                                'saran' => $data['saran'] ?? null,
                            ],
                        );

                        Notification::make()
                            ->title('Saran rapor berhasil disimpan.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading(function (): string {
                return $this->filtersSudahDipilih()
                    ? 'Belum ada siswa pada kelas ini.'
                    : 'Pilih tahun ajaran dan kelas terlebih dahulu.';
            });
    }

    private function siswaQuery(): Builder
    {
        $kelasId = $this->kelasId();
        $tahunAjaranId = $this->tahunAjaranId();

        if (
            $kelasId === null
            || $tahunAjaranId === null
            || ! $this->bolehCetakKelas($kelasId)
        ) {
            return Siswa::query()->whereKey(0);
        }

        return Siswa::query()
            ->where('kelas_id', $kelasId)
            ->withCount([
                'nilais as nilai_final_count' => function (Builder $nilaiQuery) use ($kelasId, $tahunAjaranId): void {
                    $nilaiQuery
                        ->where('is_submitted', true)
                        ->whereHas(
                            'jadwalMengajar',
                            function (Builder $jadwalQuery) use ($kelasId, $tahunAjaranId): void {
                                $jadwalQuery
                                    ->where('kelas_id', $kelasId)
                                    ->where('tahun_ajaran_id', $tahunAjaranId);
                            },
                        );
                },
            ]);
    }

    private function jumlahMapel(): int
    {
        $kelasId = $this->kelasId();
        $tahunAjaranId = $this->tahunAjaranId();

        if (
            $kelasId === null
            || $tahunAjaranId === null
            || ! $this->bolehCetakKelas($kelasId)
        ) {
            return 0;
        }

        return JadwalMengajar::query()
            ->where('kelas_id', $kelasId)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->count();
    }

    private function raporSiapDicetak(Siswa $siswa): bool
    {
        $jumlahMapel = $this->jumlahMapel();

        return $jumlahMapel > 0
            && (int) $siswa->nilai_final_count >= $jumlahMapel;
    }

    private function kelasYangBolehDicetakQuery(): Builder
    {
        $query = Kelas::query()
            ->orderBy('tingkat')
            ->orderBy('nama_kelas');

        if (auth()->user()?->isAdmin()) {
            return $query;
        }

        return $query->where(
            'wali_kelas_id',
            auth()->user()?->guru?->getKey() ?? 0,
        );
    }

    private function bolehCetakKelas(?int $kelasId): bool
    {
        if ($kelasId === null) {
            return false;
        }

        $user = auth()->user();

        if ($user?->isAdmin()) {
            return true;
        }

        return $user?->isGuru()
            && $user->guru !== null
            && Kelas::query()
                ->whereKey($kelasId)
                ->where('wali_kelas_id', $user->guru->getKey())
                ->exists();
    }

    private function tahunAjaranId(): ?int
    {
        $tahunAjaranId = $this->data['tahun_ajaran_id'] ?? null;

        return filled($tahunAjaranId)
            ? (int) $tahunAjaranId
            : null;
    }

    private function kelasId(): ?int
    {
        $kelasId = $this->data['kelas_id'] ?? null;

        return filled($kelasId)
            ? (int) $kelasId
            : null;
    }

    private function filtersSudahDipilih(): bool
    {
        return $this->tahunAjaranId() !== null
            && $this->kelasId() !== null;
    }
}