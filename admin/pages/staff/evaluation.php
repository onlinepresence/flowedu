<?php
require_once relative_path("includes/components.php");

$title = 'Evaluation Forms Management'; // Set the page title

// --- PHP Functions for Evaluation Data ---

/**
 * Helper function to fetch all evaluation forms for initial display.
 * @return array|string
 */
function get_all_evaluation_forms() {
    // Fetch all columns from evaluation_forms table, ordered by ID descending.
    // Assuming limit: 0 fetches all
    return fetchData("*", "evaluation_forms", limit: 0, order_by: "id", asc: false); 
}

$evaluation_forms = get_all_evaluation_forms();
$current_academic_year = getCurrentAcademicYear();

// Start output buffering
ob_start();
?>

<template id="form-row-template">
    <?= tr_start(attribute("class", "text-gray-700 dark:text-gray-400")) ?>
        <?= td("__TITLE__", attributes: attribute("class", "px-4 py-3 text-sm font-semibold")) ?>
        <?= td("__ACADEMIC_YEAR__", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        <?= td("__UNIQUE_CODE__", attributes: attribute("class", "px-4 py-3 text-xs italic")) ?>
        <?= td() //td_badge('__STATUS__', '__STATUS_COLOR__') ?>
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
                // Edit Action
                create_td_action(
                    "fas fa-edit",
                    "Edit Form Details",
                    array_merge(
                        attribute("class", "text-purple-500 hover:text-purple-600 cursor-pointer edit-form-details"),
                        data_attr("id", "__ID__"),
                        data_attr("title", "__TITLE__"),
                        data_attr("academic-year", "__ACADEMIC_YEAR__"),
                        data_attr("unique-code", "__UNIQUE_CODE__"),
                        data_attr("control-type", "__CONTROL_TYPE__"),
                        data_attr("start-time", "__START_TIME__"),
                        data_attr("end-time", "__END_TIME__"),
                        attribute("@click", "openModal('evaluation-modal')")
                    )
                ),
                // Manage Questions (Redirect to the next page)
                create_td_action(
                    "fas fa-list-ol",
                    "Manage Questions",
                    array_merge(
                        attribute("class", "text-blue-500 hover:text-blue-600 cursor-pointer"),
                        attribute("href", "admin/evaluation/manage-questions.php?form_id=__ID__")
                    )
                ),
                // Delete Action (Only if inactive)
                create_td_action(
                    "fas fa-trash-alt",
                    "Delete Form",
                    array_merge(
                        attribute("class", "text-red-500 hover:text-red-600 cursor-pointer delete-form action-btn"),
                        data_attr("id", "__ID__"),
                        data_attr("name", "__TITLE__"),
                        attribute("@click", "openModal('delete-modal')") // Assuming a separate modal for delete
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
                   attribute("@click", "openModal('evaluation-modal')"),
                   attribute("class", "max-w-xs")
               )) 
    ?>

    <?= 
        select("role_type", "Form Status", 
            options: [
                "all" => "All Evaluation Forms",
                "active" => "Active Forms",
                "pending" => "Pending Forms",
                "closed" => "Closed Forms"
            ], 
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
                <?php if (is_array($evaluation_forms) && count($evaluation_forms) > 0): ?>
                    <?php foreach ($evaluation_forms as $form): 
                        // Determine status and badge color
                        $status = 'Pending';
                        $status_color = 'gray';
                        $current_time = time();
                        $start_time = strtotime($form['start_time']);
                        $end_time = strtotime($form['end_time']);

                        if ($form['is_active'] && $current_time >= $start_time && $current_time <= $end_time) {
                            $status = 'Active';
                            $status_color = 'green';
                        } elseif ($current_time > $end_time) {
                            $status = 'Closed';
                            $status_color = 'red';
                        }
                    ?>
                        <?= tr_start(attribute("class", "text-gray-700 dark:text-gray-400")) ?>
                            <?= td(htmlspecialchars($form['title']), attributes: attribute("class", "px-4 py-3 text-sm font-semibold")) ?>
                            <?= td(htmlspecialchars($form['academic_year']), attributes: attribute("class", "px-4 py-3 text-sm")) ?>
                            <?= td(htmlspecialchars($form['unique_code']), attributes: attribute("class", "px-4 py-3 text-xs italic")) ?>
                            <?php td(); /*td_badge($status, $status_color)*/ ?>
                            <?= td(
                                '<span title="Start: ' . date('H:i', $start_time) . '">' . date('M d, Y', $start_time) . '</span>',
                                attributes: attribute("class", "px-4 py-3 text-sm")
                            ) ?>
                            <?= td(
                                '<span title="End: ' . date('H:i', $end_time) . '">' . date('M d, Y', $end_time) . '</span>',
                                attributes: attribute("class", "px-4 py-3 text-sm")
                            ) ?>
                            
                            <?= td_actions(
                                array_merge(
                                    // Edit Action
                                    create_td_action(
                                        "fas fa-edit",
                                        "Edit Form Details",
                                        array_merge(
                                            attribute("class", "text-purple-500 hover:text-purple-600 cursor-pointer edit-form-details"),
                                            data_attr("id", $form['id']),
                                            data_attr("title", htmlspecialchars($form['title'])),
                                            data_attr("academic-year", htmlspecialchars($form['academic_year'])),
                                            data_attr("unique-code", htmlspecialchars($form['unique_code'])),
                                            data_attr("control-type", $form['control_type']),
                                            // Formatting DATETIME for datetime-local input
                                            data_attr("start-time", date('Y-m-d\TH:i', $start_time)),
                                            data_attr("end-time", date('Y-m-d\TH:i', $end_time)),
                                            attribute("@click", "openModal('evaluation-modal')")
                                        )
                                    ),
                                    // Manage Questions
                                    create_td_action(
                                        "fas fa-list-ol",
                                        "Manage Questions",
                                        attribute("href", "admin/evaluation/manage-questions.php?form_id={$form['id']}")
                                    ),
                                    // Delete Action (Show only if not active/closed)
                                    create_td_action(
                                        "fas fa-trash-alt",
                                        "Delete Form",
                                        array_merge(
                                            attribute("class", "text-red-500 hover:text-red-600 cursor-pointer delete-form action-btn"),
                                            data_attr("id", $form['id']),
                                            data_attr("name", htmlspecialchars($form['title'])),
                                            attribute("@click", "openModal('delete-modal')") 
                                        )
                                    )
                                )
                            ) ?>
                        <?= tr_end() ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?= td_empty("No evaluation forms found. Click 'Create New Evaluation' to start.", 7) ?>
                <?php endif; ?>
            </tbody>
        <?= table_end() ?>
    </div>
</div>

<?php echo modal_start( attribute("id", "evaluation-modal") ); ?>
    <div id="evaluation-modal-content" class="modal-body">
        <?= modal_body_start() ?>
            <?= modal_title("Create New Evaluation", attributes: attribute("id", "evaluation-modal-title")) ?>
            
            <form id="evaluation-form-details" action="<?= url("admin/submit.php") ?>" method="POST">
                <?= hidden_input("submit", "create_evaluation_form", attribute("id", "submit-action")) ?>
                <?= hidden_input("form_id", "", attribute("id", "form-id")) ?>
                
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    
                    <?= input("text", "Evaluation Title", 
                        "title", 
                        required: true, 
                        attributes: array_merge(
                            attribute("maxlength", 255),
                            placeholder("e.g., Semester 1 Lecturer Evaluation")
                        )) 
                    ?>

                    <?= input("text", 
                            "Academic Year", 
                            "academic_year", 
                            required: true, 
                            value: $current_academic_year,
                            attributes: attribute("readonly")
                        ) 
                    ?>
                    
                    <?= input("datetime-local", 
                                "Start Date & Time", 
                                "start_time", 
                                required: true, 
                                attributes: attribute("step", 60)
                                ) 
                    ?>

                    <?= input("datetime-local", 
                                "End Date & Time", 
                                "end_time", 
                                required: true, 
                                attributes: attribute("step", 60)
                                ) 
                    ?>
                    
                    <div class="md:col-span-2">
                        <?= input_h("text", 
                                    "Unique Evaluation Code", 
                                    "unique_code", 
                                    sub_text: "System identifier. Auto-generated. Cannot be changed after creation.",
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
                    <?= button("submit", "Save Form", color: "blue", attributes: attribute("id", "modal-submit-btn")) ?>
                    <?= button("button", "Cancel", color: "red", attributes: attribute("@click", "closeModal()")) ?>
                </div>
            </form>
        <?= modal_body_end(); ?>
    </div>

    <div id="delete-body" class="hidden modal-body">
        <?= delete_item_component("user_roles", form_action: url("admin/submit.php"), 
            delete_text: "Are you sure you want to delete this role? This action cannot be undone and may affect users assigned to this role.") ?>
    </div>
<?= modal_end() ?>


<?php $scripts = <<<HTML
<script>
    // Helper function to generate a unique code
    function generateUniqueCode() {
        const randomString = Math.random().toString(36).substring(2, 8).toUpperCase();
        return 'EVAL-' + new Date().getFullYear().toString().substring(2) + '-' + randomString;
    }

    const current_academic_year = "$current_academic_year";

    // Function to reset the modal form for creation
    function resetEvaluationForm() {
        $('#evaluation-modal-title').text('Create New Evaluation');
        $('#evaluation-form-details')[0].reset(); 
        $('#submit-action').val('create_evaluation_form');
        $('#form-id').val('');
        
        // Populate default values
        $('#evaluation-form-details input[name=academic_year]').val(current_academic_year).prop('readonly', false);
        $('#evaluation-form-details select[name=control_type]').val('auto');
        
        // Auto-generate code
        $('#evaluation-form-details input[name=unique_code]').val(generateUniqueCode()).prop('readonly', false);
        
        // Reset button text
        $('#modal-submit-btn').html('Save Form');
    }

    $(document).ready(function() {

        // 1. Handle "Create New Evaluation" click
        $('#add-form-btn').on('click', function() {
            resetEvaluationForm();
        });

        // 2. Handle "Edit Form Details" click (Delegated event)
        $(document).on('click', '.edit-form-details', function() {
            const formData = $(this).data();
            
            // Set modal for editing
            $('#evaluation-modal-title').text('Edit Form: ' + formData.title);
            $('#submit-action').val('update_evaluation_form');
            $('#form-id').val(formData.id);

            // Populate form fields
            $('#evaluation-form-details input[name=title]').val(formData.title);
            $('#evaluation-form-details input[name=academic_year]').val(formData.academicYear).prop('readonly', true); // Block editing after creation
            $('#evaluation-form-details input[name=unique_code]').val(formData.uniqueCode).prop('readonly', true); // Block editing after creation
            $('#evaluation-form-details select[name=control_type]').val(formData.controlType);
            $('#evaluation-form-details input[name=start_time]').val(formData.startTime);
            $('#evaluation-form-details input[name=end_time]').val(formData.endTime);
        });
        
        // 3. Handle Delete setup
        $(document).on('click', '.delete-form', function(){
            const id = $(this).data('id');
            const name = $(this).data('name');
            $("#delete-body input[name=id]").val(id);
            $("#delete-body input[name=name]").val(name);
            $("#delete-body input[name=submit]").val('delete_evaluation_form'); // Custom submit action for evaluation forms
        });
        
        // 4. Form Submission (AJAX)
        $('#evaluation-form-details').on('submit', function(e) {
            e.preventDefault();
            alert_box("Logic not ready", "warning"); return;
            const form = $(this);
            const submitBtn = $('#modal-submit-btn');
            const action = $('#submit-action').val();
            
            // Simple client-side date validation
            const startTime = new Date(form.find('[name=start_time]').val());
            const endTime = new Date(form.find('[name=end_time]').val());

            if (startTime >= endTime) {
                alert_box('The "End Date & Time" must be strictly after the "Start Date & Time".', 'error');
                return;
            }

            // Temporarily enable readonly fields for serialization if needed (for update only)
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
                    closeModal(); 
                    alert_box(response.message || 'Form saved successfully!', 'success'); 
                    // Redirect to Questions page on creation, otherwise just reload list
                    if (action === 'create_evaluation_form' && response.data && response.data.new_id) {
                         window.location.href = 'admin/evaluation/manage-questions.php?form_id=' + response.data.new_id;
                    } else {
                        window.location.reload(); 
                    }
                } else {
                    if(response.errors && Object.keys(response.errors).length > 0){
                        display_form_errors(response.errors, form);
                    } else {
                        alert_box("Error: " + (response.errors.system_message || "An unknown error occurred."), 'error');
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
        
    });
</script>
HTML;
?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');