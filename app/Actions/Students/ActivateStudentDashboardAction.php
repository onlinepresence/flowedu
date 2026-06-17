<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Models\Student;
use Illuminate\Validation\ValidationException;

/**
 * Legacy submit change_status: guardian present, approved, assign official index, clear is_new.
 */
final class ActivateStudentDashboardAction
{
    public function __construct(
        private readonly AssignOfficialStudentIndexNumberAction $assignIndex
    ) {}

    public function execute(Student $student): void
    {
        if (! $student->parentGuardians()->exists()) {
            throw ValidationException::withMessages([
                'activate' => [__('Guardian information not provided.')],
            ]);
        }

        if (! $student->approved) {
            throw ValidationException::withMessages([
                'activate' => [__('Your admission has not yet been approved. Please check at another time.')],
            ]);
        }

        $index = $this->assignIndex->execute($student);

        $student->forceFill([
            'index_number' => $index,
            'is_new' => false,
        ])->save();
    }
}
