<?php
require_once relative_path("includes/components.php");

// getting the following from params
// form_code

// --- 1. Get Context and Form Data ---
// Assuming $params is a global variable holding URL parameters (as requested)
$current_tab = $params['tab'] ?? $_GET['tab'] ?? 'questions'; // Default to Questions

// Fetch the main form record (e.g., using a specific function for security/context)
// Placeholder for the form retrieval function:
$form_data = fetchData("*", "evaluation_forms", "unique_code = '$form_code'");

if (!$form_data || empty($form_data['id'])) {
    // Handle error: Form not found
    session("errors.system_message", "Form for '$form_code' was not found or is invalid");
    header("Location: " . route('admin.evaluations'));
    exit();
}

$form_id = $form_data['id'];
$title = 'Manage Evaluation: ' . htmlspecialchars($form_data['title']);

// Fetch all active questions for the current form, ordered by question_order
$questions = fetchData("*", "evaluation_questions", "form_id = $form_id AND deleted_at IS NULL", 0, order_by: "question_order", asc: true);
if(!$questions){
    $questions = [];
}

// --- 2. Tab Navigation Helper ---
function tab_link($tab_name, $current_tab, $code, $ignore_label_in_link = false) {
    $active = ($tab_name === $current_tab) ? 'border-indigo-500 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600';
    $url = $tab_name === $current_tab ? "javascript:void(0)" : route("admin.evaluation", ["form_code" => $code, "tab" => $ignore_label_in_link ? "" : $tab_name]);
    $label = ucfirst($tab_name);
    
    // Custom label mapping
    if ($tab_name === 'details') $label = 'Details & Schedule';
    if ($tab_name === 'reporting') $label = 'Reporting & Results';

    return "<a href='{$url}' class='whitespace-nowrap px-4 py-2 border-b-2 font-medium text-sm {$active}'>{$label}</a>";
}

// Start output buffering
ob_start();
?>

