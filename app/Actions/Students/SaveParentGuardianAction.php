<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Models\ParentGuardian;
use App\Models\Student;

final class SaveParentGuardianAction
{
    /**
     * @param  array{name: string, relationship: string, phone_number: string, address?: ?string, email?: ?string}  $data
     */
    public function execute(Student $student, array $data, ?int $guardianId): ParentGuardian
    {
        if ($guardianId !== null && $guardianId > 0) {
            $row = ParentGuardian::query()
                ->where('id', $guardianId)
                ->where('student_id', $student->id)
                ->firstOrFail();
            $row->forceFill([
                'name' => $data['name'],
                'relationship' => $data['relationship'],
                'phone_number' => $data['phone_number'],
                'address' => $data['address'] ?? null,
                'email' => $data['email'] ?? null,
            ])->save();

            return $row->fresh();
        }

        return ParentGuardian::query()->create([
            'student_id' => $student->id,
            'name' => $data['name'],
            'relationship' => $data['relationship'],
            'phone_number' => $data['phone_number'],
            'address' => $data['address'] ?? null,
            'email' => $data['email'] ?? null,
        ]);
    }
}
