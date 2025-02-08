<?php 
  require_once relative_path("includes/components.php");
  $title = "Login to Account";

  // login user
  if(isset($_POST["submit"]) && $_POST["submit"] == "login"){
    $next_request = login();

    if(!$next_request){
      header("location: /");
    }else{
      header("location: $next_request");
    }
  }

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
              src="../assets/img/login-office.jpeg"
              alt="Office"
            />
            <img
              aria-hidden="true"
              class="hidden object-cover w-full h-full dark:block"
              src="../assets/img/login-office-dark.jpeg"
              alt="Office"
            />
          </div>
          <form action="" method="post" class="flex items-center justify-center p-6 sm:p-12 md:w-1/2">
            <div class="w-full">
              <h1
                class="mb-4 text-xl font-semibold text-gray-700 dark:text-gray-200"
              >
                Login
              </h1>
              
              <div class="space-y-4">
                <?= system_message() ?>

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

                <!-- submit button -->
                <?= button(
                  "submit", "Login", 
                  "submit","login"
                ); ?>
              </div>

              <hr class="my-8" />

              <p class="mt-4">
                <a
                  class="text-sm font-medium text-purple-600 dark:text-purple-400 hover:underline"
                  href="javascript:void()"
                >
                  Forgot your password?
                </a>
              </p>
              <p class="mt-1">
                <a
                  class="text-sm font-medium text-purple-600 dark:text-purple-400 hover:underline"
                  href="./register"
                >
                  Create account
                </a>
              </p>
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
