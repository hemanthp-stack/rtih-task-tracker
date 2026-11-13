<?php
/**
 * RTIH Task Tracker - Create Task Handler
 * Level 1 Requirement: Add a task with title and description.
 * Level 2 Requirement: Status, validation on backend.
 * Level 3 Requirement: Prepared statements, CSRF protection, user association.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/flash.php';

// Authentication Guard
require_login();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status      = trim($_POST['status'] ?? 'Pending');
    $priority    = trim($_POST['priority'] ?? 'Medium');
    $dueDate     = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

    // Validate inputs (Backend Validation)
    $errors = validate_task_input([
        'title'    => $title,
        'status'   => $status,
        'priority' => $priority,
        'due_date' => $dueDate
    ]);

    if (!empty($errors)) {
        // Collect first friendly validation error
        $firstError = reset($errors);
        set_flash('error', $firstError);
        header('Location: index.php');
        exit;
    }

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("
            INSERT INTO tasks (user_id, title, description, status, priority, due_date, created_at, updated_at)
            VALUES (:user_id, :title, :description, :status, :priority, :due_date, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");

        $stmt->execute([
            ':user_id'     => current_user_id(),
            ':title'       => $title,
            ':description' => $description !== '' ? $description : null,
            ':status'      => $status,
            ':priority'    => $priority,
            ':due_date'    => $dueDate
        ]);

        set_flash('success', 'Task "' . e($title) . '" created successfully.');
        header('Location: index.php');
        exit;

    } catch (PDOException $e) {
        error_log("Task creation error: " . $e->getMessage());
        set_flash('error', 'Database error: Could not save the task. Please try again.');
        header('Location: index.php');
        exit;
    }
}

// Fallback Standalone Form View (if visited via direct GET)
$pageTitle = "Create Task";
require_once __DIR__ . '/includes/header.php';
?>

<div class="app-container" style="padding-top: 2rem; padding-bottom: 3rem;">
    <div style="max-width: 600px; margin: 0 auto; background: var(--bg-surface); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-md);">
        <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem;">Create New Task</h2>
        
        <form action="task_create.php" method="POST" onsubmit="return validateTaskForm(this);">
            <?= csrf_field(); ?>
            <div class="form-group">
                <label class="form-label required" for="pageTaskTitle">Task Title</label>
                <input type="text" name="title" id="pageTaskTitle" class="form-control" required maxlength="255" placeholder="Enter task title">
            </div>

            <div class="form-group">
                <label class="form-label" for="pageTaskDesc">Description</label>
                <textarea name="description" id="pageTaskDesc" class="form-control" rows="4" placeholder="Enter task description"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="pageTaskStatus">Status</label>
                    <select name="status" id="pageTaskStatus" class="form-control">
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="pageTaskPriority">Priority</label>
                    <select name="priority" id="pageTaskPriority" class="form-control">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="pageTaskDueDate">Due Date</label>
                <input type="date" name="due_date" id="pageTaskDueDate" class="form-control">
            </div>

            <div style="display:flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Task</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
