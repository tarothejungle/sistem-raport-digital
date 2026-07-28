<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\NilaiResource\Pages\CreateNilai;
use App\Filament\Admin\Resources\NilaiResource\Pages\EditNilai;
use App\Filament\Admin\Resources\NilaiResource\Pages\ListNilais;
use App\Models\JadwalMengajar;
use App\Models\Nilai;
use App\Services\KenaikanKelasService;
use App\Services\NilaiService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NilaiResource extends Resource
{
    protected static ?string $model = Nilai::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Nilai';

    protected static ?string $pluralModelLabel = 'Input Nilai';

    public static function canViewAny(): bool
    {
        return static::canManageAnyNilai();
    }

    public static function canCreate(): bool
    {
        return static::canManageAnyNilai();
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Nilai && static::canManageRecord($record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Nilai && static::canManageRecord($record);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'siswa.kelas',
            'jadwalMengajar.guru.user',
            'jadwalMengajar.mataPelajaran',
            'jadwalMengajar.kelas',
            'jadwalMengajar.tahunAjaran',
        ]);

        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        $guruId = $user?->guru?->getKey();

        if ($user?->isGuru() && $guruId !== null) {
            return $query->whereHas(
                'jadwalMengajar',
                static fn (Builder $jadwalQuery): Builder => $jadwalQuery->where('guru_id', $guruId),
            );
        }

        return $query->whereKey(0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Nilai')
                ->description('Mata pelajaran, guru, dan kelas diambil dari penugasan pengajar. KKM mengikuti pengaturan guru untuk mapel dan tahun ajaran terkait.')
                ->schema([
                    Select::make('jadwal_mengajar_id')
                        ->label('Pengajar Kelas')
                        ->options(static fn (): array => static::availableJadwalOptions())
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(static function (Set $set): void {
                            $set('siswa_id', null);
                        }),
                    Select::make('siswa_id')
                        ->label('Peserta Didik')
                        ->options(static function (Get $get): array {
                            $kelasId = JadwalMengajar::query()
                                ->whereKey($get('jadwal_mengajar_id'))
                                ->value('kelas_id');

                            if ($kelasId === null) {
                                return [];
                            }

                            $tahunAjaranId = JadwalMengajar::query()
                                ->whereKey($get('jadwal_mengajar_id'))
                                ->value('tahun_ajaran_id');

                            return app(KenaikanKelasService::class)
                                ->siswaUntukKelasTahunQuery(
                                    (int) $kelasId,
                                    $tahunAjaranId !== null ? (int) $tahunAjaranId : null,
                                )
                                ->orderBy('nama_lengkap')
                                ->pluck('nama_lengkap', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->required()
                        ->disabled(static fn (Get $get): bool => blank($get('jadwal_mengajar_id'))),
                    Placeholder::make('ringkasan_jadwal')
                        ->label('Informasi Rapor')
                        ->content(static function (Get $get): string {
                            $jadwal = JadwalMengajar::query()
                                ->with(['mataPelajaran', 'guru.user', 'kelas'])
                                ->find($get('jadwal_mengajar_id'));

                            if ($jadwal === null) {
                                return 'Pilih penugasan pengajar terlebih dahulu.';
                            }

                            return sprintf(
                                'Mapel: %s | KKM: %s | Guru: %s | Kelas: %s',
                                $jadwal->mataPelajaran?->nama_mapel ?? '-',
                                app(NilaiService::class)->kkmUntukJadwal($jadwal),
                                $jadwal->guru?->user?->name ?? '-',
                                $jadwal->kelas?->nama_kelas ?? '-',
                            );
                        })
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Nilai Rapor PTS')
                ->description('Predikat dihitung otomatis: A (90-100), B (80-89), C (KKM-79), D (di bawah KKM).')
                ->schema([
                    TextInput::make('nilai_angka')
                        ->label('Nilai Angka')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(100)
                        ->required()
                        ->live(),
                    Placeholder::make('predikat_preview')
                        ->label('Predikat (Otomatis)')
                        ->content(static function (Get $get): string {
                            $jadwal = JadwalMengajar::query()
                                ->with('mataPelajaran')
                                ->find($get('jadwal_mengajar_id'));
                            $nilaiAngka = $get('nilai_angka');

                            if ($jadwal === null || ! is_numeric($nilaiAngka)) {
                                return '-';
                            }

                            return app(NilaiService::class)->determinePredikat(
                                (int) $nilaiAngka,
                                app(NilaiService::class)->kkmUntukJadwal($jadwal),
                            );
                        }),
                    Textarea::make('deskripsi')
                        ->label('Deskripsi')
                        ->required()
                        ->rows(4)
                        ->maxLength(1000)
                        ->columnSpanFull()
                        ->helperText('Contoh: Siswa memiliki keterampilan yang SANGAT BAIK dalam memahami materi.'),
                    Toggle::make('is_submitted')
                        ->label('Finalisasi dan tampilkan di portal siswa')
                        ->default(true)
                        ->helperText('Hanya nilai yang difinalisasi yang dapat dilihat oleh siswa.')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('siswa.nisn')
                    ->label('NISN')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('siswa.nama_lengkap')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jadwalMengajar.mataPelajaran.nama_mapel')
                    ->label('Mata Pelajaran')
                    ->searchable(),
                TextColumn::make('jadwalMengajar.guru.user.name')
                    ->label('Guru')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('jadwalMengajar.kelas.nama_kelas')
                    ->label('Kelas')
                    ->badge(),
                TextColumn::make('kkm')
                    ->label('KKM')
                    ->sortable(),
                TextColumn::make('nilai_angka')
                    ->label('Angka')
                    ->badge()
                    ->sortable(),
                TextColumn::make('predikat')
                    ->label('Predikat')
                    ->badge()
                    ->color(static fn (?string $state): string => match ($state) {
                        'A' => 'success',
                        'B' => 'info',
                        'C' => 'warning',
                        'D' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),
                IconColumn::make('is_submitted')
                    ->label('Final')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('jadwal_mengajar_id')
                    ->label('Pengajar Kelas')
                    ->options(static fn (): array => static::availableJadwalOptions())
                    ->searchable(),
                SelectFilter::make('predikat')
                    ->options([
                        'A' => 'A',
                        'B' => 'B',
                        'C' => 'C',
                        'D' => 'D',
                    ]),
                TernaryFilter::make('is_submitted')->label('Finalisasi'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNilais::route('/'),
            'create' => CreateNilai::route('/create'),
            'edit' => EditNilai::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function availableJadwalOptions(): array
    {
        return static::availableJadwalQuery()
            ->get()
            ->mapWithKeys(static fn (JadwalMengajar $jadwal): array => [
                $jadwal->getKey() => $jadwal->label,
            ])
            ->all();
    }

    private static function availableJadwalQuery(): Builder
    {
        $query = JadwalMengajar::query()
            ->with(['guru.user', 'mataPelajaran', 'kelas', 'tahunAjaran'])
            ->orderBy('tahun_ajaran_id', 'desc')
            ->orderBy('kelas_id')
            ->orderBy('mapel_id');

        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        $guruId = $user?->guru?->getKey();

        if ($user?->isGuru() && $guruId !== null) {
            return $query->where('guru_id', $guruId);
        }

        return $query->whereKey(0);
    }

    private static function canManageAnyNilai(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin()
            || ($user?->isGuru() && $user->guru?->can_input_nilai === true);
    }

    private static function canManageRecord(Nilai $nilai): bool
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return true;
        }

        $guruId = $user?->guru?->getKey();

        return $user?->isGuru()
            && $user->guru?->can_input_nilai === true
            && $guruId !== null
            && $nilai->jadwalMengajar()->where('guru_id', $guruId)->exists();
    }
}
