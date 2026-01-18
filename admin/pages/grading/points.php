<?php
require_once relative_path("includes/components.php");

$title = 'Grade Points Configuration'; // Set the page title

// Start output buffering to capture the content
ob_start();
?>

<div class="container px-6 mx-auto grid">
    <!-- Grade Points Configuration -->
    <div class="mb-6 p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-cog mr-2"></i>Grade Points Configuration
        </h3>
        
        <?= information_bar(
            "Configure the grade points system used for calculating GPAs. Changes will affect all future grade calculations.",
            "info",
            false,
            attribute("class", "mb-4")
        ) ?>
        
        <form action="<?= url('admin/submit.php') ?>" method="POST" id="grade-points-form">
            <?= input("hidden", "", "request_type", "update_grade_points") ?>
            
            <?= table_start() ?>
                <?= thead_start() ?>
                    <?= th("Grade") ?>
                    <?= th("Description") ?>
                    <?= th("Points") ?>
                    <?= th("Minimum Score (%)") ?>
                    <?= th("Maximum Score (%)") ?>
                    <?= th("Actions") ?>
                <?= thead_end() ?>
                <?= tbody_start(attribute('class', 'bg-white divide-y dark:divide-gray-700 dark:bg-gray-800')) ?>
                    <?php 
                        // Default grade points - would typically fetch from database
                        $grade_points = [
                            ['grade' => 'A', 'description' => 'Excellent', 'points' => 4.0, 'min_score' => 80, 'max_score' => 100],
                            ['grade' => 'B+', 'description' => 'Very Good', 'points' => 3.5, 'min_score' => 75, 'max_score' => 79],
                            ['grade' => 'B', 'description' => 'Good', 'points' => 3.0, 'min_score' => 70, 'max_score' => 74],
                            ['grade' => 'C+', 'description' => 'Fairly Good', 'points' => 2.5, 'min_score' => 65, 'max_score' => 69],
                            ['grade' => 'C', 'description' => 'Fair', 'points' => 2.0, 'min_score' => 60, 'max_score' => 64],
                            ['grade' => 'D+', 'description' => 'Pass', 'points' => 1.5, 'min_score' => 55, 'max_score' => 59],
                            ['grade' => 'D', 'description' => 'Weak Pass', 'points' => 1.0, 'min_score' => 50, 'max_score' => 54],
                            ['grade' => 'F', 'description' => 'Fail', 'points' => 0.0, 'min_score' => 0, 'max_score' => 49],
                        ];
                        
                        foreach($grade_points as $index => $gp):
                    ?>
                        <?= tr_start() ?>
                            <?= td($gp['grade'], attributes: attribute("class", "px-4 py-3 text-sm font-semibold")) ?>
                            <?= td($gp['description'], attributes: attribute("class", "px-4 py-3 text-sm")) ?>
                            <?= td(
                                '<input type="number" step="0.1" name="points['.$index.']" value="'.$gp['points'].'" class="w-20 px-2 py-1 text-sm border rounded">',
                                attributes: attribute("class", "px-4 py-3 text-sm")
                            ) ?>
                            <?= td(
                                '<input type="number" name="min_score['.$index.']" value="'.$gp['min_score'].'" class="w-20 px-2 py-1 text-sm border rounded">',
                                attributes: attribute("class", "px-4 py-3 text-sm")
                            ) ?>
                            <?= td(
                                '<input type="number" name="max_score['.$index.']" value="'.$gp['max_score'].'" class="w-20 px-2 py-1 text-sm border rounded">',
                                attributes: attribute("class", "px-4 py-3 text-sm")
                            ) ?>
                            <?= td(
                                '<input type="hidden" name="grades['.$index.']" value="'.$gp['grade'].'">',
                                attributes: attribute("class", "px-4 py-3 text-sm")
                            ) ?>
                        <?= tr_end() ?>
                    <?php endforeach; ?>
                <?= tbody_end() ?>
            <?= table_end() ?>
            
            <div class="mt-6">
                <?= button("submit", "Save Grade Points", "submit", "update_grade_points", "purple") ?>
            </div>
        </form>
    </div>

    <!-- Current Grade Points Info -->
    <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-200">
            <i class="fas fa-info-circle mr-2"></i>Grade Points Information
        </h3>
        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <p><strong>GPA Calculation:</strong> GPA = (Sum of (Credit Hours × Grade Points)) / Total Credit Hours</p>
            <p><strong>CGPA:</strong> Cumulative GPA across all semesters</p>
            <p><strong>Minimum Passing Grade:</strong> Grade D (50%) or above</p>
        </div>
    </div>
</div>

<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
?>
