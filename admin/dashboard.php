<?php
require_once relative_path("includes/components.php");

$title = 'Your Dashboard'; // Set the page title

// Start output buffering to capture the content
ob_start();

// get all unapproved students
$unapproved_students = fetchData(
    "user_id, index_number, profile_pic, CONCAT(s.lastname,' ', s.othernames) AS fullname, gender, p.name as program_name, p.department_id, s.created_at, g.id as guardian",
    [
        ["join" => "students programs", "on" => "program_id id", "alias" => "s p"],
        ["join" => "students parent_guardians", "on" => "id student_id", "alias" => "s g"]
    ],
    "s.approved = 0", 0, join_type: "LEFT"
);
?>
    <!-- cards -->
    <?= card_container_start() ?>
        <?= dashboard_card_btn("Pending Students", fetchData("COUNT(id) as total", "students", "approved = 0")["total"], "fas fa-user-clock") ?>
        <?= dashboard_card_btn("Approved Students", fetchData("COUNT(id) as total", "students", "approved = 1")["total"], "fas fa-user-check", "green") ?>
    <?= card_container_end() ?>

    <!-- student approval table -->
    <?= table_start() ?>
        <?= thead_start() ?>
            <?= th("Admission Number") ?>
            <?= th("Name") ?>
            <?= th("Gender") ?>
            <?= th("Chosen Program") ?>
            <?= th("Guardian Information", attribute("title", "Provided guardian information")) ?>
            <?= th("Submission Date") ?>
            <?= th() ?>
        <?= thead_end() ?>

        <?= tbody_start() ?>
            <?php if($unapproved_students): foreach($unapproved_students as $student): 
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
                    <?= td(date("d-m-Y",strtotime($student["created_at"]))) ?>
                    <?= td($action) ?>
                <?= tr_end() ?>
            <?php endforeach; else:
                echo td_empty("No unapproved students found", 7); 
            endif;
            ?>
        <?= tbody_end() ?>
    <?= table_end() ?>


    <?php echo modal_start( array_merge(
        attribute("id", "modal"),
        attribute("class", "max-h-[80%] overflow-y-auto max-w-[80%]")    
    )); echo modal_header(); ?>
        <!-- view section -->
        <div id="view-body" class="modal-body">
            <?= modal_body_start(); ?>
                <?= modal_title("In Progress") ?>
                <form action="<?= url("admin/submit.php") ?>">
                    <?php require relative_path("student/setup/personal-form.php"); ?>
                </form>
            <?= modal_body_end(); ?>
        </div>
    <?= modal_end() ?>

<?php $scripts = <<<HTML
<script>
    $(document).ready(function(){
        $("#student-form-grid input").prop("readonly", true);
        $("#student-form-grid select, #student-form-grid input[type=file]").prop("disabled", true);

        // approve
        $(".approve").click(function(){
            const index_number = $(this).attr("data-index-number");
            const guardian_status = $(this).attr("data-guardian-status");
            const user_id = $(this).attr("data-user-id");

            if(parseInt(guardian_status) == 0){
                alert("Guardian information not provided yet. Cannot approve");
            }else{
                window.location.replace("/admin/approve-student/" + index_number + "/" + guardian_status + "/" + user_id);
            }
        })
    })
</script>
HTML;

?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
