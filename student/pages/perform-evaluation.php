<?php
require_once relative_path("includes/components.php");
require_once relative_path("includes/question-components.php");

$title = 'Complete Lecturer Evaluation';

$evaluation_info = fetchData("f.id AS form_id, e.id AS evaluation_id, e.status, e.teacher_id, e.response_code", [
    "join" => "evaluation_forms evaluation_responses",
    "on" => "id form_id",
    "alias" => "f e",
    "add_on" => ["e.student_id = ".user()["id"]]
], ["f.unique_code" => $code], join_type: "LEFT");

if(!$evaluation_info) {
    session('errors.system_message', "Evaluation form not found.");
    header("Location: ".url("student/evaluation"));
    exit;
}elseif($evaluation_info['status'] === 'submitted') {
    session('system_message', "You have already submitted this evaluation.");
    header("Location: ".back());
    exit;
}elseif(!$evaluation_info['evaluation_id']){    
    // create a new response entry
    $response_details = create_evaluation_response($evaluation_info['form_id']);
    
    if(is_null($response_details)){
        session('errors.system_message', "No teachers were found for your courses. Please contact the administrator.");
        header("Location: ".url("student/evaluation"));
        exit;
    }elseif(is_array($response_details)){
        $answers = [];
        $evaluation_info["teacher_id"] = $response_details["teacher_id"];
        $evaluation_info["response_code"] = $response_details["response_code"];
    }
}else{
    // fetch answers if any for this form
    $answers = fetchData("*", "response_details", ["response_id" => $evaluation_info['evaluation_id']], 0);

    if(!$answers){
        $answers = [];
    }else{
        $answers = pluck($answers, "question_id", "array", true);
    }
}

// get the form
$form = fetchData("*", "evaluation_forms", ["id" => $evaluation_info['form_id']]);

// get the teacher being evaluated
$teacher = fetchData("CONCAT(lastname, ' ', othernames) AS fullname", "teachers", ["user_id" => $evaluation_info['teacher_id']]);

// get questions
$questions = fetchData("*", "evaluation_questions", ["form_id" => $evaluation_info['form_id']], 0, order_by: "question_order", asc: true);

// Start output buffering to capture the content
ob_start();
?>

<div class="container grid md:px-6 mx-auto">
    <h1 class="mb-2 text-xl font-extrabold text-gray-800 dark:text-gray-100">Evaluation Form</h1>
    <h2 class="mb-4 text-xl font-semibold text-indigo-600 dark:text-indigo-400"><?= htmlspecialchars($form['title'] . " - " . $teacher["fullname"]) ?></h2>
    
    <div class="flex items-center justify-between flex-wrap mb-6 text-sm text-gray-600 dark:text-gray-400">
        <p>Form Code: <span class="font-mono font-bold text-gray-700 dark:text-gray-300"><?= htmlspecialchars($form['unique_code']) ?></span></p>
        <p>Due Date: <span class="font-semibold text-red-600 dark:text-red-400"><?= date('M d, Y H:i', strtotime($form['end_time'])) ?></span></p>
    </div>

    <?php 
        include_once relative_path("pages/views/question-section.php");
    ?>

</div>

<?php 
// No extra scripts needed for this demo, but the block is included for structure.
$scripts = <<<HTML
<script>
    // Any necessary client-side form validation or draft saving logic would go here.
</script>
HTML;
?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');