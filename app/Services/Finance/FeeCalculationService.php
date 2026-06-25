<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Student;
use App\Models\AcademicSession;
use App\Models\FeeStructure;
use App\Models\FeePayment;
use App\Models\Payment;
use App\Models\ScholarshipRecipient;

class FeeCalculationService
{
    /**
     * Calculate the level the student was in for a given academic session.
     */
    public function getStudentLevelInSession(Student $student, AcademicSession $targetSession): ?int
    {
        $currentSession = AcademicSession::query()->where('is_current', true)->first();
        if (!$currentSession) {
            return (int) $student->current_year;
        }

        $currentYearInt = (int) $student->current_year;

        preg_match('/^(\d{4})/', $currentSession->name, $currentMatches);
        $currentStartYear = isset($currentMatches[1]) ? (int) $currentMatches[1] : 2025;

        preg_match('/^(\d{4})/', $targetSession->name, $targetMatches);
        $targetStartYear = isset($targetMatches[1]) ? (int) $targetMatches[1] : $currentStartYear;

        $yearDiff = $currentStartYear - $targetStartYear;
        $level = $currentYearInt - ($yearDiff * 100);

        return $level >= 100 ? $level : null;
    }

    /**
     * Calculate fees, scholarship discounts, and outstanding balances for a student.
     *
     * @param Student $student
     * @param AcademicSession $session
     * @return array<string, mixed>
     */
    public function calculateStudentFees(Student $student, AcademicSession $session): array
    {
        $level = $this->getStudentLevelInSession($student, $session);

        if ($level === null) {
            return [
                'tuition' => 0.0,
                'structure_total' => 0.0,
                'hostel_cost' => 0.0,
                'gross_fees' => 0.0,
                'discount' => 0.0,
                'net_bill' => 0.0,
                'amount_paid' => 0.0,
                'balance' => 0.0,
                'structure_id' => null,
            ];
        }

        // 1. Get Fee Structure
        $structure = FeeStructure::query()
            ->where('program_id', $student->program_id)
            ->where('level', $level)
            ->where('session_id', $session->id)
            ->first();

        $tuition = $structure ? (float) $structure->tuition_fee : 0.0;
        $structureTotal = $structure ? (float) $structure->total_amount : 0.0;

        // Hostel/Hall fee
        $hostelCost = 0.0;
        if ($student->hall_id && $student->hall) {
            $hostelCost = (float) $student->hall->cost;
        }

        // Enforce finance settings billing cycle division (semester vs. yearly)
        $billingCycle = \App\Models\Setting::query()
            ->where('setting_key', 'finance_settings.billing_cycle')
            ->value('setting_value') ?? 'yearly';

        if ($billingCycle === 'semester') {
            $tuition = $tuition / 2.0;
            $structureTotal = $structureTotal / 2.0;
            $hostelCost = $hostelCost / 2.0;
        }

        $grossFees = $structureTotal + $hostelCost;

        // 2. Resolve Scholarship Discounts
        $totalDiscount = 0.0;

        // Find approved/active scholarship recipients for this student (excluding allowances)
        $recipients = ScholarshipRecipient::query()
            ->with('scholarship')
            ->where('student_id', $student->id)
            ->whereIn('status', ['approved', 'active'])
            ->whereHas('scholarship', function ($q) {
                $q->where('name', '!=', 'Student Allowance')
                  ->where('name', '!=', 'Monthly Student Allowance Scheme');
            })
            ->get();

        foreach ($recipients as $recipient) {
            $scholarship = $recipient->scholarship;
            if (! $scholarship || $scholarship->status === 'inactive') {
                continue;
            }

            $amountAwarded = (float) $recipient->amount_awarded;
            $coverageType = $scholarship->coverage_type ?? 'full';

            if ($coverageType === 'full') {
                // Covers all fee components (gross fees)
                $discount = min($grossFees, $amountAwarded);
            } elseif ($coverageType === 'tuition_only') {
                // Covers only tuition
                $discount = min($tuition, $amountAwarded);
            } elseif ($coverageType === 'hostel_only') {
                // Covers only hostel cost
                $discount = min($hostelCost, $amountAwarded);
            } elseif ($coverageType === 'partial') {
                // Covers specific components
                $components = $scholarship->coverage_components ?? [];
                $coveredSum = 0.0;

                if ($structure) {
                    foreach ($components as $comp) {
                        if ($comp === 'hostel_fee') {
                            $coveredSum += $hostelCost;
                        } elseif (isset($structure->{$comp})) {
                            $coveredSum += (float) $structure->{$comp};
                        }
                    }
                }
                $discount = min($coveredSum, $amountAwarded);
            } else {
                $discount = min($grossFees, $amountAwarded);
            }

            $totalDiscount += $discount;
        }

        $netBill = max(0.0, $grossFees - $totalDiscount);

        // 3. Sum Payments
        $amountPaid = 0.0;
        if ($structure) {
            $amountPaid = (float) Payment::query()
                ->where('student_id', $student->id)
                ->where('fee_structure_id', $structure->id)
                ->where('status', 'completed')
                ->sum('amount_paid');
        }

        $balance = max(0.0, $netBill - $amountPaid);

        return [
            'tuition' => $tuition,
            'structure_total' => $structureTotal,
            'hostel_cost' => $hostelCost,
            'gross_fees' => $grossFees,
            'discount' => $totalDiscount,
            'net_bill' => $netBill,
            'amount_paid' => $amountPaid,
            'balance' => $balance,
            'structure_id' => $structure ? $structure->id : null,
        ];
    }

    public function syncFeePaymentLedger(Student $student, AcademicSession $session): ?FeePayment
    {
        $sessions = AcademicSession::all();
        $totalPaid = 0.0;
        $totalBalance = 0.0;

        foreach ($sessions as $as) {
            $calcs = $this->calculateStudentFees($student, $as);
            $totalPaid += $calcs['amount_paid'];
            $totalBalance += $calcs['balance'];
        }

        if ($student->department_id === null) {
            return null;
        }

        return FeePayment::query()->updateOrCreate(
            [
                'student_id' => $student->id,
            ],
            [
                'department_id' => $student->department_id,
                'lastname' => $student->lastname,
                'othernames' => $student->othernames ?? '',
                'class_level' => $student->current_year,
                'amount_paid' => $totalPaid,
                'balance' => $totalBalance,
            ]
        );
    }
}
