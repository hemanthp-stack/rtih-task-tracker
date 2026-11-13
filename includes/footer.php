<?php
/**
 * RTIH Task Tracker - Application Footer & Global Modals Component
 */
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/flash.php';
?>

<!-- =========================================================================
     MODAL: Add New Task
     ========================================================================= -->
<div class="modal-overlay" id="modalAddTask" role="dialog" aria-modal="true" aria-labelledby="modalAddTitle">
    <div class="modal-dialog">
        <form action="task_create.php" method="POST" onsubmit="return validateTaskForm(this);">
            <?= csrf_field(); ?>
            <div class="modal-header">
                <h3 class="modal-title" id="modalAddTitle">Create New Task</h3>
                <button type="button" class="modal-close" onclick="closeModal('modalAddTask')" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label required" for="addTaskTitle">Task Title</label>
                    <input type="text" name="title" id="addTaskTitle" class="form-control" placeholder="e.g. Implement user authentication" required maxlength="255">
                </div>

                <div class="form-group">
                    <label class="form-label" for="addTaskDesc">Description</label>
                    <textarea name="description" id="addTaskDesc" class="form-control" rows="3" placeholder="Provide background details or acceptance criteria..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="addTaskStatus">Status</label>
                        <select name="status" id="addTaskStatus" class="form-control">
                            <option value="Pending" selected>Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="addTaskPriority">Priority</label>
                        <select name="priority" id="addTaskPriority" class="form-control">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="addTaskDueDate">Due Date (Optional)</label>
                    <input type="date" name="due_date" id="addTaskDueDate" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalAddTask')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Task</button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================================
     MODAL: Edit Task
     ========================================================================= -->
<div class="modal-overlay" id="modalEditTask" role="dialog" aria-modal="true" aria-labelledby="modalEditTitle">
    <div class="modal-dialog">
        <form action="task_edit.php" method="POST" onsubmit="return validateTaskForm(this);">
            <?= csrf_field(); ?>
            <input type="hidden" name="id" id="editTaskId">
            <div class="modal-header">
                <h3 class="modal-title" id="modalEditTitle">Edit Task</h3>
                <button type="button" class="modal-close" onclick="closeModal('modalEditTask')" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label required" for="editTaskTitle">Task Title</label>
                    <input type="text" name="title" id="editTaskTitle" class="form-control" required maxlength="255">
                </div>

                <div class="form-group">
                    <label class="form-label" for="editTaskDesc">Description</label>
                    <textarea name="description" id="editTaskDesc" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="editTaskStatus">Status</label>
                        <select name="status" id="editTaskStatus" class="form-control">
                            <option value="Pending">Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="editTaskPriority">Priority</label>
                        <select name="priority" id="editTaskPriority" class="form-control">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editTaskDueDate">Due Date</label>
                    <input type="date" name="due_date" id="editTaskDueDate" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditTask')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================================
     MODAL: Delete Task Confirmation (Level 1 Requirement)
     ========================================================================= -->
<div class="modal-overlay" id="modalDeleteTask" role="dialog" aria-modal="true" aria-labelledby="modalDeleteTitle">
    <div class="modal-dialog" style="max-width: 440px;">
        <form action="task_delete.php" method="POST">
            <?= csrf_field(); ?>
            <input type="hidden" name="id" id="deleteTaskId">
            <div class="modal-header" style="border-bottom-color: #fee2e2;">
                <h3 class="modal-title" id="modalDeleteTitle" style="color: #b91c1c; display:flex; align-items:center; gap:0.5rem;">
                    <svg style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    Confirm Deletion
                </h3>
                <button type="button" class="modal-close" onclick="closeModal('modalDeleteTask')" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <p style="color: var(--text-secondary); margin-bottom: 0.75rem;">Are you sure you want to permanently delete this task?</p>
                <div style="background: var(--bg-subtle); border: 1px solid var(--border-light); padding: 0.75rem 1rem; border-radius: var(--radius-md); font-weight: 600; color: var(--text-primary); word-break: break-word;" id="deleteTaskTitleDisplay">
                    Task Title
                </div>
                <p style="color: #991b1b; font-size: 0.8125rem; margin-top: 0.75rem;">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalDeleteTask')">Cancel</button>
                <button type="submit" class="btn btn-danger">Yes, Delete Task</button>
            </div>
        </form>
    </div>
</div>

<!-- Global Flash Message Container -->
<?= render_flash_messages(); ?>

<footer class="app-footer">
    <div class="app-container">
        <div class="footer-inner">
            <div>
                <strong>RTIH Task Tracker</strong> &mdash; Ratan Tata Innovation Hub Amaravati Foundation
                <span style="display:inline-block; margin-left:0.5rem; color:var(--text-subtle);">&bull;</span>
                <span style="margin-left:0.5rem;">Technical Assignment (Intern - IT)</span>
            </div>
            <div>
                PHP 8 &bull; MySQL &bull; PDO &bull; CSRF Protected &bull; RBAC
            </div>
        </div>
    </div>
</footer>

<script src="assets/js/app.js?v=<?= time(); ?>"></script>
</body>
</html>
