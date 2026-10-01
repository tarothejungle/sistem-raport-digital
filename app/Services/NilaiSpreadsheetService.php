<?php

namespace App\Services;

use App\Models\JadwalMengajar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\XLSX\Options as ReaderOptions;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

final class NilaiSpreadsheetService
{
    private const HEADER_ROW = 9;

    private const MAX_XLSX_ENTRIES = 256;

    private const MAX_XLSX_UNCOMPRESSED_BYTES = 20_000_000;

    public function template(JadwalMengajar $jadwalMengajar): BinaryFileResponse
    {
        app(NilaiService::class)->ensureActorCanManageSchedule($jadwalMengajar);
        $jadwalMengajar->loadMissing(['mataPelajaran', 'kelas', 'tahunAjaran']);

        $nilaiRows = collect(app(NilaiBatchService::class)->rowsForSchedule($jadwalMengajar));
        $directory = storage_path('app/temp');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $fileName = sprintf(
            'template-nilai-%s-%s.xlsx',
            Str::slug($jadwalMengajar->mataPelajaran?->nama_mapel ?? 'mapel'),
            Str::slug($jadwalMengajar->kelas?->nama_kelas ?? 'kelas'),
        );
        $path = $directory.DIRECTORY_SEPARATOR.Str::uuid().'-'.$fileName;
        $options = new Options;
        $options->mergeCells(0, 1, 4, 1);
        $options->mergeCells(0, 2, 4, 2);
        $options->mergeCells(0, 3, 4, 3);
        $options->mergeCells(0, 4, 4, 4);
        $options->mergeCells(1, 6, 3, 6);
        $options->mergeCells(1, 7, 3, 7);
        $options->setColumnWidth(18, 1);
        $options->setColumnWidth(32, 2);
        $options->setColumnWidth(12, 3);
        $options->setColumnWidth(42, 4);

        $writer = new Writer($options);
        $writer->openToFile($path);

        try {
            $sheet = $writer->getCurrentSheet();
            $sheet->setName('Nilai Siswa');
            $sheet->setSheetView(
                (new SheetView)
                    ->setShowGridLines(false)
                    ->setFreezeRow(self::HEADER_ROW + 1)
                    ->setFreezeColumn('C'),
            );

            $titleStyle = $this->titleStyle();
            $metaStyle = $this->metaStyle();
            $headerStyle = $this->headerStyle();
            $bodyStyle = $this->bodyStyle();
            $nameStyle = $this->bodyStyle()->setCellAlignment(CellAlignment::LEFT);

            $writer->addRows([
                Row::fromValues(['TEMPLATE NILAI SISWA'], $titleStyle)->setHeight(26),
                $this->safeRow([
                    strtoupper($jadwalMengajar->mataPelajaran?->nama_mapel ?? 'MATA PELAJARAN'),
                ], $titleStyle, [0])->setHeight(22),
                $this->safeRow([
                    $this->kelasLabel($jadwalMengajar),
                ], $metaStyle, [0])->setHeight(20),
                $this->safeRow([
                    'TAHUN AJARAN '.strtoupper($jadwalMengajar->tahunAjaran?->label ?? '-'),
                ], $metaStyle, [0])->setHeight(20),
                Row::fromValues([])->setHeight(8),
                Row::fromValues([
                    'KKM', app(NilaiBatchService::class)->defaultKkm($jadwalMengajar),
                ], $metaStyle)->setHeight(22),
                $this->safeRow([
                    'Deskripsi', $this->deskripsiLanjutan($jadwalMengajar),
                ], $metaStyle, [0, 1])->setHeight(34),
                Row::fromValues([])->setHeight(8),
                Row::fromValues([
                    'NISN', 'Nama Siswa', 'Nilai', 'Saran',
                ], $headerStyle)->setHeight(24),
            ]);

            foreach ($nilaiRows as $nilaiRow) {
                $writer->addRow($this->safeRow([
                    $nilaiRow['nisn'],
                    $nilaiRow['nama_lengkap'],
                    $nilaiRow['nilai_angka'],
                    $nilaiRow['saran'],
                ], $bodyStyle, [0, 1, 3], [1 => $nameStyle])->setHeight(34));
            }
        } finally {
            $writer->close();
        }

        return response()
            ->download($path, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function importUploadedFile(JadwalMengajar $jadwalMengajar, mixed $uploadedFile): int
    {
        if (is_array($uploadedFile)) {
            $uploadedFile = reset($uploadedFile) ?: null;
        }

        if (! $uploadedFile instanceof UploadedFile || ! $uploadedFile->isValid()) {
            throw ValidationException::withMessages([
                'file' => 'File upload tidak valid. Pilih ulang file template Excel.',
            ]);
        }

        if (strtolower($uploadedFile->getClientOriginalExtension()) !== 'xlsx') {
            throw ValidationException::withMessages([
                'file' => 'Gunakan file Excel berformat .xlsx.',
            ]);
        }

        $path = $uploadedFile->getRealPath() ?: $uploadedFile->getPathname();
        $this->validateArchive($path);
        $rows = $this->readRows($path);

        return $this->importRows($jadwalMengajar, $rows);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function importRows(JadwalMengajar $jadwalMengajar, array $rows): int
    {
        app(NilaiService::class)->ensureActorCanManageSchedule($jadwalMengajar);
        $jadwalMengajar->loadMissing(['mataPelajaran', 'kelas', 'tahunAjaran']);

        if (count($rows) < self::HEADER_ROW + 1) {
            throw ValidationException::withMessages([
                'file' => 'Struktur template tidak lengkap.',
            ]);
        }

        $header = array_map(static fn (mixed $value): string => trim((string) $value), $rows[self::HEADER_ROW - 1]);

        while ($header !== [] && end($header) === '') {
            array_pop($header);
        }

        if ($header !== ['NISN', 'Nama Siswa', 'Nilai', 'Saran']) {
            throw ValidationException::withMessages([
                'file' => 'Header file berubah. Unduh ulang Template Nilai Siswa untuk jadwal ini.',
            ]);
        }

        $expectedMetadata = [
            strtoupper($jadwalMengajar->mataPelajaran?->nama_mapel ?? 'MATA PELAJARAN'),
            $this->kelasLabel($jadwalMengajar),
            'TAHUN AJARAN '.strtoupper($jadwalMengajar->tahunAjaran?->label ?? '-'),
        ];
        $actualMetadata = [
            trim((string) ($rows[1][0] ?? '')),
            trim((string) ($rows[2][0] ?? '')),
            trim((string) ($rows[3][0] ?? '')),
        ];

        if ($actualMetadata !== $expectedMetadata) {
            throw ValidationException::withMessages([
                'file' => 'Template bukan milik mata pelajaran, kelas, atau tahun ajaran pada halaman ini.',
            ]);
        }

        $kkm = $rows[5][1] ?? null;
        $deskripsi = trim((string) ($rows[6][1] ?? ''));
        $siswaByNisn = collect(app(NilaiBatchService::class)->rowsForSchedule($jadwalMengajar))
            ->keyBy(static fn (array $row): string => (string) $row['nisn']);
        $nilaiSiswa = [];
        $seen = [];
        $errors = [];

        foreach (array_slice($rows, self::HEADER_ROW) as $offset => $row) {
            $line = self::HEADER_ROW + $offset + 1;
            $nisn = preg_replace('/\D+/', '', (string) ($row[0] ?? '')) ?? '';
            $nama = trim((string) ($row[1] ?? ''));
            $nilai = $row[2] ?? null;
            $saran = trim((string) ($row[3] ?? ''));

            if ($nisn === '' && $nama === '' && blank($nilai) && $saran === '') {
                continue;
            }

            $siswa = $siswaByNisn->get($nisn);

            if ($siswa === null) {
                $errors[] = "Baris {$line}: NISN {$nisn} bukan siswa pada kelas dan tahun ajaran ini.";

                continue;
            }

            if (isset($seen[$nisn])) {
                $errors[] = "Baris {$line}: NISN {$nisn} muncul lebih dari satu kali.";

                continue;
            }

            if ($nama !== (string) $siswa['nama_lengkap']) {
                $errors[] = "Baris {$line}: nama siswa untuk NISN {$nisn} tidak sesuai template.";

                continue;
            }

            $seen[$nisn] = true;
            $nilaiSiswa[] = [
                'siswa_id' => $siswa['siswa_id'],
                'nilai_angka' => $nilai,
                'saran' => $saran,
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'file' => array_slice($errors, 0, 10),
            ]);
        }

        if (count($nilaiSiswa) !== $siswaByNisn->count()) {
            throw ValidationException::withMessages([
                'file' => 'Jumlah siswa pada file tidak sesuai template. Jangan menambah atau menghapus baris siswa.',
            ]);
        }

        return app(NilaiBatchService::class)->save(
            $jadwalMengajar,
            $kkm,
            $deskripsi,
            $nilaiSiswa,
        );
    }

    private function deskripsiLanjutan(JadwalMengajar $jadwalMengajar): string
    {
        $deskripsi = $jadwalMengajar->nilais()->orderBy('id')->value('deskripsi');

        if (blank($deskripsi)) {
            return '';
        }

        return preg_replace(
            '/^Siswa memiliki keterampilan yang (?:SANGAT BAIK|BAIK|CUKUP|KURANG) dalam\s+/u',
            '',
            (string) $deskripsi,
        ) ?? (string) $deskripsi;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readRows(string $path): array
    {
        $options = new ReaderOptions;
        $options->SHOULD_PRESERVE_EMPTY_ROWS = true;
        $reader = new Reader($options);
        $reader->open($path);
        $rows = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = $row->toArray();

                    if (count($rows) > 1000) {
                        throw ValidationException::withMessages([
                            'file' => 'File nilai maksimal berisi 1.000 baris.',
                        ]);
                    }
                }

                break;
            }
        } finally {
            $reader->close();
        }

        return $rows;
    }

