<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Services\LegerPtsExportService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LegerPtsExportController extends Controller
{
    public function xlsx(
        Kelas $kelas,
        TahunAjaran $tahunAjaran,
        LegerPtsExportService $exportService,
    ): BinaryFileResponse {
        $this->authorizeKelas($kelas, $tahunAjaran);

        return $exportService->xlsx($kelas, $tahunAjaran);
    }

    public function pdf(
        Kelas $kelas,
        TahunAjaran $tahunAjaran,
        LegerPtsExportService $exportService,
    ): Response {
        $this->authorizeKelas($kelas, $tahunAjaran);

        return $exportService->pdf($kelas, $tahunAjaran);
    }

    private function authorizeKelas(Kelas $kelas, TahunAjaran $tahunAjaran): void
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return;
        }

        abort_unless(
            $user?->isGuru()
            && $tahunAjaran->is_active
            && $user->guru !== null
            && (int) $kelas->wali_kelas_id === (int) $user->guru->getKey(),
            403,
        );
    }
}
