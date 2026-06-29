<?php

namespace App\Filament\Admin\Resources\NilaiResource\Pages;

use App\Filament\Admin\Resources\NilaiResource;
use App\Services\NilaiService;
use Filament\Resources\Pages\CreateRecord;

class CreateNilai extends CreateRecord
{
    protected static string $resource = NilaiResource::class;

    public function mount(): void
    {
        parent::mount();

        $jadwalMengajarId = request()->query('jadwal_mengajar_id');
        $siswaId = request()->query('siswa_id');

        if (blank($jadwalMengajarId) || blank($siswaId)) {
            return;
        }

        $this->form->fill([
            'jadwal_mengajar_id' => (int) $jadwalMengajarId,
            'siswa_id' => (int) $siswaId,
            'is_submitted' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return app(NilaiService::class)->prepareForPersistence($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Nilai siswa berhasil disimpan.';
    }
}