<div class="max-w-7xl mx-auto p-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <?= tab_link('questions', $current_tab, $form_code, true) ?>
            <?= tab_link('details', $current_tab, $form_code) ?>
            <?= tab_link('reporting', $current_tab, $form_code) ?>
        </nav>
    </div>

    <?php if ($current_tab === 'questions'): ?>
    
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold dark:text-gray-200">Evaluation Questions</h2>

            <div class="flex gap-2">
                <?php
                    $url = route('admin.evaluation.preview', ['form_code' => $form_data['unique_code']]); 
                    echo button("button", 
                        "View Demo", 
                        color: "blue", 
                        attributes: array_merge(
                            attribute("onclick", "location.href='{$url}'")
                        )) 
                ?>
                <?= button("button", 
                        "Add New Question", 
                        color: "green", 
                        attributes: array_merge(
                            attribute("@click", "openModal('question-modal')"),
                            attribute("class", "add-new-question")
                        )) 
                ?>
            </div>
            
        </div>
        
        <div class="w-full overflow-hidden rounded-lg shadow-xs mt-6">
            <div class="w-full overflow-x-auto">
                <table class="w-full whitespace-no-wrap">
                    <thead>
                        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                            <th class="px-4 py-3 w-1/12">Order</th>
                            <th class="px-4 py-3 w-8/12">Question Text</th>
                            <th class="px-4 py-3 w-2/12">Response Type</th>
                            <th class="px-4 py-3 w-1/12">Required</th>
                            <th class="px-4 py-3 w-1/12">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="questions-table-body" class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                        <?php if (count($questions) > 0): ?>
                            <?php foreach ($questions as $q): ?>
                                <tr class="text-gray-700 dark:text-gray-400" data-id="<?= $q['id'] ?>">
                                    <?=  td($q["question_order"]) ?>
                                    <?=  td($q["question_text"]) ?>
                                    <?=  td(ucfirst(str_replace('_', ' ', $q['rating_type']))) ?>
                                    <?= $q['is_required'] ? td_badge('Yes', 'green') : td_badge('No', 'gray') ?>
                                    <?= td_actions(
                                        array_merge(
                                            create_td_action("fas fa-edit", "Edit Question", 
                                                array_merge(
                                                    attribute("class", "text-purple-500 edit-question"),
                                                    data_attr("id", $q['id']),
                                                    data_attr("text", htmlspecialchars($q['question_text'])),
                                                    data_attr("type", $q['rating_type']),
                                                    data_attr("required", $q['is_required']),
                                                    data_attr("order", $q['question_order']),
                                                    attribute("@click", "openModal('question-modal')")
                                                )
                                            ),
                                            create_td_action("fas fa-trash-alt", "Delete Question", 
                                                array_merge(
                                                    attribute("class", "text-red-500 delete-question"),
                                                    data_attr("id", $q['id']),
                                                    data_attr("name", "Question #{$q['question_order']}"),
                                                    attribute("@click", "openModal('delete-q-modal')")
                                                )
                                            )
                                        )
                                    ) ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?= tr_start(attribute("class", "empty-tr")) ?>
                                <?= td_empty("No questions found. Click 'Add New Question' to create one.", 5) ?>
                            <?= tr_end() ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    <?php elseif ($current_tab === 'details'): ?>
    
    <?php 
        $academic_year_value = $form_data['academic_year'] ?? getCurrentAcademicYear();
        
        // Re-calculate Status for the Context Panel
        $status = 'Pending';
        $status_color = 'gray';
        $current_time = time();
        $start_time = strtotime($form_data['start_time']);
        $end_time = strtotime($form_data['end_time']);

        if ($form_data['is_active'] && $current_time >= $start_time && $current_time <= $end_time) {
            $status = 'Active';
            $status_color = 'green';
        } elseif ($current_time > $end_time) {
            $status = 'Closed';
            $status_color = 'red';
        }
        
        // Placeholder: Assume these stats are available or can be fetched quickly
        $total_questions = count($questions); // Already fetched globally
        $total_responses = fetchData("COUNT(*)", "evaluation_responses", "form_id = ?", 1, [$form_id])['COUNT(*)'] ?? 0;
    ?>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2">
            <h2 class="text-xl font-semibold dark:text-gray-200 mb-4 border-b pb-2">Edit Schedule & Details</h2>

            <form id="details-schedule-form" action="<?= url("admin/submit.php") ?>" method="POST" class="max-w-xl">
                <?= hidden_input("submit", "update_evaluation_form") ?>
                <?= hidden_input("form_id", $form_id) ?>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    
                    <?= input("text", "Evaluation Title", 
                                "title", 
                                required: true, 
                                value: $form_data['title'] ?? '',
                                attributes: array_merge(attribute("maxlength", 255), placeholder("e.g., Semester 1 Lecturer Evaluation"))) 
                    ?>

                    <?= input("text", 
                                "Academic Year", 
                                "academic_year", 
                                required: true, 
                                value: $academic_year_value,
                                attributes: attribute("readonly", "readonly")) 
                    ?>
                    
                    <?= input("datetime-local", 
                                "Start Date & Time", 
                                "start_time", 
                                required: true, 
                                value: date('Y-m-d\TH:i', $start_time),
                                attributes: attribute("step", 60)) 
                    ?>

                    <?= input("datetime-local", 
                                "End Date & Time", 
                                "end_time", 
                                required: true, 
                                value: date('Y-m-d\TH:i', $end_time),
                                attributes: attribute("step", 60)) 
                    ?>
                    
                    <div class="md:col-span-2">
                        <?= input_h("text", 
                                    "Unique Evaluation Code", 
                                    "unique_code", 
                                    sub_text: "System identifier. Cannot be changed once created.",
                                    required: true, 
                                    value: $form_data['unique_code'] ?? '', 
                                    attributes: attribute("readonly", "readonly")) 
                        ?>
                    </div>

                    <?= select("control_type", 
                               "Evaluation Control Mechanism", 
                               [
                                   ["id" => "auto", "text" => "Automated (System Cron Handles Start/Stop)"],
                                   ["id" => "manual", "text" => "Manual Start/Stop (Admin Intervention)"]
                               ], 
                               nullable: "Select control type",
                               value: $form_data['control_type'], 
                               required: true) 
                    ?>
                    
                    <?php if ($form_data['control_type'] === 'manual'): ?>
                    <div class="md:col-span-2 p-4 border rounded-md bg-yellow-50 dark:bg-yellow-900 dark:border-yellow-700">
                         <?= checkbox("is_active", $form_data['is_active'], "Manually Activate/Deactivate Evaluation", true) ?>
                         <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">This switch overrides the start/end times only when **Manual Control** is selected.</p>
                    </div>
                    <?php endif; ?>

                </div>

                <div class="flex justify-end mt-8 gap-4 border-t pt-4">
                    <?= button("submit", "Save Schedule & Details", color: "blue", attributes: attribute("id", "save-details-btn")) ?>
                </div>
            </form>
        </div>
        
        <div class="lg:col-span-1 xl:my-auto p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow-inner h-fit">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                <i class="fas fa-info-circle text-indigo-500"></i> Evaluation Overview
            </h3>

            <dl class="space-y-4 text-sm">
                <div class="flex gap-8 items-center">
                    <dt class="font-medium text-gray-600 dark:text-gray-300">Current Status</dt>
                    <dd class="">
                        <?= td_badge($status, $status_color) ?>
                    </dd>
                </div>

                <div class="grid">
                    <div class="flex gap-8 items-center">
                        <dt class="font-medium text-gray-600 dark:text-gray-300">Total Questions</dt>
                        <dd class="text-xl font-bold"><?= $total_questions ?></dd>
                    </div>
                    <p class="text-xs text-indigo-500 hover:text-indigo-600 cursor-pointer" onclick='window.location.href="<?= route("admin.evaluation", ['tab' => '', 'form_code' => $form_code]) ?>"'>
                        <i class="fas fa-arrow-right"></i> Manage Questions
                    </p>
                </div>

                <div class="grid">
                    <div class="flex gap-8 items-center">
                        <dt class="font-medium text-gray-600 dark:text-gray-300">Total Responses</dt>
                        <dd class="text-xl font-bold"><?= $total_responses ?></dd>
                    </div>
                    <p class="text-xs text-indigo-500 hover:text-indigo-600 cursor-pointer" onclick="window.location.href='<?= route("admin.evaluation", ['tab' => 'reporting', 'form_code' => $form_code]) ?>'">
                        <i class="fas fa-chart-bar"></i> View Reports
                    </p>
                </div>

                <div>
                    <dt class="font-medium text-gray-600 dark:text-gray-300">Student Access Code</dt>
                    <dd class="mt-1 text-2xl font-extrabold tracking-wider bg-white dark:bg-gray-800 p-2 rounded-md border border-dashed border-gray-400 dark:border-gray-500">
                        <?= htmlspecialchars($form_data['unique_code']) ?>
                    </dd>
                </div>
            </dl>
        </div>
        
    </div>

