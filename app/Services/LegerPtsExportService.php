<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\TahunAjaran;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class LegerPtsExportService
{
    public function xlsx(Kelas $kelas, TahunAjaran $tahunAjaran): BinaryFileResponse
    {
        $leger = app(LegerPtsService::class)->build($kelas->getKey(), $tahunAjaran->getKey());
        $directory = storage_path('app/temp');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $fileName = $this->fileName($kelas, $tahunAjaran, 'xlsx');
        $path = $directory.DIRECTORY_SEPARATOR.Str::uuid().'-'.$fileName;
        $options = new Options;
        $this->configureSpreadsheetLayout($options);

        $writer = new Writer($options);
        $writer->openToFile($path);

        try {
            $sheet = $writer->getCurrentSheet();
            $sheet->setName('Leger PTS');
            $sheet->setSheetView(
                (new SheetView)
                    ->setShowGridLines(false)
                    ->setZoomScale(80)
                    ->setFreezeRow(6)
                    ->setFreezeColumn('D'),
            );

            $titleStyle = $this->titleStyle();
            $subtitleStyle = $this->subtitleStyle();
            $headerStyle = $this->headerStyle();

            $writer->addRows([
                $this->safeRow(['LEGER '.$this->kelasLabel($kelas)], $titleStyle, [0])->setHeight(26),
                Row::fromValues(['MADRASAH IBTIDAIYAH LANTABURO'], $subtitleStyle)->setHeight(22),
                $this->safeRow(['TAHUN AJARAN '.$tahunAjaran->nama], $subtitleStyle, [0])->setHeight(22),
                Row::fromValues([])->setHeight(8),
                $this->safeRow([
                    'No', 'Nama', 'NISN',
                    ...array_map(static fn (array $mapel): string => $mapel['label'], $leger['mapel']),
                    'Jumlah', 'Ranking', 'Saran-saran',
                ], $headerStyle, range(0, 16))->setHeight(52),
            ]);

            foreach ($leger['rows'] as $index => $row) {
                $rowStyle = $this->dataRowStyle($index % 2 === 1);
                $writer->addRow($this->safeRow([
                    $row['no'],
                    $row['nama_siswa'],
                    $row['nisn'],
                    ...array_map(
                        static fn (array $mapel): ?int => $mapel['nilai_angka'],
                        $row['mata_pelajaran'],
                    ),
                    $row['jumlah'],
                    $row['ranking'],
                    $row['saran'],
                ], $rowStyle, [1, 2, 16], [
                    1 => $this->nameCellStyle($index % 2 === 1),
                    16 => $this->nameCellStyle($index % 2 === 1),
                ])->setHeight(34));
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

    public function pdf(Kelas $kelas, TahunAjaran $tahunAjaran): Response
    {
        $leger = app(LegerPtsService::class)->build($kelas->getKey(), $tahunAjaran->getKey());

        return Pdf::loadView('pdf.leger-pts', compact('leger'))
            ->setPaper('a3', 'landscape')
            ->download($this->fileName($kelas, $tahunAjaran, 'pdf'));
    }

    private function fileName(Kelas $kelas, TahunAjaran $tahunAjaran, string $extension): string
    {
        return sprintf(
            'leger-pts-%s-%s.%s',
            Str::slug($kelas->nama_kelas),
            Str::slug($tahunAjaran->label),
            $extension,
        );
    }

    private function configureSpreadsheetLayout(Options $options): void
    {
        $lastColumn = 16;

        for ($row = 1; $row <= 3; $row++) {
            $options->mergeCells(0, $row, $lastColumn, $row);
        }

        $options->setColumnWidth(5, 1);
        $options->setColumnWidth(30, 2);
        $options->setColumnWidth(16, 3);
        $options->setColumnWidthForRange(11, 4, 14);
        $options->setColumnWidth(10, 15, 16);
        $options->setColumnWidth(44, 17);
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

    private function subtitleStyle(): Style
    {
        return (new Style)
            ->setFontName('Arial')
            ->setFontSize(11)
            ->setFontBold()
            ->setFontColor('334155')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    private function headerStyle(): Style
    {
        return $this->tableStyle('0F3D5E', Color::WHITE, true);
    }

    private function dataRowStyle(bool $alternate): Style
    {
        return $this->tableStyle($alternate ? 'F8FAFC' : Color::WHITE, '334155');
    }

    private function nameCellStyle(bool $alternate): Style
    {
        return $this->tableStyle($alternate ? 'F8FAFC' : Color::WHITE, '0F172A')
            ->setCellAlignment(CellAlignment::LEFT);
    }

    private function tableStyle(string $background, string $fontColor, bool $bold = false): Style
    {
        $border = new Border(
            new BorderPart(Border::LEFT, '94A3B8', Border::WIDTH_THIN),
            new BorderPart(Border::RIGHT, '94A3B8', Border::WIDTH_THIN),
            new BorderPart(Border::TOP, '94A3B8', Border::WIDTH_THIN),
            new BorderPart(Border::BOTTOM, '94A3B8', Border::WIDTH_THIN),
        );
        $style = (new Style)
            ->setFontName('Arial')
            ->setFontSize(9)
            ->setFontColor($fontColor)
            ->setBackgroundColor($background)
            ->setBorder($border)
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setShouldWrapText();

        return $bold ? $style->setFontBold() : $style;
    }

    private function kelasLabel(Kelas $kelas): string
    {
        $namaKelas = trim($kelas->nama_kelas);

        return Str::startsWith(Str::upper($namaKelas), 'KELAS ')
            ? Str::upper($namaKelas)
            : 'KELAS '.Str::upper($namaKelas);
    }
}