    private function validateArchive(string $path): void
    {
        $archive = new ZipArchive;

        if ($archive->open($path) !== true) {
            throw ValidationException::withMessages([
                'file' => 'File Excel tidak dapat dibuka. Unduh ulang template lalu coba kembali.',
            ]);
        }

        try {
            if ($archive->numFiles > self::MAX_XLSX_ENTRIES) {
                throw ValidationException::withMessages([
                    'file' => 'File Excel memiliki terlalu banyak bagian internal.',
                ]);
            }

            $totalSize = 0;

            for ($index = 0; $index < $archive->numFiles; $index++) {
                $stat = $archive->statIndex($index);
                $size = is_array($stat) ? (int) ($stat['size'] ?? 0) : 0;
                $compressedSize = is_array($stat) ? (int) ($stat['comp_size'] ?? 0) : 0;
                $totalSize += $size;

                if (
                    $size > self::MAX_XLSX_UNCOMPRESSED_BYTES
                    || $totalSize > self::MAX_XLSX_UNCOMPRESSED_BYTES
                    || ($compressedSize > 0 && $size / $compressedSize > 100)
                ) {
                    throw ValidationException::withMessages([
                        'file' => 'File Excel terlalu besar setelah diekstrak.',
                    ]);
                }
            }
        } finally {
            $archive->close();
        }
    }

