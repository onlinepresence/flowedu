<?php 
    require_once "includes/appMemory.php";

    if(!$serverDown){
        header("location:/");
    }
?>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta property="og:title" content="Under construction">
  <meta property="og:image" content="https://github.com/hendrikdemir/under-construction-template/raw/main/demo.jpg">
  <title>Under construction</title>
  <link rel="stylesheet" href="assets/css/tailwind.output.css">
</head>

<body class="bg-white h-screen grid content-center">
  <div style="margin: auto" class="container grid p-4 content-center px-5 sm:px-0">
    <div class="grid gap-5 justify-items-center text-center border">
      <img style="width: 40%; margin: auto" src="assets/img/construction.svg">
      <h1 class="font-serif text-4xl font-semibold">Coming Soon</h1>
      <p class="font-sans text-gray-400 text-lg tracking-wide">This website is under construction, come back soon!</p>
    </div>
  </div>
</body>

</html>