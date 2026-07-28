<?php

namespace App\Filament\Admin\Resources\JadwalMengajarResource\Pages;

use App\Filament\Admin\Resources\JadwalMengajarResource;
use App\Models\Guru;
use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\TahunAjaran;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateJadwalMengajar extends CreateRecord
{
    protected static string $resource = JadwalMengajarResource::class;

    protected static ?string $title = 'Atur Pengajar Kelas';

    protected static ?string $breadcrumb = 'Atur Pengajar';

    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'penugasan' => self::assignmentRows(null, null),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kelas dan Tahun Ajaran')
                ->description('Pilih kelas dan tahun ajaran untuk melihat atau memperbarui pengajar.')
                ->schema([
                    Select::make('kelas_id')
                        ->label('Kelas')
                        ->options(
                            Kelas::query()
                                ->orderBy('nama_kelas')
                                ->pluck('nama_kelas', 'id')
                                ->all(),
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required()
                        ->afterStateUpdated(static function (Get $get, Set $set): void {
                            $set('penugasan', self::assignmentRows(
                                $get('kelas_id'),
                                $get('tahun_ajaran_id'),
                            ));
                        }),

                    Select::make('tahun_ajaran_id')
                        ->label('Tahun Ajaran')
                        ->options(
                            TahunAjaran::query()
                                ->orderByDesc('is_active')
                                ->orderByDesc('nama')
                                ->get()
                                ->mapWithKeys(static fn (TahunAjaran $tahunAjaran): array => [
                                    $tahunAjaran->getKey() => $tahunAjaran->label,
                                ])
                                ->all(),
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required()
                        ->afterStateUpdated(static function (Get $get, Set $set): void {
                            $set('penugasan', self::assignmentRows(
                                $get('kelas_id'),
                                $get('tahun_ajaran_id'),
                            ));
                        }),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Mata Pelajaran dan Guru Pengampu')
                ->description('Ganti guru yang diperlukan, lalu simpan seluruh penugasan kelas.')
                ->schema([
                    Repeater::make('penugasan')
                        ->label('')
                        ->schema([
                            Hidden::make('mapel_id')
                                ->required(),

                            TextInput::make('nama_mapel')
                                ->label('Mata Pelajaran')
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('guru_id')
                                ->label('Guru Pengampu')
                                ->options(
                                    Guru::query()
                                        ->with('user')
                                        ->get()
                                        ->sortBy(
                                            static fn (Guru $guru): string => strtolower($guru->nama),
                                        )
                                        ->mapWithKeys(static fn (Guru $guru): array => [
                                            $guru->getKey() => sprintf(
                                                '%s (@%s)',
                                                $guru->nama,
                                                $guru->user?->username ?? '-',
                                            ),
                                        ])
                                        ->all(),
                                )
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->columns(2)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var array<int, array<string, mixed>> $penugasan */
        $penugasan = $data['penugasan'] ?? [];

        $penugasanAktif = collect($penugasan)
            ->filter(static fn (array $item): bool => filled($item['mapel_id'] ?? null)
                && filled($item['guru_id'] ?? null))
            ->values();

        if ($penugasanAktif->isEmpty()) {
            throw ValidationException::withMessages([
                'data.penugasan' => 'Pilih minimal satu guru pengampu.',
            ]);
        }

        /** @var JadwalMengajar $jadwalPertama */
        $jadwalPertama = DB::transaction(function () use ($data, $penugasanAktif): JadwalMengajar {
            return $penugasanAktif
                ->map(function (array $item) use ($data): JadwalMengajar {
                    return JadwalMengajar::query()->updateOrCreate(
                        [
                            'mapel_id' => $item['mapel_id'],
                            'kelas_id' => $data['kelas_id'],
                            'tahun_ajaran_id' => $data['tahun_ajaran_id'],
                        ],
                        [
                            'guru_id' => $item['guru_id'],
                        ],
                    );
                })
                ->first();
        });

        return $jadwalPertama;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan Pengajar Kelas')
                ->icon('heroicon-o-check')
                ->color('primary'),

            $this->getCancelFormAction()
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pengajar kelas berhasil disimpan.';
    }

    /**
     * @return array<int, array{mapel_id: int, nama_mapel: string, guru_id: int|null}>
     */
    private static function assignmentRows(mixed $kelasId, mixed $tahunAjaranId): array
    {
        $guruByMapel = filled($kelasId) && filled($tahunAjaranId)
            ? JadwalMengajar::query()
                ->where('kelas_id', $kelasId)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->pluck('guru_id', 'mapel_id')
            : collect();

        return MataPelajaran::query()
            ->orderBy('kelompok')
            ->orderBy('nama_mapel')
            ->get()
            ->map(static fn (MataPelajaran $mataPelajaran): array => [
                'mapel_id' => $mataPelajaran->getKey(),
                'nama_mapel' => $mataPelajaran->nama_mapel,
                'guru_id' => $guruByMapel->get($mataPelajaran->getKey()),
            ])
            ->all();
    }
}
