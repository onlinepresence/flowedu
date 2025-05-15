<?php
require_once relative_path("includes/components.php");

$title = 'Setup Programs'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
<?php if(isset($_SESSION["admin_register"]) && $_SESSION["admin_register"]): ?>
<form action="<?= url("admin/submit.php") ?>" method="POST">
    <?= form_body_start() ?>
        <!-- Program Name -->
        <?= input("text", "Program Name", "name", required: true, attributes: placeholder("Name of the program")); ?>

        <!-- Program price -->
        <?= input("number", "Program Fee", "cost", required: true, attributes: array_merge(placeholder("0.00"), attribute("step", 0.01))); ?>
        
        <!-- Program certificate -->
        <?= input("text", "Program Certification", "certificate", required: true, attributes: placeholder("Eg. Bachelor of Education (B.Ed)")); ?>

        <?php 
            $departments = departments();
            $departments = $departments ? pluck($departments, "id", "name") : ["" => "No Departments created"];
            echo select("department_id", "Course's Department", $departments, required: true)
        ?>
        
    <?= form_body_end() ?>

    <!-- Submit Button -->
    <div class="mt-4 sm:w-48">
        <?= button("submit", "Add Program", "submit", "create_program", "blue") ?>
    </div>
</form>
<?php else: ?>
    <div class="max-w-96 w-72">
        <?= button("button", "Add New Program", attributes: array_merge(
            attribute("@click", "openModal"), 
            data_attr("modal-body", "form-body"),
            attribute("id", "add-program-button"),
            attribute("class", "action-btn")
        )) ?>
    </div>
<?php endif; ?>

<!-- list of programs -->
<div class="mt-8"></div>
<?= table_start(); ?>
    <?= thead_start() ?>
        <?php 
            echo th("Name of Program");
            echo th("Certification");
            echo th("Cost of Program");
            echo th("Department");
            echo th();
        ?>
    <?= thead_end() ?>
    <?= tbody_start() ?>
        <?php if($programs = programs(complete: true)): 
            foreach($programs as $program):
        ?>
            <?= tr_start(); ?>
                <?php 
                    $action = "
                        <div class=\"flex gap-2 items-center\">
                            <i @click=\"openModal\" data-id=\"{$program['id']}\" data-modal-body=\"form-body\" data-show-footer=\"0\" class=\"fas action-btn fa-pen text-blue-500 hover:text-blue-600 cursor-pointer action-edit\" title=\"Edit\"></i>
                            <i @click=\"openModal\" data-id=\"{$program['id']}\" data-modal-body=\"delete-body\" data-show-footer=\"1\" class=\"fas action-btn fa-trash-can text-red-500 hover:text-red-600 cursor-pointer action-delete\" title=\"Delete\"></i>
                        </div>
                    ";
                ?>
                <?= td($program["name"]); ?>
                <?= td($program["certificate"]); ?>
                <?= td("GHC ".number_format($program["cost"], 2)); ?>
                <?= td($program["department_name"], attributes: data_attr("department-id", $program["department_id"])); ?>
                <?= td($action) ?>
            <?= tr_end(); ?>
        <?php endforeach; else: echo td_empty("No programs have been set yet", 5); endif; ?>
    <?= tbody_end() ?>
<?= table_end(); ?>

<?php echo modal_start( attribute("id", "modal")); echo modal_header(); ?>
    <!-- update section -->
    <div id="form-body" class="hidden modal-body">
        <?= modal_body_start(); ?>
            <?= modal_title("", attribute("id", "modal-title")) ?>
            
            <form action="<?= url("admin/submit.php") ?>" name="program-form" method="POST">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    
                    <!-- program id for update -->
                    <?= input("hidden", name: "program_id") ?>

                    <!-- Program Name -->
                    <?= input("text", "Program Name", "name", required: true, attributes: placeholder("Name of the program")); ?>

                    <!-- Program price -->
                    <?= input("number", "Program Fee", "cost", required: true, attributes: array_merge(placeholder("0.00"), attribute("step", 0.01))); ?>
                    
                    <!-- Program certificate -->
                    <?= input("text", "Program Certification", "certificate", required: true, attributes: placeholder("Eg. Bachelor of Education (B.Ed)")); ?>

                    <?php 
                        $departments = departments();
                        $departments = $departments ? pluck($departments, "id", "name") : ["" => "No Departments created"];
                        echo select("department_id", "Course's Department", $departments, required: true)
                    ?>
                    
                </div>

                <!-- Submit Button -->
                <div class="mt-4 sm:w-48">
                    <?= button("submit", "Add Program", "submit", "create_program", "blue") ?>
                </div>
            </form>
            
        <?= modal_body_end(); ?>
    </div>

    <!-- delete section -->
    <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("programs", form_action: url("admin/submit.php"), 
            delete_text: "This will remove all associated records for this course program. Proceed to delete this department?") ?>
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

        $("#add-program-button").click(function(){
            $("#form-body input[name=name]").val("");
            $("#form-body input[name=cost]").val("");
            $("#form-body input[name=certificate]").val("");
            $("#form-body select[name=department_id]").val("").change();
            $("#form-body input[name=program_id]").val("");
            
            $("#modal-title").text("Add New Program");
            $("#form-body button[name=submit]").val("create_program").html("Add Program");
        });

        $(".action-edit").click(function(){
            $("#modal-title").text("Update Program");
            $("#form-body button[name=submit]").val("update_program").html("Update Program");

            const parent = $(this).closest("tr");
            const id = $(this).attr("data-id");
            const name = $.trim(parent.find("td:nth-child(1)").text());
            const certificate = $.trim(parent.find("td:nth-child(2)").text());
            const cost = $.trim(parent.find("td:nth-child(3)").text().replace("GHC ", "").replace(",", ""));
            const department = parent.find("td:nth-child(4)").data("department-id") || "";

            $("#form-body input[name=name]").val(name);
            $("#form-body input[name=certificate]").val(certificate);
            $("#form-body input[name=cost]").val(cost);
            $("#form-body select[name=department_id]").val(department).change();
            $("#form-body input[name=program_id]").val(id);
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
