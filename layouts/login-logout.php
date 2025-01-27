<!DOCTYPE html>
<html :class="{ 'theme-dark': dark }" x-data="data()" lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $title ?? "Form Layout" ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?= asset("css/tailwind.output.css") ?>" />
    <script src="<?= asset("js/alpine.min.js") ?>" defer></script>
    <script src="<?= asset("js/init-alpine.js") ?>"></script>
  </head>
  <body>
    <?= $content ?? "" ?>
  </body>
</html>
<?php flush_session() ?>
