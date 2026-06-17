<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\AcademicSession;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentPaymentHistoryPage extends Component
{
    public function render(): View
    {
        $student = auth()->user()?->student;
        $payments = collect();
        $sessions = collect();

        if ($student !== null) {
            $payments = Payment::query()
                ->where('student_id', $student->id)
                ->with(['feeStructure.session', 'feeStructure.program'])
                ->orderByDesc('payment_date')
                ->get();

            $sessions = get_student_academic_sessions($student);
        }

        return view('livewire.student.student-payment-history-page', [
            'payments' => $payments,
            'sessions' => $sessions,
        ])->layout('components.layouts.student', ['title' => __('Payment History'), 'hideHeader' => true]);
    }
}
