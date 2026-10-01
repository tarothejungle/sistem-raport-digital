<?php

namespace App\Filament\Admin\Pages;

use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Services\LegerPtsService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class LegerNilaiPts extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.admin.pages.leger-nilai-pts';

    protected static ?string $slug = 'leger-nilai-pts';

    protected static ?string $title = 'Leger Nilai PTS';

    protected static ?string $navigationLabel = 'Leger Nilai PTS';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 5;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin()
            || ($user?->isGuru()
                && $user->guru !== null
                && Kelas::query()
                    ->where('wali_kelas_id', $user->guru->getKey())
                    ->exists());
    }

    public function mount(): void
    {
        $this->form->fill([
            'tahun_ajaran_id' => TahunAjaran::query()
                ->where('is_active', true)
                ->value('id'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pilih Leger PTS')
                    ->description('Pilih tahun ajaran dan kelas untuk menampilkan rekap nilai PTS berbasis proyek.')
                    ->schema([
                        Select::make('tahun_ajaran_id')
                            ->label('Tahun Ajaran')
                            ->options(TahunAjaran::query()
                                ->when(! auth()->user()?->isAdmin(), static fn (Builder $query): Builder => $query->where('is_active', true))
                                ->orderByDesc('is_active')
                                ->orderByDesc('nama')
                                ->get()
                                ->mapWithKeys(static fn (TahunAjaran $tahun): array => [
                                    $tahun->getKey() => $tahun->label,
                                ]))
                            ->native(true)
                            ->live()
                            ->required()
                            ->placeholder('Pilih tahun ajaran'),
                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->options($this->kelasYangBolehDilihatQuery()
                                ->pluck('nama_kelas', 'id'))
                            ->native(true)
                            ->live()
                            ->required()
                            ->placeholder('Pilih kelas'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    /** @return array<string, mixed>|null */
    public function legerData(): ?array
    {
        $kelasId = $this->kelasId();
        $tahunAjaranId = $this->tahunAjaranId();

        if (
            $kelasId === null
            || $tahunAjaranId === null
            || ! $this->bolehLihatKelas($kelasId, $tahunAjaranId)
        ) {
            return null;
        }

        return app(LegerPtsService::class)->build($kelasId, $tahunAjaranId);
    }

    public function exportUrl(string $format): ?string
    {
        $kelasId = $this->kelasId();
        $tahunAjaranId = $this->tahunAjaranId();

        if (
            ! in_array($format, ['xlsx', 'pdf'], true)
            || $kelasId === null
            || $tahunAjaranId === null
            || ! $this->bolehLihatKelas($kelasId, $tahunAjaranId)
        ) {
            return null;
        }

        return route("admin.leger-pts.{$format}", [
            'kelas' => $kelasId,
            'tahunAjaran' => $tahunAjaranId,
        ]);
    }

    private function kelasYangBolehDilihatQuery(): Builder
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

    private function bolehLihatKelas(int $kelasId, ?int $tahunAjaranId = null): bool
    {
        $user = auth()->user();

        return $user?->isAdmin()
            || ($user?->isGuru()
                && ($tahunAjaranId === null || TahunAjaran::query()
                    ->whereKey($tahunAjaranId)
                    ->where('is_active', true)
                    ->exists())
                && $user->guru !== null
                && Kelas::query()
                    ->whereKey($kelasId)
                    ->where('wali_kelas_id', $user->guru->getKey())
                    ->exists());
    }

    private function tahunAjaranId(): ?int
    {
        return filled($this->data['tahun_ajaran_id'] ?? null)
            ? (int) $this->data['tahun_ajaran_id']
            : null;
    }

    private function kelasId(): ?int
    {
        return filled($this->data['kelas_id'] ?? null)
            ? (int) $this->data['kelas_id']
            : null;
    }
}