    private function kelasLabel(JadwalMengajar $jadwalMengajar): string
    {
        $namaKelas = trim((string) $jadwalMengajar->kelas?->nama_kelas);

        return Str::startsWith(Str::upper($namaKelas), 'KELAS ')
            ? Str::upper($namaKelas)
            : 'KELAS '.Str::upper($namaKelas);
    }

    /**
     * @param  array<int, mixed>  $values
     * @param  array<int, int>  $textColumns
     * @param  array<int, Style>  $columnStyles
     */
    private function safeRow(array $values, ?Style $rowStyle, array $textColumns, array $columnStyles = []): Row
    {
        $cells = [];

        foreach ($values as $index => $value) {
            $style = $columnStyles[$index] ?? null;
            $cells[] = in_array($index, $textColumns, true)
                ? new StringCell((string) $value, $style)
                : Cell::fromValue($value, $style);
        }

        return new Row($cells, $rowStyle);
    }

    private function titleStyle(): Style
    {
        return (new Style)
            ->setFontName('Arial')
            ->setFontSize(14)
            ->setFontBold()
            ->setFontColor('0F172A')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    private function metaStyle(): Style
    {
        return (new Style)
            ->setFontName('Arial')
            ->setFontSize(10)
            ->setFontBold()
            ->setFontColor('334155')
            ->setBackgroundColor('EFF6FF')
            ->setCellAlignment(CellAlignment::LEFT)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setShouldWrapText();
    }

    private function headerStyle(): Style
    {
        return $this->tableStyle('0F3D5E', Color::WHITE)->setFontBold();
    }

    private function bodyStyle(): Style
    {
        return $this->tableStyle(Color::WHITE, '334155');
    }

    private function tableStyle(string $background, string $fontColor): Style
    {
        $border = new Border(
            new BorderPart(Border::LEFT, '94A3B8', Border::WIDTH_THIN),
            new BorderPart(Border::RIGHT, '94A3B8', Border::WIDTH_THIN),
            new BorderPart(Border::TOP, '94A3B8', Border::WIDTH_THIN),
            new BorderPart(Border::BOTTOM, '94A3B8', Border::WIDTH_THIN),
        );

        return (new Style)
            ->setFontName('Arial')
            ->setFontSize(10)
            ->setFontColor($fontColor)
            ->setBackgroundColor($background)
            ->setBorder($border)
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }
}
