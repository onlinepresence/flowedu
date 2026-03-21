<?php
require_once relative_path("includes/components.php");

$title = 'Students Data';

// Start output buffering to capture the content
ob_start();
?>

<!-- ============================================== -->
<!-- 1. TEMPLATES FOR PAGINATION ROWS -->
<!-- ============================================== -->

<template id="student-row-template">
    <?= tr_start(attribute("class", "text-gray-700 dark:text-gray-400")) ?>
        <!-- Student Index Number + Profile Picture -->
        <?= td("__INDEX_NUMBER__", "__PROFILE_PIC__") ?>
        <!-- Name -->
        <?= td("__NAME__", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        <!-- Gender -->
        <?= td("__GENDER__", attributes: attribute("class", "px-4 py-3 text-sm capitalize")) ?>
        <!-- Form Level -->
        <?= td("__FORM_LEVEL__", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        <!-- Program -->
        <?= td("__PROGRAM__", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        
        <!-- Actions -->
        <?= td_actions(
            array_merge(
                create_td_action("fas fa-eye", "View", array_merge(
                    attribute("class", "text-blue-500 cursor-pointer hover:text-blue-600 action-view action-btn"),
                    attribute("@click", "openModal()"),
                    data_attr("id", "__USER_ID__")
                )),
                create_td_action("fas fa-edit", "Edit", array_merge(
                    attribute("class", "text-yellow-500 cursor-pointer hover:text-yellow-600 action-edit action-btn"),
                    attribute("@click", "openModal()"),
                    data_attr("id", "__USER_ID__")
                ))
            )
        ) ?>
    <?= tr_end() ?>
</template>

<template id="empty-row-template">
    <?= td_empty("No students found", 6) ?>
</template>

<!-- ============================================== -->
<!-- 2. FILTERS AND DOWNLOAD BUTTON -->
<!-- ============================================== -->

<div>
    <h2 class="mb-2 text-lg font-bold">Filters</h2>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <?= 
            select("level", "Student Level", [
                ["id" => 100, "text" => "Level 100"],
                ["id" => 200, "text" => "Level 200"],
                ["id" => 300, "text" => "Level 300"],
                ["id" => 400, "text" => "Level 400"]
            ], "All Levels", attributes: array_merge(
                attribute("id", "level"), data_attr("filter", "level")
            ))
        ?>
        <?php 
            $faculties = faculties();
            echo select("faculty", "Faculty", $faculties, "All Faculties", keys: select_keys("id", "name"), attributes: array_merge(
                attribute("id", "faculty"), data_attr("filter", "faculty")
            ))
        ?>
        <?php 
            $departments = departments();
            echo select("department", "Department", $departments, "All Departments", keys: select_keys("id", "name"), attributes: array_merge(
                attribute("id", "department"), data_attr("filter", "department")
            ))
        ?>
        <?php 
            $programs = programs();
            echo select("program", "Program", $programs, "All Programs", keys: select_keys("id", "name"), attributes: array_merge(
                attribute("id", "program"), data_attr("filter", "program")
            ))
        ?>
    </div>
    <div class="flex gap-2 mt-4 max-w-52">
        <?= button("button", "Download", attributes: attribute("id", "download")) ?>
    </div>
</div>

<!-- ============================================== -->
<!-- 3. RESULTS TABLE WITH PAGINATION UI -->
<!-- ============================================== -->

<div class="w-full mt-3 overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <?= table_start(attribute("class", "w-full whitespace-no-wrap")) ?>
            <?= thead_start() ?>
                <?= tr_start() ?>
                    <?= th('Student', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Name', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Gender', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Form Level', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Program', attribute('class', 'px-4 py-3')) ?>
                    <?= th('Actions', attribute('class', 'px-4 py-3')) ?>
                <?= tr_end() ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading students...", 6) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>
    
    <!-- Pagination Footer -->
    <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
        <p class="flex items-center col-span-3 gap-2">
            Showing <span id="page-info" class="mx-1">0–0</span> of <span id="total-count">0</span> students
        </p>
        <span class="col-span-2"></span>
        <!-- Pagination Buttons -->
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

<!-- View / Edit student modal -->
<?= modal_start(array_merge(
    attribute("id", "student-record-modal"),
    attribute("class", "max-h-[90vh] overflow-y-auto max-w-4xl")
));
echo modal_header(); ?>
<div class="modal-body">
    <?= modal_body_start(attribute("class", "max-w-4xl w-full")) ?>
        <?= modal_title("Student record", attribute("id", "student-modal-title")) ?>
        <p id="student-modal-load" class="hidden no-print flex items-center justify-center gap-2 py-8 text-gray-600 dark:text-gray-300">
            <i class="fas fa-spinner animate-spin"></i>
            <span>Loading student…</span>
        </p>
        <p id="student-modal-error" class="hidden no-print text-center text-red-600 dark:text-red-400 py-6 px-4 border border-red-200 dark:border-red-800 rounded"></p>

        <div id="student-modal-forms" class="hidden">
            <form id="admin-student-record-form" method="post" enctype="multipart/form-data" class="space-y-4">
                <?php
                $user = [];
                $admin_student_form = true;
                $is_student = false;
                require relative_path("student/setup/personal-form.php");
                ?>
            </form>

            <form id="admin-guardian-record-form" method="post" class="mt-6 space-y-4">
                <?= fieldset_start(array_merge(attribute("id", "guardian-form"), attribute("class", "mt-2"))) ?>
                    <?= fieldset_legend("Guardian information") ?>
                    <?= input("hidden", "", "student_id", "", false) ?>
                    <?= input("hidden", "", "id", "0", false) ?>
                    <?php
                    $guardian = [];
                    $is_student = true;
                    require_once relative_path("student/setup/guardian-form.php");
                    ?>
                <?= fieldset_end() ?>
            </form>

            <div class="no-print mt-6 flex flex-wrap gap-3">
                <?= button("button", "Print", "", "", "", attribute("id", "btn-student-print")) ?>
                <?= button("button", "Save changes", "", "", "blue", attribute("id", "btn-student-save")) ?>
                <?= button("button", "Cancel", "", "", "red", array_merge(
                    attribute("id", "btn-student-cancel"),
                    attribute("@click", "closeModal()")
                )) ?>
            </div>
        </div>
    <?= modal_body_end() ?>
</div>
<?= modal_end() ?>

<?php
    $pagination_script = pagination_script(
        'admin/ajax/student.php', 
        'student-row-template', 
        'students', 
        [
            "PROFILE_PIC" => "profile_pic", 
            "INDEX_NUMBER" => "index_number", 
            "NAME" => "fullname",
            "GENDER" => "gender", 
            "PROGRAM" => "program_name", 
            "FORM_LEVEL" => "current_year",
            "USER_ID" => "user_id" // Added for actions
        ],
        ["submit" => "fetch_students"], 
        ["profile_pic"]
    );
        
    $scripts = <<<HTML
    <script>
        $(document).ready(function(){
            $pagination_script

            let lastStudentPayload = null;

            function alpineRoot() {
                const el = document.documentElement;
                return el && el._x_dataStack && el._x_dataStack[0] ? el._x_dataStack[0] : null;
            }
            
            function resetStudentModalForms() {
                $('#admin-student-record-form .file-preview-link').remove();
                $('#admin-student-record-form input[name="profile_pic"]').prop('disabled', false);
                $('#student-modal-error').addClass('hidden').text('');
            }

            function setStudentModalMode(mode) {
                const \$all = $('#admin-student-record-form, #admin-guardian-record-form').find('input, select, textarea');
                if (mode === 'view') {
                    \$all.prop('disabled', true);
                    $('#btn-student-save').addClass('hidden');
                } else {
                    \$all.prop('disabled', false);
                    $('#admin-student-record-form input[name="index_number"]').prop('readonly', true);
                    $('#btn-student-save').removeClass('hidden');
                }
                $('#student-modal-title').text(mode === 'view' ? 'View student' : 'Edit student');
            }

            function loadStudentIntoModal(userId, mode) {
                resetStudentModalForms();
                $('#student-modal-forms').addClass('hidden');
                $('#student-modal-error').addClass('hidden');
                $('#student-modal-load').removeClass('hidden');

                ajaxCall({
                    url: "/admin/submit.php",
                    data: { submit: "fetch_user", id: userId, type: "student" },
                    beforeSend: function () {}
                }).then(function (response) {
                    $('#student-modal-load').addClass('hidden');
                    if (!response) {
                        $('#student-modal-error').removeClass('hidden').text('Could not load student.');
                        return;
                    }
                    if (response.status && response.data && response.data.student) {
                        const data = response.data;
                        lastStudentPayload = data;
                        fill_form(data.student, $("#student-form-grid"), { profile_pic: "View Profile Picture" });
                        $('#admin-guardian-record-form input[name="student_id"]').val(data.student.student_id);
                        $('#admin-guardian-record-form input[name="id"]').val(data.guardian && data.guardian.id ? data.guardian.id : 0);
                        if (data.guardian) {
                            fill_form(data.guardian, $("#guardian-form"));
                        } else {
                            fill_form({ name: '', relationship: '', address: '', phone_number: '', email: '' }, $("#guardian-form"));
                        }
                        $('#admin-student-record-form select[name="program_id"]').trigger('change');
                        $('#admin-student-record-form select[name="hall_id"]').trigger('change');
                        $('#student-modal-forms').removeClass('hidden');
                        setStudentModalMode(mode);
                    } else {
                        const msg = (response.errors && (response.errors.system_message || response.errors.system_error)) || "Could not load student.";
                        $('#student-modal-error').removeClass('hidden').text(msg);
                    }
                });
            }

            $(document).on('click', '.action-view', function () {
                const uid = $(this).data('id');
                if (uid) loadStudentIntoModal(uid, 'view');
            });

            $(document).on('click', '.action-edit', function () {
                const uid = $(this).data('id');
                if (uid) loadStudentIntoModal(uid, 'edit');
            });

            $('#admin-student-record-form').on('change', 'select[name="program_id"]', function () {
                const \$o = $(this).find('option:selected');
                const dept = \$o.attr('data-dept-id');
                if (dept != null && dept !== '') {
                    $('input[name="department_id"]', '#admin-student-record-form').val(dept);
                }
            });

            $('#admin-student-record-form').on('change', 'select[name="hall_id"]', function () {
                /* hall cost fields are omitted in admin context; department still syncs from program */
            });

            $('#btn-student-print').on('click', function () {
                if (!lastStudentPayload || !lastStudentPayload.student || !lastStudentPayload.student.index_number) return;
                const path = '/admin/students/print/' + encodeURIComponent(lastStudentPayload.student.index_number);
                window.open(path, '_blank', 'noopener,noreferrer');
            });

            $('#btn-student-save').on('click', function () {
                const \$sForm = $('#admin-student-record-form');
                const \$gForm = $('#admin-guardian-record-form');
                \$sForm.find('input, select, textarea').prop('disabled', false);
                \$gForm.find('input, select, textarea').prop('disabled', false);

                const fd1 = new FormData(\$sForm[0]);
                fd1.append('submit', 'admin_update_student');
                ajaxCall({
                    url: "/admin/submit.php",
                    method: "POST",
                    data: fd1,
                    sendRaw: true
                }).then(function (res) {
                    if (res.status) {
                        const fd2 = new FormData(\$gForm[0]);
                        fd2.append('submit', 'admin_save_guardian');
                        ajaxCall({
                            url: "/admin/submit.php",
                            method: "POST",
                            data: fd2,
                            sendRaw: true
                        }).then(function (res2) {
                            if (res2.status) {
                                alert_box((res2.data && res2.data.message) ? res2.data.message : 'Saved.', 'success');
                                if (typeof loadPaginatedData === 'function') {
                                    loadPaginatedData(typeof currentPage !== 'undefined' ? currentPage : 1);
                                }
                                const root = alpineRoot();
                                if (root && typeof root.closeModal === 'function') root.closeModal();
                            } else {
                                display_form_errors(res2.errors || {}, \$gForm);
                            }
                        });
                    } else {
                        display_form_errors(res.errors || {}, \$sForm);
                    }
                });
            });

            // Handle filter changes (this is key for pagination with filters)
            $('#level, #faculty, #department, #program').on('change', function() {
                // When a filter changes, reset to page 1
                loadPaginatedData(1); 
            });

            // Handle search button click (assuming you add a search input/button)
            $('#search-btn').on('click', function() {
                loadPaginatedData(1);
            });

            $("#download").click(function () {
                const level = $("#level").val();
                const faculty = $("#faculty").val();
                const department = $("#department").val();
                const program = $("#program").val();

                // Note: The download logic still uses a blocking AJAX call, 
                // which is acceptable for file downloads.
                $.ajax({
                    url: relative_path("admin/ajax/student.php"),
                    type: "POST",
                    data: {
                        level: level,
                        faculty: faculty,
                        department: department,
                        program: program,
                        submit: "download_students",
                        response_type: "json"
                    },
                    dataType: "json",
                    beforeSend: function () {
                        $("#download").prop("disabled", true).text("Processing...");
                    },
                    success: function (response) {
                        $("#download").prop("disabled", false).text("Download");

                        if (response.status && response.data && response.data.file_url) {
                            // file_url is expected to be provided by the backend, e.g. "files/export.xlsx"
                            const link = document.createElement("a");
                            link.href = response.data.file_url;
                            link.download = response.data.filename || "students.xlsx";
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        } else {
                            // Handle error message
                            const errorMsg = response.errors && response.errors.length
                                ? response.errors.join(" ")
                                : "An unknown error occurred.";
                            // In a real application, replace alert with a modal/message box
                            console.error(errorMsg); 
                        }
                    },
                    error: function (xhr, status, error) {
                        $("#download").prop("disabled", false).text("Download");
                        console.error("Request failed: " + error);
                    }
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