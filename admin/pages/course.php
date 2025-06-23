<?php
require_once relative_path("includes/components.php");

$title = 'Courses'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
<div class="max-w-96 w-72">
    <?= button("button", "Add New Course", attributes: array_merge(
        attribute("@click", "openModal"), 
        data_attr("modal-body", "form-body"),
        attribute("id", "add-course-button"),
        attribute("class", "action-btn")
    )) ?>
</div>

<!-- list of programs -->
<div class="mt-8"></div>
<?= table_start(); ?>
    <?= thead_start() ?>
        <?php 
            $column_names = ["Course Name", "Course Code", "Program Name"];
            foreach($column_names as $column){
                echo th($column);
            }
            echo th();
        ?>
    <?= thead_end() ?>
    <?= tbody_start() ?>
        <?php if($courses = courses(complete: true)): 
            foreach($courses as $course):
        ?>
            <?= tr_start(); ?>
                <?php 
                    /*$action = "
                        <div class=\"flex gap-2 items-center\">
                            <i @click=\"openModal\" data-id=\"{$course['id']}\" data-modal-body=\"form-body\" data-show-footer=\"0\" data-course='".json_encode($course)."' class=\"fas action-btn fa-pen text-blue-500 hover:text-blue-600 cursor-pointer action-edit\" title=\"Edit\"></i>
                            <i @click=\"openModal\" data-id=\"{$course['id']}\" data-modal-body=\"delete-body\" data-show-footer=\"1\" class=\"fas action-btn fa-trash-can text-red-500 hover:text-red-600 cursor-pointer action-delete\" title=\"Delete\"></i>
                        </div>
                    ";*/
                    $action = "
                        <div class=\"flex gap-2 items-center\">
                            <i @click=\"openModal\" data-id=\"{$course['id']}\" data-modal-body=\"delete-body\" data-show-footer=\"1\" class=\"fas action-btn fa-trash-can text-red-500 hover:text-red-600 cursor-pointer action-delete\" title=\"Delete\"></i>
                        </div>
                    ";
                ?>
                <?= td($course["name"]); ?>
                <?= td($course["code"]); ?>
                <?= td($course["program_name"]); ?>
                <?= td($action) ?>
            <?= tr_end(); ?>
        <?php endforeach; else: echo td_empty("No courses have been set yet", (count($column_names) + 1)); endif; ?>
    <?= tbody_end() ?>
<?= table_end(); ?>

<?php echo modal_start( attribute("id", "modal")); echo modal_header(); ?>
    <!-- update section -->
    <div id="form-body" class="hidden modal-body">
        <?= modal_body_start(); ?>
            <?= modal_title("", attribute("id", "modal-title")) ?>
            
            <form action="<?= url("admin/submit.php") ?>" name="program-form" method="POST">
                <div class="grid gap-4 lg:gap-6">
                    <!-- course id for update -->
                    <?= input("hidden", name: "course_id") ?>

                    <!-- Course Name -->
                    <?= input("text", "Course Name", "name", required: true, attributes: placeholder("Name of the course")); ?>

                    <!-- Course Code -->
                    <?= input_h("text", "Course Code", "code", sub_text:"Keep blank if you want the system to manually define the code", attributes: placeholder("Enter course code")); ?>

                    <?php 
                        $programs = programs();
                        $programs = $programs ? pluck($programs, "id", "name") : ["" => "No Programs created"];
                        echo select("program_id", "Course Program", $programs, "Select A Program", required:true)
                    ?>

                    <?= 
                        select_h(
                            "course_semester", "Course Semester",[
                                ["id" => 1, "text" => "Semester 1"],
                                ["id" => 2, "text" => "Semester 2"]
                            ], "This will be used to automate course registrations for students",
                            "Select Course Semester"
                        )
                    ?>

                    <?= 
                        select_h("year_level", "Year Level", [
                            ["id" => 1, "text" => "Year 1"],
                            ["id" => 2, "text" => "Year 2"],
                            ["id" => 3, "text" => "Year 3"],
                            ["id" => 4, "text" => "Year 4"],
                        ],
                        "This will be used to automate course registrations for students",
                        "Select Year Level"
                        )
                    ?>
                    
                </div>

                <!-- Submit Button -->
                <div class="mt-4 sm:w-48">
                    <?= button("submit", "Add Course", "submit", "create_course", "blue") ?>
                </div>
            </form>
            
        <?= modal_body_end(); ?>
    </div>

    <!-- delete section -->
    <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("courses", form_action: url("admin/submit.php"), 
            delete_text: "This will remove all associated records for this course. Proceed to delete this course?") ?>
    </div>
<?= modal_end() ?>

<?php 
$extra_script = delete_item_component_script();
$scripts = <<<HTML
<script>
    $(document).ready(function(){
        $(".action-btn").click(function(){
            const modal_body = $(this).attr("data-modal-body");
            $("#modal .modal-body").addClass("hidden");
            $("#" + modal_body).removeClass("hidden");
        });

        $("#add-course-button").click(function(){
            $("#form-body input[name=code]").val("").attr("readonly", false);
            $("#form-body input[name=name]").val("").attr("readonly", false);
            $("#form-body select[name=program_id]").val("").change();
            $("#form-body input[name=course_id]").val("");
            
            $("#modal-title").text("Add New Course");
            $("#form-body button[name=submit]").val("create_course").html("Add Course");
        });

        $(".action-edit").click(function(){
            $("#modal-title").text("Update Course");
            $("#form-body button[name=submit]").val("update_course").html("Update Course");

            const parent = $(this).closest("tr");
            const course = JSON.parse($(this).attr("data-course"));

            $("#form-body input[name=name]").val(course.name).attr("readonly", true);
            $("#form-body input[name=code]").val(course.code).attr("readonly", true);
            $("#form-body select[name=program_id]").val(course.program_id).change();
            $("#form-body input[name=course_id]").val(course.id);
            $("#form-body select[name=course_semester]").val(course.course_semester).change();
            $("#form-body select[name=year_level]").val(course.year_level).change();
        });

        $extra_script
    })
</script>
HTML;
?>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
