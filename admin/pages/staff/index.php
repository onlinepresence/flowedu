<?php
require_once relative_path("includes/components.php");

$title = 'Admins'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>
<div class="max-w-96 w-72">
    <?= button("button", "Add New Admin", attributes: array_merge(
        attribute("@click", "openModal"), 
        data_attr("modal-body", "form-body"),
        attribute("id", "add-admin-button"),
        attribute("class", "action-btn")
    )) ?>
</div>

<!-- list of admins -->
<div class="mt-8"></div>
<?= table_start(); ?>
    <?= thead_start() ?>
        <?php 
            $column_names = ["Name", "Email", "Type"];
            foreach($column_names as $column){
                echo th($column);
            }
            echo th();
        ?>
    <?= thead_end() ?>
    <?= tbody_start() ?>
        <?php if($admins = admins(complete: true)): 
            foreach($admins as $admin):
        ?>
            <?= tr_start(); ?>
                <?= td($admin["lastname"] . " " . $admin["othernames"]); ?>
                <?= td($admin["email"]); ?>
                <?= td($admin["admin_type"]); ?>
                <?= td("<div class='flex gap-2 items-center'>
                            <i @click='openModal' data-id='{$admin['id']}' data-modal-body='delete-body' data-show-footer='1' class='fas action-btn fa-trash-can text-red-500 hover:text-red-600 cursor-pointer action-delete' title='Delete'></i>
                        </div>"); ?>
            <?= tr_end(); ?>
        <?php endforeach; else: echo td_empty("No admins have been set yet", (count($column_names) + 1)); endif; ?>
    <?= tbody_end() ?>
<?= table_end(); ?>

<?php echo modal_start( attribute("id", "modal")); echo modal_header(); ?>
    <!-- add admin section -->
    <div id="form-body" class="hidden modal-body">
        <?= modal_body_start(); ?>
            <?= modal_title("", attribute("id", "modal-title")) ?>
            
            <form action="<?= url("admin/submit.php") ?>" name="admin-form" method="POST">
                <div class="grid gap-4 lg:gap-6">
                    <!-- Admin Email -->
                    <?= input("email", "Admin Email", "email", required: true, attributes: placeholder("Enter admin email")); ?>

                    <!-- Admin Type -->
                    <?= select("type", "User Type", [["id" => "2", "text" => "Administrator"]], required: true); ?>

                    <!-- Admin Password -->
                    <?= input("password", "Password", "password", required: true, attributes: array_merge(
                        placeholder("Enter password"), attribute("minlength", 8)
                    )); ?>
                </div>

                <!-- Submit Button -->
                <div class="mt-4 sm:w-48">
                    <?= button("submit", "Add Admin", "submit", "add_user", "blue") ?>
                </div>
            </form>
            
        <?= modal_body_end(); ?>
    </div>

    <!-- delete section -->
    <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("admins", form_action: url("admin/submit.php"), 
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

        $("#add-admin-button").click(function(){
            $("#form-body input[name=email]").val("").attr("readonly", false);
            $("#form-body input[name=type]").val("").attr("readonly", false);
            $("#form-body input[name=password]").val("");
            
            $("#modal-title").text("Add New Admin");
            $("#form-body button[name=submit]").val("add_user").html("Add Admin");
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
