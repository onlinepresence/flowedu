<?php
require_once relative_path("includes/components.php");

$title = 'Lecturer Evaluation';

// Start output buffering to capture the content
ob_start();

// Dummy check for evaluation period - replace with actual logic
$evaluation_open = false; // This should come from your database or settings
$lecturer_name = "Dr. John Smith"; // This should come from your database
?>

<div class="container px-6 mx-auto grid">
    <?php if ($evaluation_open): ?>
        <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <div class="mb-6">
                <p class="text-lg font-semibold text-gray-700 dark:text-gray-200"><?php echo $lecturer_name; ?></p>
            </div>
            
            <form action="submit.php" method="POST">
                <input type="hidden" name="lecturer_id" value="1">
                
                <div class="space-y-6">
                    <!-- Teaching Effectiveness -->
                    <div class="mb-4">
                        <label class="block text-sm text-gray-700 dark:text-gray-400">The lecturer explains concepts clearly and effectively</label>
                        <div class="mt-2 flex space-x-4">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="teaching_effectiveness" value="<?php echo $i; ?>" class="text-purple-600 form-radio focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-400"><?php echo $i; ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Course Organization -->
                    <div class="mb-4">
                        <label class="block text-sm text-gray-700 dark:text-gray-400">The course materials and lectures are well organized</label>
                        <div class="mt-2 flex space-x-4">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="course_organization" value="<?php echo $i; ?>" class="text-purple-600 form-radio focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-400"><?php echo $i; ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Student Engagement -->
                    <div class="mb-4">
                        <label class="block text-sm text-gray-700 dark:text-gray-400">The lecturer encourages student participation and questions</label>
                        <div class="mt-2 flex space-x-4">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="student_engagement" value="<?php echo $i; ?>" class="text-purple-600 form-radio focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-400"><?php echo $i; ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Additional Comments -->
                    <div class="mb-4">
                        <label class="block text-sm text-gray-700 dark:text-gray-400">Additional Comments (Optional)</label>
                        <textarea name="comments" rows="3" class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-textarea focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray"></textarea>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">Submit Evaluation</button>
                    </div>
                </div>
            </form>
        </div>
    <?php else: ?>
        <?php echo placeholder_element(
            'Evaluation Period Closed',
            'The lecturer evaluation period is currently closed. Please check back during the designated evaluation period.',
            'fas fa-exclamation-triangle'
        ); ?>
    <?php endif; ?>
</div>

<?php $scripts = <<<HTML
<script>
    $(document).ready(function(){
        // Add form validation if needed
        $('form').on('submit', function(e) {
            // Add your validation logic here
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
