<?php
require_once relative_path("includes/components.php");

$title = 'Student Disciplinary Records';
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-plus-circle mr-2"></i>Add disciplinary record
        </h3>
        
        <form action="<?= url('admin/submit.php') ?>" method="POST" id="discipline-form">
            <?= input("hidden", "", "request_type", "add_disciplinary_record") ?>
            <?= input("hidden", "", "student_id", "", false, attribute("id", "discipline-student-id")) ?>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Student (search by index or name)</label>
                <div class="flex gap-2 flex-wrap">
                    <input type="text" id="discipline-student-search" class="flex-1 min-w-[200px] rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600" placeholder="Search…" autocomplete="off">
                    <button type="button" id="discipline-search-go" class="px-3 py-2 bg-gray-200 dark:bg-gray-600 rounded text-sm">Search</button>
                </div>
                <div id="discipline-search-hits" class="mt-2 hidden border rounded dark:border-gray-600 max-h-36 overflow-y-auto text-sm"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?= textarea("offense", "Offense / incident", "", true, attribute("rows", "3")) ?>
                <?= textarea("action_taken", "Action taken", "", true, attribute("rows", "3")) ?>
                <?= textarea("comments", "Comments", "", false, attribute("rows", "2")) ?>
                <?= input("date", "Date of action", "date_of_action", "", true) ?>
                <?= input("date", "Return date (optional)", "return_date", "", false) ?>
            </div>
            
            <div class="mt-6">
                <?= button("submit", "Add record", "submit", "add_disciplinary_record", "purple") ?>
            </div>
        </form>
    </div>

    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-list mr-2"></i>Records
        </h3>
        
        <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
            <?= input("text", "Search", "search_student", "", false, array_merge(
                attribute("id", "search-student"),
                attribute("placeholder", "Index, name, offense")
            )) ?>
            
            <?php 
                $return_opts = [
                    ["id" => "", "text" => "All"],
                    ["id" => "open", "text" => "Open"],
                    ["id" => "closed", "text" => "Closed"],
                ];
            ?>
            <?= select("filter_return", "Case status", $return_opts, "All", attributes: attribute("id", "filter-return")) ?>
            
            <?php $programs = programs(); ?>
            <?= select("filter_program", "Program", $programs, "All Programs", keys: select_keys("id", "name"), attributes: attribute("id", "filter-program")) ?>
            
            <div class="flex items-end">
                <button type="button" id="discipline-refresh" class="w-full px-4 py-2 text-sm bg-purple-600 text-white rounded-lg">Refresh</button>
            </div>
        </div>
        
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Index") ?>
                <?= th("Student") ?>
                <?= th("Program") ?>
                <?= th("Offense") ?>
                <?= th("Date") ?>
                <?= th("Status") ?>
                <?= th("") ?>
            <?= thead_end() ?>
            <?= tbody_start(['id' => 'disciplinary-tbody']) ?>
                <?= td_empty("Loading…", 7) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
</div>

<?php $scripts = <<<HTML
<script>
function loadDisciplinaryRecords() {
    $.post(relative_path('admin/ajax/student.php'), {
        submit: 'fetch_disciplinary_records',
        response_type: 'json',
        search: $('#search-student').val(),
        return_status: $('#filter-return').val(),
        program_id: $('#filter-program').val()
    }, function(res) {
        var body = '';
        if (res.status && res.data && res.data.records && res.data.records.length) {
            res.data.records.forEach(function(r) {
                var open = parseInt(r.return_status, 10) === 0;
                body += '<tr>';
                body += '<td>' + $('<div/>').text(r.index_number || '').html() + '</td>';
                body += '<td>' + $('<div/>').text(r.fullname || '').html() + '</td>';
                body += '<td>' + $('<div/>').text(r.program_name || '').html() + '</td>';
                body += '<td class="max-w-xs truncate">' + $('<div/>').text(r.offense || '').html() + '</td>';
                body += '<td>' + $('<div/>').text(r.date_of_action || '').html() + '</td>';
                body += '<td>' + (open ? 'Open' : 'Closed') + '</td>';
                body += '<td>';
                if (open) {
                    body += '<button type="button" class="text-purple-600 text-sm resolve-record" data-id="'+r.id+'">Close case</button>';
                }
                body += '</td></tr>';
            });
        } else {
            body = '<tr><td colspan="7" class="px-4 py-2 text-center text-gray-500">No records</td></tr>';
        }
        $('#disciplinary-tbody').html(body);
    }, 'json');
}

$(document).ready(function(){
    loadDisciplinaryRecords();
    $('#filter-return, #filter-program').on('change', loadDisciplinaryRecords);
    var dt;
    $('#search-student').on('input', function(){
        clearTimeout(dt);
        dt = setTimeout(loadDisciplinaryRecords, 400);
    });
    $('#discipline-refresh').on('click', loadDisciplinaryRecords);

    var t;
    $('#discipline-student-search').on('input', function(){
        clearTimeout(t);
        t = setTimeout(runDiscSearch, 350);
    });
    $('#discipline-search-go').on('click', runDiscSearch);

    function runDiscSearch() {
        var q = $('#discipline-student-search').val().trim();
        if (q.length < 1) { $('#discipline-search-hits').addClass('hidden').empty(); return; }
        $.post(relative_path('admin/ajax/student.php'), {
            submit: 'search_students',
            response_type: 'json',
            q: q,
            limit: 15
        }, function(res){
            if (!res.data || !res.data.students || !res.data.students.length) {
                $('#discipline-search-hits').html('<p class="p-2 text-gray-500">No matches</p>').removeClass('hidden');
                return;
            }
            var h = '<ul class="divide-y dark:divide-gray-600">';
            res.data.students.forEach(function(s){
                h += '<li class="p-2 flex justify-between gap-2"><span>'+$('<div/>').text(s.index_number+' — '+s.fullname).html()+'</span>';
                h += '<button type="button" class="text-purple-600 text-xs pick-disc-student" data-id="'+s.id+'">Select</button></li>';
            });
            h += '</ul>';
            $('#discipline-search-hits').html(h).removeClass('hidden');
        }, 'json');
    }

    $(document).on('click', '.pick-disc-student', function(){
        $('#discipline-student-id').val($(this).data('id'));
        $('#discipline-search-hits').addClass('hidden');
    });

    $(document).on('click', '.resolve-record', function(){
        var id = $(this).data('id');
        if (!confirm('Mark this case closed (set return status)?')) return;
        $.post(relative_path('admin/ajax/student.php'), {
            submit: 'resolve_disciplinary_record',
            response_type: 'json',
            record_id: id
        }, function(res){
            if (res.status) loadDisciplinaryRecords();
            else alert('Failed');
        }, 'json');
    });
});
</script>
HTML;

$content = ob_get_clean();
require relative_path('layouts/auth.php');
