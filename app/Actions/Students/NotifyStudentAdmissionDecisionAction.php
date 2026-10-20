<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Jobs\SendCollegeNotificationMailJob;
use App\Models\School;
use App\Models\Student;

final class NotifyStudentAdmissionDecisionAction
{
    public function approved(Student $student): void
    {
        $this->send(
            $student,
            __('Admission application approved - :school', ['school' => $this->schoolName()]),
            'emails.student-admission-approved',
        );
    }

    public function rejected(Student $student, string $reason): void
    {
        $this->send(
            $student,
            __('Admission application update - :school', ['school' => $this->schoolName()]),
            'emails.student-admission-rejected',
            ['rejectionReason' => $reason],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function send(Student $student, string $subject, string $view, array $data = []): void
    {
        $recipients = $this->recipients($student);
        if ($recipients === []) {
            return;
        }

        $student->loadMissing(['program.department']);
        $school = School::current();

        $html = view($view, array_merge([
            'student' => $student,
            'school' => $school,
            'schoolName' => $this->schoolName(),
        ], $data))->render();

        foreach ($recipients as $email) {
            SendCollegeNotificationMailJob::dispatch($email, $subject, $html);
        }
    }

    /**
     * @return list<string>
     */
    private function recipients(Student $student): array
    {
        $student->loadMissing(['user', 'parentGuardians']);

        $emails = collect([$student->user?->email])
            ->merge($student->parentGuardians->pluck('email'))
            ->filter(fn ($email): bool => is_string($email) && filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false)
            ->map(fn (string $email): string => mb_strtolower(trim($email)))
            ->unique()
            ->values()
            ->all();

        return $emails;
    }

    private function schoolName(): string
    {
        return (string) (School::current()?->name ?? config('app.name'));
    }
}
