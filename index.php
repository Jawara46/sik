<?php

/**
 * SIK-T Shared Hosting Entry Point
 * This file allows the application to run from the root directory
 * while keeping the core files in the 'public' folder.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Serve static files from the public directory if they exist
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

require_once __DIR__ . '/public/index.php';
