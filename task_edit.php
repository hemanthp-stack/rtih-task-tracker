<?php
/**
 * RTIH Task Tracker - Edit Task Handler
 * Level 1 Requirement: Edit a task's title and description.
 * Level 2 Requirement: Edit status, backend validation.
 * Level 3 Requirement: Authorization (IDOR protection), prepared statements, CSRF.
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

    $taskId      = (int)($_POST['id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status      = trim($_POST['status'] ?? 'Pending');
    $priority    = trim($_POST['priority'] ?? 'Medium');
    $dueDate     = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

    if (!$taskId) {
        set_flash('error', 'Invalid task identifier.');
        header('Location: index.php');
        exit;
    }

    // Authorization & Ownership Verification (IDOR Defense)
    $task = authorize_task_access($taskId, $pdo);
    if (!$task) {
        http_response_code(403);
        set_flash('error', 'Authorization Error: You do not have permission to edit this task.');
        header('Location: index.php');
        exit;
    }

    // Validate inputs
    $errors = validate_task_input([
        'title'    => $title,
        'status'   => $status,
        'priority' => $priority,
        'due_date' => $dueDate
    ]);

    if (!empty($errors)) {
        $firstError = reset($errors);
        set_flash('error', $firstError);
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE tasks
            SET title = :title,
                description = :description,
                status = :status,
                priority = :priority,
                due_date = :due_date,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

        $stmt->execute([
            ':title'       => $title,
            ':description' => $description !== '' ? $description : null,
            ':status'      => $status,
            ':priority'    => $priority,
            ':due_date'    => $dueDate,
            ':id'          => $taskId
        ]);

        set_flash('success', 'Task "' . e($title) . '" updated successfully.');
        header('Location: index.php');
        exit;

    } catch (PDOException $e) {
        error_log("Task update error: " . $e->getMessage());
        set_flash('error', 'Database error: Could not update the task.');
        header('Location: index.php');
        exit;
    }
}

// GET Request: Standalone Edit Page Fallback
$taskId = (int)($_GET['id'] ?? 0);
if (!$taskId) {
    set_flash('error', 'Task ID is required.');
    header('Location: index.php');
    exit;
}

// Ownership Authorization Check
$task = authorize_task_access($taskId, $pdo);
if (!$task) {
    http_response_code(403);
    set_flash('error', 'Authorization Error: You are not authorized to view or edit this task.');
    header('Location: index.php');
    exit;
}

$pageTitle = "Edit Task #" . $taskId;
require_once __DIR__ . '/includes/header.php';
?>

<div class="app-container" style="padding-top: 2rem; padding-bottom: 3rem;">
    <div style="max-width: 600px; margin: 0 auto; background: var(--bg-surface); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-md);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.5rem; font-weight: 800;">Edit Task #<?= $taskId; ?></h2>
            <span class="role-tag" style="background:var(--bg-subtle); color:var(--text-secondary);">
                Created: <?= format_date($task['created_at']); ?>
            </span>
        </div>

        <form action="task_edit.php" method="POST" onsubmit="return validateTaskForm(this);">
            <?= csrf_field(); ?>
            <input type="hidden" name="id" value="<?= (int)$task['id']; ?>">

            <div class="form-group">
                <label class="form-label required" for="editTitle">Task Title</label>
                <input type="text" name="title" id="editTitle" class="form-control" value="<?= e($task['title']); ?>" required maxlength="255">
            </div>

            <div class="form-group">
                <label class="form-label" for="editDesc">Description</label>
                <textarea name="description" id="editDesc" class="form-control" rows="4"><?= e($task['description']); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="editStatus">Status</label>
                    <select name="status" id="editStatus" class="form-control">
                        <option value="Pending" <?= $task['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editPriority">Priority</label>
                    <select name="priority" id="editPriority" class="form-control">
                        <option value="Low" <?= ($task['priority'] ?? '') === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?= ($task['priority'] ?? 'Medium') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?= ($task['priority'] ?? '') === 'High' ? 'selected' : ''; ?>>High</option>
                        <option value="Urgent" <?= ($task['priority'] ?? '') === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="editDueDate">Due Date</label>
                <input type="date" name="due_date" id="editDueDate" class="form-control" value="<?= e($task['due_date'] ?? ''); ?>">
            </div>

            <div style="display:flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
