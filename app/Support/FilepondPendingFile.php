<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class FilepondPendingFile
{
    public static function assertOwnedPendingPath(?string $path, int $userId): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        $prefix = 'filepond-tmp/'.$userId.'/';

        return str_starts_with($path, $prefix)
            && ! str_contains(Str::after($path, $prefix), '..')
            && Storage::disk('local')->exists($path);
    }

    /**
     * Move a validated pending upload to the public disk. Returns path relative to the public disk root, or null.
     */
    public static function moveToPublicDisk(?string $pendingPath, int $userId, string $directory): ?string
    {
        if (! self::assertOwnedPendingPath($pendingPath, $userId)) {
            return null;
        }

        $full = Storage::disk('local')->path($pendingPath);
        $ext = pathinfo($full, PATHINFO_EXTENSION);
        $name = Str::uuid()->toString().($ext !== '' ? '.'.$ext : '');

        $dest = trim($directory, '/').'/'.$name;

        $stream = fopen($full, 'r');
        if ($stream === false) {
            return null;
        }

        try {
            Storage::disk('public')->writeStream($dest, $stream);
        } finally {
            fclose($stream);
        }

        Storage::disk('local')->delete($pendingPath);

        return $dest;
    }

    /**
     * Move pending file to a local private path (e.g. backups). Returns path relative to local disk root.
     */
    public static function moveToLocalDisk(?string $pendingPath, int $userId, string $directory): ?string
    {
        if (! self::assertOwnedPendingPath($pendingPath, $userId)) {
            return null;
        }

        $full = Storage::disk('local')->path($pendingPath);
        $ext = pathinfo($full, PATHINFO_EXTENSION);
        $name = Str::uuid()->toString().($ext !== '' ? '.'.$ext : '');
        $dest = trim($directory, '/').'/'.$name;

        Storage::disk('local')->move($pendingPath, $dest);

        return $dest;
    }
}
