<?php
require_once relative_path("includes/components.php");

$title = 'Your Dashboard'; // Set the page title

// Start output buffering
ob_start();
?>

<!-- Dashboard summary cards -->
<?= card_container_start() ?>
    <?= dashboard_card_btn("Pending Students", fetchData("COUNT(id) as total", "students", "approved = 0")["total"], "fas fa-user-clock") ?>
    <?= dashboard_card_btn("Approved Students", fetchData("COUNT(id) as total", "students", "approved = 1")["total"], "fas fa-user-check", "green") ?>
<?= card_container_end() ?>

<!-- filters -->
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
</div>

<!-- Table for unapproved students -->
<div class="w-full mt-6 overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <?= table_start(attribute("class", "w-full whitespace-no-wrap")) ?>
            <?= thead_start() ?>
                <?= tr_start() ?>
                    <?= th("Admission Number", attribute("class", "px-4 py-3")) ?>
                    <?= th("Name", attribute("class", "px-4 py-3")) ?>
                    <?= th("Gender", attribute("class", "px-4 py-3")) ?>
                    <?= th("Chosen Program", attribute("class", "px-4 py-3")) ?>
                    <?= th("Guardian Information", attribute("class", "px-4 py-3")) ?>
                    <?= th("Submission Date", attribute("class", "px-4 py-3")) ?>
                    <?= th("Actions", attribute("class", "px-4 py-3")) ?>
                <?= tr_end() ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?= td_empty("Loading unapproved students...", 7) ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    </div>

    <!-- Pagination section -->
    <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
        <p class="flex items-center col-span-3 gap-2">
            Showing <span id="page-info" class="mx-1">0–0</span> of <span id="total-count">0</span> students
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

<!-- Table row templates -->
<template id="unapproved-row-template">
    <?= tr_start(attribute("class", "text-gray-700 dark:text-gray-400")) ?>
        <?= td("__INDEX_NUMBER__", "__PROFILE_PIC__") ?>
        <?= td("__NAME__", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        <?= td("__GENDER__", attributes: attribute("class", "px-4 py-3 text-sm capitalize")) ?>
        <?= td("__PROGRAM__", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        <?= td("__GUARDIAN_PROVIDED__", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        <?= td("__CREATED_AT__", attributes: attribute("class", "px-4 py-3 text-sm")) ?>
        
        <?= td_actions(
            array_merge(
                create_td_action(
                    "fas fa-check",
                    "Approve",
                    array_merge(
                        attribute("class", "text-green-500 hover:text-green-600 cursor-pointer approve"),
                        data_attr("user-id", "__USER_ID__"),
                        data_attr("index-number", "__INDEX_NUMBER__"),
                        data_attr("guardian-status", "__GUARDIAN_STATUS__")
                    )
                ),
                create_td_action(
                    "fas fa-eye",
                    "View",
                    array_merge(
                        attribute("class", "text-blue-500 hover:text-blue-600 cursor-pointer view"),
                        data_attr("id", "__USER_ID__"),
                        attribute("@click", "openModal")
                    )
                )
            )
        ) ?>
    <?= tr_end() ?>
</template>

<template id="empty-row-template">
    <?= td_empty("No unapproved students found", 7); ?>
</template>

<!-- Approval Modal -->
<?php echo modal_start( array_merge(
    attribute("id", "modal"),
    attribute("class", "max-h-[80%] overflow-y-auto max-w-[80%]")
)); echo modal_header(); ?>
    <div id="view-body" class="modal-body">
        <?= modal_body_start(attribute("style", "max-width: 1024px")) ?>
            <?= modal_title("In Progress") ?>
            <form id="modal-form">
                <?php require relative_path("student/setup/personal-form.php"); ?>
                <?= fieldset_start(array_merge(attribute("id", "guardian-form"), attribute("class", "mt-6"))) ?>
                    <?= fieldset_legend("Guardian Information") ?>
                    <?php require_once relative_path("/student/setup/guardian-form.php") ?>
                <?= fieldset_end() ?>

                <!-- Modal Buttons -->
                <div class="mt-4 w-full flex gap-4">
                    <?= button("button", "Approve", color: "blue", attributes: attribute("class", "approve")) ?>
                    <?= button("reset", "Cancel", color: "red", attributes: array_merge(
                        attribute("class", "reject"), attribute("@click", "closeModal()")
                    )) ?>
                </div>
            </form>
            <p id="modal-load-element" class="hidden text-center gap-4 mt-4 border py-6 px-4">
                <i class="fas fa-spinner animate-spin"></i>
                <span>Fetching Student details</span>
            </p>
            <p class="hidden text-center border py-6 px-4" id="modal-status"></p>
        <?= modal_body_end(); ?>
    </div>
<?= modal_end() ?>

<?php
// Inject the pagination logic
$pagination_script = pagination_script(
    'admin/ajax/student.php', // endpoint
    'unapproved-row-template',          // row template ID
    'students',                         // table body container ID
    [
        "USER_ID" => "user_id",
        "INDEX_NUMBER" => "index_number",
        "NAME" => "fullname",
        "GENDER" => "gender",
        "PROGRAM" => "program_name",
        "GUARDIAN" => "guardian_text",
        "CREATED_AT" => "created_at",
        "GUARDIAN_STATUS" => "guardian",
        "GUARDIAN_PROVIDED" => "guardian_provided",
        "PROFILE_PIC" => "profile_pic"
    ],
    ["submit" => "fetch_unapproved_students"],
    ["profile_pic"]
);
?>

<?php
$scripts = <<<HTML
<script>
$(document).ready(function() {
    $pagination_script

    // Handle filter changes
    $('#level, #faculty, #department, #program').on('change', function() {
        loadPaginatedData(1);
    });

    // Delegate events to dynamically loaded elements
    $(document).on('click', '.approve', function() {
        const index_number = $(this).attr('data-index-number');
        const guardian_status = $(this).attr('data-guardian-status');
        const user_id = $(this).attr('data-user-id');

        if (parseInt(guardian_status) === 0) {
            alert("Guardian information not provided yet. Cannot approve");
        } else {
            window.location.replace("/admin/approve-student/" + index_number + "/" + guardian_status + "/" + user_id);
        }
    });

    $(document).on('click', '.view', function() {
        const student_id = $(this).data('id');
        ajaxCall({
            url: "/admin/submit.php",
            data: {submit: "fetch_user", id: student_id, type: "student"},
            beforeSend: function() {
                $("#modal-form, #modal-status").addClass("hidden");
                $("#modal-load-element").removeClass("hidden").addClass("show");
            }
        }).then((response) => {
            setTimeout(() => {
                if (response.status) {
                    const data = response.data;
                    fill_form(data.student, $("#student-form-grid"), {
                        profile_pic: "View Profile Picture"
                    });
                    if (data.guardian) fill_form(data.guardian, $("#guardian-form"));
                    $("#modal-form .approve").attr({
                        "data-index-number": data.student.index_number,
                        "data-guardian-status": data.guardian ? 1 : 0,
                        "data-user-id": data.student.user_id
                    });
                    $("#modal-form").removeClass("hidden");
                    $("#modal-load-element").addClass("hidden");
                } else {
                    $("#modal-load-element").addClass("hidden");
                    $("#modal-status").removeClass("hidden").html(response.errors.system_message);
                }
            }, 500);
        }).error((error) => {
            console.log(error);
        });
    });
});
</script>
HTML;
?>

<?php
$content = ob_get_clean();
require relative_path('layouts/auth.php');
