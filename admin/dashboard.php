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
                    <?= td(date("M d, Y",strtotime($student["created_at"]))) ?>
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
            <?= modal_body_start(
                attribute("style", "max-width: 1024px")
            ); ?>
                <?= modal_title("In Progress") ?>
                <form id="modal-form">
                    <?php require relative_path("student/setup/personal-form.php"); ?>
                    <?= fieldset_start(array_merge(
                        attribute("id", "guardian-form"), attribute("class", "mt-6")
                    )) ?>
                        <?= fieldset_legend("Guardian Information") ?>
                        <?php require_once relative_path("/student/setup/guardian-form.php") ?>
                    <?= fieldset_end() ?>

                    <!-- Submit Button -->
                    <div class="mt-4 w-full flex gap-4">
                        <?= button("button", "Approve", color: "blue", attributes: attribute("class", "approve")) ?>
                        <?= button("reset", "Cancel", color: "red", attributes: array_merge(
                            attribute("class", "reject"), attribute("@click", "closeModal()")
                        )) ?>
                    </div>
                </form>
                <p id="modal-load-element" class="hidden text-center gap-4 mt-4 border py-6 px-4">
                    <i class="fas fa-spinner animate-spin"></i>
                    <span>Fetching Student details</span>
                </p>
                <p class="hidden text-center border py-6 px-4" id="modal-status"></p>
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

        $(".view").click(function(){
            const student_id = $(this).data("id");
            
            ajaxCall({
                url: "/admin/submit.php",
                data: {submit: "fetch_user", id: student_id, type: "student"},
                beforeSend: function(){
                    // hide student form
                    $("#modal-form, #modal-status").addClass("hidden");
                    $("#modal-load-element").removeClass("hidden").addClass("show");
                }
            }).then((response) => {
                setTimeout(() => {
                    if(response.status){
                        const data = response.data;
                        fill_form(data.student, $("#student-form-grid"), {
                            profile_pic: "View Profile Picture"
                        });

                        if(data.guardian)
                            fill_form(data.guardian, $("#guardian-form"));
                        
                        $("#modal-form .approve").attr({
                            "data-index-number": data.student.index_number,
                            "data-guardian-status": data.guardian ? 1 : 0,
                            "data-user-id": data.student.user_id
                        })
                        $("#modal-form").removeClass("hidden");
                        $("#modal-load-element").addClass("hidden");
                    }else{
                        $("#modal-load-element").addClass("hidden");
                        $("#modal-status").removeClass("hidden").html(response.errors.system_message)
                    }
                }, 500);              
                
            }).error((error) => {
                console.log(error);
                
            })
            
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
