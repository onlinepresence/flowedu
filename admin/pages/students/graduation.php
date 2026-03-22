<?php
require_once relative_path("includes/components.php");
require_once relative_path("includes/clearance_departments.php");

$title = 'Graduation Management';
$ctx = current_session_and_semester();
$sessionBanner = $ctx['session']['name'] ?? null;

ob_start();
?>

<div class="container px-6 mx-auto grid">
    <?php if ($sessionBanner): ?>
    <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm text-gray-600 dark:text-gray-300">
        <strong>Current session:</strong> <?= htmlspecialchars($sessionBanner) ?>
    </div>
    <?php endif; ?>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
                <i class="fas fa-user-graduate mr-2"></i>Process graduation
            </h3>
            
            <form action="<?= url('admin/submit.php') ?>" method="POST">
                <?= input("hidden", "", "request_type", "process_graduation") ?>
                
                <div class="grid grid-cols-1 gap-4">
                    <?php 
                        $levels = [
                            ["id" => 400, "text" => "Level 400 (Final Year)"]
                        ];
                    ?>
                    <?= select("level", "Student Level", $levels, "Select Level", required: true) ?>
                    
                    <?php $programs = programs(); ?>
                    <?= select("program_id", "Program (Optional)", $programs, "All Programs", keys: select_keys("id", "name")) ?>
                    
                    <?php 
                        $sessions = fetchData("*", "academic_sessions", "", 0, "", "", "", "name", true);
                        $session_options = [["id" => "", "text" => "Use current session"]];
                        if(is_array($sessions) && !empty($sessions)) {
                            foreach($sessions as $session) {
                                $session_options[] = ["id" => $session['id'], "text" => $session['name']];
                            }
                        }
                    ?>
                    <?= select("session_id", "Academic Session", $session_options, "Use current session") ?>
                    
                    <?= input("date", "Graduation Date", "graduation_date", "", true) ?>
                </div>
                
                <?= information_bar(
                    "This will mark eligible final year students as graduated. Ensure all requirements are met.",
                    "warning",
                    false,
                    attribute("class", "mt-4")
                ) ?>
                
                <div class="mt-4">
                    <?= button("submit", "Process Graduation", "submit", "process_graduation", "purple") ?>
                </div>
            </form>
        </div>
        
        <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
                <i class="fas fa-chart-bar mr-2"></i>Graduation statistics
            </h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-purple-50 dark:bg-purple-900 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total graduated</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400" id="total-graduated">-</p>
                </div>
                <div class="p-4 bg-green-50 dark:bg-green-900 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">This calendar year</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400" id="this-year-graduated">-</p>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="button" onclick="loadGraduationStats()"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700"
                >
                    <i class="fas fa-sync-alt mr-2"></i>Refresh stats
                </button>
            </div>
        </div>
    </div>

    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-clipboard-check mr-2"></i>Student clearance
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Search a student, then update each unit. Future roles can limit which departments appear here.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <input type="text" id="clearance-student-search" class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 md:col-span-2" placeholder="Index number or name">
            <button type="button" id="clearance-search-btn" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Find students</button>
        </div>
        <div id="clearance-search-list" class="hidden mb-4 border rounded dark:border-gray-600 max-h-36 overflow-y-auto text-sm"></div>
        <div id="clearance-detail" class="hidden">
            <p class="font-medium mb-2" id="clearance-student-label"></p>
            <input type="hidden" id="clearance-student-id" value="">
            <div class="space-y-2" id="clearance-rows"></div>
        </div>
    </div>

    <div class="mb-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-list mr-2"></i>Graduated students
        </h3>
        
        <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php $programs = programs(); ?>
            <?= select("filter_program", "Program", $programs, "All Programs", keys: select_keys("id", "name"), attributes: array_merge(
                attribute("id", "filter-program"),
                data_attr("filter", "program")
            )) ?>
            
            <?= input("date", "From Date", "filter_from_date", "", false, array_merge(
                attribute("id", "filter-from-date"),
                data_attr("filter", "from_date")
            )) ?>
            
            <?= input("date", "To Date", "filter_to_date", "", false, array_merge(
                attribute("id", "filter-to-date"),
                data_attr("filter", "to_date")
            )) ?>
        </div>
        
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Index") ?>
                <?= th("Name") ?>
                <?= th("Program") ?>
                <?= th("Graduation date") ?>
                <?= th("Status") ?>
            <?= thead_end() ?>
            <?= tbody_start(['id' => 'graduated-students-body']) ?>
                <?= td_empty("Loading…", 5) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
</div>

<?php 
$url = url();
$scripts = <<<HTML
<script>
const url = "$url";
function loadGraduationStats() {
    $.post(url + 'admin/ajax/student.php', {
        submit: 'get_graduation_stats',
        response_type: 'json'
    }, function(res) {
        if (res.status && res.data) {
            $('#total-graduated').text(res.data.total != null ? res.data.total : 0);
            $('#this-year-graduated').text(res.data.this_year != null ? res.data.this_year : 0);
        }
    }, 'json');
}

