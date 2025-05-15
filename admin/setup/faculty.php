<?php
require_once relative_path("includes/components.php");

$title = 'Setup Faculties'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>

<form action="<?= url("admin/submit.php") ?>" method="POST">
    <?= form_body_start() ?>
        <!-- Faculty Name -->
        <?= input("text", "Faculty Name", "name", required: true, attributes: array_merge(
            placeholder("Faculty of Arts"), attribute("class", "w-full"))
        ); ?>

        <!-- show if there are deans available -->
        <?php if($deans = deans(columns: ["user_id as id, CONCAT(lastname, ' ', othernames) as name"], complete: true)): ?>
            <?= select(
                "dean_id", "Dean", $deans, required: true, keys: select_keys("id", "name"), 
                nullable: "Select A Faculty Dean",
                attributes: array_merge(
                    placeholder("Select Dean"), attribute("class", "w-full"))
            ); ?>
        <?php endif; ?>
    <?= form_body_end() ?>

    <!-- Submit Button -->
    <div class="mt-4 sm:w-48">
        <?= button("submit", "Add Faculty", "submit", "create_faculty", "blue") ?>
    </div>
</form>

<!-- list of faculties -->
<div class="mt-8"></div>
<?= table_start(); ?>
    <?= thead_start() ?>
        <?php 
            echo th("Name of Faculty");
            echo th("Name of Dean");
            echo th();
        ?>
    <?= thead_end() ?>
    <?= tbody_start() ?>
        <?php if($faculties = faculties(complete: true)):
            foreach($faculties as $faculty):
        ?>
            <?= tr_start(); ?>
                <?php 
                    $action = "
                        <div class=\"flex gap-2 items-center\">
                            <i @click=\"openModal\" data-id=\"{$faculty['id']}\" data-modal-body=\"update-body\" data-show-footer=\"0\" class=\"fas action-btn fa-pen text-blue-500 hover:text-blue-600 cursor-pointer action-edit\" title=\"Edit\"></i>
                            <i @click=\"openModal\" data-id=\"{$faculty['id']}\" data-modal-body=\"delete-body\" data-show-footer=\"1\" class=\"fas action-btn fa-trash-can text-red-500 hover:text-red-600 cursor-pointer action-delete\" title=\"Delete\"></i>
                        </div>
                    ";
                ?>
                <?= td($faculty["name"]); ?>
                <?= td($faculty["dean_id"] ? $faculty["lastname"].' '.$faculty["othernames"] : "Not Set", attributes: $faculty["dean_id"] ? data_attr("dean-id", $faculty["dean_id"]) : []); ?>
                <?= td($action) ?>
            <?= tr_end(); ?>
        <?php endforeach; else: echo td_empty("No faculties have been set yet", 3); endif; ?>
    <?= tbody_end() ?>
<?= table_end(); ?>

<?php echo modal_start( attribute("id", "modal")); echo modal_header(); ?>
    <!-- update section -->
    <div id="update-body" class="hidden modal-body">
        <?= modal_body_start(); ?>
            <?= modal_title("Update Faculty Info") ?>
            
            <form action="<?= url("admin/submit.php") ?>" method="POST">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Faculty Name -->
                    <?= input("text", "Faculty Name", "name", required: true, attributes: array_merge(
                        placeholder("Faculty of Arts"), attribute("class", "w-full"))
                    ); ?>

                    <!-- faculty id -->
                    <?= input("hidden", name: "faculty_id") ?>

                    <!-- Show if there are deans available -->
                    <?php if(isset($deans) && !empty($deans)): ?>
                        <?= select(
                            "dean_id", "Dean", $deans, required: true, keys: select_keys("id", "name"), 
                            nullable: "Select A Faculty Dean",
                            attributes: array_merge(
                                placeholder("Select Dean"), attribute("class", "w-full"))
                        ); ?>
                    <?php endif; ?>
                </div>

                <!-- submit button -->
                 <div class="mt-4">
                    <?= button("submit", "Update", "submit", "update_faculty") ?>
                 </div>
            </form>
            
        <?= modal_body_end(); ?>
    </div>

    <!-- delete section -->
    <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("faculties", form_action: url("admin/submit.php"), 
            delete_text: "This will remove all associated departments and courses. Proceed to delete this faculty?") ?>
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
            const dean = parent.find("td:nth-child(2)").data("dean-id") || "";

            $("#update-body input[name=name]").val(name);
            $("#update-body select[name=dean_id]").val(dean).change();
            $("#update-body input[name=faculty_id]").val(id);
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
