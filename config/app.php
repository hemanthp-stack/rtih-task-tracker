<?php
/**
 * Task Tracker - Application Configuration
 */

// Start session if not already active with hardened security settings
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Application Constants (Updated to Task Tracker)
define('APP_NAME', 'Task Tracker');
define('APP_TAGLINE', 'Professional Task Management');
define('APP_VERSION', '1.1.0');

// Base URL helper
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base = rtrim($protocol . $host . $scriptDir, '/\\');
    return $base . '/' . ltrim($path, '/');
}
