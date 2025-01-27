<?php

// Get the file name/path from the command line argument
$input = $argv[1] ?? null;

// Check if the user has provided a file name/path
if (!$input) {
    echo "Please provide a file name or path.\n";
    exit(1);
}

// Normalize the file name (add .php if it's missing)
$fileName = pathinfo($input, PATHINFO_EXTENSION) ? $input : $input . '.php';

// Define the path to your template file
$templatePath = __DIR__ . '/layouts/template'; // Adjust this path if necessary

// Check if the template file exists
if (!file_exists($templatePath)) {
    echo "Template file not found at: $templatePath\n";
    exit(1);
}

// Read the template content
$templateContent = file_get_contents($templatePath);

// Determine the full path to the new file
$newFilePath = __DIR__ . '/' . $fileName;

// Create the new file and write the template content into it
if (file_put_contents($newFilePath, $templateContent)) {
    echo "File '$fileName' created successfully!\n";
} else {
    echo "Failed to create file '$fileName'.\n";
    exit(1);
}
