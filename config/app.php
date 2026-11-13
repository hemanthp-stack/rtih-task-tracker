<?php
/**
 * RTIH Task Tracker - Application Configuration
 * Developed for RTIH Technical Assignment (Intern - IT)
 */

// Start session if not already active with hardened security settings
if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session cookie parameters
    session_set_cookie_params([
        'lifetime' => 0,             // Session ends when browser closes
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // True if HTTPS
        'httponly' => true,          // Prevent JavaScript access to session cookie
        'samesite' => 'Lax'          // Mitigate CSRF
    ]);
    session_start();
}

// Application Constants
define('APP_NAME', 'TaskFlow');
define('APP_TAGLINE', 'RTIH Technical Task Tracker');
define('APP_VERSION', '1.0.0');

// Base URL helper
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base = rtrim($protocol . $host . $scriptDir, '/\\');
    return $base . '/' . ltrim($path, '/');
}
