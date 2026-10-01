<?php

namespace App\Services;

use App\Models\Kelas;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;
use SimpleXMLElement;
use ZipArchive;

final class SiswaImportService
{
    private const MAX_ROWS = 5000;

    private const MAX_COLUMNS = 64;

    private const MAX_XLSX_ENTRIES = 256;

    private const MAX_XLSX_UNCOMPRESSED_BYTES = 20_000_000;

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
            'json' => throw ValidationException::withMessages([
                'file' => 'Import JSON harus melalui pratinjau dan konfirmasi pada menu Data Siswa.',
            ]),
            default => throw ValidationException::withMessages([
                'file' => 'Gunakan file berformat .xlsx, .csv, atau .json.',
            ]),
        };

        return DB::transaction(function () use ($rows): array {
            $prepared = [
                'records' => $this->prepareRecords($rows),
                'skipped' => 0,
            ];

            $summary = [
                'created' => 0,
                'updated' => 0,
                'skipped' => $prepared['skipped'],
            ];

            $siswaService = app(SiswaService::class);

            foreach ($prepared['records'] as $record) {
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
                'file' => 'File upload tidak valid. Pilih ulang file Excel, CSV, atau JSON lalu coba kembali.',
            ]);
        }

        $absolutePath = $uploadedFile->getRealPath() ?: $uploadedFile->getPathname();
        $extension = strtolower($uploadedFile->getClientOriginalExtension());

        return $this->import($absolutePath, $extension);
    }

    /**
     * @return array{
     *     students: array<int, array{selection_key: string, emis_id: string, nisn: string|null, nama_lengkap: string, source_class: string, source_key: string}>,
     *     source_classes: array<string, string>,
     *     suggested_mappings: array<string, int|null>,
     *     skipped: int
     * }
     */
    public function previewUploadedJson(mixed $uploadedFile): array
    {
        [$absolutePath, $extension] = $this->uploadedFilePath($uploadedFile);

        if ($extension !== 'json') {
            throw ValidationException::withMessages([
                'file' => 'Gunakan file berformat .json.',
            ]);
        }

        return $this->previewJson($absolutePath);
    }

    /**
     * @return array{
     *     students: array<int, array{selection_key: string, emis_id: string, nisn: string|null, nama_lengkap: string, source_class: string, source_key: string}>,
     *     source_classes: array<string, string>,
     *     suggested_mappings: array<string, int|null>,
     *     skipped: int
     * }
     */
    public function previewJson(string $absolutePath): array
    {
        if (! is_file($absolutePath)) {
            throw ValidationException::withMessages([
                'file' => 'File impor tidak ditemukan atau sudah tidak tersedia.',
            ]);
        }

        $preview = $this->prepareJsonPreview($this->readJson($absolutePath));
        $kelasByName = Kelas::query()
            ->get(['id', 'nama_kelas'])
            ->mapWithKeys(static fn (Kelas $kelas): array => [
                self::normalizeClassName($kelas->nama_kelas) => $kelas->getKey(),
            ]);

        $preview['suggested_mappings'] = collect($preview['source_classes'])
            ->mapWithKeys(static fn (string $name, string $key): array => [
                $key => $kelasByName->get(self::normalizeClassName($name)),
            ])
            ->all();

        return $preview;
    }

    /**
     * @param  array<int, string>  $selectedStudents
     * @param  array<string, int|string|null>  $classMappings
     * @return array{created: int, updated: int, skipped: int}
     */
    public function importUploadedJsonSelection(
        mixed $uploadedFile,
        array $selectedStudents,
        array $classMappings,
    ): array {
        [$absolutePath, $extension] = $this->uploadedFilePath($uploadedFile);

        if ($extension !== 'json') {
            throw ValidationException::withMessages([
                'file' => 'Gunakan file berformat .json.',
            ]);
        }

        return $this->importJsonSelection($absolutePath, $selectedStudents, $classMappings);
    }

    /**
     * @param  array<int, string>  $selectedStudents
     * @param  array<string, int|string|null>  $classMappings
     * @return array{created: int, updated: int, skipped: int}
     */
    public function importJsonSelection(
        string $absolutePath,
        array $selectedStudents,
        array $classMappings,
    ): array {
        $preview = $this->previewJson($absolutePath);
        $selectedStudents = array_values(array_unique(array_map(
            static fn (mixed $selectionKey): string => trim((string) $selectionKey),
            $selectedStudents,
        )));

        if ($selectedStudents === []) {
            throw ValidationException::withMessages([
                'selected_students' => 'Pilih minimal satu siswa untuk diimpor.',
            ]);
        }

        $studentsByKey = collect($preview['students'])->keyBy('selection_key');
        $unknownStudents = collect($selectedStudents)
            ->reject(static fn (string $selectionKey): bool => $studentsByKey->has($selectionKey));

        if ($unknownStudents->isNotEmpty()) {
            throw ValidationException::withMessages([
                'selected_students' => 'Pilihan siswa tidak sesuai dengan isi file JSON. Unggah ulang file.',
            ]);
        }

        $requiredSourceKeys = collect($selectedStudents)
            ->map(static fn (string $selectionKey): string => $studentsByKey->get($selectionKey)['source_key'])
            ->unique()
            ->values();
        $kelasIds = Kelas::query()->pluck('id')->map(static fn (int $id): int => $id)->all();
        $records = [];

        foreach ($requiredSourceKeys as $sourceKey) {
            $kelasId = (int) ($classMappings[$sourceKey] ?? 0);

            if (! in_array($kelasId, $kelasIds, true)) {
                $sourceClass = $preview['source_classes'][$sourceKey] ?? $sourceKey;

                throw ValidationException::withMessages([
                    "class_mapping.{$sourceKey}" => "Pilih kelas tujuan untuk rombel {$sourceClass}.",
                ]);
            }
        }

        foreach ($selectedStudents as $selectionKey) {
            $student = $studentsByKey->get($selectionKey);
            $records[] = [
                'emis_id' => $student['emis_id'],
                'nisn' => $student['nisn'],
                'nama_lengkap' => $student['nama_lengkap'],
                'kelas_id' => (int) $classMappings[$student['source_key']],
            ];
        }

        return DB::transaction(function () use ($records, $preview): array {
            $summary = [
                'created' => 0,
                'updated' => 0,
                'skipped' => $preview['skipped'],
            ];
            $siswaService = app(SiswaService::class);

            foreach ($records as $record) {
                $result = $siswaService->upsertFromImport(
                    $record['nisn'],
                    $record['nama_lengkap'],
                    $record['kelas_id'],
                    $record['emis_id'],
                );
                $summary[$result['status']]++;
            }

            return $summary;
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function uploadedFilePath(mixed $uploadedFile): array
    {
        if (is_array($uploadedFile)) {
            $uploadedFile = reset($uploadedFile) ?: null;
        }

        if (! $uploadedFile instanceof UploadedFile || ! $uploadedFile->isValid()) {
            throw ValidationException::withMessages([
                'file' => 'File upload tidak valid. Pilih ulang file lalu coba kembali.',
            ]);
        }

        return [
            $uploadedFile->getRealPath() ?: $uploadedFile->getPathname(),
            strtolower($uploadedFile->getClientOriginalExtension()),
        ];
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
     * @return array<int, array<string, mixed>>
     */
    private function readJson(string $absolutePath): array
    {
        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            throw ValidationException::withMessages([
                'file' => 'File JSON tidak dapat dibaca.',
            ]);
        }

        try {
            $rows = json_decode($contents, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'file' => 'Format JSON tidak valid.',
            ]);
        }

        if (! is_array($rows) || ! array_is_list($rows)) {
            throw ValidationException::withMessages([
                'file' => 'Isi JSON harus berupa daftar data siswa.',
            ]);
        }

        if (count($rows) > self::MAX_ROWS) {
            throw ValidationException::withMessages([
                'file' => 'File impor maksimal berisi 5.000 siswa.',
            ]);
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{
     *     students: array<int, array{selection_key: string, emis_id: string, nisn: string|null, nama_lengkap: string, source_class: string, source_key: string}>,
     *     source_classes: array<string, string>,
     *     skipped: int
     * }
     */
    private function prepareJsonPreview(array $rows): array
    {
        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'File JSON tidak berisi data siswa.',
            ]);
        }

        $students = [];
        $sourceClasses = [];
        $seenStudents = [];
        $skipped = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                $skipped++;

                continue;
            }

            $emisId = trim((string) ($row['id'] ?? ''));
            $nisn = $this->digits((string) ($row['nisn'] ?? ''));
            $nisn = preg_match('/^\d{8,20}$/', $nisn) === 1 ? $nisn : null;
            $namaLengkap = trim((string) ($row['full_name'] ?? $row['nama_lengkap'] ?? ''));
            $namaKelas = trim((string) (
                $row['study_group_name']
                ?? data_get($row, 'learning_activity.study_group.name')
                ?? ''
            ));

            if ($emisId === '' || $namaLengkap === '' || $namaKelas === '') {
                $skipped++;

                continue;
            }

            $selectionKey = 'emis:'.$emisId;

            if (isset($seenStudents[$selectionKey])) {
                $skipped++;

                continue;
            }

            $sourceKey = sha1(self::normalizeClassName($namaKelas));
            $seenStudents[$selectionKey] = true;
            $sourceClasses[$sourceKey] = $namaKelas;
            $students[] = [
                'selection_key' => $selectionKey,
                'emis_id' => $emisId,
                'nisn' => $nisn,
                'nama_lengkap' => $namaLengkap,
                'source_class' => $namaKelas,
                'source_key' => $sourceKey,
            ];
        }

        if ($students === []) {
            throw ValidationException::withMessages([
                'file' => 'Tidak ada data siswa yang dapat diimpor. Pastikan id, full_name, dan study_group_name terisi.',
            ]);
        }

        return [
            'students' => $students,
            'source_classes' => $sourceClasses,
            'skipped' => $skipped,
        ];
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
                if (count($rows) >= self::MAX_ROWS) {
                    throw ValidationException::withMessages([
                        'file' => 'File impor maksimal berisi 5.000 baris.',
                    ]);
                }

                if (count($row) > self::MAX_COLUMNS) {
                    throw ValidationException::withMessages([
                        'file' => 'File impor maksimal berisi 64 kolom.',
                    ]);
                }

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

        $zip = new ZipArchive;

        if ($zip->open($absolutePath) !== true) {
            throw ValidationException::withMessages([
                'file' => 'File Excel tidak dapat dibuka. Simpan kembali sebagai Excel Workbook (*.xlsx), lalu coba lagi.',
            ]);
        }

        try {
            $this->validateArchiveSize($zip);
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

    private function validateArchiveSize(ZipArchive $zip): void
    {
        if ($zip->numFiles > self::MAX_XLSX_ENTRIES) {
            throw ValidationException::withMessages([
                'file' => 'File Excel memiliki terlalu banyak bagian internal.',
            ]);
        }

        $totalSize = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
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
            if (count($strings) >= self::MAX_ROWS * self::MAX_COLUMNS) {
                throw ValidationException::withMessages([
                    'file' => 'File Excel memiliki terlalu banyak teks bersama.',
                ]);
            }

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
            if (count($rows) >= self::MAX_ROWS) {
                throw ValidationException::withMessages([
                    'file' => 'File impor maksimal berisi 5.000 baris.',
                ]);
            }

            $row->registerXPathNamespace('x', $namespace);
            $values = [];

            foreach ($row->xpath('./x:c') ?: [] as $cell) {
                $cell->registerXPathNamespace('x', $namespace);
                $reference = (string) $cell['r'];
                $columnIndex = $this->columnIndexFromReference($reference);

                if ($columnIndex >= self::MAX_COLUMNS) {
                    throw ValidationException::withMessages([
                        'file' => 'File impor maksimal berisi 64 kolom.',
                    ]);
                }

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
