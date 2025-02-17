<?php
    // Ensure the script runs from the correct directory
    chdir(__DIR__ . "/../");

    // Include session and function files
    require_once "includes/session.php";

    // run email queues
    run_worker("email");