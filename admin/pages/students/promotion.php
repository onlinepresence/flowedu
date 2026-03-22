<?php
require_once relative_path("includes/components.php");
require_once relative_path("includes/settings_functions.php");

$title = 'Student Promotion';

$ctx = current_session_and_semester();
$currentSession = $ctx['session'] ?? [];
$currentSemester = $ctx['semester'] ?? [];
$hasCurrentSession = !empty($currentSession['id']);
$promotionMode = get_setting('students.promotion_mode', 'auto');

ob_start();
?>

<?php if (!$hasCurrentSession): ?>
<div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
    <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
        <i class="fas fa-lock mr-2"></i>Promotion unavailable
    </h3>
    <?= information_bar(
        "Set a current academic session before managing student promotion. Go to Academic Sessions and mark the active year as current.",
        "warning",
        false,
        attribute("class", "mb-4")
    ) ?>
    <a href="<?= url('admin/academic/sessions') ?>" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700">
        <i class="fas fa-calendar-alt mr-2"></i>Manage academic sessions
    </a>
</div>
<?php else: ?>

<div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
    <h3 class="mb-2 text-lg font-semibold text-gray-700 dark:text-gray-200">Current academic context</h3>
    <p class="text-sm text-gray-600 dark:text-gray-400">
        <strong>Session:</strong> <?= htmlspecialchars($currentSession['name'] ?? '—') ?>
        <?php if (!empty($currentSemester['name'])): ?>
            &nbsp;·&nbsp; <strong>Active semester:</strong> <?= htmlspecialchars($currentSemester['name']) ?>
        <?php endif; ?>
    </p>
</div>

<div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
    <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
        <i class="fas fa-sliders-h mr-2"></i>Promotion mode
    </h3>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        <strong>Automatic</strong> — the system worker promotes eligible students (approved, not graduated, below final year) once per run when a current session exists.
        <strong>Manual</strong> — you preview and confirm promotions in bulk.
    </p>
    <div class="flex flex-wrap gap-6 items-center">
        <label class="inline-flex items-center cursor-pointer">
            <input type="radio" name="promotion_mode_ui" value="auto" class="mr-2" <?= $promotionMode === 'auto' ? 'checked' : '' ?>>
            <span>Automatic</span>
        </label>
        <label class="inline-flex items-center cursor-pointer">
            <input type="radio" name="promotion_mode_ui" value="manual" class="mr-2" <?= $promotionMode === 'manual' ? 'checked' : '' ?>>
            <span>Manual</span>
        </label>
        <button type="button" id="save-promotion-mode" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700">
            Save mode
        </button>
        <span id="promotion-mode-msg" class="text-sm text-gray-500"></span>
    </div>
</div>

<div id="promotion-auto-panel" class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 <?= $promotionMode === 'manual' ? 'hidden' : '' ?>">
    <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
        <i class="fas fa-robot mr-2"></i>Automatic promotion
    </h3>
    <?= information_bar(
        "While this mode is on, the system will automatically run promotion for the current academic session. Eligible students move up one level (100→200, etc.) until they reach their program’s final year. Re-runs skip students already promoted for the same session and transition.",
        "info",
        false,
        attribute("class", "mb-4")
    ) ?>
</div>

