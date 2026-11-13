<?php
/**
 * RTIH Task Tracker - Authentication & Authorization Helpers
 * Implements Level 3: Login, Role-Based Access Control, and IDOR Defense.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Check if a user is currently authenticated.
 *
 * @return bool
 */
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user's ID.
 *
 * @return int|null
 */
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Check if the logged-in user has the Administrator role.
 *
 * @return bool
 */
function is_admin() {
    return (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

/**
 * Get current user session payload.
 *
 * @return array|null
 */
function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'user'
    ];
}

/**
 * Middleware: Protect routes requiring authentication.
 * Redirects unauthenticated visitors to login.php.
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = "Please log in to access your task dashboard.";
        header("Location: login.php");
        exit;
    }
}

/**
 * Authenticate and log in a user with secure session regeneration.
 *
 * @param array $user
 */
function login_user($user) {
    // Prevent Session Fixation attack
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'] ?? 'user';
    $_SESSION['login_time'] = time();
}

/**
 * Securely terminate the user session.
 */
function logout_user() {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Backend Authorization / Access Control Enforcement (IDOR Protection):
 * Verifies whether the active user has rights to modify or view the specified task.
 * Rules:
 * 1. Admin users have global oversight and can manage all tasks.
 * 2. Regular users can ONLY manage tasks that belong directly to their user_id.
 *
 * @param int $taskId
 * @param PDO|null $pdo
 * @return array|false Returns the task record if permitted, false otherwise.
 */
function authorize_task_access($taskId, $pdo = null) {
    if (!is_logged_in()) {
        return false;
    }

    if ($pdo === null) {
        $pdo = get_db_connection();
    }

    $userId = current_user_id();
    $isAdmin = is_admin();

    if ($isAdmin) {
        // Admins can access any task
        $stmt = $pdo->prepare("SELECT t.*, u.name as owner_name, u.email as owner_email FROM tasks t JOIN users u ON t.user_id = u.id WHERE t.id = :id LIMIT 1");
        $stmt->execute([':id' => $taskId]);
    } else {
        // Regular users can ONLY access tasks WHERE user_id matches their session
        $stmt = $pdo->prepare("SELECT t.*, u.name as owner_name, u.email as owner_email FROM tasks t JOIN users u ON t.user_id = u.id WHERE t.id = :id AND t.user_id = :user_id LIMIT 1");
        $stmt->execute([':id' => $taskId, ':user_id' => $userId]);
    }

    return $stmt->fetch();
}
