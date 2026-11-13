/**
 * TaskFlow Interaction Script
 * Manages 2 Classic Minimal Themes, Modals, Views, Quick Status, and Notifications.
 */

document.addEventListener('DOMContentLoaded', () => {
    initThemeSwitcher();
    initViewSwitch();
    initSearchInput();
    initQuickStatusChanger();
    initAutoDismissToasts();
    initKeyboardShortcuts();
});

/**
 * Theme Switcher (Classic Light vs Obsidian Dark)
 */
function initThemeSwitcher() {
    const btn = document.getElementById('themeToggleBtn');
    if (!btn) return;

    // Retrieve saved theme or default to classic-light
    const currentTheme = localStorage.getItem('taskflow_theme') || 'classic-light';
    applyTheme(currentTheme);

    btn.addEventListener('click', () => {
        const activeTheme = document.documentElement.getAttribute('data-theme') || 'classic-light';
        const newTheme = activeTheme === 'classic-light' ? 'classic-dark' : 'classic-light';
        applyTheme(newTheme);
    });

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('taskflow_theme', theme);
    }
}

/**
 * Switch between Card Grid and Table View
 */
function initViewSwitch() {
    const gridView = document.getElementById('tasksGridView');
    const tableView = document.getElementById('tasksTableView');
    const btnGrid = document.getElementById('viewBtnGrid');
    const btnTable = document.getElementById('viewBtnTable');

    if (!gridView || !tableView || !btnGrid || !btnTable) return;

    const savedView = localStorage.getItem('taskflow_view') || 'grid';
    setView(savedView);

    btnGrid.addEventListener('click', (e) => {
        e.stopPropagation();
        setView('grid');
        localStorage.setItem('taskflow_view', 'grid');
    });

    btnTable.addEventListener('click', (e) => {
        e.stopPropagation();
        setView('table');
        localStorage.setItem('taskflow_view', 'table');
    });

    function setView(mode) {
        if (mode === 'table') {
            gridView.style.display = 'none';
            tableView.style.display = 'block';
            btnTable.classList.add('active');
            btnGrid.classList.remove('active');
        } else {
            gridView.style.display = 'grid';
            tableView.style.display = 'none';
            btnGrid.classList.add('active');
            btnTable.classList.remove('active');
        }
    }
}

/**
 * Search input clear button
 */
function initSearchInput() {
    const searchInput = document.getElementById('taskSearchInput');
    const clearBtn = document.getElementById('searchClearBtn');

    if (!searchInput || !clearBtn) return;

    function toggleClear() {
        clearBtn.style.display = searchInput.value.trim() ? 'block' : 'none';
    }

    searchInput.addEventListener('input', toggleClear);
    toggleClear();

    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        toggleClear();
        const form = searchInput.closest('form');
        if (form) form.submit();
    });
}

/**
 * Open and Close Modals
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.add('is-active');
    document.body.style.overflow = 'hidden';

    const firstInput = modal.querySelector('input:not([type="hidden"]), textarea, select');
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 80);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.remove('is-active');
    document.body.style.overflow = '';
}

// Close when clicking overlay backdrop
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('is-active');
        document.body.style.overflow = '';
    }
});

/**
 * Open Task Details & Edit Modal
 */
let currentLoadedTask = null;

async function openEditTaskModal(taskId) {
    try {
        const res = await fetch(`api/task_details.php?id=${encodeURIComponent(taskId)}`, {
            headers: { 'Accept': 'application/json' }
        });

        if (!res.ok) {
            throw new Error('Failed to retrieve task data.');
        }

        const data = await res.json();
        if (!data.success) {
            showToast('error', data.error || 'Failed to load task details.');
            return;
        }

        const task = data.task;
        currentLoadedTask = task;

        document.getElementById('editTaskId').value = task.id;
        document.getElementById('editTaskTitle').value = task.title;
        document.getElementById('editTaskDesc').value = task.description || '';
        document.getElementById('editTaskStatus').value = task.status;
        document.getElementById('editTaskPriority').value = task.priority || 'Medium';
        document.getElementById('editTaskDueDate').value = task.due_date || '';

        // Update modal header badges
        const idBadge = document.getElementById('editTaskIdBadge');
        if (idBadge) idBadge.textContent = `TASK #${task.id}`;

        const statusBadge = document.getElementById('editTaskStatusBadge');
        if (statusBadge) {
            let badgeClass = 'status-pending';
            if (task.status === 'In Progress') badgeClass = 'status-in-progress';
            if (task.status === 'Completed') badgeClass = 'status-completed';
            statusBadge.innerHTML = `<span class="status-badge ${badgeClass}"><span class="badge-dot"></span>${escapeHtml(task.status)}</span>`;
        }

        const createdEl = document.getElementById('editTaskCreatedAt');
        if (createdEl) createdEl.textContent = task.created_at || '—';

        const updatedEl = document.getElementById('editTaskUpdatedAt');
        if (updatedEl) updatedEl.textContent = task.updated_at || '—';

        openModal('modalEditTask');
    } catch (err) {
        console.error(err);
        showToast('error', 'Error retrieving task details. Please try again.');
    }
}

