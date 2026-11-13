<?php
/**
 * RTIH Task Tracker - Delete Task Handler
 * Level 1 Requirement: Delete a task after asking the user to confirm.
 * Level 3 Requirement: Authorization (IDOR defense), CSRF protection.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/flash.php';

// Authentication Guard
require_login();

$pdo = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $taskId = (int)($_POST['id'] ?? 0);
    if (!$taskId) {
        set_flash('error', 'Invalid task identifier.');
        header('Location: index.php');
        exit;
    }

    // Authorization & Ownership Verification (IDOR Defense)
    $task = authorize_task_access($taskId, $pdo);
    if (!$task) {
        http_response_code(403);
        set_flash('error', 'Authorization Error: You do not have permission to delete this task.');
        header('Location: index.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
        $stmt->execute([':id' => $taskId]);

        set_flash('success', 'Task "' . e($task['title']) . '" was successfully deleted.');
        header('Location: index.php');
        exit;

    } catch (PDOException $e) {
        error_log("Task deletion error: " . $e->getMessage());
        set_flash('error', 'Database error: Could not delete the task.');
        header('Location: index.php');
        exit;
    }
}

// Fallback GET View: Confirmation page if JavaScript modal is bypassed
$taskId = (int)($_GET['id'] ?? 0);
if (!$taskId) {
    set_flash('error', 'Task ID is required.');
    header('Location: index.php');
    exit;
}

$task = authorize_task_access($taskId, $pdo);
if (!$task) {
    http_response_code(403);
    set_flash('error', 'Authorization Error: You are not authorized to delete this task.');
    header('Location: index.php');
    exit;
}

$pageTitle = "Confirm Deletion";
require_once __DIR__ . '/includes/header.php';
?>

<div class="app-container" style="padding-top: 3rem; padding-bottom: 3rem;">
    <div style="max-width: 480px; margin: 0 auto; background: var(--bg-surface); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-lg);">
        <div style="color: #b91c1c; display:flex; align-items:center; gap:0.5rem; margin-bottom: 1rem;">
            <svg style="width:28px;height:28px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <h2 style="font-size: 1.35rem; font-weight: 800;">Confirm Task Deletion</h2>
        </div>

        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
            Are you sure you want to permanently delete the following task?
        </p>

        <div style="background: var(--bg-subtle); border: 1px solid var(--border-light); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 0.25rem;">
                <?= e($task['title']); ?>
            </div>
            <div style="font-size: 0.8125rem; color: var(--text-muted);">
                Status: <?= e($task['status']); ?> &bull; Created: <?= format_date($task['created_at']); ?>
            </div>
        </div>

        <form action="task_delete.php" method="POST">
            <?= csrf_field(); ?>
            <input type="hidden" name="id" value="<?= (int)$task['id']; ?>">
            
            <div style="display:flex; justify-content: flex-end; gap: 0.75rem;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-danger">Yes, Delete Task</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
