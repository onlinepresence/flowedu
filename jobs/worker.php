<?php
    // Ensure the script runs from the correct directory
    chdir(__DIR__ . "/../");

    // Include session and function files
    require_once "includes/load_env.php";
    require_once "includes/job_session.php";
    
    // run email queues
    run_worker("email");

    // remove temp files
    run_worker("delete_tmp");