/**
 * Trigger deletion directly from within the Edit modal
 */
function triggerDeleteFromEditModal() {
    if (!currentLoadedTask) return;
    const taskId = currentLoadedTask.id;
    const taskTitle = currentLoadedTask.title;
    closeModal('modalEditTask');
    confirmDeleteTask(taskId, taskTitle);
}

/**
 * Open Confirm Delete Modal
 */
function confirmDeleteTask(taskId, taskTitle) {
    const modal = document.getElementById('modalDeleteTask');
    if (!modal) return;

    document.getElementById('deleteTaskId').value = taskId;
    const titleSpan = document.getElementById('deleteTaskTitleDisplay');
    if (titleSpan) {
        titleSpan.textContent = taskTitle || `#${taskId}`;
    }

    openModal('modalDeleteTask');
}

/**
 * Instant Task Status Toggle via AJAX
 */
function initQuickStatusChanger() {
    document.querySelectorAll('.quick-status-select').forEach(select => {
        select.addEventListener('change', async (e) => {
            e.stopPropagation();
            const taskId = select.dataset.taskId;
            const newStatus = select.value;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const originalStatus = select.dataset.currentStatus;

            select.disabled = true;

            try {
                const formData = new FormData();
                formData.append('task_id', taskId);
                formData.append('status', newStatus);
                formData.append('csrf_token', csrfToken);

                const res = await fetch('api/task_status.php', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                const json = await res.json();
                if (json.success) {
                    showToast('success', `Task #${taskId} moved to "${newStatus}".`);
                    select.dataset.currentStatus = newStatus;
                    setTimeout(() => window.location.reload(), 450);
                } else {
                    showToast('error', json.error || 'Failed to update status.');
                    select.value = originalStatus;
                }
            } catch (err) {
                console.error(err);
                showToast('error', 'Network error updating status.');
                select.value = originalStatus;
            } finally {
                select.disabled = false;
            }
        });
    });
}

/**
 * Dispatch Toast Notification
 */
function showToast(type, message) {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast-card toast-${type}`;
    
    let iconSvg = '';
    if (type === 'success') {
        iconSvg = '<svg style="width:20px;height:20px;color:#10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
    } else if (type === 'error') {
        iconSvg = '<svg style="width:20px;height:20px;color:#ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
    } else {
        iconSvg = '<svg style="width:20px;height:20px;color:#3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    }

    toast.innerHTML = `
        <div class="toast-icon">${iconSvg}</div>
        <div class="toast-body">${escapeHtml(message)}</div>
        <button type="button" class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        if (toast.parentElement) toast.remove();
    }, 4500);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function initAutoDismissToasts() {
    document.querySelectorAll('.toast-card').forEach(toast => {
        setTimeout(() => {
            if (toast.parentElement) toast.remove();
        }, 4500);
    });
}

function initKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.is-active').forEach(modal => {
                modal.classList.remove('is-active');
            });
            document.body.style.overflow = '';
        }
    });
}

function validateTaskForm(form) {
    const titleInput = form.querySelector('[name="title"]');
    if (!titleInput) return true;

    const val = titleInput.value.trim();
    if (!val) {
        titleInput.classList.add('is-invalid');
        showToast('error', 'Please enter a task title.');
        titleInput.focus();
        return false;
    }

    if (val.length < 2) {
        titleInput.classList.add('is-invalid');
        showToast('error', 'Task title must be at least 2 characters long.');
        titleInput.focus();
        return false;
    }

    titleInput.classList.remove('is-invalid');
    return true;
}
