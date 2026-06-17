<?php

declare(strict_types=1);

if (! function_exists('get_student_academic_sessions')) {
    /**
     * Get the searchable academic sessions for a student based on their fee details, payments, and allowances.
     *
     * @param  \App\Models\Student|int  $student
     * @return \Illuminate\Database\Eloquent\Collection<\App\Models\AcademicSession>
     */
    function get_student_academic_sessions($student)
    {
        $studentId = $student instanceof \App\Models\Student ? $student->id : (int) $student;

        // Session IDs from FeePayment
        $feePaymentSessionIds = \App\Models\FeePayment::where('student_id', $studentId)
            ->pluck('academic_session_id')
            ->toArray();

        // Session IDs from Payment via FeeStructure
        $paymentSessionIds = \App\Models\Payment::where('student_id', $studentId)
            ->whereHas('feeStructure')
            ->with('feeStructure')
            ->get()
            ->pluck('feeStructure.session_id')
            ->toArray();

        // Session IDs from ScholarshipRecipient (e.g. allowances)
        $scholarshipSessionIds = \App\Models\ScholarshipRecipient::where('student_id', $studentId)
            ->pluck('academic_session_id')
            ->toArray();

        // Combine all and unique them
        $allSessionIds = array_unique(array_filter(array_merge(
            $feePaymentSessionIds,
            $paymentSessionIds,
            $scholarshipSessionIds,
            [\App\Models\AcademicSession::activeSessionId()] // always include current active session
        )));

        return \App\Models\AcademicSession::whereIn('id', $allSessionIds)
            ->orderByDesc('id')
            ->get();
    }
}
