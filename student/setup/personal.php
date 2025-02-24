<?php
require_once relative_path("includes/components.php");

$title = 'Personal Details'; // Set the page title
$user = user();

// Start output buffering to capture the content
ob_start();
?>
<form action="<?= url("student/submit.php") ?>" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
    <?php $is_student = true; require "personal-form.php"; ?>

    <!-- Submit Button -->
    <div class="mt-4 sm:w-48">
        <?= button("submit", empty(user()["username"]) ? "Submit Personal Details" : "Save Changes", "submit", "create_student", "blue", user()["approved"] ? attribute("disabled") : []) ?>
    </div>
</form>

<?php $scripts = <<<HTML
<script>
    $(document).ready(function(){
        $("select[name=program_id]").change(function(){
            const val = $(this).val();

            if(val != ""){
                const option = $(this).find("option:selected");
                const certificate = option.attr("data-cert");
                const department_id = option.attr("data-dept-id");
                const cost = option.attr("data-cost");

                $("input[name=department_id]").val(department_id);
                $("#program_certificate").val(certificate);
                $("#program_cost").val("GHC " + cost);
            }else{
                $("input[name=department_id], #program_certificate, #program_cost").val("");
            }
        })

        $("select[name=hall_id]").change(function(){
            const val = $(this).val();

            if(val != ""){
                const option = $(this).find("option:selected");
                const cost = option.attr("data-cost");
                const period = option.attr("data-period");

                $("#hall_period").val(period);
                $("#hall_cost").val("GHC " + cost);
            }
        })

        // make selection cases
        $("select[name=hall_id], select[name=program_id]").change();

        // make uneditable if approved
        if($("button[name=submit]").prop("disabled") === true){
            $("input, textarea").attr("readonly", true);
            $("select").prop("disabled", true);
        }
    })
</script>
HTML;
?>
<?php
// Capture the content and assign it to a variable
$content = ob_get_clean();

// Include the layout, which will render the content dynamically
require relative_path('layouts/auth.php');
