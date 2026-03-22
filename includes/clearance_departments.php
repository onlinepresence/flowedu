<?php

/**
 * Canonical clearance unit keys (student-facing + admin). Extend allowed_* for future RBAC.
 *
 * @return array<string, string> key => label
 */
function clearance_department_definitions(): array
{
    return [
        'library' => 'Library',
        'finance' => 'Finance Office',
        'academic' => 'Academic Office',
        'hostel' => 'Hostel/Hall',
        'sports' => 'Sports Department',
        'medical' => 'Medical Center',
    ];
}

/**
 * Department keys the current user may update (future: filter by role/permission).
 *
 * @return list<string>
 */
function allowed_clearance_department_keys(): array
{
    return array_keys(clearance_department_definitions());
}

function default_clearance_status_for_department(string $department_key): string
{
    return $department_key === 'sports' ? 'not_required' : 'pending';
}

function clearance_department_is_allowed(string $department_key): bool
{
    return in_array($department_key, allowed_clearance_department_keys(), true);
}