<?php elseif ($current_tab === 'reporting'): ?>

    <h2 class="text-xl font-semibold dark:text-gray-200 mb-4">Evaluation Reporting</h2>
    
    <p class="text-gray-600 dark:text-gray-400">
        This section will display aggregate statistics, such as average scores per teacher and per question, and allow for downloading individual response data. 
        Reporting features will be accessible once the evaluation period closes or is manually stopped.
    </p>
    
    <div class="mt-6 p-4 border rounded-md dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
        <p class="font-medium">Reporting Features To Be Implemented:</p>
        <ul class="list-disc list-inside text-sm mt-2">
            <li>Aggregate Scorecard (Overall AVG, AVG per Question/Teacher)</li>
            <li>Individual Response Viewer (Anonymized or Code-based)</li>
            <li>Data Export (CSV/Excel)</li>
        </ul>
    </div>

    <?php elseif ($current_tab === 'reporting'): ?>
    
        <h2 class="text-xl font-semibold dark:text-gray-200 mb-4">Evaluation Reporting</h2>
        
        <p class="text-gray-600 dark:text-gray-400">
            This section will display aggregate statistics, such as average scores per teacher and per question, and allow for downloading individual response data. 
            Reporting features will be accessible once the evaluation period closes or is manually stopped.
        </p>
        
        <div class="mt-6 p-4 border rounded-md dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
            <p class="font-medium">Reporting Features To Be Implemented:</p>
            <ul class="list-disc list-inside text-sm mt-2">
                <li>Aggregate Scorecard (Overall AVG, AVG per Question/Teacher)</li>
                <li>Individual Response Viewer (Anonymized or Code-based)</li>
                <li>Data Export (CSV/Excel)</li>
            </ul>
        </div>

    <?php endif; ?>

