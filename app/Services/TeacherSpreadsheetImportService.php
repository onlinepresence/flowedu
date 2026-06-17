<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Staff\CreateTeacherUser;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TeacherSpreadsheetImportService
{
    public function __construct(
        private readonly SpreadsheetImportService $spreadsheetImportService,
        private readonly CreateTeacherUser $createTeacherUser,
    ) {}

    /**
     * @return array{created: int, errors: list<string>}
     */
    public function importFromFilepondRelativePath(string $relativePath, int $adminUserId): array
    {
        $relativePath = trim($relativePath);
        $prefix = 'filepond-tmp/'.$adminUserId.'/';
        if ($relativePath === '' || ! str_starts_with($relativePath, $prefix) || str_contains(Str::after($relativePath, $prefix), '..')) {
            return ['created' => 0, 'errors' => [__('Invalid upload path.')]];
        }

        $absolutePath = storage_path('app/'.$relativePath);
        if (! is_readable($absolutePath)) {
            return ['created' => 0, 'errors' => [__('Uploaded file is no longer available. Upload again.')]];
        }

        $rows = $this->spreadsheetImportService->readFirstSheetRowsFromPath($absolutePath, 500);
        if ($rows === []) {
            return ['created' => 0, 'errors' => [__('Could not read spreadsheet or file is empty.')]];
        }

        $header = array_map(fn ($h) => is_string($h) ? strtolower(trim($h)) : '', $rows[0]);
        $expected = ['email', 'username', 'lastname', 'othernames', 'staff_id', 'department_id', 'phone_number'];
        $missing = array_values(array_diff($expected, $header));
        if ($missing !== []) {
            return [
                'created' => 0,
                'errors' => [__('Missing required columns: :cols.', ['cols' => implode(', ', $missing)])],
            ];
        }

        $indexes = [];
        foreach ($expected as $col) {
            $indexes[$col] = array_search($col, $header, true);
        }

        $created = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $rowIndex => $line) {
            $excelRow = $rowIndex + 2;
            $cell = function (string $col) use ($indexes, $line): string {
                $i = $indexes[$col] ?? null;
                if ($i === null || $i === false) {
                    return '';
                }
                $v = $line[$i] ?? '';

                return is_scalar($v) ? trim((string) $v) : '';
            };

            $email = strtolower($cell('email'));
            $username = $cell('username');
            $lastname = $cell('lastname');
            if ($email === '' || $username === '' || $lastname === '') {
                $errors[] = __('Row :row: email, username, and lastname are required.', ['row' => $excelRow]);

                continue;
            }

            $userByUsername = User::query()->where('username', $username)->first();
            if ($userByUsername !== null) {
                if (User::query()->where('email', $email)->where('id', '!=', $userByUsername->id)->exists()) {
                    $errors[] = __('Row :row: email is already in use by another account.', ['row' => $excelRow]);

                    continue;
                }
            } else {
                if (User::query()->where('email', $email)->exists()) {
                    $errors[] = __('Row :row: email already exists.', ['row' => $excelRow]);

                    continue;
                }
            }

            $deptRaw = $cell('department_id');
            $departmentId = $deptRaw !== '' ? (int) $deptRaw : null;
            if ($departmentId !== null && $departmentId > 0 && ! Department::query()->whereKey($departmentId)->exists()) {
                $errors[] = __('Row :row: invalid department_id.', ['row' => $excelRow]);

                continue;
            }

            $password = Str::password(12);

            try {
                DB::transaction(function () use ($email, $username, $lastname, $cell, $departmentId, $password): void {
                    $this->createTeacherUser->execute([
                        'name' => trim($lastname.' '.$cell('othernames')),
                        'username' => $username,
                        'email' => $email,
                        'password' => $password,
                        'lastname' => $lastname,
                        'othernames' => $cell('othernames') !== '' ? $cell('othernames') : null,
                        'staff_id' => $cell('staff_id') !== '' ? $cell('staff_id') : null,
                        'department_id' => $departmentId !== null && $departmentId > 0 ? $departmentId : null,
                        'phone_number' => $cell('phone_number') !== '' ? $cell('phone_number') : null,
                        'active' => true,
                    ]);
                });
                $created++;
            } catch (\Throwable $e) {
                report($e);
                $errors[] = __('Row :row: could not create teacher.', ['row' => $excelRow]);
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }
}
