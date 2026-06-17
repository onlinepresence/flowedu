<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

/**
 * Entry point for grading uploads and templates (legacy includes/spreadsheet.php).
 */
final class SpreadsheetImportService
{
    /**
     * @return list<list<mixed>> First sheet rows as scalar arrays (for validation pipelines).
     */
    public function readFirstSheetRows(UploadedFile $file, int $maxRows = 500): array
    {
        $path = $file->getRealPath();
        if ($path === false) {
            return [];
        }

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (ReaderException) {
            return [];
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];
        $i = 0;
        foreach ($sheet->getRowIterator() as $row) {
            if ($i >= $maxRows) {
                break;
            }
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $line = [];
            foreach ($cellIterator as $cell) {
                $line[] = $cell->getValue();
            }
            $rows[] = $line;
            $i++;
        }

        return $rows;
    }

    /**
     * Read the first sheet from a file on the local disk (e.g. Filepond temp path under storage/app).
     *
     * @return list<list<mixed>>
     */
    public function readFirstSheetRowsFromPath(string $absolutePath, int $maxRows = 500): array
    {
        if ($absolutePath === '' || ! is_readable($absolutePath)) {
            return [];
        }

        try {
            $spreadsheet = IOFactory::load($absolutePath);
        } catch (ReaderException) {
            return [];
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];
        $i = 0;
        foreach ($sheet->getRowIterator() as $row) {
            if ($i >= $maxRows) {
                break;
            }
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $line = [];
            foreach ($cellIterator as $cell) {
                $line[] = $cell->getValue();
            }
            $rows[] = $line;
            $i++;
        }

        return $rows;
    }
}