</div>

<?php echo modal_start( attribute("id", "question-modal") ); ?>
    <div id="question-modal-content" class="modal-body">
        <?= modal_body_start(attribute("class", "max-h-96")) ?>
            <?= modal_title("Add New Question", attributes: attribute("id", "question-modal-title")) ?>
            
            <form id="question-form" action="<?= url("admin/submit.php") ?>" method="POST">
                <?= hidden_input("submit", "create_evaluation_question", attribute("id", "q-submit-action")) ?>
                <?= hidden_input("form_id", $form_id) ?>
                <?= hidden_input("question_id", "", attribute("id", "question-id")) ?>
                
                <div class="grid grid-cols-1 gap-6">
                    
                    <?= textarea("question_text", "Question Text",  
                                 required: true, attributes: placeholder("e.g., The lecturer was well-prepared for the class.")) ?>

                    <?= select("rating_type", "Response Type",
                               get_question_types(QUESTION_TYPES::EVALUATION), 
                               nullable: "Select Type",
                               required: true,
                               attributes: attribute("id", "rating_type_select"))
                    ?>

                    <div id="options-group" class="hidden p-4 border rounded-md dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Define Options</label>
                        <div id="options-container" class="space-y-3">
                        </div>
                        <div class="mt-4 flex justify-end">
                            <?= button("button", "Add Option", color: "blue", attributes: attribute("id", "add-option-btn")) ?>
                        </div>
                    </div>

                    <?= input_h("number", "Display Order", "question_order", 
                                sub_text: "Determines the position of the question on the form.",
                                required: true,
                                value: count($questions) + 1, // Default to next order
                                attributes: attribute("min", 1))
                    ?>

                    <div class="p-4 border rounded-md dark:border-gray-700">
                        <?= checkbox("is_required", "1", "This question must be answered by the student.") ?>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-4">
                    <?= button("submit", "Save Question", color: "blue", attributes: array_merge(
                        attribute("id", "q-modal-submit-btn"),
                        attribute("class", "w-full")
                    )) ?>
                    <?= button("button", "Cancel", color: "red", attributes: array_merge(
                        attribute("@click", "closeModal()"),
                        attribute("class", "w-full")
                    )) ?>
                </div>
            </form>
        <?= modal_body_end(); ?>
    </div>

    <div id="delete-q-body" class="modal-body hidden">
        <?= delete_item_component("evaluation_questions", form_action: url("admin/submit.php"), 
            delete_text: "Are you sure you want to delete this question? This will delete the question, yet preserve historical responses. You will not be able to reuse this question ID.",
            modal_title: "Delete Question"
            ) ?>
    </div>
<?= modal_end() ?>


