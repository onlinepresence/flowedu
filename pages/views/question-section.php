<?php 
$user_is_admin = isset(user()['admin_id']);

if($questions): ?>
    <form method="POST" action="javascript:alert('Form submitted/saved!');">
        <div class="grid gap-6 xl:grid-cols-2">
            <?php foreach ($questions as $key => $question): ?>
                <?php echo render_evaluation_question($question, $user_is_admin ? null : ($answers[$question["id"]] ?? null), $key + 1) ?>
            <?php endforeach; ?>
        </div>

        <?php if(!$user_is_admin): ?>
        <!-- Action Buttons -->
        <div class="sticky bottom-0 flex flex-col justify-end p-4 pt-6 space-y-4 rounded-lg shadow-xl sm:flex-row sm:space-y-0 sm:space-x-4 bg-gray-50 dark:bg-gray-900">
            <?= 
                button(
                    "submit", 
                    "Save Draft", 
                    color: 'yellow', 
                    attributes: array_merge(
                        attribute("class", "w-full sm:w-auto text-sm font-semibold py-3 px-6 transition duration-150 ease-in-out transform hover:scale-105"),
                    )
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
        <?php endif; ?>
    </form>
<?php else: ?>
    <?php echo placeholder_element("No Questions Available", "It seems there are no evaluation questions for this form.", "fas fa-question-circle") ?>
<?php endif ?>