<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\Backup\DatabaseBackupService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class BackupDownloadController extends Controller
{
    public function show(Backup $backup, DatabaseBackupService $backups): BinaryFileResponse
    {
        $path = $backups->absolutePathForBackup($backup);
        if ($path === null || ! is_readable($path)) {
            abort(404);
        }

        return response()->download($path, $backup->filename);
    }
}
