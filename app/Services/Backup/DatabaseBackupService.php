<?php

declare(strict_types=1);

namespace App\Services\Backup;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseBackupService
{
    public function supportsMysqlDump(): bool
    {
        return Config::get('database.default') === 'mysql';
    }

    /**
     * @return array{ok: bool, backup?: Backup, message?: string}
     */
    public function createBackup(?User $creator): array
    {
        if (! $this->supportsMysqlDump()) {
            return [
                'ok' => false,
                'message' => __('Database backup requires MySQL; current connection is not supported in this environment.'),
            ];
        }

        $filename = 'backup_'.date('Ymd_His').'.sql';
        $dir = trim((string) Config::get('backup.directory', 'backups'), '/');
        $relative = $dir.'/'.$filename;

        $disk = Storage::disk('local');
        $disk->makeDirectory($dir);
        $fullPath = $disk->path($relative);

        try {
            $this->runMysqldumpToFile($fullPath);
        } catch (Throwable $e) {
            Log::error('college.backup.dump_failed', ['exception' => $e->getMessage()]);

            return [
                'ok' => false,
                'message' => __('Backup failed: :msg', ['msg' => $e->getMessage()]),
            ];
        }

        if (! is_file($fullPath) || filesize($fullPath) === 0) {
            return [
                'ok' => false,
                'message' => __('Backup file was not created or is empty.'),
            ];
        }

        $backup = Backup::query()->create([
            'filename' => $filename,
            'file_path' => $relative,
            'file_size' => (int) filesize($fullPath),
            'created_by' => $creator?->id,
            'created_at' => now(),
        ]);

        return ['ok' => true, 'backup' => $backup];
    }

    /**
     * Creates a safety dump before restore; does not fail restore if this step fails (logs only).
     */
    public function tryPreRestoreBackup(?User $creator): void
    {
        $result = $this->createBackup($creator);
        if (! $result['ok']) {
            Log::warning('college.backup.pre_restore_failed', ['message' => $result['message'] ?? 'unknown']);
        }
    }

    /**
     * @throws Throwable
     */
    public function restoreFromSqlFile(string $absoluteSqlPath): void
    {
        if (! $this->supportsMysqlDump()) {
            throw new \RuntimeException('Restore requires MySQL.');
        }

        if (! is_readable($absoluteSqlPath)) {
            throw new \InvalidArgumentException('SQL file is not readable.');
        }

        $mysql = (string) Config::get('backup.mysql_path', 'mysql');
        $connection = Config::get('database.connections.mysql', []);
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '3306');
        $database = (string) ($connection['database'] ?? '');
        $username = (string) ($connection['username'] ?? '');
        $password = (string) ($connection['password'] ?? '');

        if ($database === '' || $username === '') {
            throw new \RuntimeException('Database credentials are not configured.');
        }

        $process = new Process(
            [$mysql, '-h', $host, '-P', $port, '-u', $username, '-p'.$password, $database],
            null,
            null,
            file_get_contents($absoluteSqlPath) ?: null,
            3600.0
        );
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'mysql import failed.');
        }
    }

    public function absolutePathForBackup(Backup $backup): ?string
    {
        $path = $backup->file_path;
        if ($path === null || $path === '') {
            return null;
        }

        if ($this->isAbsolutePath($path)) {
            return is_file($path) ? $path : null;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            return null;
        }

        return $disk->path($path);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || (strlen($path) > 2 && ctype_alpha($path[0]) && $path[1] === ':' && ($path[2] === '\\' || $path[2] === '/'));
    }

    /**
     * @throws Throwable
     */
    private function runMysqldumpToFile(string $fullPath): void
    {
        $mysqldump = (string) Config::get('backup.mysqldump_path', 'mysqldump');
        $connection = Config::get('database.connections.mysql', []);
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '3306');
        $database = (string) ($connection['database'] ?? '');
        $username = (string) ($connection['username'] ?? '');
        $password = (string) ($connection['password'] ?? '');

        if ($database === '' || $username === '') {
            throw new \RuntimeException('Database credentials are not configured.');
        }

        $process = new Process([
            $mysqldump,
            '-h', $host,
            '-P', $port,
            '-u', $username,
            '-p'.$password,
            $database,
            '--result-file='.$fullPath,
        ]);
        $process->setTimeout(3600.0);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'mysqldump failed.');
        }
    }
}
