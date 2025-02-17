<?php
    list(
        "icon" => $icon, "message" => $message,
        "title" => $title, "status" => $status,
        "username" => $username, "user_type" => $type
    ) = verify_email();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activation</title>
    <link rel="stylesheet" href="<?= asset("fontawesome/css/fontawesome.min.css") ?>">
    <link rel="stylesheet" href="<?= asset("fontawesome/css/all.min.css") ?>">
    <link rel="stylesheet" href="<?= asset("css/tailwind.output.css") ?>">
    <script src="<?= asset("js/tailwind.js") ?>"></script>
    <script>
      tailwind.config = {
        darkMode: 'class', // Ensures dark mode is applied via a class
      };
    </script>
    <script src="<?= asset("js/alpine.min.js") ?>" defer></script>
    <script src="<?= asset("js/init-alpine.js") ?>"></script>
</head>
<body class="bg-gray-100 font-sans">

<div class="container mx-auto px-4 py-8 text-center" style="max-width: 786px;">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">

        <div class="bg-blue-500 py-4 text-center">
            <i class="mx-auto fas fa-envelope-circle-check text-4xl text-zinc-100"></i> 
        </div>


        <div class="p-8">

            <h2 class="text-2xl font-bold text-center mb-4"><?= $title ?></h2>

            <div class="text-center mb-4">
                <i class="mx-auto text-6xl h-16 w-16 <?= $icon ?>"></i>
            </div>

            <p class="text-gray-700 mb-4">Hello <?= $username ?>,</p>
            <p class="text-gray-700 mb-6"><?= $message ?></p>

            <?php if(in_array($status, ["success", "already_verified"])): ?>
            <div class="text-center mb-6">
                <a href="<?= user() ? url("$type/dashboard") : url("/") ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= user() ? "GO TO DASHBOARD" : "LOGIN TO YOUR ACCOUNT" ?>
                </a>
            </div>
            <?php else: ?>
                <p class="text-gray-700 mb-4"><a href="<?= url("/") ?>" class="hover:underline-offset-2 underline">Login</a> to your account to resend the verification link</p>
            <?php endif; ?>

            <!-- <p class="text-gray-700 mb-4">Thank you for choosing Startup Email Templates.</p> -->

            <div class="text-center text-gray-500 text-sm">
                <p>&copy; <?= date("Y") ?> SHSDesk, Inc. All rights reserved.</p>
            </div>

        </div>

    </div>
</div>

</body>
</html>