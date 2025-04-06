<?php
    function loadEnv($path = __DIR__ . '/../.env') {
        if (!file_exists($path)) return;
    
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    
            [$name, $value] = explode('=', $line, 2);
            $name = strtoupper(trim($name));
            $value = trim($value);
    
            // Remove surrounding quotes if present
            $value = trim($value, "\"'");
    
            // Set in PHP environment
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }

    /**
     * Get a value from the environment 
     */
    function env($name, bool $local_only = false){
        return getenv(strtoupper($name), $local_only);
    }

    loadEnv();