<div id="promotion-manual-panel" class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 <?= $promotionMode === 'auto' ? 'hidden' : '' ?>">
    <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
        <i class="fas fa-graduation-cap mr-2"></i>Manual bulk promotion
    </h3>
    <?= information_bar(
        "Preview students matching your filters, refine with search, then confirm. Only checked students are promoted.",
        "info",
        false,
        attribute("class", "mb-4")
    ) ?>

    <form id="promotion-form">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php
                $levels = [
                    ["id" => 100, "text" => "Level 100"],
                    ["id" => 200, "text" => "Level 200"],
                    ["id" => 300, "text" => "Level 300"],
                    ["id" => 400, "text" => "Level 400"]
                ];
            ?>
            <?= select("from_level", "From Level", $levels, "Select Level", required: true) ?>
            <?= select("to_level", "To Level", $levels, "Select Level", required: true) ?>
            <?php $programs = programs(); ?>
            <?= select("program_id", "Program (Optional)", $programs, "All Programs", keys: select_keys("id", "name")) ?>
        </div>

        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Find students (index or name)</label>
            <div class="flex gap-2 flex-wrap">
                <input type="text" id="promotion-student-search" class="flex-1 min-w-[200px] rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600" placeholder="Type to search…" autocomplete="off">
                <button type="button" id="promotion-search-btn" class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-600 rounded">Search</button>
            </div>
            <div id="promotion-search-results" class="mt-2 max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded hidden"></div>
        </div>

        <div class="mt-6">
            <?= button("button", "Preview promotion", "", "", "purple", attribute("id", "preview-promotion")) ?>
        </div>
    </form>
</div>

<div id="promotion-preview" class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 hidden">
    <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
        <i class="fas fa-list mr-2"></i>Promotion preview
    </h3>
    <p class="text-sm mb-2 text-gray-600 dark:text-gray-400"><span id="preview-total">0</span> student(s). Uncheck anyone to exclude.</p>
    <?= table_start() ?>
        <?= thead_start() ?>
            <?= th("") ?>
            <?= th("Index") ?>
            <?= th("Name") ?>
            <?= th("Level") ?>
            <?= th("New level") ?>
            <?= th("Program") ?>
        <?= thead_end() ?>
        <?= tbody_start(['id' => 'promotion-preview-body']) ?>
        <?= tbody_end() ?>
    <?= table_end() ?>
    <div class="flex gap-4 mt-6">
        <?= button("button", "Cancel", "cancel_promotion", "", "gray", attribute("id", "cancel-promotion")) ?>
        <?= button("button", "Confirm promotion", "", "", "green", attribute("id", "confirm-promotion")) ?>
    </div>
</div>

<?php endif; ?>