<?php
$extra_script = delete_item_component_script(); 
$scripts = <<<HTML
<script>
    // Function to create a new option input field
    function createOptionField(value = '') {
        const uniqueId = 'option-' + Math.random().toString(36).substring(2, 9);
        
        // Use a simple text input for the option label, named 'options[]'
        const fieldHtml = `
            <div class="flex gap-2 items-center" id="\${uniqueId}">
                <input type="text" 
                    name="options[]" 
                    placeholder="Enter option label" 
                    value="\${value}"
                    required 
                    class="flex-grow w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-md shadow-sm p-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <button type="button" data-target="\${uniqueId}" class="remove-option-btn text-red-500 hover:text-red-700 p-2 rounded-full transition duration-150 ease-in-out">
                    <i class="fas fa-times"></i>
                </button>
            </div>`;
        
        $('#options-container').append(fieldHtml);
    }

    // Function to handle showing/hiding the options container
    function toggleOptionsVisibility(type) {
        const isSelectType = (type === 'select_single' || type === 'select_multiple');
        $('#options-group').toggleClass('hidden', !isSelectType);
        
        if (isSelectType && $('#options-container').children().length === 0) {
            // If it's a select type and no options exist (new form), add one default option
            createOptionField();
        }
    }

    $(document).ready(function() {

        // --- Question Modal Logic ---

        // Function to reset the question modal form for creation
        function resetQuestionForm() {
            $('#question-modal-title').text('Add New Question');
            $('#question-form')[0].reset(); 
            $('#q-submit-action').val('create_evaluation_question');
            $('#question-id').val('');
            
            // Set default values for new question
            const currentQuestionsCount = $('#questions-table-body tr:not(.empty-tr)').length;
            $('#question-form input[name=question_order]').val(currentQuestionsCount + 1);
            $('#question-form select[name=rating_type]').val('scale_5'); // Default to scale_5
            $('#question-form input[name=is_required]').prop('checked', true); // Default to required

            // Reset the options container
            $('#options-container').empty();
            $('#options-group').addClass('hidden');
        }

        // Handle "Add New Question" click
        $('button:contains("Add New Question")').on('click', function() {
            resetQuestionForm();
        });

        // Handle "Edit Question" click (Delegated event)
        $(document).on('click', '.edit-question', function() {
            const qData = $(this).data();
            
            resetQuestionForm(); // Reset first
            
            // Set modal for editing
            $('#question-modal-title').text('Edit Question #' + qData.order);
            $('#q-submit-action').val('update_evaluation_question');
            $('#question-id').val(qData.id);

            // Populate form fields
            $('#question-form textarea[name=question_text]').val(qData.text);
            $('#question-form select[name=rating_type]').val(qData.type);
            $('#question-form input[name=question_order]').val(qData.order);
            
            // Handle checkbox (data-attr will be 1 or 0, convert to boolean)
            if (parseInt(qData.required) === 1) {
                 $('#question-form input[name=is_required]').prop('checked', true);
            } else {
                 $('#question-form input[name=is_required]').prop('checked', false);
            }

            // Handle Options (must have been added to the TD action data attributes)
            if (qData.type === 'select_single' || qData.type === 'select_multiple') {
                
                // Re-show the options container
                toggleOptionsVisibility(qData.type);

                // Fetch and decode options from the data attribute (options_json)
                const optionsJson = qData.optionsJson; // Assume you pass this data-attribute
                
                try {
                    // NOTE: In the previous step, we didn't include the JS to fetch options_json.
                    // We assume the PHP rendering populates a data-options-json attribute with base64 encoded JSON string.
                    const options = JSON.parse(atob(optionsJson));
                    
                    $('#options-container').empty(); // Clear default/reset options
                    
                    if (Array.isArray(options) && options.length > 0) {
                        options.forEach(optionText => {
                            createOptionField(optionText);
                        });
                    } else {
                        createOptionField(); // Add one if options_json was empty but type requires it
                    }
                } catch (e) {
                    console.error("Failed to parse options JSON:", e);
                    createOptionField();
                }
            }
        });
        
        // Handle Delete Question setup
        $(document).on('click', '.delete-question', function(){
            const id = $(this).data('id');
            const name = $(this).data('name');
            $("#delete-q-body input[name=question_id]").val(id);
            // Display the name in the component (assuming component uses input[name=name])
            $("#delete-q-body input[name=name]").val(name); 
        });

        // --- Form Submission Handlers ---
        
        // 1. Question Form Submission (AJAX)
        $('#question-form').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitBtn = $('#q-modal-submit-btn');

            // Handle non-checked checkbox value (optional, but good practice for consistency)
            const isRequiredCheckbox = form.find('input[name=is_required]');
            if (!isRequiredCheckbox.is(':checked')) {
                // If unchecked, append a hidden field with the expected value (0)
                form.append('<input type="hidden" name="is_required" value="0">');
            }
            
            ajaxCall({
                url: form.attr('action'),
                data: form.serialize(),
                method: 'POST',
                beforeSend: function() {
                    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner animate-spin"></i> Saving...');
                }
            }).then((response) => {
                const data = response.data;

                if (response.status) {
                    alert_box(data.message || 'Question saved successfully!', 'success'); 
                    // Reload the questions tab to see changes (new order, new question)
                    window.location.reload(); 
                } else {
                    if(response.errors && Object.keys(response.errors).length > 0){
                        display_form_errors(response.errors, form);
                    } else {
                        alert_box("Error: " + (response.errors.system_message || "An unknown error occurred."), 'error');
                    }
                }
            }).finally(() => {
                submitBtn.prop('disabled', false).html('Save Question');
                // Remove temporary hidden field
                form.find('input[name=is_required][type=hidden]').remove();
            });
        });

        // 2. Details & Schedule Form Submission (Page Refresh on success)
        $('#details-schedule-form').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitBtn = $('#save-details-btn');
            
            const startTime = new Date(form.find('[name=start_time]').val());
            const endTime = new Date(form.find('[name=end_time]').val());

            if (startTime >= endTime) {
                alert_box('The "End Date & Time" must be strictly after the "Start Date & Time".', 'error');
                return;
            }
            
            ajaxCall({
                url: form.attr('action'),
                data: form.serialize(),
                method: 'POST',
                beforeSend: function() {
                    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner animate-spin"></i> Saving...');
                }
            }).then((response) => {
                if (response.status) {
                    alert_box(response.message || 'Details updated successfully!', 'success'); 
                    // Reload the current tab to show any changes (e.g., status/dates)
                    window.location.reload(); 
                } else {
                    if(response.errors && Object.keys(response.errors).length > 0){
                        display_form_errors(response.errors, form);
                    } else {
                        alert_box("Error: " + (response.errors.system_message || "An unknown error occurred."), 'error');
                    }
                }
            }).finally(() => {
                submitBtn.prop('disabled', false).html('Save Schedule & Details');
            });
        });

        // event listeners for response type changes
        $('#rating_type_select').on('change', function() {
            const selectedType = $(this).val();
            toggleOptionsVisibility(selectedType);
        });
        
        // Event listener for adding an option
        $('#add-option-btn').on('click', function() {
            createOptionField();
        });
        
        // Event listener for removing an option (delegated)
        $(document).on('click', '.remove-option-btn', function() {
            const targetId = $(this).data('target');
            $('#' + targetId).remove();
            
            // Ensure at least one option is present if the container is visible
            if (!$('#options-group').hasClass('hidden') && $('#options-container').children().length === 0) {
                createOptionField();
            }
        });

        // Initial check on page load if question-modal is open (e.g., from an error refresh)
        toggleOptionsVisibility($('#rating_type_select').val());

        $(".add-new-question").click(function(){
            // Clear previous errors if any
            clear_form_errors($('#question-form'));
        });

        $extra_script
    });
</script>
HTML;
?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');