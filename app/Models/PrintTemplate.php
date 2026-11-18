<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use setasign\Fpdi\Tcpdf\Fpdi;
use ZipArchive;

class PrintTemplate extends Model
{
    public const CATEGORIES = ['members' => 'Members', 'census' => 'Census', 'events' => 'Collection'];

    protected $fillable = ['name', 'category', 'format', 'content', 'pdf_top', 'pdf_bottom', 'pdf_margin', 'pdf_layout'];

    protected $hidden = ['content'];

    public function usesRosterLayout(): bool
    {
        return $this->format === 'pdf' && $this->pdf_layout === 'roster';
    }

    /** @param array<string, mixed> $data */
    public static function rosterPdf(array $data): string
    {
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetTitle('Senior Citizen Organization Roster');
        $pdf->SetMargins(17.7, 18.7, 21.3);
        $pdf->SetFooterMargin(8);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->setCellHeightRatio(1.15);
        $pdf->SetLineWidth(0.28);
        $pdf->AddPage();
        $pdf->writeHTML(view('members.roster-pdf', $data)->render());

        return $pdf->Output('member-roster.pdf', 'S');
    }

    /**
     * @param  array<string, string>  $heading
     * @param  list<array<string, string>>  $rows
     */
    public function generate(array $heading, array $rows): string
    {
        $source = tempnam(sys_get_temp_dir(), 'kasenior-template-');
        $output = tempnam(sys_get_temp_dir(), 'kasenior-report-');

        try {
            file_put_contents($source, base64_decode($this->content, true));
            if ($this->usesRosterLayout()) {
                if ($this->category !== 'members') {
                    throw ValidationException::withMessages(['pdf_layout' => 'The roster reference layout is for member reports. Choose Members or use a blank letterhead.']);
                }
                $reader = new Fpdi;
                $pageCount = $reader->setSourceFile($source);
                for ($page = 1; $page <= $pageCount; $page++) {
                    $reader->importPage($page);
                }

                return self::rosterPdf([
                    'members' => collect($rows)->map(fn (array $row): Member => new Member($row)),
                    'asOf' => Carbon::parse($heading['report_date']),
                    'association' => 'PUROK 7-C',
                    'reportBarangay' => 'Baliok',
                    'reportDistrict' => 'Talomo',
                    'organizedAt' => '2023-09-09',
                    'submittedBy' => '', 'verifiedBy' => '', 'notedBy' => '',
                ]);
            }
            if ($this->format !== 'pdf') {
                $this->validateArchive($source);
            }
            match ($this->format) {
                'docx' => $this->word($source, $output, $heading, $rows),
                'xlsx' => $this->excel($source, $output, $heading, $rows),
                'pdf' => $this->pdf($source, $output, $heading, $rows),
            };

            return file_get_contents($output);
        } finally {
            @unlink($source);
            @unlink($output);
        }
    }

