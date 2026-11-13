<?php
/**
 * RTIH Task Tracker - Get Task Details API Endpoint
 * Safely fetches a single task for the Edit modal with ownership check.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

$taskId = (int)($_GET['id'] ?? 0);
if (!$taskId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid task ID.']);
    exit;
}

$pdo = get_db_connection();

// Verify Authorization / Ownership (IDOR Defense)
$task = authorize_task_access($taskId, $pdo);
if (!$task) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied: You do not have permission to view or edit this task.']);
    exit;
}

echo json_encode([
    'success' => true,
    'task'    => [
        'id'          => (int)$task['id'],
        'title'       => $task['title'],
        'description' => $task['description'],
        'status'      => $task['status'],
        'priority'    => $task['priority'] ?? 'Medium',
        'due_date'    => $task['due_date'] ?? '',
        'created_at'  => $task['created_at'],
        'updated_at'  => $task['updated_at']
    ]
]);
