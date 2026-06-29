<?php

namespace App\Services;

use App\Models\Kelas;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;
use ZipArchive;

final class SiswaImportService
{
    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function import(string $absolutePath, ?string $extension = null): array
    {
        if (! is_file($absolutePath)) {
            throw ValidationException::withMessages([
                'file' => 'File impor tidak ditemukan atau sudah tidak tersedia.',
            ]);
        }

        $extension = strtolower($extension ?: pathinfo($absolutePath, PATHINFO_EXTENSION));

        $rows = match ($extension) {
            'xlsx' => $this->readXlsx($absolutePath),
            'csv' => $this->readCsv($absolutePath),
            default => throw ValidationException::withMessages([
                'file' => 'Gunakan file Excel berformat .xlsx atau file .csv.',
            ]),
        };

        $records = $this->prepareRecords($rows);

        return DB::transaction(function () use ($records): array {
            $summary = [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
            ];

            $siswaService = app(SiswaService::class);

            foreach ($records as $record) {
                $result = $siswaService->upsertFromImport(
                    $record['nisn'],
                    $record['nama_lengkap'],
                    $record['kelas_id'],
                );

                $summary[$result['status']]++;
            }

            return $summary;
        });
    }

    /**
     * Membaca file yang masih berada di temporary upload Livewire.
     * File tidak perlu disimpan ke storage permanen sebelum diimpor.
     *
     * @return array{created: int, updated: int, skipped: int}
     */
    public function importUploadedFile(mixed $uploadedFile): array
    {
        if (is_array($uploadedFile)) {
            $uploadedFile = reset($uploadedFile) ?: null;
        }

        if (! $uploadedFile instanceof UploadedFile || ! $uploadedFile->isValid()) {
            throw ValidationException::withMessages([
                'file' => 'File upload tidak valid. Pilih ulang file Excel atau CSV lalu coba kembali.',
            ]);
        }

        $absolutePath = $uploadedFile->getRealPath() ?: $uploadedFile->getPathname();
        $extension = strtolower($uploadedFile->getClientOriginalExtension());

        return $this->import($absolutePath, $extension);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array{nisn: string, nama_lengkap: string, kelas_id: int}>
     */
    private function prepareRecords(array $rows): array
    {
        $rows = array_values(array_filter(
            $rows,
            static fn (array $row): bool => collect($row)
                ->contains(static fn (string $value): bool => trim($value) !== ''),
        ));

        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'file' => 'File harus memiliki baris judul dan minimal satu data siswa.',
            ]);
        }

        $headerMap = $this->headerMap($rows[0]);
        $requiredHeaders = [
            'nisn' => 'NISN',
            'nama_lengkap' => 'Nama Lengkap',
            'kelas' => 'Kelas',
        ];

        $missingHeaders = collect($requiredHeaders)
            ->filter(static fn (string $label, string $key): bool => ! array_key_exists($key, $headerMap))
            ->values()
            ->all();

        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'file' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.',
            ]);
        }

        $kelasByName = Kelas::query()
            ->get(['id', 'nama_kelas'])
            ->mapWithKeys(static fn (Kelas $kelas): array => [
                self::normalizeClassName($kelas->nama_kelas) => $kelas->getKey(),
            ]);

        if ($kelasByName->isEmpty()) {
            throw ValidationException::withMessages([
                'file' => 'Belum ada data kelas. Tambahkan kelas terlebih dahulu sebelum mengimpor siswa.',
            ]);
        }

        $records = [];
        $errors = [];
        $seenNisn = [];

        foreach (array_slice($rows, 1) as $offset => $row) {
            $line = $offset + 2;
            $nisn = $this->digits($row[$headerMap['nisn']] ?? '');
            $namaLengkap = trim($row[$headerMap['nama_lengkap']] ?? '');
            $namaKelas = trim($row[$headerMap['kelas']] ?? '');

            // Abaikan baris kosong serta catatan/petunjuk pada akhir template.
            if ($nisn === '' && $namaLengkap === '' && $namaKelas === '') {
                continue;
            }

            if (! preg_match('/^\d{8,20}$/', $nisn)) {
                $errors[] = "Baris {$line}: NISN harus berisi 8 sampai 20 digit angka.";
                continue;
            }

            if ($namaLengkap === '') {
                $errors[] = "Baris {$line}: Nama Lengkap wajib diisi.";
                continue;
            }

            $kelasId = $kelasByName->get(self::normalizeClassName($namaKelas));

            if ($kelasId === null) {
                $errors[] = "Baris {$line}: kelas '{$namaKelas}' belum terdaftar pada menu Kelas.";
                continue;
            }

            if (isset($seenNisn[$nisn])) {
                $errors[] = "Baris {$line}: NISN {$nisn} muncul lebih dari satu kali pada file.";
                continue;
            }

            $seenNisn[$nisn] = true;
            $records[] = [
                'nisn' => $nisn,
                'nama_lengkap' => $namaLengkap,
                'kelas_id' => (int) $kelasId,
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'file' => array_slice($errors, 0, 10),
            ]);
        }

        if ($records === []) {
            throw ValidationException::withMessages([
                'file' => 'Tidak ada data siswa yang dapat diimpor. Pastikan kolom NISN, Nama Lengkap, dan Kelas terisi.',
            ]);
        }

        return $records;
    }

    /**
     * @param  array<int, string>  $header
     * @return array<string, int>
     */
    private function headerMap(array $header): array
    {
        $aliases = [
            'nisn' => 'nisn',
            'noinduknisn' => 'nisn',
            'noinduk' => 'nisn',
            'nama' => 'nama_lengkap',
            'namalengkap' => 'nama_lengkap',
            'namasiswa' => 'nama_lengkap',
            'kelas' => 'kelas',
        ];

        $map = [];

        foreach ($header as $index => $value) {
            $normalized = preg_replace('/[^a-z0-9]+/', '', self::normalizeText($value)) ?? '';

            if (isset($aliases[$normalized])) {
                $map[$aliases[$normalized]] = $index;
            }
        }

        return $map;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readCsv(string $absolutePath): array
    {
        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'File CSV tidak dapat dibaca.',
            ]);
        }

        $rows = [];

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = array_map(
                    static fn (mixed $value): string => trim((string) $value),
                    $row,
                );
            }
        } finally {
            fclose($handle);
        }

        if (isset($rows[0][0])) {
            $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', $rows[0][0]) ?? $rows[0][0];
        }

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readXlsx(string $absolutePath): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages([
                'file' => 'Ekstensi PHP Zip belum aktif. Aktifkan extension=zip pada PHP Laragon, restart Laragon, lalu impor ulang.',
            ]);
        }

        if (! function_exists('simplexml_load_string')) {
            throw ValidationException::withMessages([
                'file' => 'Ekstensi PHP XML/SimpleXML belum aktif. Aktifkan ekstensi XML pada PHP Laragon, restart Laragon, lalu impor ulang.',
            ]);
        }

        $zip = new ZipArchive();

        if ($zip->open($absolutePath) !== true) {
            throw ValidationException::withMessages([
                'file' => 'File Excel tidak dapat dibuka. Simpan kembali sebagai Excel Workbook (*.xlsx), lalu coba lagi.',
            ]);
        }

        try {
            $sheetContents = $this->firstWorksheetContents($zip);

            if ($sheetContents === null) {
                throw ValidationException::withMessages([
                    'file' => 'Worksheet pertama pada file Excel tidak ditemukan.',
                ]);
            }

            $sharedStrings = $this->readSharedStrings($zip);

            return $this->readWorksheetRows($sheetContents, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    private function firstWorksheetContents(ZipArchive $zip): ?string
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if (
                is_string($name)
                && str_starts_with($name, 'xl/worksheets/')
                && str_ends_with($name, '.xml')
            ) {
                $contents = $zip->getFromIndex($index);

                return is_string($contents) ? $contents : null;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function readSharedStrings(ZipArchive $zip): array
    {
        $contents = $zip->getFromName('xl/sharedStrings.xml');

        if (! is_string($contents)) {
            return [];
        }

        $xml = simplexml_load_string($contents, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);

        if (! $xml instanceof SimpleXMLElement) {
            return [];
        }

        $namespace = $xml->getNamespaces(true)[''] ?? null;

        if ($namespace === null) {
            return [];
        }

        $xml->registerXPathNamespace('x', $namespace);
        $strings = [];

        foreach ($xml->xpath('//x:si') ?: [] as $item) {
            $item->registerXPathNamespace('x', $namespace);
            $parts = $item->xpath('.//x:t') ?: [];
            $strings[] = implode('', array_map(static fn (SimpleXMLElement $part): string => (string) $part, $parts));
        }

        return $strings;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<int, array<int, string>>
     */
    private function readWorksheetRows(string $contents, array $sharedStrings): array
    {
        $xml = simplexml_load_string($contents, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);

        if (! $xml instanceof SimpleXMLElement) {
            throw ValidationException::withMessages([
                'file' => 'Isi worksheet Excel tidak dapat dibaca.',
            ]);
        }

        $namespace = $xml->getNamespaces(true)[''] ?? null;

        if ($namespace === null) {
            throw ValidationException::withMessages([
                'file' => 'Format worksheet Excel tidak didukung.',
            ]);
        }

        $xml->registerXPathNamespace('x', $namespace);
        $rows = [];

        foreach ($xml->xpath('//x:sheetData/x:row') ?: [] as $row) {
            $row->registerXPathNamespace('x', $namespace);
            $values = [];

            foreach ($row->xpath('./x:c') ?: [] as $cell) {
                $cell->registerXPathNamespace('x', $namespace);
                $reference = (string) $cell['r'];
                $columnIndex = $this->columnIndexFromReference($reference);
                $values[$columnIndex] = $this->cellValue($cell, $sharedStrings, $namespace);
            }

            if ($values === []) {
                continue;
            }

            ksort($values);
            $maxIndex = max(array_keys($values));
            $normalizedRow = array_fill(0, $maxIndex + 1, '');

            foreach ($values as $columnIndex => $value) {
                $normalizedRow[$columnIndex] = trim($value);
            }

            $rows[] = $normalizedRow;
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     */
    private function cellValue(SimpleXMLElement $cell, array $sharedStrings, string $namespace): string
    {
        $type = (string) $cell['t'];

        if ($type === 'inlineStr') {
            $parts = $cell->xpath('./x:is//x:t') ?: [];

            return implode('', array_map(static fn (SimpleXMLElement $part): string => (string) $part, $parts));
        }

        $valueNodes = $cell->xpath('./x:v') ?: [];
        $value = isset($valueNodes[0]) ? (string) $valueNodes[0] : '';

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        return $value;
    }

    private function columnIndexFromReference(string $reference): int
    {
        $letters = preg_replace('/\d+/', '', strtoupper($reference)) ?? '';
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max($index - 1, 0);
    }

    private function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private static function normalizeClassName(string $value): string
    {
        $normalized = preg_replace('/[^a-z0-9]+/u', '', self::normalizeText($value)) ?? '';

        return preg_replace('/^kelas/', '', $normalized) ?? $normalized;
    }

    private static function normalizeText(string $value): string
    {
        $value = trim($value);

        return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }
}
