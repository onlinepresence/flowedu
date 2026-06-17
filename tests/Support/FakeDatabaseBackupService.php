<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Backup;
use App\Models\User;
use App\Services\Backup\DatabaseBackupService;
use Illuminate\Support\Facades\Storage;

/**
 * Test double: avoids mysqldump/mysql (CI uses sqlite).
 */
final class FakeDatabaseBackupService extends DatabaseBackupService
{
    public function supportsMysqlDump(): bool
    {
        return true;
    }

    /**
     * @return array{ok: bool, backup?: Backup, message?: string}
     */
    public function createBackup(?User $creator): array
    {
        Storage::disk('local')->makeDirectory('backups');
        $filename = 'fake_'.uniqid('', true).'.sql';
        $relative = 'backups/'.$filename;
        Storage::disk('local')->put($relative, '-- test dump');

        $backup = Backup::query()->create([
            'filename' => $filename,
            'file_path' => $relative,
            'file_size' => (int) Storage::disk('local')->size($relative),
            'created_by' => $creator?->id,
            'created_at' => now(),
        ]);

        return ['ok' => true, 'backup' => $backup];
    }

    public function tryPreRestoreBackup(?User $creator): void
    {
        $this->createBackup($creator);
    }

    public function restoreFromSqlFile(string $absoluteSqlPath): void
    {
        // no-op for tests
    }
}
