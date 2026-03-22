<?php
require_once relative_path("includes/components.php");
$title = 'Attendance Reports';
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-chart-pie mr-2"></i>Attendance Reports
        </h3>
        
        <form action="<?= url('admin/submit.php') ?>" method="POST">
            <?= input("hidden", "", "request_type", "generate_attendance_report") ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php $programs = programs(); ?>
                <?= select("program_id", "Program", $programs, "All Programs", keys: select_keys("id", "name")) ?>
                <?php $levels = [["id" => "", "text" => "All Levels"], ["id" => 100, "text" => "Level 100"], ["id" => 200, "text" => "Level 200"], ["id" => 300, "text" => "Level 300"], ["id" => 400, "text" => "Level 400"]]; ?>
                <?= select("level", "Level", $levels, "All Levels") ?>
                <?php $sessions = fetchData("*", "academic_sessions", "", 0, "", "", "", "name", true);
                $session_options = [["id" => "", "text" => "All Sessions"]];
                if(is_array($sessions) && !empty($sessions)) {
                    foreach($sessions as $session) {
                        $session_options[] = ["id" => $session['id'], "text" => $session['name']];
                    }
                } ?>
                <?= select("session_id", "Session", $session_options, "All Sessions") ?>
            </div>
            <div class="mt-6">
                <?= button("submit", "Generate Report", "submit", "generate_attendance_report", "purple") ?>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require relative_path('layouts/auth.php');
?>
