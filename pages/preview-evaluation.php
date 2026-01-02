<?php
require_once relative_path("includes/components.php");
require_once relative_path("includes/question-components.php");

$title = "Preview for $form_code"; // Set the page title

// get the form
$form = fetchData("*", "evaluation_forms", ["unique_code" => $form_code]);

if(!$form) {
    session('errors.system_message', "Evaluation form not found.");
    header("Location: ".back());
    exit;
}

// get questions
$questions = fetchData("*", "evaluation_questions", ["form_id" => $form['id']], 0, order_by: "question_order", asc: true);

// Start output buffering to capture the content
ob_start();
?>

<div class="container grid px-6 mx-auto">
    <h1 class="mb-2 text-xl font-extrabold text-gray-800 dark:text-gray-100">Evaluation Form</h1>
    <h2 class="mb-4 text-xl font-semibold text-indigo-600 dark:text-indigo-400"><?= htmlspecialchars($form['title']) ?></h2>
    
    <div class="flex items-center justify-between mb-6 text-sm text-gray-600 dark:text-gray-400">
        <p>Form Code: <span class="font-mono font-bold text-gray-700 dark:text-gray-300"><?= htmlspecialchars($form['unique_code']) ?></span></p>
        <p>Due Date: <span class="font-semibold text-red-600 dark:text-red-400"><?= date('M d, Y H:i', strtotime($form['end_time'])) ?></span></p>
    </div>

    <?php 
        include_once relative_path("pages/views/question-section.php");
    ?>
</div>

<?php $scripts = <<<HTML
<script>
    $(document).ready(function(){
        
    })
</script>
HTML;
?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
