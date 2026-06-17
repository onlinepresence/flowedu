<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminType extends Model
{
    protected $fillable = [
        'name',
        'display_name',
    ];

    /**
     * Baseline rows for the "User role type" dropdown (also seeded by {@see \Database\Seeders\AdminSystemSeeder}).
     */
    public static function ensureDefaults(): void
    {
        $types = [
            ['name' => 'owner', 'display_name' => 'Owner'],
            ['name' => 'system_admin', 'display_name' => 'System administrator'],
            ['name' => 'registrar', 'display_name' => 'Registrar'],
            ['name' => 'hod', 'display_name' => 'Head of Department'],
            ['name' => 'principal', 'display_name' => 'Principal'],
            ['name' => 'vice_principal', 'display_name' => 'Vice Principal'],
            ['name' => 'finance_officer', 'display_name' => 'Finance Officer'],
            ['name' => 'dean_of_students', 'display_name' => 'Dean of Student Affairs'],
            ['name' => 'librarian', 'display_name' => 'College Librarian'],
            ['name' => 'internal_auditor', 'display_name' => 'Internal Auditor'],
            ['name' => 'secretary', 'display_name' => 'Secretary'],
            ['name' => 'admissions_officer', 'display_name' => 'Admissions Officer'],
            ['name' => 'exams_officer', 'display_name' => 'Examinations Officer'],
            ['name' => 'quality_assurance_officer', 'display_name' => 'Quality Assurance Officer'],
            ['name' => 'human_resource_manager', 'display_name' => 'Human Resource Manager'],
            ['name' => 'accountant', 'display_name' => 'Accountant'],
            ['name' => 'public_relations_officer', 'display_name' => 'Public Relations Officer'],
            ['name' => 'procurement_officer', 'display_name' => 'Procurement Officer'],
        ];

        foreach ($types as $row) {
            static::query()->firstOrCreate(
                ['name' => $row['name']],
                ['display_name' => $row['display_name']]
            );
        }
    }
}
