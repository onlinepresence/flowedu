<?php
require_once relative_path("includes/session.php");

$title = 'Student record';

$admins = ['admin', 'hod', 'dean', 'owner'];
$print_error = null;
$student = null;
$guardian = null;

if (!in_array($_SESSION['user_type'] ?? '', $admins, true)) {
    $print_error = 'You are not authorized to view this page.';
} else {
    $idx_raw = isset($index_number) ? trim((string) $index_number) : '';
    $idx = rawurldecode($idx_raw);
    if ($idx === '') {
        $print_error = 'No index number was provided.';
    } else {
        global $connect;
        $safe = $connect->real_escape_string($idx);
        $student_row = fetchData('id, user_id', 'students', "index_number = '$safe' AND approved = 1");
        if (!$student_row) {
            $print_error = 'Student not found or is not an approved record.';
        } else {
            $uid = (int) $student_row['user_id'];
            $student = get_user_details($uid, 'student');
            
            // user will always be a student
            $guardian = fetchData(
                'id, name, relationship, address, phone_number, email',
                'parent_guardians',
                "student_id={$student_row['id']}"
            );
            if (!$guardian) {
                $guardian = null;
            }
        }
    }
}

function ps_esc(?string $v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

ob_start();

if ($print_error !== null) {
    echo '<div class="ps-error"><p>' . ps_esc($print_error) . '</p><p class="no-print"><a href="' . ps_esc(url('/admin/students')) . '">Return to student list</a></p></div>';
} else {
    $school_row = school();
    $school_name = $school_row['name'] ?? 'School';
    $school_address = trim((string) ($school_row['address'] ?? ''));

    $full_name = trim(implode(' ', array_filter([
        $student['lastname'] ?? '',
        $student['firstname'] ?? '',
        $student['othernames'] ?? '',
    ])));

    $program = !empty($student['program_id']) ? programs((int) $student['program_id']) : null;
    $program_name = $program['name'] ?? '—';

    $department = !empty($student['department_id']) ? departments((int) $student['department_id']) : null;
    $department_name = $department['name'] ?? '—';

    $hall = !empty($student['hall_id']) ? halls((int) $student['hall_id']) : null;
    $hall_name = $hall['name'] ?? '—';

    $profile_url = !empty($student['profile_pic']) ? asset($student['profile_pic']) : '';

    $dl = static function (string $label, $value): void {
        $display = ($value !== null && $value !== '') ? ps_esc((string) $value) : '—';
        echo '<dt>' . ps_esc($label) . '</dt><dd>' . $display . '</dd>';
    };

    ?>
    <header class="ps-school">
        <h1><?= ps_esc($school_name) ?></h1>
        <?php if ($school_address !== ''): ?>
            <p><?= ps_esc($school_address) ?></p>
        <?php endif; ?>
        <span class="ps-tag">Student information sheet</span>
    </header>

    <section class="ps-hero">
        <div class="ps-photo-wrap">
            <?php if ($profile_url !== ''): ?>
                <img src="<?= ps_esc($profile_url) ?>" alt="" width="200" height="250" />
            <?php else: ?>
                <div class="ps-photo-placeholder">No photo on file</div>
            <?php endif; ?>
        </div>
        <div class="ps-biodata">
            <h2><?= ps_esc($full_name !== '' ? $full_name : 'Student') ?></h2>
            <p class="ps-index">Index: <?= ps_esc($student['index_number'] ?? '') ?></p>
            <dl class="ps-dl">
                <?php $dl('Date of birth', $student['date_of_birth'] ?? null); ?>
                <?php $dl('Gender', isset($student['gender']) ? ucfirst((string) $student['gender']) : null); ?>
                <?php $dl('Phone', $student['phone_number'] ?? null); ?>
                <?php $dl('Email', $student['email'] ?? null); ?>
            </dl>
        </div>
    </section>

    <div class="ps-grid cols-2">
        <section class="ps-card">
            <h3>Academic</h3>
            <dl class="ps-dl">
                <?php $dl('Program', $program_name); ?>
                <?php $dl('Department', $department_name); ?>
                <?php $dl('Hall', $hall_name); ?>
                <?php $dl('Level', isset($student['current_year']) ? (string) $student['current_year'] : null); ?>
            </dl>
        </section>

        <section class="ps-card">
            <h3>Personal &amp; contact</h3>
            <dl class="ps-dl">
                <?php $dl('Home / GPS address', $student['contact_address'] ?? null); ?>
                <?php $dl('Nationality', $student['nationality'] ?? null); ?>
                <?php $dl('Religion', $student['religion'] ?? null); ?>
                <?php $dl('Denomination', $student['denomination'] ?? null); ?>
                <?php $dl('Ghana Card', $student['ghana_card'] ?? null); ?>
                <?php
                $disability = $student['disability_status'] ?? null;
                $dl('Disability', $disability !== null && $disability !== '' ? ucfirst((string) $disability) : null);
                ?>
                <?php $dl('Disability type', $student['disability_type'] ?? null); ?>
            </dl>
        </section>

        <section class="ps-card">
            <h3>Health</h3>
            <dl class="ps-dl">
                <?php $dl('Health insurance no.', $student['insurance_number'] ?? null); ?>
                <?php $dl('Allergies', $student['allergy'] ?? null); ?>
            </dl>
        </section>

        <section class="ps-card">
            <h3>Guardian / next of kin</h3>
            <?php if ($guardian): ?>
                <dl class="ps-dl">
                    <?php $dl('Name', $guardian['name'] ?? null); ?>
                    <?php $dl('Relationship', $guardian['relationship'] ?? null); ?>
                    <?php $dl('Address', $guardian['address'] ?? null); ?>
                    <?php $dl('Phone', $guardian['phone_number'] ?? null); ?>
                    <?php $dl('Email', $guardian['email'] ?? null); ?>
                </dl>
            <?php else: ?>
                <p style="margin:0;color:var(--ps-muted);font-size:0.9rem;">No guardian record on file.</p>
            <?php endif; ?>
        </section>
    </div>

    <footer class="ps-foot">
        Generated <?= ps_esc(date('j F Y, H:i')) ?> · Official use only
    </footer>
    <?php
}

$content = ob_get_clean();

require relative_path('layouts/print-student.php');