function loadGraduatedStudents() {
    $.post(url + 'admin/ajax/student.php', {
        submit: 'fetch_graduated_students',
        response_type: 'json',
        program_id: $('#filter-program').val(),
        from_date: $('#filter-from-date').val(),
        to_date: $('#filter-to-date').val()
    }, function(res) {
        var body = '';
        if (res.status && res.data && res.data.students && res.data.students.length) {
            res.data.students.forEach(function(s) {
                body += '<tr>';
                body += '<td>' + $('<div/>').text(s.index_number || '').html() + '</td>';
                body += '<td>' + $('<div/>').text(s.fullname || '').html() + '</td>';
                body += '<td>' + $('<div/>').text(s.program_name || '').html() + '</td>';
                body += '<td>' + $('<div/>').text(s.graduation_date || '').html() + '</td>';
                body += '<td>' + $('<div/>').text(s.status || '').html() + '</td>';
                body += '</tr>';
            });
        } else {
            body = '<tr><td colspan="5" class="px-4 py-2 text-center text-gray-500">No records</td></tr>';
        }
        $('#graduated-students-body').html(body);
    }, 'json');
}

function loadClearanceForStudent(sid, label) {
    $('#clearance-student-id').val(sid);
    $('#clearance-student-label').text(label);
    $('#clearance-detail').removeClass('hidden');
    $.post(url + 'admin/ajax/student.php', {
        submit: 'fetch_student_clearances',
        response_type: 'json',
        student_id: sid
    }, function(res) {
        if (!res.status || !res.data || !res.data.clearances) return;
        var h = '';
        res.data.clearances.forEach(function(c) {
            var key = c.department_key;
            h += '<div class="clearance-row flex flex-wrap items-center gap-2 p-3 border dark:border-gray-600 rounded" data-key="'+$('<div/>').text(key).html()+'">';
            h += '<span class="font-medium w-40">' + $('<div/>').text(c.label || key).html() + '</span>';
            h += '<span class="text-xs text-gray-500">' + $('<div/>').text(c.cleared_at || '—').html() + '</span>';
            h += '<select class="clearance-status rounded text-sm border-gray-300 dark:bg-gray-700">';
            ['pending','cleared','not_required'].forEach(function(st){
                h += '<option value="'+st+'"'+(c.status===st?' selected':'')+'>'+st+'</option>';
            });
            h += '</select>';
            h += '<button type="button" class="save-clearance px-2 py-1 text-xs bg-purple-600 text-white rounded">Save</button>';
            h += '</div>';
        });
        $('#clearance-rows').html(h);
    }, 'json');
}

$(document).ready(function(){
    loadGraduationStats();
    loadGraduatedStudents();

    $('#filter-program, #filter-from-date, #filter-to-date').on('change', loadGraduatedStudents);

    $('#clearance-search-btn').on('click', function(){
        var q = $('#clearance-student-search').val().trim();
        if (q.length < 1) return;
        $.post(url + 'admin/ajax/student.php', {
            submit: 'search_students',
            response_type: 'json',
            q: q,
            limit: 15
        }, function(res) {
            if (!res.status || !res.data || !res.data.students || !res.data.students.length) {
                $('#clearance-search-list').html('<p class="p-2 text-gray-500">No matches</p>').removeClass('hidden');
                return;
            }
            var h = '<ul class="divide-y dark:divide-gray-600">';
            res.data.students.forEach(function(s) {
                var label = s.index_number + ' — ' + s.fullname;
                h += '<li class="p-2"><button type="button" class="text-purple-600 hover:underline pick-clearance-student" data-id="'+s.id+'">'+$('<div/>').text(label).html()+'</button></li>';
            });
            h += '</ul>';
            $('#clearance-search-list').html(h).removeClass('hidden');
        }, 'json');
    });

    $(document).on('click', '.pick-clearance-student', function(){
        var id = $(this).data('id');
        var t = $(this).text();
        loadClearanceForStudent(id, t);
    });

    $(document).on('click', '.save-clearance', function(){
        var row = $(this).closest('.clearance-row');
        var key = row.data('key');
        var sid = $('#clearance-student-id').val();
        var st = row.find('select.clearance-status').val();
        $.post(url + 'admin/submit.php', {
            submit: 'save_clearance_department',
            response_type: 'json',
            student_id: sid,
            department_key: key,
            clearance_status: st,
            notes: ''
        }, function(res) {
            if (res.status) loadClearanceForStudent(sid, $('#clearance-student-label').text());
            else alert('Could not save');
        }, 'json');
    });
});
</script>
HTML;

$content = ob_get_clean();
require relative_path('layouts/auth.php');
