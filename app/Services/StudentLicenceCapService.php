<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Student;

class StudentLicenceCapService
{
    public function __construct(
        protected SchoolLicenceService $licenceService
    ) {}

    /**
     * Active students for billing: approved, not graduated, user account active (legacy licence_active_students_count).
     */
    public function activeStudentsCount(): int
    {
        return Student::query()
            ->where('approved', true)
            ->where('graduated', false)
            ->whereHas('user', fn ($q) => $q->where('active', true))
            ->count();
    }

    public function capReached(): bool
    {
        $max = $this->licenceService->maxActiveStudents();
        if ($max === null || $max <= 0) {
            return false;
        }

        return $this->activeStudentsCount() >= $max;
    }

    public function blocksNewAdmissions(): bool
    {
        if (! $this->licenceService->isEnforcementEnabled()) {
            return false;
        }

        return $this->licenceService->studentCapMode() === 'block' && $this->capReached();
    }

    /**
     * Message when an approval must be blocked, or null if allowed (legacy admin/approve-student.php).
     */
    public function messageIfCannotApproveAnotherStudent(): ?string
    {
        if (! $this->licenceService->isEnforcementEnabled()) {
            return null;
        }

        if ($this->licenceService->studentCapMode() !== 'block') {
            return null;
        }

        $max = $this->licenceService->maxActiveStudents();
        if ($max === null) {
            return null;
        }

        if ($this->activeStudentsCount() < $max) {
            return null;
        }

        return 'Active student limit for your licence has been reached. Contact your administrator to upgrade or adjust the cap.';
    }

    /**
     * Banner text for admin dashboard when active count is at or over the licence cap.
     */
    public function dashboardCapNotice(): ?string
    {
        if (! $this->licenceService->isEnforcementEnabled()) {
            return null;
        }

        $max = $this->licenceService->maxActiveStudents();
        if ($max === null || $max <= 0) {
            return null;
        }

        $active = $this->activeStudentsCount();
        if ($active < $max) {
            return null;
        }

        $mode = $this->licenceService->studentCapMode();

        if ($mode === 'warn') {
            return __('Active students (:current) have reached the licence cap (:max). Consider upgrading or raising the cap.', [
                'current' => $active,
                'max' => $max,
            ]);
        }

        return __('Active student limit (:max) reached — new approvals are blocked until the cap is raised or students are deactivated.', [
            'max' => $max,
        ]);
    }
}
