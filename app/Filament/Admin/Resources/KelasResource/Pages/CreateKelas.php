<?php

namespace App\Filament\Admin\Resources\KelasResource\Pages;

use App\Filament\Admin\Resources\KelasResource;
use App\Models\Guru;
use App\Services\KelasService;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateKelas extends CreateRecord
{
    protected static string $resource = KelasResource::class;

    protected static ?string $title = 'Tambah Semua Kelas';

    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'kelas' => [
                ['nama_kelas' => null, 'tingkat' => null, 'wali_kelas_id' => null],
            ],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Daftar Kelas')
                ->description('Tambahkan seluruh kelas sekaligus. Setiap baris menyimpan nama kelas, tingkat, dan wali kelas.')
                ->schema([
                    Repeater::make('kelas')
                        ->label('')
                        ->schema([
                            TextInput::make('nama_kelas')
                                ->label('Nama Kelas')
                                ->required()
                                ->maxLength(100),
                            TextInput::make('tingkat')
                                ->label('Tingkat')
                                ->numeric()
                                ->integer()
                                ->minValue(1)
                                ->maxValue(12)
                                ->required(),
                            Select::make('wali_kelas_id')
                                ->label('Wali Kelas')
                                ->options(static fn (): array => Guru::query()
                                    ->with('user')
                                    ->get()
                                    ->sortBy(static fn (Guru $guru): string => strtolower($guru->nama))
                                    ->mapWithKeys(static fn (Guru $guru): array => [
                                        $guru->getKey() => sprintf(
                                            '%s (@%s)',
                                            $guru->nama,
                                            $guru->user?->username ?? '-',
                                        ),
                                    ])
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->placeholder('Belum ditentukan'),
                        ])
                        ->columns(3)
                        ->addActionLabel('Tambah Baris Kelas')
                        ->reorderable(false)
                        ->minItems(1)
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
        /** @var array<int, array<string, mixed>> $rows */
        $rows = $data['kelas'] ?? [];
        $created = app(KelasService::class)->createMany($rows);

        return $created[0];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Semua data kelas berhasil disimpan';
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Semua Kelas')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->submit('create'),
            Action::make('kembali')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}