<?php
$promotionModeJson = json_encode($promotionMode);
$url = url();
$scripts = <<<HTML
<script>
$(function(){
    var mode = {$promotionModeJson};
    const url = "$url"

    function togglePanels(m) {
        if (m === 'auto') {
            $('#promotion-auto-panel').removeClass('hidden');
            $('#promotion-manual-panel').addClass('hidden');
        } else {
            $('#promotion-auto-panel').addClass('hidden');
            $('#promotion-manual-panel').removeClass('hidden');
        }
    }

    $('input[name="promotion_mode_ui"]').on('change', function(){
        mode = $(this).val();
        togglePanels(mode);
    });

    $('#save-promotion-mode').on('click', function(){
        var m = $('input[name="promotion_mode_ui"]:checked').val();
        $.post(url + 'admin/submit.php', {
            submit: 'save_promotion_settings',
            response_type: 'json',
            promotion_mode: m
        }, function(res){
            if (res.status) {
                $('#promotion-mode-msg').text(res.data && res.data.message ? res.data.message : 'Saved').removeClass('text-red-600').addClass('text-green-600');
                mode = m;
                togglePanels(mode);
            } else {
                $('#promotion-mode-msg').text('Could not save').removeClass('text-green-600').addClass('text-red-600');
            }
        }, 'json');
    });

    var searchTimer;
    $('#promotion-student-search').on('input', function(){
        clearTimeout(searchTimer);
        var q = $(this).val().trim();
        searchTimer = setTimeout(function(){ runStudentSearch(q); }, 350);
    });
    $('#promotion-search-btn').on('click', function(){
        runStudentSearch($('#promotion-student-search').val().trim());
    });

    function runStudentSearch(q) {
        if (q.length < 1) { $('#promotion-search-results').addClass('hidden').empty(); return; }
        var pid = $('select[name="program_id"]').val() || '';
        $.post(url + 'admin/ajax/student.php', {
            submit: 'search_students',
            response_type: 'json',
            q: q,
            program_id: pid
        }, function(res){
            if (!res.status || !res.data || !res.data.students) return;
            var h = '<ul class="text-sm divide-y dark:divide-gray-600">';
            res.data.students.forEach(function(s){
                h += '<li class="py-1 flex justify-between gap-2"><span>' + $('<div/>').text(s.index_number + ' — ' + s.fullname).html() + '</span>';
                h += '<button type="button" class="text-purple-600 text-xs add-to-preview" data-id="'+s.id+'" data-index="'+$('<div/>').text(s.index_number).html()+'" data-name="'+$('<div/>').text(s.fullname).html()+'" data-level="'+s.current_year+'" data-program="'+$('<div/>').text(s.program_name||'').html()+'">Add</button></li>';
            });
            h += '</ul>';
            $('#promotion-search-results').html(h).removeClass('hidden');
        }, 'json');
    }

    var manualRows = [];

    $(document).on('click', '.add-to-preview', function(){
        var id = $(this).data('id');
        if (manualRows.some(function(r){ return r.id == id; })) return;
        manualRows.push({
            id: id,
            index_number: $(this).data('index'),
            fullname: $(this).data('name'),
            current_year: $(this).data('level'),
            program_name: $(this).data('program')
        });
    });

    $('#preview-promotion').on('click', function(){
        var fromL = $('select[name="from_level"]').val();
        var toL = $('select[name="to_level"]').val();
        if (!fromL || !toL) { alert('Select from and to levels'); return; }
        $.post(url + 'admin/submit.php', $('#promotion-form').serialize() + '&submit=preview_promotion&response_type=json', function(res){
            if (!res.status) { alert(res.errors && Object.values(res.errors)[0] || 'Preview failed'); return; }
            var students = res.data.students || [];
            if (manualRows.length) {
                var ids = manualRows.map(function(r){ return String(r.id); });
                students = students.filter(function(s){ return ids.indexOf(String(s.id)) >= 0; });
            }
            renderPreview(students, fromL, toL);
        }, 'json');
    });

    function renderPreview(students, fromL, toL) {
        var body = '';
        students.forEach(function(s){
            body += '<tr>';
            body += '<td><input type="checkbox" class="promo-pick" name="student_ids[]" value="'+s.id+'" checked></td>';
            body += '<td>' + $('<div/>').text(s.index_number).html() + '</td>';
            body += '<td>' + $('<div/>').text(s.fullname).html() + '</td>';
            body += '<td>' + $('<div/>').text(s.current_year).html() + '</td>';
            body += '<td>' + $('<div/>').text(toL).html() + '</td>';
            body += '<td>' + $('<div/>').text(s.program_name||'').html() + '</td>';
            body += '</tr>';
        });
        $('#promotion-preview-body').html(body);
        $('#preview-total').text(students.length);
        $('#promotion-preview').removeClass('hidden');
    }

    $('#cancel-promotion').on('click', function(){
        $('#promotion-preview').addClass('hidden');
        $('#promotion-preview-body').empty();
    });

    $('#confirm-promotion').on('click', function(){
        var picks = $('.promo-pick:checked').map(function(){ return $(this).val(); }).get();
        if ($('#promotion-preview-body tr').length && picks.length < 1) {
            alert('Select at least one student, or cancel.');
            return;
        }
        var data = $('#promotion-form').serializeArray();
        data.push({name: 'submit', value: 'confirm_promotion'});
        data.push({name: 'response_type', value: 'json'});
        picks.forEach(function(id){ data.push({name: 'student_ids[]', value: id}); });
        $.post(url('admin/submit.php'), data, function(res){
            if (res.status) {
                alert(res.data.message || 'Done');
                $('#promotion-preview').addClass('hidden');
            } else {
                alert(res.errors && Object.values(res.errors)[0] || 'Failed');
            }
        }, 'json');
    });
});
</script>
HTML;

$content = ob_get_clean();
require relative_path('layouts/auth.php');
