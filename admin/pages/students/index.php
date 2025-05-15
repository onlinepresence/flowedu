<?php
require_once relative_path("includes/components.php");

$title = 'Students Data'; // Set the page title

// Start output buffering to capture the content
ob_start();

// get all students (approved) from the system

?>

<!-- filters -->
<div>
    <h2 class="text-lg font-bold mb-2">Filters</h2>
    <div class="flex gap-4">
        <?= 
            select("level", "Student Level", [
                ["id" => 100, "text" => "Level 100"],
                ["id" => 200, "text" => "Level 200"],
                ["id" => 300, "text" => "Level 300"],
                ["id" => 400, "text" => "Level 400"]
            ], "All Levels", attributes: attribute("class", "w-full"))
        ?>
        <?php 
            $faculties = faculties();
            echo select("faculty", "Faculty", $faculties, "All Faculties", keys: select_keys("id", "name"), attributes: attribute("class", "w-full"))
        ?>
        <?php 
            $departments = departments();
            echo select("department", "Department", $departments, "All Departments", keys: select_keys("id", "name"), attributes: attribute("class", "w-full"))
        ?>
        <?php 
            $programs = programs();
            echo select("program", "Program", $programs, "All Programs", keys: select_keys("id", "name"), attributes: attribute("class", "w-full"))
        ?>
    </div>
    <div class="mt-4 max-w-52 flex gap-2">
        <?= button("button", "Search") ?>
        <?= button("button", "Download") ?>
    </div>
</div>

<!-- results to be displayed in table -->
<?= table_start(attribute("class", "mt-3")) ?>
    <?= thead_start() ?>
        <?= th("Index Number") ?>
        <?= th("Name") ?>
        <?= th("Gender") ?>
        <?= th("Program") ?>
        <?= th("Actions") ?>
    <?= thead_end() ?>

    <?= tbody_start() ?>
        <?php if((1+1) == 3): foreach($unapproved_students as $student): 
            $action = "
                <div class=\"flex gap-2 items-center\">
                    <i data-user-id=\"{$student['user_id']}\" data-index-number=\"{$student['index_number']}\" data-guardian-status=\"".intval($student["guardian"])."\" class=\"fas approve fa-check text-green-500 hover:text-green-600 cursor-pointer\" title=\"Approve\"></i>
                    <i @click=\"openModal\" data-id=\"{$student['user_id']}\" class=\"fas view fa-eye text-blue-500 hover:text-blue-600 cursor-pointer\" title=\"View\"></i>
                </div>
            ";
        ?>
            <?= tr_start() ?>
                <?= td($student["index_number"], asset($student["profile_pic"],false)) ?>
                <?= td($student["fullname"]) ?>
                <?= td($student["gender"]) ?>
                <?= td($student["program_name"]) ?>
                <?= td($student["guardian"] ? "Provided" : "Not Provided") ?>
                <?= td(date("M d, Y",strtotime($student["created_at"]))) ?>
                <?= td($action) ?>
            <?= tr_end() ?>
        <?php endforeach; else:
            echo td_empty("Development Ongoing...", 5); 
        endif;
        ?>
    <?= tbody_end() ?>
<?= table_end() ?>

<?php $scripts = <<<HTML
<script>
    $(document).ready(function(){
        
    })
</script>
HTML;
?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
