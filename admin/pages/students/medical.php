<?php
require_once relative_path("includes/components.php");

$title = 'Student Medical Information';
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-search mr-2"></i>Search student
        </h3>
        <div class="flex flex-wrap gap-4 items-end">
            <?= input("text", "Index number or name", "q", "", false, array_merge(
                attribute("id", "search-medical"),
                attribute("placeholder", "Type to search…"),
                attribute("autocomplete", "off")
            )) ?>
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Results update as you type (short delay).</p>
    </div>

    <div id="medical-results-wrap" class="mb-6 hidden">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-users mr-2"></i>Results
        </h3>
        <div class="overflow-x-auto rounded-lg border dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-2">Student</th>
                        <th class="px-4 py-2">Index</th>
                        <th class="px-4 py-2">Program</th>
                        <th class="px-4 py-2">Allergies</th>
                        <th class="px-4 py-2">Insurance</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody id="medical-results-body" class="divide-y dark:divide-gray-700 bg-white dark:bg-gray-900"></tbody>
            </table>
        </div>
    </div>

    <div id="medical-edit-panel" class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 hidden">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-file-medical mr-2"></i>Edit medical record
        </h3>
        <form action="<?= url('admin/submit.php') ?>" method="POST" id="medical-form">
            <?= input("hidden", "", "request_type", "update_medical") ?>
            <?= input("hidden", "", "user_id", "", false, attribute("id", "medical-user-id")) ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?= textarea("allergies", "Allergies", "", false, array_merge(
                    attribute("id", "field-allergies"),
                    attribute("placeholder", "Known allergies")
                )) ?>
                <?= input("text", "Insurance number", "insurance_number", "", false, array_merge(
                    attribute("id", "field-insurance"),
                    attribute("placeholder", "Policy or NHIS number")
                )) ?>
                <?= textarea("medical_conditions", "Medical conditions", "", false, array_merge(
                    attribute("id", "field-conditions"),
                    attribute("placeholder", "Ongoing conditions, diagnoses")
                )) ?>
                <?= textarea("medications", "Medications", "", false, attribute("id", "field-medications")) ?>
                <?= textarea("immunization_records", "Immunization records", "", false, attribute("id", "field-immunization")) ?>
                <?= textarea("emergency_contacts", "Emergency contacts", "", false, array_merge(
                    attribute("id", "field-emergency"),
                    attribute("placeholder", "Name, relationship, phone (one block or multiple lines)")
                )) ?>
            </div>
            <div class="mt-6">
                <?= button("submit", "Save", "submit", "update_medical", "purple") ?>
            </div>
        </form>
    </div>
</div>

<?php $scripts = <<<HTML
<script>
var medicalTimer;
function runMedicalSearch() {
    var q = $('#search-medical').val().trim();
    if (q.length < 1) {
        $('#medical-results-wrap').addClass('hidden');
        $('#medical-results-body').empty();
        return;
    }
    $.post(relative_path('admin/ajax/student.php'), {
        submit: 'search_medical_students',
        response_type: 'json',
        search: q,
        limit: 25
    }, function(res) {
        if (!res.status || !res.data || !res.data.students || !res.data.students.length) {
            $('#medical-results-body').html('<tr><td colspan="6" class="px-4 py-3 text-gray-500">No matches</td></tr>');
            $('#medical-results-wrap').removeClass('hidden');
            return;
        }
        var rows = '';
        res.data.students.forEach(function(s) {
            rows += '<tr>';
            rows += '<td class="px-4 py-2">' + $('<div/>').text(s.fullname || '').html() + '</td>';
            rows += '<td class="px-4 py-2">' + $('<div/>').text(s.index_number || '').html() + '</td>';
            rows += '<td class="px-4 py-2">' + $('<div/>').text(s.program_name || '').html() + '</td>';
            rows += '<td class="px-4 py-2">' + $('<div/>').text(s.allergies || s.medical_conditions || '—').html() + '</td>';
            rows += '<td class="px-4 py-2">' + $('<div/>').text(s.insurance_number || '—').html() + '</td>';
            rows += '<td class="px-4 py-2"><button type="button" class="text-purple-600 text-sm edit-medical" data-user="'+s.user_id+'">Edit</button></td>';
            rows += '</tr>';
        });
        $('#medical-results-body').html(rows);
        $('#medical-results-wrap').removeClass('hidden');
    }, 'json');
}

$(document).ready(function(){
    $('#search-medical').on('input', function(){
        clearTimeout(medicalTimer);
        medicalTimer = setTimeout(runMedicalSearch, 400);
    });

    $(document).on('click', '.edit-medical', function(){
        var uid = $(this).data('user');
        $.post(relative_path('admin/ajax/student.php'), {
            submit: 'get_medical_student',
            response_type: 'json',
            user_id: uid
        }, function(res){
            if (!res.status || !res.data || !res.data.student) { alert('Could not load'); return; }
            var st = res.data.student;
            var h = res.data.history || {};
            $('#medical-user-id').val(st.user_id);
            $('#field-allergies').val(st.allergy || h.allergies || '');
            $('#field-insurance').val(st.insurance_number || '');
            $('#field-conditions').val(h.medical_conditions || '');
            $('#field-medications').val(h.medications || '');
            $('#field-immunization').val(h.immunization_records || '');
            $('#field-emergency').val(h.emergency_contacts || '');
            $('#medical-edit-panel').removeClass('hidden');
            document.getElementById('medical-edit-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 'json');
    });
});
</script>
HTML;

$content = ob_get_clean();
require relative_path('layouts/auth.php');
