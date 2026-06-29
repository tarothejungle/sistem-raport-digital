<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Services\RaportPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class RaportPdfController extends Controller
{
    public function preview(
        Siswa $siswa,
        TahunAjaran $tahunAjaran,
        RaportPdfService $raportPdfService,
    ): Response {
        $this->authorizeCetakRapor($siswa);

        $fileName = $this->fileName($siswa, $tahunAjaran);

        $pdfContent = $this->makePdf(
            $siswa,
            $tahunAjaran,
            $raportPdfService,
        )->output();

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf(
                'inline; filename="%s"',
                $fileName,
            ),
        ]);
    }

    public function download(
        Siswa $siswa,
        TahunAjaran $tahunAjaran,
        RaportPdfService $raportPdfService,
    ): Response {
        $this->authorizeCetakRapor($siswa);

        return $this->makePdf($siswa, $tahunAjaran, $raportPdfService)
            ->download($this->fileName($siswa, $tahunAjaran));
    }

    private function authorizeCetakRapor(Siswa $siswa): void
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return;
        }

        $guru = $user?->guru;

        $adalahWaliKelas = $user?->isGuru()
            && $guru !== null
            && Kelas::query()
                ->whereKey($siswa->kelas_id)
                ->where('wali_kelas_id', $guru->getKey())
                ->exists();

        abort_unless($adalahWaliKelas, 403);
    }

    private function makePdf(
        Siswa $siswa,
        TahunAjaran $tahunAjaran,
        RaportPdfService $raportPdfService,
    ): \Barryvdh\DomPDF\PDF {
        return Pdf::loadView(
            'pdf.CetakRapor',
            $raportPdfService->build($siswa, $tahunAjaran),
        )
            ->setPaper('a4', 'portrait');
    }

    private function fileName(Siswa $siswa, TahunAjaran $tahunAjaran): string
    {
        return sprintf(
            'raport-%s-%s.pdf',
            Str::slug($siswa->nama_lengkap),
            Str::slug($tahunAjaran->label),
        );
    }
}