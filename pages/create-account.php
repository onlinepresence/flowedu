<?php 
  require_once relative_path("includes/components.php");
  
  $admin_register = $_SESSION["admin_register"] ?? false;
  $title = "Create an Account";
  $type = $admin_register ? "admin" : "student";

  $submit = [
    "admin" => "admin/submit.php",
    "student" => "student/submit.php",
    "teacher" => "teacher/submit.php"
  ];

  // Start output buffering to capture the content
  ob_start();
?>

<div class="flex items-center min-h-screen p-6 bg-gray-50 dark:bg-gray-900">
  <div
    class="flex-1 h-full max-w-4xl mx-auto overflow-hidden bg-white rounded-lg shadow-xl dark:bg-gray-800"
  >
    <div class="flex flex-col overflow-y-auto md:flex-row">
      <div class="h-32 md:h-auto md:w-1/2">
        <img
          aria-hidden="true"
          class="object-cover w-full h-full dark:hidden"
          src="<?= asset("img/create-account-office.jpeg", false) ?>"
          alt="Office"
        />
        <img
          aria-hidden="true"
          class="hidden object-cover w-full h-full dark:block"
          src="<?= asset("img/create-account-office-dark.jpeg", false) ?>"
          alt="Office"
        />
      </div>

      <form action="<?= url($submit[$type]) ?>" method="post" class="flex items-center justify-center p-6 sm:p-12 md:w-1/2">
        <div class="w-full">
          <h1
            class="mb-4 text-xl font-semibold text-gray-700 dark:text-gray-200"
          >
            Create account
          </h1>

          <div class="space-y-4">
            <?php 
              if(isset($_SESSION["errors"]["message"])){
                echo form_message($_SESSION["errors"]["message"], "red");
              }
            ?>
            <!-- email -->
            <?= input("email", "Email", "email", required: true, attributes: [
              "placeholder" => "Email Address"
            ]) ?>

            <!-- password -->
            <?= 
              input("password", "Password", "password", required: true, attributes: [
                "placeholder" => "Password"
              ])
            ?>

            <!-- password confirmation -->
            <?= 
              input("password", "Confirm Password", "password_confirm", required: true, attributes: [
                "placeholder" => "Confirm Password"
              ])
            ?>

            <?php 
              if($admin_register){
                echo input_h(label: "System Secret", name: "system_secret", required: true, sub_text: "System Secret provided to start up system");
              }
            ?>
            <!-- secret for admin -->

            <!-- hidden elements -->
            <input type="hidden" name="type" value="<?= $type ?>">
            <input type="hidden" name="admin_register" value="<?= intval($admin_register) ?>">
          </div>

          <!-- You should use a button here, as the anchor is only used for the example  -->
          <?= button(
            "submit", $admin_register ? "Setup Account" : "Create Account", 
            "submit","create_account"
          ); ?>

          <?php if(!$admin_register): ?>
          <p class="mt-4">
            <a
              class="text-sm font-medium text-purple-600 dark:text-purple-400 hover:underline"
              href="./login.html"
            >
              Already have an account? Login
            </a>
          </p>
          <?php endif; ?>
        </div>
      </form>

    </div>
  </div>
</div>

<?php
  // Capture the content and assign it to a variable
  $content = ob_get_clean();
  
  // Include the layout, which will render the content dynamically
  require relative_path('layouts/login-logout.php');
