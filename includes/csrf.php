<?php
/**
 * RTIH Task Tracker - CSRF Protection Helper
 * Defends state-changing requests against Cross-Site Request Forgery.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get or generate the cryptographically secure CSRF token.
 *
 * @return string
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden HTML input containing the CSRF token.
 *
 * @return string
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify a CSRF token using timing-safe comparison.
 *
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionToken) || empty($token)) {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

/**
 * Enforce CSRF verification for POST/PUT/DELETE requests.
 * Aborts with 403 Forbidden if invalid.
 */
function require_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token()) {
            http_response_code(403);
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Security Error: Invalid or expired CSRF token.']);
            } else {
                die("<h1>403 Forbidden</h1><p>Invalid or expired CSRF security token. Please refresh the page and try again.</p>");
            }
            exit;
        }
    }
}
