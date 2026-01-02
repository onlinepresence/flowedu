<?php
require_once relative_path("includes/components.php");

$title = 'Evaluation Forms Management'; // Set the page title

// --- PHP Data (Minimal, just helper data) ---

// Assuming this function exists and returns the current academic year string (e.g., "2024/2025")
$current_academic_year = getCurrentAcademicYear(); 

// Start output buffering
ob_start();
?>

<template id="form-row-template">
    <?= tr_start(attribute("class", "text-gray-700 dark:text-gray-400")) ?>
        <?= td("__TITLE__", attributes: attribute("class", "px-4 py-3 text-sm font-semibold")) ?>
        <?= td("__ACADEMIC_YEAR__", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        <?= td("__UNIQUE_CODE__", attributes: attribute("class", "px-4 py-3 text-xs italic")) ?>
        
        <?= td("<span class='px-2 py-1 font-semibold leading-tight text-__STATUS_COLOR__-700 bg-__STATUS_COLOR__-100 rounded-full dark:bg-__STATUS_COLOR__-700 dark:text-__STATUS_COLOR__-100'>__STATUS__</span>", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        
        <?= td(
            '<span title="Start: __START_TIME_FORMATTED__">__START_DATE_FORMATTED__</span>',
            attributes: attribute("class", "px-4 py-3 text-sm")
        ) ?>
        <?= td(
            '<span title="End: __END_TIME_FORMATTED__">__END_DATE_FORMATTED__</span>',
            attributes: attribute("class", "px-4 py-3 text-sm")
        ) ?>
        
        <?= td_actions(
            array_merge(
                // view questions demo
                create_td_action(
                    "fas fa-eye",
                    "View Questions",
                    array_merge(
                        attribute("href", route('admin.evaluation.preview', ['form_code' => '__UNIQUE_CODE__'])),
                        attribute("target", "_blank")
                    )
                ),

                // Edit Action
                create_td_action(
                    "fas fa-edit",
                    "Edit Form Details",
                    array_merge(
                        attribute("class", "text-purple-500 hover:text-purple-600 cursor-pointer edit-form-details action-btn"),
                        data_attr("id", "__ID__"),
                        data_attr("title", "__TITLE__"),
                        data_attr("academic-year", "__ACADEMIC_YEAR__"),
                        data_attr("unique-code", "__UNIQUE_CODE__"),
                        data_attr("control-type", "__CONTROL_TYPE__"),
                        data_attr("start-time", "__START_DATETIME__"), // Use DATETIME for JS
                        data_attr("end-time", "__END_DATETIME__"),     // Use DATETIME for JS
                        attribute("@click", "openModal"),
                        data_attr("modal-body", "evaluation-modal-content"),
                    )
                ),
                // Manage Questions
                create_td_action(
                    "fas fa-list-ol",
                    "Manage Questions",
                    attribute("href", url("admin/staff/evaluation/__UNIQUE_CODE__"))
                ),
                // Delete Action (Note: Hide/Show delete based on status is handled in JS/backend check)
                create_td_action(
                    "fas fa-trash-alt",
                    "Delete Form",
                    array_merge(
                        attribute("class", "text-red-500 hover:text-red-600 cursor-pointer action-delete action-btn"),
                        data_attr("id", "__ID__"),
                        data_attr("modal-body", "delete-body"),
                        data_attr("name", "__TITLE__"),
                        attribute("@click", "openModal")
                    )
                )
            )
        ) ?>
    <?= tr_end() ?>
</template>

<div class="flex items-center justify-between mb-6">
    <?= button("button", 
                "Create New Evaluation", 
                color: "blue", 
                attributes: array_merge(
                    attribute("id", "add-form-btn"),
                    attribute("@click", "openModal"),
                    attribute("class", "max-w-xs")
                )) 
    ?>

    <div class="flex items-center gap-4">
        <?= 
            select("status_filter", 
                options: [
                    "active" => "Active Forms",
                    "pending" => "Pending Forms",
                    "closed" => "Closed Forms"
                ], 
                nullable: "All Evaluation Forms",
                attributes: array_merge(
                    data_attr("filter", "status"), // Key for AJAX backend
                    attribute("id", "status_filter"),
                    attribute("onchange", "loadPaginatedData(1)") // Trigger AJAX reload on change
                ))
        ?>
    </div>
</div>

<div class="w-full overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <?= table_start(attribute("class", "w-full whitespace-no-wrap")) ?>
            <?= thead_start() ?>
                <?= tr_start() ?>
                    <?= th("Title", attribute("class", "px-4 py-3")) ?>
                    <?= th("Year", attribute("class", "px-4 py-3")) ?>
                    <?= th("Code", attribute("class", "px-4 py-3")) ?>
                    <?= th("Status", attribute("class", "px-4 py-3")) ?>
                    <?= th("Starts", attribute("class", "px-4 py-3")) ?>
                    <?= th("Ends", attribute("class", "px-4 py-3")) ?>
                    <?= th("Actions", attribute("class", "px-4 py-3")) ?>
                <?= tr_end() ?>
            <?= thead_end() ?>
            
            <tbody id="forms-table-body" class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                <?= td_empty("Loading evaluation forms...", 7) ?>
            </tbody>
        <?= table_end() ?>
    </div>
    
    <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
        <p class="flex items-center col-span-3 gap-2">
            Showing <span id="page-info" class="mx-1">0–0</span> of <span id="total-count">0</span> forms
        </p>
        <span class="col-span-2"></span>
        <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
            <nav aria-label="Table navigation">
                <ul id="pagination" class="inline-flex items-center">
                    <li>
                        <button id="prev-page" class="px-3 py-1 rounded-md rounded-l-lg focus:outline-none focus:shadow-outline-purple" aria-label="Previous"></button>
                    </li>
                    <li>
                        <button id="next-page" class="px-3 py-1 rounded-md rounded-r-lg focus:outline-none focus:shadow-outline-purple" aria-label="Next"></button>
                    </li>
                </ul>
            </nav>
        </span>
    </div>
</div>

<?php echo modal_start( attribute("id", "modal") ); ?>
    <div id="evaluation-modal-content" class="modal-body">
        <?= modal_body_start() ?>
            <?= modal_title("Create New Evaluation", attributes: attribute("id", "evaluation-modal-title")) ?>
            
            <form id="evaluation-form-details" action="<?= url("admin/submit.php") ?>" method="POST">
                <?= hidden_input("submit", "create_evaluation_form", attribute("id", "submit-action")) ?>
                <?= hidden_input("form_id", "", attribute("id", "form-id")) ?>
                
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    
                    <?= input("text", "Evaluation Title", "title", 
                        required: true, 
                        attributes: array_merge(
                            attribute("maxlength", 255),
                            placeholder("e.g., Semester 1 Lecturer Evaluation")
                        )) 
                    ?>

                    <?= input("text", "Academic Year",
                                    "academic_year", 
                                    required: true, 
                                    value: $current_academic_year,
                                    attributes: attribute("readonly")
                                ) 
                    ?>
                    
                    <?= input("datetime-local", "Start Date & Time", 
                            "start_time",
                            required: true, 
                            attributes: attribute("step", 60)
                        ) 
                    ?>

                    <?= input("datetime-local", "End Date & Time",
                            "end_time", 
                            required: true, 
                            attributes: attribute("step", 60)
                        ) 
                    ?>
                    
                    <div class="md:col-span-2">
                        <?= input_h("text", "Unique Evaluation Code", 
                            "unique_code", 
                            sub_text: "System identifier. Auto-generated upon creation.",
                            required: true, 
                            attributes: array_merge(
                                attribute("maxlength", 50),
                                placeholder("EVAL-25-XXXX"),
                                attribute('readonly')
                            )
                        ) ?>
                    </div>

                    <?= select("control_type", 
                            "Evaluation Control Mechanism", 
                            [
                                ["id" => "auto", "text" => "Automated (System Cron Handles Start/Stop)"],
                                ["id" => "manual", "text" => "Manual Start/Stop (Admin Intervention)"]
                            ], 
                            nullable: "Select control type",
                            value: 'auto', // Default value
                            required: true
                        ) 
                    ?>

                </div>

                <div class="mt-6 flex justify-end gap-4">
                    <?= button("submit", "Save Form", color: "blue", attributes: array_merge(
                        attribute("id", "modal-submit-btn"),
                        attribute("class", "w-full")
                        )) ?>
                    <?= button("button", "Cancel", color: "red", attributes: array_merge(
                        attribute("@click", "closeModal()"),
                        attribute("id", "modal-cancel-btn"),
                        attribute("class", "w-full")
                    )) ?>
                </div>
            </form>
        <?= modal_body_end(); ?>
    </div>

    <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("evaluation_forms", form_action: url("admin/submit.php"), 
            delete_text: "Are you sure you want to delete this evaluation form? This action cannot be undone and will delete all associated questions and settings.") ?>
    </div>
<?= modal_end() ?>

<?php
$content = ob_get_clean();

// ==============================================
// 5. SCRIPTS
// ==============================================

// Set up the pagination script call
$pagination_script = pagination_script(
    'admin/ajax/evaluation.php',    // Target AJAX file (you need to create this)
    'form-row-template',            // Template ID
    'forms',                        // Data key in backend response ($data["forms"])
    [
        "ID" => "id",             
        "TITLE" => "title",         
        "ACADEMIC_YEAR" => "academic_year",
        "UNIQUE_CODE" => "unique_code",
        "CONTROL_TYPE" => "control_type",
        
        // Dates for display
        "START_DATE_FORMATTED" => "start_date_formatted",
        "END_DATE_FORMATTED" => "end_date_formatted",
        "START_TIME_FORMATTED" => "start_time_formatted",
        "END_TIME_FORMATTED" => "end_time_formatted",
        
        // DATETIME for JS to populate edit modal
        "START_DATETIME" => "start_datetime",
        "END_DATETIME" => "end_datetime",

        // Status data processed in backend
        "STATUS" => "status",
        "STATUS_COLOR" => "status_color",
    ],
    ["submit" => "fetch_evaluation_forms"], // Default submit action
);

$extra_script = delete_item_component_script();
$scripts = <<<HTML
<script>
    // Helper function to generate a unique code
    function generateUniqueCode() {
        const randomString = Math.random().toString(36).substring(2, 8).toUpperCase();
        return 'EVAL-' + new Date().getFullYear().toString().substring(2) + '-' + randomString;
    }

    const current_academic_year = "$current_academic_year";

    // function to remove error span
    function remove_error_span(){
        $("#evaluation-form-details .error-span").remove();
    }

    // Function to reset the modal form for creation
    function resetEvaluationForm() {
        $('#evaluation-modal-title').text('Create New Evaluation');
        $('#evaluation-form-details')[0].reset(); 
        $('#submit-action').val('create_evaluation_form');
        $('#form-id').val('');
        
        // Populate default values
        $('#evaluation-form-details input[name=academic_year]').val(current_academic_year).prop('readonly', true);
        $('#evaluation-form-details select[name=control_type]').val('auto');

        remove_error_span();
        
        // Auto-generate code for new forms
        $('#evaluation-form-details input[name=unique_code]').val(generateUniqueCode()).prop('readonly', true);
        
        // Reset button text
        $('#modal-submit-btn').html('Save Form');
    }

    $(document).ready(function() {

        // Initialize pagination on page load
        $pagination_script

        // 1. Handle "Create New Evaluation" click
        $('#add-form-btn').on('click', function() {
            resetEvaluationForm();
        });

        // 2. Handle "Edit Form Details" click (Delegated event)
        $(document).on('click', '.edit-form-details', function() {
            remove_error_span();
            const formData = $(this).data();
            
            // Set modal for editing
            $('#evaluation-modal-title').text('Edit Form: ' + formData.title);
            $('#submit-action').val('update_evaluation_form');
            $('#form-id').val(formData.id);

            // Populate form fields
            $('#evaluation-form-details input[name=title]').val(formData.title);
            // Academic year and unique code remain read-only for editing
            $('#evaluation-form-details input[name=academic_year]').val(formData.academicYear).prop('readonly', true); 
            $('#evaluation-form-details input[name=unique_code]').val(formData.uniqueCode).prop('readonly', true); 
            $('#evaluation-form-details select[name=control_type]').val(formData.controlType);
            $('#evaluation-form-details input[name=start_time]').val(formData.startTime);
            $('#evaluation-form-details input[name=end_time]').val(formData.endTime);
        });

        $(document).on("click", ".action-btn", function(){
            const modal_body = $(this).attr("data-modal-body");
            $("#modal .modal-body").addClass("hidden");
            $("#" + modal_body).removeClass("hidden");
        });
        
        // 4. Form Submission (AJAX)
        $('#evaluation-form-details').on('submit', function(e) {
            e.preventDefault();

            remove_error_span();
            
            const form = $(this);
            const submitBtn = $('#modal-submit-btn');
            const action = $('#submit-action').val();
            
            // Simple client-side date validation
            const startTime = new Date(form.find('[name=start_time]').val());
            const endTime = new Date(form.find('[name=end_time]').val());

            if (startTime >= endTime) {
                // Assuming alert_box is available globally
                alert_box('The "End Date & Time" must be strictly after the "Start Date & Time".', 'error');
                return;
            }

            // Temporarily enable readonly fields for serialization
            const readonlyFields = form.find('input[readonly]');
            readonlyFields.prop('readonly', false).addClass("temp_enabled");

            ajaxCall({
                url: form.attr('action'),
                data: form.serialize(),
                method: 'POST',
                beforeSend: function() {
                    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner animate-spin"></i> Saving...');
                }
            }).then((response) => {
                if (response.status) {
                    $("#modal-cancel-btn").click(); // Close the modal
                    const data = response.data;

                    // Assuming alert_box is available globally
                    alert_box(data.message || 'Form saved successfully!', 'success'); 
                    
                    if (action === 'create_evaluation_form' && data.new_id) {
                         // Redirect to Questions page upon successful creation
                         window.location.href = 'admin/staff/evaluation/' + data.new_id;
                    } else {
                        // Reload the table using the AJAX pagination helper
                        loadPaginatedData(1);
                    }
                } else {
                    if(response.errors && Object.keys(response.errors).length > 0){
                        // Assuming display_form_errors is available globally
                        display_form_errors(response.errors, form);
                    } else {
                        alert_box("Error: " + (response.errors.system_message || "An unknown error occurred."), 'error');
                        console.error("Form Submission Error:", response.errors);
                    }
                }
            }).catch((error) => {
                console.error("AJAX Error:", error);
                alert_box('A network error occurred. Please try again.', 'error');
            }).finally(() => {
                submitBtn.prop('disabled', false).html('Save Form');
                // Restore readonly state
                readonlyFields.prop('readonly', true).removeClass("temp_enabled");
            });
        });

        $extra_script
        
    });
</script>
HTML;
?>
<?php
require relative_path('layouts/auth.php');