    private function validateArchive(string $source): void
    {
        $archive = new ZipArchive;
        if ($archive->open($source) !== true) {
            throw ValidationException::withMessages(['file' => 'This is not a valid Office document.']);
        }

        try {
            $size = 0;
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $entry = $archive->statIndex($index);
                $size += $entry['size'];
                if ($size > 25000000 || $archive->numFiles > 2000 || str_contains(strtolower($entry['name']), 'vbaproject')) {
                    throw ValidationException::withMessages(['file' => 'Use a smaller document without macros (maximum 25 MB expanded).']);
                }
            }
        } finally {
            $archive->close();
        }
    }

    /** @param array<string, string> $heading
     * @param  list<array<string, string>>  $rows
     */
    private function word(string $source, string $output, array $heading, array $rows): void
    {
        Settings::setOutputEscapingEnabled(true);
        $document = new TemplateProcessor($source);
        if (! in_array('member_name', $document->getVariables(), true)) {
            throw ValidationException::withMessages(['file' => 'Add ${member_name} to a Word table row to mark the repeating data row.']);
        }
        $document->cloneRowAndSetValues('member_name', $rows);
        $document->setValues($heading);
        $document->saveAs($output);
    }

    /** @param array<string, string> $heading
     * @param  list<array<string, string>>  $rows
     */
    private function excel(string $source, string $output, array $heading, array $rows): void
    {
        $reader = IOFactory::createReader('Xlsx');
        $book = $reader->load($source);
        try {
            $anchors = [];
            foreach ($book->getWorksheetIterator() as $sheet) {
                foreach ($sheet->getCellCollection()->getCoordinates() as $coordinate) {
                    if (str_contains((string) $sheet->getCell($coordinate)->getValue(), '${member_name}')) {
                        $anchors[] = [$sheet, $sheet->getCell($coordinate)->getRow()];
                    }
                }
            }
            if (count($anchors) !== 1) {
                throw ValidationException::withMessages(['file' => 'Add ${member_name} exactly once in the Excel row you want repeated.']);
            }
            [$sheet, $rowNumber] = $anchors[0];
            foreach ($sheet->getMergeCells() as $range) {
                [$start, $end] = Coordinate::rangeBoundaries($range);
                if ($start[1] <= $rowNumber && $end[1] >= $rowNumber) {
                    throw ValidationException::withMessages(['file' => 'Use unmerged cells in the repeating Excel row.']);
                }
            }
            $columns = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
            if (count($rows) > 1) {
                $sheet->insertNewRowBefore($rowNumber + 1, count($rows) - 1);
            }
            $values = [];
            for ($column = 1; $column <= $columns; $column++) {
                $values[$column] = (string) $sheet->getCell([$column, $rowNumber])->getValue();
            }
            foreach ($rows as $offset => $row) {
                $destination = $rowNumber + $offset;
                $sheet->getRowDimension($destination)->setRowHeight($sheet->getRowDimension($rowNumber)->getRowHeight());
                foreach ($values as $column => $value) {
                    $destinationCell = Coordinate::stringFromColumnIndex($column).$destination;
                    $sheet->duplicateStyle($sheet->getStyle([$column, $rowNumber]), $destinationCell);
                    $sheet->setCellValueExplicit([$column, $destination], $this->replace($value, $row + $heading), DataType::TYPE_STRING);
                }
            }
            if ($rows === []) {
                $sheet->removeRow($rowNumber);
            }
            foreach ($book->getWorksheetIterator() as $worksheet) {
                foreach ($worksheet->getCellCollection()->getCoordinates() as $coordinate) {
                    $value = $worksheet->getCell($coordinate)->getValue();
                    if (is_string($value) && str_contains($value, '${')) {
                        $worksheet->setCellValueExplicit($coordinate, $this->replace($value, $heading), DataType::TYPE_STRING);
                    }
                }
            }
            $writer = new Xlsx($book);
            $writer->setPreCalculateFormulas(false);
            $writer->save($output);
        } finally {
            $book->disconnectWorksheets();
        }
    }

    /** @param array<string, string> $values */
    private function replace(string $text, array $values): string
    {
        $replacements = [];
        foreach ($values as $key => $value) {
            $replacements['${'.$key.'}'] = $value;
        }

        return strtr($text, $replacements);
    }

    /** @param array<string, string> $heading
     * @param  list<array<string, string>>  $rows
     */
    private function pdf(string $source, string $output, array $heading, array $rows): void
    {
        $pdf = new Fpdi;
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);
        if ($pdf->setSourceFile($source) !== 1) {
            throw ValidationException::withMessages(['file' => 'Letterheads must be one page. For a completed member roster with multiple pages, choose the Members category and Roster reference PDF layout.']);
        }
        $background = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($background);
        $margin = (float) $this->pdf_margin;
        $top = (float) $this->pdf_top;
        $bottom = $size['height'] - (float) $this->pdf_bottom;
        $width = $size['width'] - 2 * $margin;
        if ($width < 100 || $bottom - $top < 60) {
            throw ValidationException::withMessages(['pdf_top' => 'These margins leave too little room for the report. Reduce the margins.']);
        }
        $columns = ['member_name' => 'Member', 'age' => 'Age', 'location' => 'Purok', 'status' => 'Status'];
        if ($heading['event_name'] !== '') {
            $columns = ['member_name' => 'Member', 'location' => 'Purok', 'amount_paid' => 'Paid (PHP)', 'balance' => 'Balance (PHP)'];
        }
        $columnWidth = $width / count($columns);
        $pdf->SetFont('dejavusans', '', 9);
        $newPage = function () use ($pdf, $background, $size, $margin, $top, $heading, $width, $columns, $columnWidth): float {
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($background);
            $pdf->SetXY($margin, $top);
            $pdf->MultiCell($width, 6, $heading['report_title'].' — '.$heading['report_date'], 0, 'L');
            $y = $pdf->GetY() + 2;
            foreach (array_values($columns) as $index => $label) {
                $pdf->SetXY($margin + $index * $columnWidth, $y);
                $pdf->MultiCell($columnWidth, 8, $label, 1, 'L');
            }

            return $y + 8;
        };
        $y = $newPage();
        foreach ($rows as $row) {
            $height = 8.0;
            foreach ($columns as $key => $label) {
                $height = max($height, $pdf->getStringHeight($columnWidth, $row[$key]) + 2);
            }
            if ($height > $bottom - $top - 30) {
                throw ValidationException::withMessages(['template' => 'A report row is too tall for this PDF layout. Reduce the margins or use an Office template.']);
            }
            if ($y + $height > $bottom) {
                $y = $newPage();
            }
            foreach (array_keys($columns) as $index => $key) {
                $pdf->SetXY($margin + $index * $columnWidth, $y);
                $pdf->MultiCell($columnWidth, $height, $row[$key], 1, 'L');
            }
            $y += $height;
        }
        if ($rows === []) {
            $pdf->SetXY($margin, $y + 2);
            $pdf->MultiCell($width, 8, 'No matching records.', 0, 'L');
        }
        $pdf->Output($output, 'F');
    }
}
