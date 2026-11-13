<?php
/**
 * RTIH Task Tracker - Fast Status Transition API Endpoint
 * Level 2 requirement: Allow users to change a task's status.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Check authentication
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

// Enforce CSRF verification for state modification
if (!verify_csrf_token($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Security token invalid or expired.']);
    exit;
}

$taskId = (int)($_POST['task_id'] ?? 0);
$status = trim($_POST['status'] ?? '');

$validStatuses = ['Pending', 'In Progress', 'Completed'];
if (!$taskId || !in_array($status, $validStatuses, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid task ID or status value.']);
    exit;
}

$pdo = get_db_connection();

// Verify Authorization / Ownership (IDOR Defense)
$task = authorize_task_access($taskId, $pdo);
if (!$task) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied: You do not have permission to modify this task.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE tasks SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->execute([
        ':status' => $status,
        ':id'     => $taskId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully.',
        'status'  => $status
    ]);
} catch (PDOException $e) {
    error_log("Status Update Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'A database error occurred while updating status.']);
}
