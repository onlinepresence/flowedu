<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Minimal .env key writer with write verification.
 *
 * Updates existing KEY= lines in place (preserving comments and order) or
 * appends missing keys. After writing, the file is re-read to verify every
 * pair landed; on any failure the caller gets the exact lines for manual
 * paste instead of a dead end.
 */
final class EnvWriter
{
    /**
     * @param array<string, string> $pairs
     * @return array{ok: bool, lines: list<string>, path: string}
     */
    public static function write(array $pairs, ?string $path = null): array
    {
        // Tests override the target via config so the real .env is untouched.
        $path ??= config('controlplane.env_path', \base_path('.env'));
        $lines = [];

        foreach ($pairs as $key => $value) {
            $lines[] = $key.'='.$value;
        }

        if ($pairs === []) {
            return ['ok' => true, 'lines' => $lines, 'path' => $path];
        }

        try {
            $current = file_exists($path) ? (string) file_get_contents($path) : '';
            $eol = str_contains($current, "\r\n") ? "\r\n" : "\n";

            foreach ($pairs as $key => $value) {
                $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
                $replacement = $key.'='.$value;
                if (preg_match($pattern, $current) === 1) {
                    // Callback so $ and \ in tokens are written verbatim.
                    $current = (string) preg_replace_callback(
                        $pattern,
                        static fn (): string => $replacement,
                        $current
                    );
                } else {
                    $current = rtrim($current, "\r\n").$eol.$replacement.$eol;
                }
            }

            $written = @file_put_contents($path, $current);
            if ($written === false) {
                return ['ok' => false, 'lines' => $lines, 'path' => $path];
            }

            // Verify: re-read and confirm every pair is present verbatim.
            $check = (string) file_get_contents($path);
            foreach ($lines as $line) {
                if (! str_contains($check, $line)) {
                    return ['ok' => false, 'lines' => $lines, 'path' => $path];
                }
            }

            return ['ok' => true, 'lines' => $lines, 'path' => $path];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'lines' => $lines, 'path' => $path];
        }
    }

    /**
     * Delete whole KEY= lines (e.g. voiding a local demo key on elevation).
     *
     * @param list<string> $keys
     * @return array{ok: bool, path: string}
     */
    public static function remove(array $keys, ?string $path = null): array
    {
        $path ??= config('controlplane.env_path', \base_path('.env'));

        if ($keys === []) {
            return ['ok' => true, 'path' => $path];
        }

        try {
            if (! file_exists($path)) {
                return ['ok' => true, 'path' => $path];
            }

            $current = (string) file_get_contents($path);
            $eol = str_contains($current, "\r\n") ? "\r\n" : "\n";

            $kept = [];
            foreach (preg_split('/\r\n|\n/', $current) as $line) {
                $drop = false;
                foreach ($keys as $key) {
                    if (str_starts_with(trim((string) $line), $key.'=')) {
                        $drop = true;
                        break;
                    }
                }
                if (! $drop) {
                    $kept[] = $line;
                }
            }

            $new = implode($eol, $kept);
            if ($new !== '' && ! str_ends_with($new, $eol)) {
                $new .= $eol;
            }

            if (@file_put_contents($path, $new) === false) {
                return ['ok' => false, 'path' => $path];
            }

            // Verify: re-read and confirm every key is gone.
            foreach (preg_split('/\r\n|\n/', (string) file_get_contents($path)) as $line) {
                foreach ($keys as $key) {
                    if (str_starts_with(trim((string) $line), $key.'=')) {
                        return ['ok' => false, 'path' => $path];
                    }
                }
            }

            return ['ok' => true, 'path' => $path];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'path' => $path];
        }
    }
}
