<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\KenaikanKelasService;
use App\Services\RaportPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class RaportPdfController extends Controller
{
    public function preview(
        Siswa $siswa,
        TahunAjaran $tahunAjaran,
        RaportPdfService $raportPdfService,
    ): Response {
        $this->authorizeCetakRapor($siswa, $tahunAjaran);

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
        $this->authorizeCetakRapor($siswa, $tahunAjaran);

        return $this->makePdf($siswa, $tahunAjaran, $raportPdfService)
            ->download($this->fileName($siswa, $tahunAjaran));
    }

    public function bulkDownload(
        Request $request,
        TahunAjaran $tahunAjaran,
        RaportPdfService $raportPdfService,
    ): BinaryFileResponse {
        abort_unless(class_exists(ZipArchive::class), 500, 'Ekstensi ZIP PHP belum aktif.');

        $rawIds = (string) $request->query('siswa');
        abort_if(strlen($rawIds) > 2000, 422, 'Daftar siswa terlalu panjang.');

        $ids = collect(explode(',', $rawIds))
            ->map(static fn (string $id): int => (int) trim($id))
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        abort_if($ids->isEmpty(), 404);
        abort_if($ids->count() > 30, 422, 'Maksimal 30 rapor per unduhan.');

        $siswas = Siswa::query()
            ->whereKey($ids)
            ->get()
            ->sortBy(static fn (Siswa $siswa): int => $ids->search($siswa->getKey()))
            ->values();

        abort_if($siswas->isEmpty(), 404);

        $zipDirectory = storage_path('app/temp');

        if (! is_dir($zipDirectory)) {
            mkdir($zipDirectory, 0755, true);
        }

        $zipName = sprintf('rapor-%s-%s.zip', Str::slug($tahunAjaran->label), now()->format('YmdHis'));
        $zipPath = $zipDirectory.DIRECTORY_SEPARATOR.$zipName;
        $zip = new ZipArchive;

        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'File ZIP gagal dibuat.');

        try {
            foreach ($siswas as $siswa) {
                $this->authorizeCetakRapor($siswa, $tahunAjaran);

                $zip->addFromString(
                    $this->fileName($siswa, $tahunAjaran),
                    $this->makePdf($siswa, $tahunAjaran, $raportPdfService)->output(),
                );
            }
        } catch (\Throwable $exception) {
            $zip->close();
            @unlink($zipPath);

            throw $exception;
        }

        $zip->close();

        return response()
            ->download($zipPath, $zipName)
            ->deleteFileAfterSend(true);
    }

    private function authorizeCetakRapor(Siswa $siswa, TahunAjaran $tahunAjaran): void
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return;
        }

        $guru = $user?->guru;
        $kelas = app(KenaikanKelasService::class)->kelasUntukTahunAjaran(
            $siswa,
            $tahunAjaran->getKey(),
        ) ?? $siswa->kelas;

        $adalahWaliKelas = $user?->isGuru()
            && $guru !== null
            && $kelas !== null
            && Kelas::query()
                ->whereKey($kelas->getKey())
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
