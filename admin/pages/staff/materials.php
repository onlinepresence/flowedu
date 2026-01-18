<?php
require_once relative_path("includes/components.php");

$title = 'Course Materials Review'; // Set the page title
$page_title = 'Course Materials Review';

// Mock materials data - replace with actual database queries
$materials_data = [
    [
        'id' => 1,
        'title' => 'Introduction to Programming - Lecture 1',
        'type' => 'pdf',
        'course_code' => 'CS 101',
        'course_name' => 'Introduction to Computer Science',
        'teacher_name' => 'Dr. John Smith',
        'uploaded_date' => '2025-01-15',
        'file_size' => '2.5 MB',
        'downloads' => 45,
        'status' => 'pending'
    ],
    [
        'id' => 2,
        'title' => 'Database Design Principles',
        'type' => 'pdf',
        'course_code' => 'CS 301',
        'course_name' => 'Database Systems',
        'teacher_name' => 'Prof. Jane Doe',
        'uploaded_date' => '2025-01-18',
        'file_size' => '3.2 MB',
        'downloads' => 32,
        'status' => 'approved'
    ],
    [
        'id' => 3,
        'title' => 'Practice Exercises - Week 3',
        'type' => 'pdf',
        'course_code' => 'MATH 201',
        'course_name' => 'Mathematics for Computing',
        'teacher_name' => 'Dr. Michael Brown',
        'uploaded_date' => '2025-01-22',
        'file_size' => '1.8 MB',
        'downloads' => 38,
        'status' => 'pending'
    ]
];

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Review and approve course materials uploaded by teachers
            </p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 p-4 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Filter by Status
                </label>
                <select id="status-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Status</option>
                    <option value="pending">Pending Approval</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Filter by Teacher
                </label>
                <select id="teacher-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Teachers</option>
                    <option value="Dr. John Smith">Dr. John Smith</option>
                    <option value="Prof. Jane Doe">Prof. Jane Doe</option>
                    <option value="Dr. Michael Brown">Dr. Michael Brown</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Filter by Course
                </label>
                <select id="course-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Courses</option>
                    <option value="CS 101">CS 101</option>
                    <option value="CS 301">CS 301</option>
                    <option value="MATH 201">MATH 201</option>
                </select>
            </div>
            <div class="flex items-end">
                <?= button("button", "Reset Filters", attributes: array_merge(
                    attribute("id", "reset-filters"),
                    attribute("class", "w-full")
                )) ?>
            </div>
        </div>
    </div>

    <?php if (empty($materials_data)): ?>
        <?= placeholder_element(
            "No Materials Found",
            "No course materials have been uploaded by teachers yet.",
            "fas fa-file-upload"
        ) ?>
    <?php else: ?>
        <!-- Materials Table -->
        <?= table_start() ?>
            <?= thead_start() ?>
                <?= th("Title") ?>
                <?= th("Course") ?>
                <?= th("Teacher") ?>
                <?= th("Type") ?>
                <?= th("Uploaded") ?>
                <?= th("Size") ?>
                <?= th("Downloads") ?>
                <?= th("Status") ?>
                <?= th("Actions") ?>
            <?= thead_end() ?>
            <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                <?php foreach ($materials_data as $material): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <?= td(htmlspecialchars($material['title']), attributes: attribute('class', 'font-medium')) ?>
                        <?= td(htmlspecialchars($material['course_code'] . ' - ' . $material['course_name'])) ?>
                        <?= td(htmlspecialchars($material['teacher_name'])) ?>
                        <?= td(strtoupper($material['type'])) ?>
                        <?= td(date('M d, Y', strtotime($material['uploaded_date']))) ?>
                        <?= td($material['file_size']) ?>
                        <?= td($material['downloads']) ?>
                        <?php 
                            $status_color = $material['status'] === 'approved' ? 'green' : 
                                          ($material['status'] === 'rejected' ? 'red' : 'yellow');
                        ?>
                        <?= td_badge(ucfirst($material['status']), $status_color) ?>
                        
                        <?= td_actions(array_merge(
                            create_td_action("fas fa-eye", "View", attribute("class", "text-blue-600 hover:text-blue-800 dark:text-blue-400 px-3 py-1")),
                            create_td_action("fas fa-check", "Approve", attribute("class", "text-green-600 hover:text-green-800 dark:text-green-400 px-3 py-1")),
                            create_td_action("fas fa-times", "Reject", attribute("class", "text-red-600 hover:text-red-800 dark:text-red-400 px-3 py-1")),
                            create_td_action("fas fa-trash", "Delete", attribute("class", "text-gray-600 hover:text-gray-800 dark:text-gray-400 px-3 py-1"))
                        )) ?>
                    </tr>
                <?php endforeach; ?>
            <?= tbody_end() ?>
        <?= table_end() ?>
    <?php endif; ?>
</div>

<?php $scripts = <<<HTML
<script>
$(document).ready(function(){
    $("#status-filter, #teacher-filter, #course-filter").change(function(){
        // Filter logic would be implemented here
        alert_box("Filter functionality will be implemented", "info");
    });
    
    $("#reset-filters").click(function(){
        $("#status-filter, #teacher-filter, #course-filter").val("");
    });
});

function viewMaterial(id) {
    alert_box("View material functionality for ID: " + id, "info");
}

function approveMaterial(id) {
    if(confirm("Are you sure you want to approve this material?")) {
        $.ajax({
            url: relative_path("admin/submit.php"),
            method: "POST",
            data: {
                submit: "approve_material",
                material_id: id,
                response_type: "json"
            },
            success: function(response) {
                if(response.status) {
                    alert_box(response.data.message || "Material approved successfully", "success");
                    location.reload();
                } else {
                    if(response.errors) {
                        display_form_errors(response.errors, $('body'));
                    } else {
                        alert_box("Failed to approve material", "danger");
                    }
                }
            },
            error: function() {
                alert_box("System error occurred", "danger");
            }
        });
    }
}

function rejectMaterial(id) {
    const reason = prompt("Please provide a reason for rejection:");
    if(reason !== null) {
        $.ajax({
            url: relative_path("admin/submit.php"),
            method: "POST",
            data: {
                submit: "reject_material",
                material_id: id,
                rejection_reason: reason,
                response_type: "json"
            },
            success: function(response) {
                if(response.status) {
                    alert_box(response.data.message || "Material rejected successfully", "success");
                    location.reload();
                } else {
                    if(response.errors) {
                        display_form_errors(response.errors, $('body'));
                    } else {
                        alert_box("Failed to reject material", "danger");
                    }
                }
            },
            error: function() {
                alert_box("System error occurred", "danger");
            }
        });
    }
}

function deleteMaterial(id) {
    if(confirm("Are you sure you want to delete this material? This action cannot be undone.")) {
        $.ajax({
            url: relative_path("admin/submit.php"),
            method: "POST",
            data: {
                submit: "delete_material",
                material_id: id,
                response_type: "json"
            },
            success: function(response) {
                if(response.status) {
                    alert_box(response.data.message || "Material deleted successfully", "success");
                    location.reload();
                } else {
                    alert_box(response.errors ? response.errors.system_error : "Failed to delete material", "danger");
                }
            },
            error: function() {
                alert_box("System error occurred", "danger");
            }
        });
    }
}
</script>
HTML;
?>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
?>
