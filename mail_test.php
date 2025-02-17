<?php require "includes/session.php" ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activated</title>
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

<div class="container mx-auto px-4 py-8 text-center md:max-w-screen-md">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">

        <div class="bg-blue-500 py-4 text-center">
            <img src="your_logo_url_here.png" alt="Your Logo" class="mx-auto h-12"> 
            </div>


        <div class="p-8">

            <h2 class="text-2xl font-bold text-center mb-4">Account Activated</h2>

            <div class="text-center mb-4">
                <img src="your_icon_url_here.png" alt="Check Icon" class="mx-auto h-16 w-16">
                </div>

            <p class="text-gray-700 mb-4">Hello John,</p>
            <p class="text-gray-700 mb-6">Thank you, your email has been verified. Your account is now active.</p>

            <div class="text-center mb-6">
                <a href="#" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    LOGIN TO YOUR ACCOUNT
                </a>
            </div>

            <p class="text-gray-700 mb-4">Thank you for choosing Startup Email Templates.</p>

            <div class="text-center text-gray-500 text-sm">
                <p>&copy; 2017 StartupEmails, Inc. All rights reserved.</p>
                <p>123 Incredible Street, SomeTown, OR, 87466 US, (123) 456-7890</p>
                <p class="mt-4">
                    <a href="#" class="underline">View as a Web Page</a> | 
                    <a href="#" class="underline">unsubscribe</a>
                </p>
            </div>

        </div>

    </div>
</div>

</body>
</html>