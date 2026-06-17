<?php

namespace Tests\Unit\Services;

use App\Services\SpreadsheetImportService;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class SpreadsheetImportServiceTest extends TestCase
{
    public function test_reads_csv_rows_from_upload(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        $this->assertNotFalse($tmp);
        file_put_contents($tmp, "col_a,col_b\n1,2\n3,4");

        $file = new UploadedFile($tmp, 'sheet.csv', 'text/csv', null, true);

        $service = new SpreadsheetImportService;
        $rows = $service->readFirstSheetRows($file);

        $this->assertSame(['col_a', 'col_b'], $rows[0]);
        $this->assertSame([1, 2], $rows[1]);
        $this->assertSame([3, 4], $rows[2]);

        @unlink($tmp);
    }

    public function test_reads_first_sheet_from_absolute_path(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        $this->assertNotFalse($tmp);
        file_put_contents($tmp, "h1,h2\nx,y");

        $service = new SpreadsheetImportService;
        $rows = $service->readFirstSheetRowsFromPath($tmp);

        $this->assertSame(['h1', 'h2'], $rows[0]);
        $this->assertSame(['x', 'y'], $rows[1]);

        @unlink($tmp);
    }
}
