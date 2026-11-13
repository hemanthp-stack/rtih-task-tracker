<?php
/**
 * Application Footer & Unified Task Modals Component
 */
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/flash.php';
?>

<!-- =========================================================================
     MODAL: Create New Task
     ========================================================================= -->
<div class="modal-overlay" id="modalAddTask" role="dialog" aria-modal="true" aria-labelledby="modalAddTitle">
    <div class="modal-dialog">
        <form action="task_create.php" method="POST" onsubmit="return validateTaskForm(this);">
            <?= csrf_field(); ?>
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" id="modalAddTitle">Create New Task</h3>
                    <p style="font-size:0.8rem; color:var(--text-muted); margin-top:0.2rem;">Add a new milestone or action item to your tracker</p>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('modalAddTask')" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label required" for="addTaskTitle">Task Title</label>
                    <input type="text" name="title" id="addTaskTitle" class="form-control" placeholder="e.g. Design user interface mockups" required maxlength="255">
                </div>

                <div class="form-group">
                    <label class="form-label" for="addTaskDesc">Description</label>
                    <textarea name="description" id="addTaskDesc" class="form-control" rows="3" placeholder="Provide background details, requirements, or acceptance criteria..."></textarea>
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
                    <label class="form-label" for="addTaskDueDate">Target Due Date</label>
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
     MODAL: View & Edit Task (Unified Viewer & Editor)
     ========================================================================= -->
<div class="modal-overlay" id="modalEditTask" role="dialog" aria-modal="true" aria-labelledby="modalEditTitle">
    <div class="modal-dialog">
        <form action="task_edit.php" method="POST" onsubmit="return validateTaskForm(this);">
            <?= csrf_field(); ?>
            <input type="hidden" name="id" id="editTaskId">
            <div class="modal-header">
                <div>
                    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
                        <span class="role-tag" style="background:var(--primary-100); color:var(--primary-700); font-family:var(--font-mono);" id="editTaskIdBadge">TASK #</span>
                        <span id="editTaskStatusBadge"></span>
                    </div>
                    <h3 class="modal-title" id="modalEditTitle">Task Details &amp; Edit</h3>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('modalEditTask')" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label required" for="editTaskTitle">Task Title</label>
                    <input type="text" name="title" id="editTaskTitle" class="form-control" required maxlength="255">
                </div>

                <div class="form-group">
                    <label class="form-label" for="editTaskDesc">Description</label>
                    <textarea name="description" id="editTaskDesc" class="form-control" rows="3" placeholder="Add additional details or notes..."></textarea>
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

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="editTaskDueDate">Due Date</label>
                        <input type="date" name="due_date" id="editTaskDueDate" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Timeline Metadata</label>
                        <div style="font-size:0.78rem; color:var(--text-muted); padding:0.625rem 0.875rem; background:var(--bg-subtle); border:1px solid var(--border-light); border-radius:var(--radius-md);">
                            <div>Created: <span id="editTaskCreatedAt">—</span></div>
                            <div style="margin-top:0.2rem;">Updated: <span id="editTaskUpdatedAt">—</span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="display:flex; justify-content:space-between; align-items:center;">
                <button type="button" class="btn btn-secondary btn-sm" style="color:var(--danger-500); border-color:#fecaca;" id="editModalDeleteBtn" onclick="triggerDeleteFromEditModal()">
                    <svg style="width:14px;height:14px;display:inline-block;vertical-align:-2px;margin-right:2px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Delete Task
                </button>
                <div style="display:flex; gap:0.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditTask')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================================
     MODAL: Confirm Delete
     ========================================================================= -->
<div class="modal-overlay" id="modalDeleteTask" role="dialog" aria-modal="true" aria-labelledby="modalDeleteTitle">
    <div class="modal-dialog" style="max-width: 440px;">
        <form action="task_delete.php" method="POST">
            <?= csrf_field(); ?>
            <input type="hidden" name="id" id="deleteTaskId">
            <div class="modal-header" style="border-bottom-color: rgba(239, 68, 68, 0.2);">
                <h3 class="modal-title" id="modalDeleteTitle" style="color: var(--danger-500); display:flex; align-items:center; gap:0.5rem;">
                    <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <p style="color: var(--danger-500); font-size: 0.8125rem; margin-top: 0.75rem;">This action cannot be undone.</p>
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
                <strong>TaskFlow</strong> &mdash; Modern Task Management System
                <span style="display:inline-block; margin-left:0.5rem; color:var(--text-subtle);">&bull;</span>
                <span style="margin-left:0.5rem;">Professional Technical Work</span>
            </div>
            <div style="display:flex; align-items:center; gap:0.5rem;">
                <span class="badge-dot" style="background:#10b981;"></span>
                <span>System Operational &bull; 2 Classic Themes</span>
            </div>
        </div>
    </div>
</footer>

<script src="assets/js/app.js?v=<?= time(); ?>"></script>
</body>
</html>
