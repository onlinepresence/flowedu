<?php
require_once relative_path("includes/components.php");

$title = 'Admins'; // Set the page title

// Define static options for the admin type (role ID 2 is typically 'Administrator')
$admin_types = [
    ["id" => "2", "text" => "Administrator"]
];

// Start output buffering to capture the content
ob_start();
?>

<template id="admin-row-template">
    <?= tr_start() ?>
        <td class="hidden" data-id="__ID__"></td> 
        <?= td("__NAME__") ?>
        <?= td("__EMAIL__") ?>
        <?= td("__TYPE__") ?>

        <?= td_actions(
            array_merge(
                create_td_action(
                    "fas fa-trash-can",
                    "Delete",
                    array_merge(
                        attribute("class", "text-red-500 cursor-pointer hover:text-red-600 action-delete action-btn"),
                        data_attr("id", "__ID__"),
                        data_attr("modal-body", "delete-body"),
                        data_attr("show-footer", "1"),
                        attribute("@click", "openModal")
                    )
                )
            )
        ) ?>
    <?= tr_end() ?>
</template>

<template id="empty-row-template">
    <?= td_empty("No admins found", 4) ?>
</template>

<div class="max-w-96 w-72">
    <?= button("button", "Add New Admin", attributes: array_merge(
        attribute("@click", "openModal"), 
        data_attr("modal-body", "form-body"),
        attribute("id", "add-admin-button"),
        attribute("class", "action-btn")
    )) ?>
</div>

<div class="mt-8"></div>
<div class="w-full mt-3 overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <?= table_start(); ?>
            <?= thead_start() ?>
                <?= tr_start() ?>
                    <?= th("Name") ?>
                    <?= th("Email") ?>
                    <?= th("Type") ?>
                    <?= th("Actions") ?>
                <?= tr_end() ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading admins...", 4) ?> 
            <?= tbody_end() ?>
        <?= table_end(); ?>
    </div>
    
    <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
        <p class="flex items-center col-span-3 gap-2">
            Showing <span id="page-info" class="mx-1">0–0</span> of <span id="total-count">0</span> admins
        </p>
        <span class="col-span-2"></span>
        <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
            <nav aria-label="Table navigation">
                <ul id="pagination" class="inline-flex items-center">
                    <li>
                        <button id="prev-page" class="px-3 py-1 rounded-md rounded-l-lg focus:outline-none focus:shadow-outline-purple" aria-label="Previous">
                            <svg aria-hidden="true" class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                <path d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" fill-rule="evenodd"></path>
                            </svg>
                        </button>
                    </li>
                    <li>
                        <button id="next-page" class="px-3 py-1 rounded-md rounded-r-lg focus:outline-none focus:shadow-outline-purple" aria-label="Next">
                            <svg class="w-4 h-4 fill-current" aria-hidden="true" viewBox="0 0 20 20">
                                <path d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" fill-rule="evenodd"></path>
                            </svg>
                        </button>
                    </li>
                </ul>
            </nav>
        </span>
    </div>
</div>

<?php echo modal_start( attribute("id", "modal")); echo modal_header(); ?>
    <div id="form-body" class="hidden modal-body">
        <?= modal_body_start(); ?>
            <?= modal_title("Add New Admin", attribute("id", "modal-title")) ?>
            
            <form action="<?= url("admin/submit.php") ?>" name="admin-form" method="POST">
                <div class="grid gap-4 lg:gap-6">
                    <?= input("email", "Admin Email", "email", required: true, attributes: placeholder("Enter admin email")); ?>

                    <?= select("type", "User Type", $admin_types, "Select User Type", required: true); ?>

                    <?= input("password", "Password", "password", required: true, attributes: array_merge(
                        placeholder("Enter password"), attribute("minlength", 8)
                    )); ?>
                </div>

                <div class="mt-4 sm:w-48">
                    <?= button("submit", "Add Admin", "submit", "add_user", "blue") ?>
                </div>
            </form>
            
        <?= modal_body_end(); ?>
    </div>

    <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("users", form_action: url("admin/submit.php"), 
            delete_text: "This will remove all associated records for this admin. Proceed to delete this admin?") ?>
    </div>
<?= modal_end() ?>

<?php 
// ==============================================
// 5. SCRIPTS
// ==============================================

// Set up the pagination script call
$pagination_script = pagination_script(
    'admin/ajax/admin.php',    // Target AJAX file (you need to create this)
    'admin-row-template',      // Template ID
    'admins',                  // Data key in backend response ($data["admins"])
    [
        "ID" => "id",             
        "NAME" => "full_name",         
        "EMAIL" => "email",
        "TYPE" => "admin_type",
    ],
    ["submit" => "fetch_admins"] // The action to trigger
);


$extra_script = delete_item_component_script();
$scripts = <<<HTML
<script>
    $(document).ready(function(){
        // Initialize pagination on page load
        $pagination_script

        // Existing modal control logic
        $(document).on("click", ".action-btn", function(){
            const modal_body = $(this).attr("data-modal-body");
            $("#modal .modal-body").addClass("hidden");
            $("#" + modal_body).removeClass("hidden");
        });

        // Handler for the Add New Admin button (Reset form for creation)
        $("#add-admin-button").click(function(){
            // Reset form
            $("#form-body form")[0].reset();
            
            // Ensure fields are not readonly (though they are not set to readonly in this case)
            $("#form-body input[name=email]").attr("readonly", false);
            $("#form-body select[name=type]").val("2").change();
            
            // Set titles and button for ADD operation
            $("#modal-title").text("Add New Admin");
            $("#form-body button[name=submit]").val("add_user").html("Add Admin");
        });

        // Handler for Delete action
        $(document).on("click", ".action-delete", function(){
            const id = $(this).attr("data-id");
            // Populate the delete form with the user ID
            $("#delete-body input[name=id]").val(id);
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