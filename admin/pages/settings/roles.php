<?php
require_once relative_path("includes/components.php");

$title = 'Roles and Permissions'; // Set the page title

// --- Master Permissions List (Generate once) ---
// This list MUST match the master list in your backend/database
$master_permissions = [
    'student_management' => 'Manage Students (CRUD)',
    'teacher_management' => 'Manage Teachers (CRUD)',
    'course_management' => 'Manage Courses (CRUD)',
    'view_dashboard_admin' => 'View Admin Dashboard',
    'view_financial_data' => 'View Financial Data',
    'approve_registrations' => 'Approve Student Registrations',
    // Add all other permissions here...
    'delete_user' => 'Delete Users',
    'view_profile' => 'View Own Profile'
];

// Helper to generate the HTML for the permissions checkboxes
function generate_permissions_html($permissions_list) {
    $html = [];
    foreach ($permissions_list as $value => $label) {
        // Note: The name attribute is changed to 'permissions[]' for form submission
        $html[] = checkbox("permissions[]", $value, htmlspecialchars($label), attributes: attribute("id", $value));
    }
    return implode("\n", $html);
}

$permissions_html = generate_permissions_html($master_permissions);

$system_user_roles = get_system_user_roles();

// Start output buffering
ob_start();
?>

<template id="role-row-template">
    <?= tr_start(attribute("class", "text-gray-700 dark:text-gray-400")) ?>
        <?= td("__DISPLAY_NAME__", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        <?= td("__NAME__", attributes: attribute("class", "px-4 py-3 text-xs italic")) ?>
        <?= td("<span class='px-2 py-1 font-semibold leading-tight text-__BADGE_COLOR__-700 bg-__BADGE_COLOR__-100 rounded-full dark:bg-__BADGE_COLOR__-700 dark:text-__BADGE_COLOR__-100' data-permissions-count='__PERMISSION_COUNT__'>__PERMISSION_COUNT__</span>", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        <?= td("__CREATED_AT__", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        
        <?= td_actions(
            array_merge(
                // Edit Action
                create_td_action(
                    "fas fa-edit",
                    "Edit Role",
                    array_merge(
                        attribute("class", "text-purple-500 hover:text-purple-600 cursor-pointer edit-role action-btn"),
                        data_attr("id", "__ID__"),
                        data_attr("modal-body", "role-modal-content"), // Use a container ID for the modal body
                        data_attr("permissions", "__PERMISSIONS_BASE64__"), // Base64 encoded permissions list
                        data_attr("display-name", "__DISPLAY_NAME__"),
                        data_attr("system-name", "__NAME__"),
                        data_attr("role-name", "__USER_TYPE__"),
                        attribute("@click", "openModal('role-modal')")
                    )
                ),
                // Delete Action
                create_td_action(
                    "fas fa-trash-alt",
                    "Delete Role",
                    array_merge(
                        attribute("class", "text-red-500 hover:text-red-600 cursor-pointer delete-role action-btn"),
                        data_attr("id", "__ID__"),
                        data_attr("modal-body", "delete-body"),
                        data_attr("show-footer", "1"),
                        data_attr("name", "__DISPLAY_NAME__"),
                        attribute("@click", "openModal('role-modal')")
                    )
                )
            )
        ) ?>
    <?= tr_end() ?>
</template>

<template id="empty-row-template">
    <?= td_empty("No user roles found. Click 'Add New Role' to create one.", 5) ?>
</template>

<div class="flex items-center justify-between mb-6">
    <?= button("button", 
                "Add New Role", 
                color: "blue", 
                attributes: array_merge(
                    attribute("id", "add-role-btn"),
                    attribute("@click", "openModal('role-modal')"),
                    data_attr("modal-body", "role-modal-content"),
                    attribute("class", "max-w-xs")
                )) 
    ?>

    <?= 
        select("role_type", "User Role Type", 
            options: array_merge(
                ["all" => "All Roles"], 
                $system_user_roles
            ), 
            attributes: array_merge(
                data_attr("filter", "role_type"),
                attribute("id", "role_type_filter")
            ))
    ?>
</div>

<div class="w-full overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <?= table_start(attribute("class", "w-full whitespace-no-wrap")) ?>
            <?= thead_start() ?>
                <?= tr_start() ?>
                    <?= th("Role Name", attribute("class", "px-4 py-3")) ?>
                    <?= th("System Name", attribute("class", "px-4 py-3")) ?>
                    <?= th("Total Permissions", attribute("class", "px-4 py-3")) ?>
                    <?= th("Created On", attribute("class", "px-4 py-3")) ?>
                    <?= th("Actions", attribute("class", "px-4 py-3")) ?>
                <?= tr_end() ?>
            <?= thead_end() ?>
            
            <tbody id="roles-table-body" class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                <?= td_empty("Loading roles...", 5) ?> 
            </tbody>
        <?= table_end() ?>
    </div>
    
    <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
        <p class="flex items-center col-span-3 gap-2">
            Showing <span id="page-info" class="mx-1">0–0</span> of <span id="total-count">0</span> roles
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

<?php echo modal_start( attribute("id", "role-modal") ); ?>
    <div id="role-modal-content" class="modal-body">
        <?= modal_body_start() ?>
            <?= modal_title("Add New Role", attributes: attribute("id", "role-modal-title")) ?>
            
            <form id="role-form" action="<?= url("admin/submit.php") ?>" method="POST">
                <?= hidden_input("submit", "create_role", attribute("id", "submit-action")) ?>
                <?= hidden_input("role_id", "", attribute("id", "role-id")) ?>
                
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <?= input("text", "Role Display Name", 
                                    "display_name", 
                                    required: true, 
                                    attributes: array_merge(
                                        attribute("maxlength", 255),
                                        placeholder("e.g., Department Head")
                                    )) ?>
                    <?= select("role_name", "User Role Type",
                            options: $system_user_roles,
                            nullable: "Select Role Type",
                            required: true
                        )
                    ?>
                    
                    <div class="md:col-span-2">
                        <?= input_h("text", "System Name (Unique Identifier)", 
                                    "name",  sub_text: "System identifier. Leave blank to auto-generate. Cannot be changed later.",
                                    attributes: array_merge(
                                        attribute("maxlength", 255), 
                                        data_attr("original", ""), 
                                        placeholder("e.g., dept_head")
                                    )) ?>
                    </div>
                    
                </div>

                <h3 class="mt-6 mb-3 text-lg font-semibold border-b pb-2 dark:text-gray-300">Permissions</h3>
                <div id="permissions-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4 border rounded-md dark:border-gray-700">
                    <?= $permissions_html ?>
                </div>

                <div class="mt-6 flex justify-end gap-4">
                    <?= button("submit", "Save Role", color: "blue", attributes: attribute("id", "modal-submit-btn")) ?>
                    <?= button("button", "Cancel", color: "red", attributes: array_merge(attribute("id", "cancel_btn"), attribute("@click", "closeModal()"))) ?>
                </div>
            </form>
        <?= modal_body_end(); ?>
    </div>

    <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("user_roles", form_action: url("admin/submit.php"), 
            delete_text: "Are you sure you want to delete this role? This action cannot be undone and may affect users assigned to this role.") ?>
    </div>
<?= modal_end() ?>

<?php
$content = ob_get_clean();

// ==============================================
// 5. SCRIPTS
// ==============================================

// Set up the pagination script call
$pagination_script = pagination_script(
    'admin/ajax/school.php',       // Target AJAX file (you need to create this)
    'role-row-template',         // Template ID
    'roles',                     // Data key in backend response ($data["roles"])
    [
        "ID" => "id",             
        "DISPLAY_NAME" => "display_name",         
        "NAME" => "name",
        "PERMISSIONS" => "permissions", // Permissions array/JSON string
        "PERMISSIONS_BASE64" => "permissions_base64", // Base64 encoded permissions (for JS consumption)
        "PERMISSION_COUNT" => "permission_count",
        "CREATED_AT" => "created_at_formatted",
        "BADGE_COLOR" => "badge_color",
        "USER_TYPE" => "role_name"
    ],
    ["submit" => "fetch_roles"]
);

$scripts = <<<HTML
<script>
    $(document).ready(function() {
        // Initialize pagination on page load
        $pagination_script

        // --- Role Form Setup ---
        
        // Function to reset the form for "Add New Role"
        function resetRoleForm() {
            $('#role-modal-title').text('Add New Role');
            $('#role-form')[0].reset(); // Clear form
            $('#submit-action').val('create_role');
            $('#role-id').val('');
            $('#role-form input[name=name]').prop('readonly', false).val('').attr('data-original', '');
            $("#role-form select[name=role_name]").prop('disabled', false).change();
            // Uncheck all permissions
            $('#permissions-container input[type="checkbox"]').prop('checked', false);

            // remove error span
            $("#role-modal .error-span").remove();
        }

        // Handle "Add New Role" button click
        $('#add-role-btn').on('click', function() {
            resetRoleForm();
        });

        // Handle "Edit Role" click (Delegated event)
        $(document).on('click', '.edit-role', function() {
            const element = $(this);
            const roleData = element.data();

            // reset form
            resetRoleForm();
            
            // Set modal for editing
            $('#role-modal-title').text('Edit Role: ' + roleData.displayName);
            $('#submit-action').val('update_role'); // Change action to update
            $('#role-id').val(roleData.id);
            
            // Populate form fields
            $('#role-modal input[name=display_name]').val(roleData.displayName);
            // System Name is usually fixed after creation, so disable it
            $('#role-modal input[name=name]').val(roleData.systemName).prop('readonly', true).attr('data-original', roleData.systemName); 
            $('#role-modal select[name=role_name]').val(roleData.roleName).prop('disabled', true).change();
            
            // Handle Permissions
            $('#role-modal #permissions-container input[type="checkbox"]').prop('checked', false);
            
            // Decode Base64 string and parse JSON
            const encodedPermissions = roleData.permissions;
            // NOTE: 'atob' is deprecated, but widely supported for simple cases. 
            // If permissions strings are very large, consider a dedicated JS library for Base64.
            const permissionsJson = encodedPermissions ? atob(encodedPermissions) : '[]'; 
            
            try {
                const activePermissions = JSON.parse(permissionsJson);

                if (Array.isArray(activePermissions)) {
                    activePermissions.forEach(perm => {
                        // Check the corresponding checkbox
                        $('#permissions-container input[value="' + perm + '"]').prop('checked', true);
                    });
                }
            } catch (e) {
                console.error("Failed to parse permissions JSON:", e);
            }
            
            // Re-open the modal body if it was closed by the initial global openModal function
            $('#role-modal-content').removeClass('hidden'); 
        });

        // Handle Delete setup
        $(document).on('click', '.delete-role', function(){
            const id = $(this).data('id');
            const name = $(this).data('name');
            // Populate the delete form with the ID and the name for confirmation
            $("#delete-body input[name=id]").val(id);
            $("#delete-body input[name=name]").val(name);
        });

        $("select#role_type_filter").on('change', function(){
            loadPaginatedData(1);
        });

        // --- Attach AJAX submission handler to the form ---
        $('#role-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = $('#modal-submit-btn');

            // Basic client-side validation
            if (!form[0].checkValidity()) {
                form[0].reportValidity(); // Use built-in browser validation
                return;
            }

            // enable disabled fields before serialization
            form.find('select:disabled, input:disabled').prop('disabled', false).addClass("were_disabled");

            ajaxCall({
                url: form.attr('action'),
                data: form.serialize(),
                method: 'POST',
                beforeSend: function() {
                    // Disable button, show spinner
                    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner animate-spin"></i> Saving...');
                }
            }).then((response) => {
                if (response.status) {
                    // Close modal, refresh table data
                    $("#cancel_btn").click();

                    // Use the pagination script's reload function
                    loadPaginatedData(1); 
                    // Assuming you have a function to display messages
                    alert_box(response.message || 'Role saved successfully!', 'success'); 
                } else {
                    if(response.errors && Object.keys(response.errors).length > 0){
                        let errorMessages = response.errors;
                        display_form_errors(errorMessages, form);
                    } else {
                        alert_box("Error: An unknown error occurred.");
                        console.error(response);
                    }
                }
            }).catch((error) => {
                console.error("AJAX Error:", error);
                alert('A network error occurred. ' + error.toString());
            }).finally(() => {
                // Re-enable button
                submitBtn.prop('disabled', false).html('Save Role');
                form.find(".were_disabled").prop('disabled', true).removeClass("were_disabled");
            });
        });
    });
</script>
HTML;
?>

<?php
// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');