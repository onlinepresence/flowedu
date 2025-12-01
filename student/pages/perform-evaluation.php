<?php
require_once relative_path("includes/components.php");

$title = 'Complete Lecturer Evaluation';

// --- MOCK DATA SIMULATION ---
// In a real application, this data would be fetched from the database based on the 'code' parameter
// e.g., $evaluation_code = $_GET['code'] ?? '';
$mock_evaluation = [
    'title' => 'Lecturer Performance Evaluation - Dr. Jane Doe',
    'code' => 'LPE-JD-123',
    'due_date' => '2025-12-31 23:59:00',
    'questions' => [
        // Likert Scale (5-point)
        ['id' => 1, 'type' => 'likert', 'text' => 'The lecturer was well-prepared for all classes.', 'max_scale' => 5],
        ['id' => 2, 'type' => 'likert', 'text' => 'The lecturer communicated complex topics clearly.', 'max_scale' => 5],
        ['id' => 3, 'type' => 'likert', 'text' => 'The course material was relevant to my program of study.', 'max_scale' => 5],
        // Open Text (Short)
        ['id' => 4, 'type' => 'text', 'text' => 'What did you find most beneficial about the teaching style?'],
        // Likert Scale (4-point)
        ['id' => 5, 'type' => 'likert', 'text' => 'I would recommend this lecturer to other students.', 'max_scale' => 4],
        // Long Text Area
        ['id' => 6, 'type' => 'textarea', 'text' => 'Please provide any additional comments or constructive feedback for the lecturer (min 10 words).'],
    ]
];

// Likert Scale Labels (Standard 5-point, used for max_scale=5 questions)
$likert_labels_5 = [
    1 => 'Strongly Disagree',
    2 => 'Disagree',
    3 => 'Neutral',
    4 => 'Agree',
    5 => 'Strongly Agree',
];

// Likert Scale Labels (4-point, used for max_scale=4 questions)
$likert_labels_4 = [
    1 => 'Poor',
    2 => 'Fair',
    3 => 'Good',
    4 => 'Excellent',
];

// --- RENDERING FUNCTIONS ---

/**
 * Renders a Likert scale question with radio buttons.
 * @param array $question Question data.
 * @return string HTML for the question block.
 */
function render_likert_question(array $question): string {
    global $likert_labels_5, $likert_labels_4;
    
    $question_id = $question['id'];
    $question_text = htmlspecialchars($question['text']);
    $max_scale = $question['max_scale'] ?? 5;
    
    $labels = ($max_scale === 5) ? $likert_labels_5 : $likert_labels_4;
    
    $options_html = "";
    for ($i = 1; $i <= $max_scale; $i++) {
        $label = $labels[$i] ?? $i;
        $name = "q_{$question_id}";
        $id = "{$name}_{$i}";
        
        $options_html .= <<<HTML
            <div class="flex items-center space-x-2">
                <input type="radio" id="{$id}" name="{$name}" value="{$i}" class="w-4 h-4 text-indigo-600 transition duration-150 ease-in-out form-radio dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-indigo-500">
                <label for="{$id}" class="text-sm font-medium text-gray-700 dark:text-gray-400">{$label}</label>
            </div>
        HTML;
    }

    return <<<HTML
        <div class="p-6 mb-6 bg-white border border-gray-100 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700">
            <h4 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">{$question_id}. {$question_text}</h4>
            <div class="flex flex-wrap items-center justify-between space-y-3 sm:space-y-0 sm:space-x-4">
                {$options_html}
            </div>
        </div>
    HTML;
}

/**
 * Renders a text or textarea question.
 * @param array $question Question data.
 * @return string HTML for the question block.
 */
function render_text_question(array $question): string {
    $question_id = $question['id'];
    $question_text = htmlspecialchars($question['text']);
    $name = "q_{$question_id}";
    $is_long = $question['type'] === 'textarea';
    
    if ($is_long) {
        $input_html = <<<HTML
            <textarea id="{$name}" name="{$name}" rows="5" class="w-full p-3 mt-1 transition duration-150 ease-in-out border border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Type your detailed response here..."></textarea>
        HTML;
    } else {
        $input_html = <<<HTML
            <input type="text" id="{$name}" name="{$name}" class="w-full p-3 mt-1 transition duration-150 ease-in-out border border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Type your response here...">
        HTML;
    }

    return <<<HTML
        <div class="p-6 mb-6 bg-white border border-gray-100 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700">
            <h4 class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200">{$question_id}. {$question_text}</h4>
            {$input_html}
        </div>
    HTML;
}


// Start output buffering to capture the content
ob_start();
?>

<div class="container grid px-6 mx-auto">
    <h1 class="mb-2 text-3xl font-extrabold text-gray-800 dark:text-gray-100">Evaluation Form</h1>
    <h2 class="mb-4 text-xl font-semibold text-indigo-600 dark:text-indigo-400"><?= htmlspecialchars($mock_evaluation['title']) ?></h2>
    
    <div class="flex items-center justify-between mb-6 text-sm text-gray-600 dark:text-gray-400">
        <p>Form Code: <span class="font-mono font-bold text-gray-700 dark:text-gray-300"><?= htmlspecialchars($mock_evaluation['code']) ?></span></p>
        <p>Due Date: <span class="font-semibold text-red-600 dark:text-red-400"><?= date('M d, Y H:i', strtotime($mock_evaluation['due_date'])) ?></span></p>
    </div>

    <form method="POST" action="javascript:alert('Form submitted/saved!');" class="space-y-6">
        
        <?php foreach ($mock_evaluation['questions'] as $question): ?>
            <?php 
                if ($question['type'] === 'likert') {
                    echo render_likert_question($question);
                } elseif ($question['type'] === 'text' || $question['type'] === 'textarea') {
                    echo render_text_question($question);
                }
                // Add more question types (e.g., checkbox, radio group) here if needed
            ?>
        <?php endforeach; ?>

        <!-- Action Buttons -->
        <div class="sticky bottom-0 flex flex-col justify-end p-4 pt-6 space-y-4 rounded-lg shadow-xl sm:flex-row sm:space-y-0 sm:space-x-4 bg-gray-50 dark:bg-gray-900">
            <?= 
                button(
                    "submit", 
                    "Save Draft", 
                    color: 'yellow', 
                    attributes: attribute("class", "w-full sm:w-auto text-sm font-semibold py-3 px-6 transition duration-150 ease-in-out transform hover:scale-105")
                ) 
            ?>
            <?= 
                button(
                    "submit", 
                    "Submit Evaluation", 
                    color: 'green', 
                    attributes: array_merge(
                        attribute("class", "w-full sm:w-auto text-sm font-semibold py-3 px-6 transition duration-150 ease-in-out transform hover:scale-105"),
                        attribute("onclick", "event.preventDefault(); alert('Submission simulated! Thank you for your feedback.');")
                    )
                ) 
            ?>
        </div>
    </form>

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