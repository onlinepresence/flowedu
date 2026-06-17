<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Services\StudentLicenceCapService;
use Illuminate\Validation\ValidationException;

/**
 * Call from student approval flows (legacy admin/approve-student.php parity).
 */
class AssertStudentApprovalAllowedByLicence
{
    public function __construct(
        protected StudentLicenceCapService $capService
    ) {}

    /**
     * @throws ValidationException when cap blocks approval (block mode + enforcement on).
     */
    public function __invoke(): void
    {
        $message = $this->capService->messageIfCannotApproveAnotherStudent();
        if ($message !== null) {
            throw ValidationException::withMessages([
                'system_message' => $message,
            ]);
        }
    }
}
