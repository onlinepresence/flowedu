<?php
require_once relative_path("includes/components.php");

$title = 'Setup Departments'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
<form action="<?= url("admin/submit.php") ?>" method="POST">
    <?= form_body_start() ?>
        <!-- Department Name -->
        <?= input("text", "Department Name", "name", required: true, attributes: placeholder("Name of the department")); ?>

        <!-- Faculty Dropdown -->
        <?php
            // Fetch faculties from the database
            $faculties = faculties();
            $faculty_options = $faculties ? pluck($faculties, "id", "name") : [];
            echo select("faculty_id", "Department Faculty", $faculty_options, true);
        ?>

        <!-- show if there are department_heads available -->
        <?php if($department_heads = department_heads(columns: ["user_id as id, CONCAT(lastname, ' ', othernames) as name"], complete: true)): ?>
            <?= select(
                "hod", "Head of Department", $department_heads, keys: select_keys("id", "name"), 
                nullable: "Select A Department Head",
                attributes: attribute("class", "w-full")
                ); ?>
        <?php endif; ?>
    <?= form_body_end() ?>

    <!-- Submit Button -->
    <div class="mt-4 sm:w-48">
        <?= button("submit", "Add Department", "submit", "create_department", "blue") ?>
    </div>
</form>

<!-- List of departments -->
<div class="mt-8"></div>
<?= table_start(); ?>
    <?= thead_start() ?>
        <?php 
            echo th("Name of Department");
            echo th("Faculty");
            echo th("Head of Department");
            echo th();
        ?>
    <?= thead_end() ?>
    <?= tbody_start() ?>
        <?php if($departments = departments(complete: true)):
            foreach($departments as $department) :
        ?>
            <?= tr_start(); ?>
                <?php 
                    $action = "
                        <div class=\"flex gap-2 items-center\">
                            <i @click=\"openModal\" data-id=\"{$department['id']}\" data-modal-body=\"update-body\" data-show-footer=\"0\" class=\"fas action-btn fa-pen text-blue-500 hover:text-blue-600 cursor-pointer action-edit\" title=\"Edit\"></i>
                            <i @click=\"openModal\" data-id=\"{$department['id']}\" data-modal-body=\"delete-body\" data-show-footer=\"1\" class=\"fas action-btn fa-trash-can text-red-500 hover:text-red-600 cursor-pointer action-delete\" title=\"Delete\"></i>
                        </div>
                    ";
                ?>
                <?= td($department["name"]); ?>
                <?= td($department["faculty_id"] ? $department["faculty_name"] : "Not Set", attributes: data_attr("faculty-id", $department["faculty_id"])); ?>
                <?= td($department["hod"] ? $department["lastname"].' '.$department["othernames"] : "Not Set", attributes: data_attr("hod", $department["hod"])); ?>
                <?= td($action) ?>
            <?= tr_end(); ?>
        <?php endforeach; else: echo td_empty("No departments have been set yet", 4); endif; ?>
    <?= tbody_end() ?>
<?= table_end(); ?>

<?php echo modal_start( attribute("id", "modal")); echo modal_header(); ?>
    <!-- update section -->
    <div id="update-body" class="hidden modal-body">
        <?= modal_body_start(); ?>
            <?= modal_title("Update Department Info") ?>
            
            <form action="<?= url("admin/submit.php") ?>" method="POST">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Department Name -->
                    <?= input("text", "Department Name", "name", required: true, attributes: placeholder("Name of the department")); ?>

                    <!-- Faculty Dropdown -->
                    <?php
                        // Fetch faculties from the database
                        $faculties = faculties();
                        $faculty_options = $faculties ? pluck($faculties, "id", "name") : [];
                        echo select("faculty_id", "Department Faculty", $faculty_options, true);
                    ?>

                    <!-- show if there are department_heads available -->
                    <?php if(isset($department_heads) && $department_heads): ?>
                        <?= select(
                            "hod", "Head of Department", $department_heads, keys: select_keys("id", "name"), 
                            nullable: "Select A Department Head",
                            attributes: attribute("class", "w-full")
                            ); ?>
                    <?php endif; ?>

                    <!-- department id -->
                    <?= input("hidden", name: "department_id") ?>
                </div>

                <!-- submit button -->
                 <div class="mt-4">
                    <?= button("submit", "Update", "submit", "update_department") ?>
                 </div>
            </form>
            
        <?= modal_body_end(); ?>
    </div>

    <!-- delete section -->
    <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("departments", form_action: url("admin/submit.php"), 
            delete_text: "This will remove all associated courses and other records for this page. Proceed to delete this department?") ?>
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

        $(".action-edit").click(function(){
            const parent = $(this).closest("tr");
            const id = $(this).attr("data-id");
            const name = $.trim(parent.find("td:nth-child(1)").text());
            const faculty = parent.find("td:nth-child(2)").data("faculty-id") || "";
            const hod = parent.find("td:nth-child(3)").data("hod") || "";

            $("#update-body input[name=name]").val(name);
            $("#update-body select[name=faculty_id]").val(faculty).change();
            $("#update-body select[name=hod]").val(hod).change();
            $("#update-body input[name=department_id]").val(id);
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
