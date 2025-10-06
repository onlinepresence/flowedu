<?php
require_once relative_path("includes/components.php");

$title = 'Teachers'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
<div class="max-w-96 w-72">
    <?= button("button", "Add New Teacher", attributes: array_merge(
        attribute("@click", "openModal"), 
        data_attr("modal-body", "form-body"),
        attribute("id", "add-teacher-button"),
        attribute("class", "action-btn")
    )) ?>
</div>

<!-- list of teachers -->
<div class="mt-8"></div>
<?= table_start(); ?>
    <?= thead_start() ?>
        <?php 
            $column_names = ["Name", "Email", "Ghana Card"];
            foreach($column_names as $column){
                echo th($column);
            }
            echo th();
        ?>
    <?= thead_end() ?>
    <?= tbody_start() ?>
        <?php if($teachers = teachers(complete: true)): 
            foreach($teachers as $teacher):
        ?>
            <?= tr_start(); ?>
                <?= td($teacher["lastname"] ? $teacher["lastname"] . " " . $teacher["othernames"] : "Not Set"); ?>
                <?= td($teacher["email"]); ?>
                <?= td($teacher["ghana_card"]); ?>
                <?= td("<div class='flex gap-2 items-center'>
                            <i @click='openModal' data-id='{$teacher['id']}' data-modal-body='delete-body' data-show-footer='1' class='fas action-btn fa-trash-can text-red-500 hover:text-red-600 cursor-pointer action-delete' title='Delete'></i>
                        </div>"); ?>
            <?= tr_end(); ?>
        <?php endforeach; else: echo td_empty("No teachers have been set yet", (count($column_names) + 1)); endif; ?>
    <?= tbody_end() ?>
<?= table_end(); ?>

<?php echo modal_start( attribute("id", "modal")); echo modal_header(); ?>
    <!-- add teacher section -->
    <div id="form-body" class="hidden modal-body">
        <?= modal_body_start(); ?>
            <?= modal_title("", attribute("id", "modal-title")) ?>
            
            <form action="<?= url("admin/submit.php") ?>" name="admin-form" method="POST">
                <div class="grid gap-4 lg:gap-6">
                    <!-- Teacher Email -->
                    <?= input("email", "Teacher Email", "email", required: true, attributes: placeholder("Enter admin email")); ?>

                    <!-- User Type -->
                    <?= hidden_input("type", "teacher") ?>

                    <!-- Admin Password -->
                    <?= input_h("password", "Password", "password", sub_text: "You can leave this blank and we’ll create a random password for you.", attributes: array_merge(
                        placeholder("Enter password"), attribute("minlength", 8)
                    )); ?>
                </div>

                <!-- Submit Button -->
                <div class="mt-4 sm:w-48">
                    <?= button("submit", "Add Teacher", "submit", "add_user", "blue") ?>
                </div>
            </form>
            
        <?= modal_body_end(); ?>
    </div>

    <!-- delete section -->
    <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("teachers", form_action: url("admin/submit.php"), 
            delete_text: "This will remove all associated records for this admin. Proceed to delete this admin?") ?>
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

        $("#add-teacher-button").click(function(){
            $("#form-body input[name=email]").val("").attr("readonly", false);
            $("#form-body input[name=password]").val("");
            
            $("#modal-title").text("Add New Teacher");
            $("#form-body button[name=submit]").val("add_user").html("Add Teacher");
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
