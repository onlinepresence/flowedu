<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\FeeStructure;
use App\Models\Payment;
use App\Models\ScholarshipRecipient;
use App\Models\Setting;
use App\Models\FeeBreakdownRequest;
use App\Models\User;
use App\Notifications\CollegeNotification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentFeesIndexPage extends Component
{
    use DispatchesCollegeToasts;

    public ?int $selectedStructureId = null;

    public bool $showBreakdownModal = false;

    public bool $showRequestModal = false;

    public function mount(): void
    {
        $student = auth()->user()?->student;
        if ($student !== null && $student->program_id !== null) {
            $sessionLevelMap = $this->getSessionLevelMap($student);
            $structures = FeeStructure::query()
                ->where('program_id', $student->program_id)
                ->orderByDesc('session_id')
                ->get()
                ->filter(function ($structure) use ($sessionLevelMap) {
                    return isset($sessionLevelMap[$structure->session_id]) 
                        && (int)$structure->level === (int)$sessionLevelMap[$structure->session_id];
                });

            if ($structures->isNotEmpty()) {
                $this->selectedStructureId = $structures->first()->id;
            }
        }
    }

    public function selectStructure(int $id): void
    {
        $this->selectedStructureId = $id;
    }

    public function requestDetailedBreakdown(): void
    {
        $student = auth()->user()?->student;
        if ($student === null || $this->selectedStructureId === null) {
            return;
        }

        $existing = FeeBreakdownRequest::where('student_id', $student->id)
            ->where('fee_structure_id', $this->selectedStructureId)
            ->first();

        if ($existing) {
            if ($existing->status === 'pending') {
                $this->collegeToast(__('You already have a pending request for this fee breakdown.'), 'warning');
                return;
            } elseif ($existing->status === 'approved') {
                $this->collegeToast(__('Your request has already been approved.'), 'info');
                return;
            } else {
                // Allow resubmitting if rejected
                $existing->update([
                    'status' => 'pending',
                    'resolved_by' => null,
                    'resolved_at' => null,
                ]);
            }
        } else {
            FeeBreakdownRequest::create([
                'student_id' => $student->id,
                'fee_structure_id' => $this->selectedStructureId,
                'status' => 'pending',
            ]);
        }

        // Notify Admins
        $admins = User::where('type', 'admin')->get();
        $feeStructure = FeeStructure::with('session')->find($this->selectedStructureId);
        $sessionName = $feeStructure?->session?->name ?? '—';
        foreach ($admins as $admin) {
            $admin->notify(new CollegeNotification(
                __('Fee Breakdown Request'),
                __(':name is requesting a detailed breakdown of Level :level fees for the :session session.', [
                    'name' => $student->user->name,
                    'level' => $feeStructure?->level,
                    'session' => $sessionName,
                ]),
                route('admin.finance.fees', ['activeTab' => 'requests'])
            ));
        }

        $this->collegeToast(__('Request submitted successfully to the accounts office.'));
    }

    public function openBreakdown(): void
    {
        $this->showBreakdownModal = true;
    }

    public function closeBreakdown(): void
    {
        $this->showBreakdownModal = false;
    }

    public function openRequestDetails(): void
    {
        $this->showRequestModal = true;
    }

    public function closeRequestDetails(): void
    {
        $this->showRequestModal = false;
    }

    public function render(): View
    {
        $student = auth()->user()?->student;
        $structures = collect();
        $selectedStructure = null;
        $billedAmount = 0.0;
        $paidAmount = 0.0;
        $scholarshipAmount = 0.0;
        $balance = 0.0;
        $payments = collect();
        $breakdown = [];
        $hasDetailedAccess = false;
        $requestStatus = null; // pending, approved, rejected, or null (none)

        if ($student !== null && $student->program_id !== null) {
            $sessionLevelMap = $this->getSessionLevelMap($student);
            $structures = FeeStructure::query()
                ->where('program_id', $student->program_id)
                ->with('session')
                ->get()
                ->filter(function ($structure) use ($sessionLevelMap) {
                    return isset($sessionLevelMap[$structure->session_id]) 
                        && (int)$structure->level === (int)$sessionLevelMap[$structure->session_id];
                })
                ->sortByDesc('session_id')
                ->values();

            if ($this->selectedStructureId !== null) {
                $selectedStructure = $structures->firstWhere('id', $this->selectedStructureId);
            }

            if ($selectedStructure !== null) {
                // 1. Calculate Billed Amount
                $billedAmount = (float) $selectedStructure->total_amount;

                // 2. Calculate Paid Amount
                $paidAmount = (float) Payment::query()
                    ->where('student_id', $student->id)
                    ->where('fee_structure_id', $selectedStructure->id)
                    ->sum('amount_paid');

                // 3. Calculate Scholarships for this academic year
                $scholarshipAmount = (float) ScholarshipRecipient::query()
                    ->where('student_id', $student->id)
                    ->where('academic_session_id', $selectedStructure->session_id)
                    ->where('status', 'approved')
                    ->sum('amount_awarded');

                // 4. Balance
                $balance = max(0.0, $billedAmount - $paidAmount - $scholarshipAmount);

                // 5. Retrieve Payments
                $payments = Payment::query()
                    ->where('student_id', $student->id)
                    ->where('fee_structure_id', $selectedStructure->id)
                    ->orderByDesc('payment_date')
                    ->get();

                // 6. Check access status
                $prefVal = Setting::where('setting_key', 'system_preferences.show_detailed_bill_breakdown')->value('setting_value');
                $globalPreference = $prefVal === '1';

                $req = FeeBreakdownRequest::where('student_id', $student->id)
                    ->where('fee_structure_id', $selectedStructure->id)
                    ->first();

                if ($req) {
                    $requestStatus = $req->status;
                }

                $hasDetailedAccess = $globalPreference || ($requestStatus === 'approved');

                // 7. Get Itemized components
                if ($hasDetailedAccess) {
                    $breakdown[] = [
                        'label' => __('Tuition Fee'),
                        'amount' => (float) $selectedStructure->tuition_fee,
                    ];

                    $extraKeys = Setting::where('setting_key', 'finance.extra_fee_keys')->value('setting_value');
                    $enabledKeys = is_string($extraKeys) ? json_decode($extraKeys, true) : null;
                    if (!is_array($enabledKeys) || empty($enabledKeys)) {
                        $enabledKeys = [
                            'library_fee', 'lab_fee', 'medical_fee', 'sports_fee', 'examination_fee',
                            'registration_fee', 'ict_fee', 'id_card_fee', 'facility_maintenance_fee',
                            'utility_fee', 'field_trip_fee', 'internship_fee', 'src_dues'
                        ];
                    }

                    $labels = [
                        'library_fee' => __('Library Fee'),
                        'lab_fee' => __('Lab Fee'),
                        'medical_fee' => __('Medical Fee'),
                        'sports_fee' => __('Sports Fee'),
                        'examination_fee' => __('Examination Fee'),
                        'registration_fee' => __('Registration Fee'),
                        'ict_fee' => __('ICT Fee'),
                        'id_card_fee' => __('ID Card Fee'),
                        'facility_maintenance_fee' => __('Facility Maintenance Fee'),
                        'utility_fee' => __('Utility Fee'),
                        'field_trip_fee' => __('Field Trip / Practicum Fee'),
                        'internship_fee' => __('Internship / Attachment Fee'),
                        'src_dues' => __('SRC / Student Dues'),
                    ];

                    foreach ($enabledKeys as $key) {
                        $val = (float) ($selectedStructure->{$key} ?? 0.0);
                        if ($val > 0) {
                            $breakdown[] = [
                                'label' => $labels[$key] ?? ucwords(str_replace('_', ' ', $key)),
                                'amount' => $val,
                            ];
                        }
                    }
                }
            }
        }

        // Fetch school info for printable invoice
        $schoolName = Setting::where('setting_key', 'school.name')->value('setting_value') ?? __('APEX POLYTECHNIC');
        $schoolAddress = Setting::where('setting_key', 'school.address')->value('setting_value') ?? __('123 Campus Drive, Accra');
        $schoolPhone = Setting::where('setting_key', 'school.phone')->value('setting_value') ?? __('+233 30 123 4567');
        $schoolEmail = Setting::where('setting_key', 'school.email')->value('setting_value') ?? __('info@apex.edu.gh');
        $schoolMotto = Setting::where('setting_key', 'school.motto')->value('setting_value') ?? __('EXCELLENCE & KNOWLEDGE');

        return view('livewire.student.student-fees-index-page', [
            'student' => $student,
            'structures' => $structures,
            'selectedStructure' => $selectedStructure,
            'billedAmount' => $billedAmount,
            'paidAmount' => $paidAmount,
            'scholarshipAmount' => $scholarshipAmount,
            'balance' => $balance,
            'payments' => $payments,
            'hasDetailedAccess' => $hasDetailedAccess,
            'requestStatus' => $requestStatus,
            'breakdown' => $breakdown,
            'schoolName' => $schoolName,
            'schoolAddress' => $schoolAddress,
            'schoolPhone' => $schoolPhone,
            'schoolEmail' => $schoolEmail,
            'schoolMotto' => $schoolMotto,
        ])->layout('components.layouts.student', ['title' => __('Fees Details'), 'hideHeader' => true]);
    }

    /**
     * @param mixed $student
     * @return array<int, int>
     */
    private function getSessionLevelMap($student): array
    {
        $currentSession = \App\Models\AcademicSession::where('is_current', true)->first();
        if (!$currentSession) {
            $currentSession = \App\Models\AcademicSession::orderByDesc('start_date')->first();
        }
        if (!$currentSession) {
            return [];
        }

        $allSessions = \App\Models\AcademicSession::orderBy('start_date', 'asc')->get();
        $currentIndex = $allSessions->pluck('id')->search($currentSession->id);
        if ($currentIndex === false) {
            return [];
        }

        $studentLevel = (int) $student->current_year;
        $sessionLevelMap = [];

        foreach ($allSessions as $index => $session) {
            $diff = $index - $currentIndex;
            $levelInSession = $studentLevel + ($diff * 100);
            if ($levelInSession >= 100 && $levelInSession <= $studentLevel) {
                $sessionLevelMap[$session->id] = $levelInSession;
            }
        }

        return $sessionLevelMap;
    }
}
