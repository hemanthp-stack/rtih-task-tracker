<?php
/**
 * Task Tracker - Main Task Dashboard
 * Complete implementation of Level 1, Level 2, Level 3, and Bonus features.
 * Features 2 Classic Minimal Color Themes and Interactive Filter / View / Edit flows.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/flash.php';

// Authentication Guard (Level 3)
require_login();

$pdo = get_db_connection();
$userId = current_user_id();
$isAdmin = is_admin();

// --------------------------------------------------------------------------
// 1. Filter, Search & Pagination Parameters
// --------------------------------------------------------------------------
$search    = trim($_GET['search'] ?? '');
$status    = trim($_GET['status'] ?? 'all');
$priority  = trim($_GET['priority'] ?? 'all');
$sort      = trim($_GET['sort'] ?? 'newest');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 6; // 6 tasks per page for clean presentation

// --------------------------------------------------------------------------
// 2. Fetch KPI Metrics Summary (Bonus Feature 2)
// --------------------------------------------------------------------------
$kpiParams = [];
$kpiUserSql = "";
if (!$isAdmin) {
    $kpiUserSql = "WHERE user_id = :user_id";
    $kpiParams[':user_id'] = $userId;
}

$kpiStmt = $pdo->prepare("
    SELECT
        COUNT(*) as total_count,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as progress_count,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_count
    FROM tasks
    $kpiUserSql
");
$kpiStmt->execute($kpiParams);
$kpiData = $kpiStmt->fetch();

$totalTasks      = (int)($kpiData['total_count'] ?? 0);
$pendingTasks    = (int)($kpiData['pending_count'] ?? 0);
$progressTasks   = (int)($kpiData['progress_count'] ?? 0);
$completedTasks  = (int)($kpiData['completed_count'] ?? 0);
$completionRate  = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

// --------------------------------------------------------------------------
// 3. Build Filtered Query (Level 2 & Authorization)
// --------------------------------------------------------------------------
$whereClauses = [];
$queryParams = [];

// Authorization / Ownership Filter (IDOR Defense)
if (!$isAdmin) {
    $whereClauses[] = "t.user_id = :auth_user_id";
    $queryParams[':auth_user_id'] = $userId;
}

// Search Filter (Title & Description)
if ($search !== '') {
    $whereClauses[] = "(t.title LIKE :search OR t.description LIKE :search_desc)";
    $queryParams[':search'] = '%' . $search . '%';
    $queryParams[':search_desc'] = '%' . $search . '%';
}

// Status Filter
if (in_array($status, ['Pending', 'In Progress', 'Completed'], true)) {
    $whereClauses[] = "t.status = :status_filter";
    $queryParams[':status_filter'] = $status;
}

// Priority Filter
if (in_array($priority, ['Low', 'Medium', 'High', 'Urgent'], true)) {
    $whereClauses[] = "t.priority = :priority_filter";
    $queryParams[':priority_filter'] = $priority;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Count Total Filtered Tasks for Pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks t $whereSql");
$countStmt->execute($queryParams);
$filteredCount = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($filteredCount / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

// Sorting Options
$sortOrder = "t.created_at DESC";
switch ($sort) {
    case 'oldest':
        $sortOrder = "t.created_at ASC";
        break;
    case 'due_soon':
        $sortOrder = "CASE WHEN t.due_date IS NULL THEN 1 ELSE 0 END, t.due_date ASC, t.created_at DESC";
        break;
    case 'priority':
        $sortOrder = "CASE t.priority 
            WHEN 'Urgent' THEN 1 
            WHEN 'High' THEN 2 
            WHEN 'Medium' THEN 3 
            WHEN 'Low' THEN 4 
            ELSE 5 END, t.created_at DESC";
        break;
    case 'title':
        $sortOrder = "t.title ASC";
        break;
    case 'newest':
    default:
        $sortOrder = "t.created_at DESC";
        break;
}

// Fetch Filtered & Paginated Tasks with Owner Details (Prepared Statement)
$tasksQuery = "
    SELECT t.*, u.name as owner_name, u.email as owner_email
    FROM tasks t
    JOIN users u ON t.user_id = u.id
    $whereSql
    ORDER BY $sortOrder
    LIMIT $perPage OFFSET $offset
";

$tasksStmt = $pdo->prepare($tasksQuery);
$tasksStmt->execute($queryParams);
$tasks = $tasksStmt->fetchAll();

$pageTitle = "Task Dashboard";
require_once __DIR__ . '/includes/header.php';
?>

<main class="app-container" style="flex: 1; padding-bottom: 3rem;">

    <!-- =====================================================================
         KPI Summary Cards (Interactive Filters)
         Clicking any KPI card filters tasks immediately
         ===================================================================== -->
    <section class="kpi-section" aria-label="Task Summary Statistics">
        <div class="kpi-grid">
            <!-- Total Tasks Filter -->
            <a href="<?= build_query_url(['status' => 'all', 'page' => 1]); ?>" class="kpi-card <?= $status === 'all' ? 'is-active-kpi' : ''; ?>" title="Click to view all tasks">
                <div class="kpi-header">
                    <span class="kpi-label">Total Tasks</span>
                    <div class="kpi-icon-wrapper" style="background: var(--primary-100); color: var(--primary-600);">
                        <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                </div>
                <div class="kpi-value"><?= $totalTasks; ?></div>
                <div class="kpi-progress-bar">
                    <div class="kpi-progress-fill" style="width: <?= $completionRate; ?>%;"></div>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem; display:flex; justify-content:space-between;">
                    <span>Completion Rate</span>
                    <strong style="color: #10b981;"><?= $completionRate; ?>%</strong>
                </div>
                <div class="kpi-click-hint">Click to show all</div>
            </a>

            <!-- Pending Filter -->
            <a href="<?= build_query_url(['status' => 'Pending', 'page' => 1]); ?>" class="kpi-card <?= $status === 'Pending' ? 'is-active-kpi' : ''; ?>" title="Click to filter Pending tasks">
                <div class="kpi-header">
                    <span class="kpi-label">Pending</span>
                    <div class="kpi-icon-wrapper" style="background: var(--pending-bg); color: var(--pending-text);">
                        <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div class="kpi-value"><?= $pendingTasks; ?></div>
                <div style="font-size: 0.75rem; color: var(--pending-text); margin-top: 0.5rem; font-weight: 500;">
                    Awaiting commencement
                </div>
                <div class="kpi-click-hint">Click to filter pending</div>
            </a>

            <!-- In Progress Filter -->
            <a href="<?= build_query_url(['status' => 'In Progress', 'page' => 1]); ?>" class="kpi-card <?= $status === 'In Progress' ? 'is-active-kpi' : ''; ?>" title="Click to filter In Progress tasks">
                <div class="kpi-header">
                    <span class="kpi-label">In Progress</span>
                    <div class="kpi-icon-wrapper" style="background: var(--progress-bg); color: var(--progress-text);">
                        <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                </div>
                <div class="kpi-value"><?= $progressTasks; ?></div>
                <div style="font-size: 0.75rem; color: var(--progress-text); margin-top: 0.5rem; font-weight: 500;">
                    Currently active
                </div>
                <div class="kpi-click-hint">Click to filter active</div>
            </a>

            <!-- Completed Filter -->
            <a href="<?= build_query_url(['status' => 'Completed', 'page' => 1]); ?>" class="kpi-card <?= $status === 'Completed' ? 'is-active-kpi' : ''; ?>" title="Click to filter Completed tasks">
                <div class="kpi-header">
                    <span class="kpi-label">Completed</span>
                    <div class="kpi-icon-wrapper" style="background: var(--completed-bg); color: var(--completed-text);">
                        <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                </div>
                <div class="kpi-value"><?= $completedTasks; ?></div>
                <div style="font-size: 0.75rem; color: var(--completed-text); margin-top: 0.5rem; font-weight: 500;">
                    Successfully delivered
                </div>
                <div class="kpi-click-hint">Click to filter completed</div>
            </a>
        </div>
    </section>

    <!-- =====================================================================
         Toolbar: Search, Status Filters, View Toggle, & Add Action
         ===================================================================== -->
    <section class="toolbar-section">
        <div class="toolbar-card">
            <!-- Left Side: Search & Filter Form -->
            <form action="index.php" method="GET" class="toolbar-left" id="searchFilterForm">
                <input type="hidden" name="status" value="<?= e($status); ?>">
                <input type="hidden" name="priority" value="<?= e($priority); ?>">
                <input type="hidden" name="sort" value="<?= e($sort); ?>">

                <div class="search-box">
                    <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" name="search" id="taskSearchInput" class="search-input" placeholder="Search tasks by title..." value="<?= e($search); ?>">
                    <button type="button" id="searchClearBtn" class="search-clear" title="Clear search">&times;</button>
                </div>

                <!-- Priority Dropdown -->
                <select name="priority" class="search-input" style="width:auto; padding-left:0.75rem;" onchange="this.form.submit();">
                    <option value="all" <?= $priority === 'all' ? 'selected' : ''; ?>>All Priorities</option>
                    <option value="Urgent" <?= $priority === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                    <option value="High" <?= $priority === 'High' ? 'selected' : ''; ?>>High</option>
                    <option value="Medium" <?= $priority === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="Low" <?= $priority === 'Low' ? 'selected' : ''; ?>>Low</option>
                </select>

                <!-- Sort Order Dropdown -->
                <select name="sort" class="search-input" style="width:auto; padding-left:0.75rem;" onchange="this.form.submit();">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="due_soon" <?= $sort === 'due_soon' ? 'selected' : ''; ?>>Due Date (Soonest)</option>
                    <option value="priority" <?= $sort === 'priority' ? 'selected' : ''; ?>>Priority (Highest)</option>
                    <option value="title" <?= $sort === 'title' ? 'selected' : ''; ?>>Alphabetical (A-Z)</option>
                </select>
            </form>

            <!-- Right Side: View Switch & Create Task Button -->
            <div class="toolbar-right">
                <!-- View Mode Switcher -->
                <div class="view-switch" role="group" aria-label="View Switcher">
                    <button type="button" class="view-switch-btn active" id="viewBtnGrid" title="Card Grid View">
                        <svg style="width:17px;height:17px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    </button>
                    <button type="button" class="view-switch-btn" id="viewBtnTable" title="Compact Table View">
                        <svg style="width:17px;height:17px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                    </button>
                </div>

                <!-- Create Task Button -->
                <button type="button" class="btn btn-primary" onclick="openModal('modalAddTask')">
                    <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>New Task</span>
                </button>
            </div>
        </div>

        <!-- Status Filter Tabs with Counts -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top: 1rem; flex-wrap:wrap; gap:0.75rem;">
            <div class="filter-tabs">
                <a href="<?= build_query_url(['status' => 'all', 'page' => 1]); ?>" class="filter-tab <?= $status === 'all' ? 'active' : ''; ?>">
                    All Tasks
                    <span class="tab-count"><?= $totalTasks; ?></span>
                </a>
                <a href="<?= build_query_url(['status' => 'Pending', 'page' => 1]); ?>" class="filter-tab <?= $status === 'Pending' ? 'active' : ''; ?>">
                    Pending
                    <span class="tab-count"><?= $pendingTasks; ?></span>
                </a>
                <a href="<?= build_query_url(['status' => 'In Progress', 'page' => 1]); ?>" class="filter-tab <?= $status === 'In Progress' ? 'active' : ''; ?>">
                    In Progress
                    <span class="tab-count"><?= $progressTasks; ?></span>
                </a>
                <a href="<?= build_query_url(['status' => 'Completed', 'page' => 1]); ?>" class="filter-tab <?= $status === 'Completed' ? 'active' : ''; ?>">
                    Completed
                    <span class="tab-count"><?= $completedTasks; ?></span>
                </a>
            </div>

            <?php if ($search !== '' || $status !== 'all' || $priority !== 'all'): ?>
                <div style="font-size:0.8125rem; color:var(--text-muted); display:flex; align-items:center; gap:0.5rem;">
                    <span>Found <strong><?= $filteredCount; ?></strong> matching tasks</span>
                    <a href="index.php" class="btn btn-secondary btn-sm" style="padding:0.2rem 0.5rem;">Clear Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- =====================================================================
         Task Listings: Card Grid View & Compact Table View
         Clicking any task opens the View & Edit Modal
         ===================================================================== -->
    <?php if (empty($tasks)): ?>
        <div class="empty-state">
            <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
            <h3 class="empty-title">No tasks found</h3>
            <p class="empty-desc">
                <?php if ($search !== '' || $status !== 'all' || $priority !== 'all'): ?>
                    No tasks match your current filter and search criteria. Try clearing filters or refining your query.
                <?php else: ?>
                    You don't have any tasks in your tracker yet. Click below to create your first milestone!
                <?php endif; ?>
            </p>
            <?php if ($search !== '' || $status !== 'all' || $priority !== 'all'): ?>
                <a href="index.php" class="btn btn-secondary">Clear Filters</a>
            <?php else: ?>
                <button type="button" class="btn btn-primary" onclick="openModal('modalAddTask')">Create Task</button>
            <?php endif; ?>
        </div>
    <?php else: ?>

        <!-- 1. GRID VIEW (Clicking any card opens View & Edit modal) -->
        <div class="tasks-grid" id="tasksGridView">
            <?php foreach ($tasks as $task): ?>
                <div class="task-card <?= $task['status'] === 'Completed' ? 'is-completed' : ''; ?>" id="taskCard-<?= $task['id']; ?>" onclick="openEditTaskModal(<?= (int)$task['id']; ?>)" title="Click to view & edit task">
                    <div>
                        <div class="task-card-header">
                            <h4 class="task-card-title">
                                <?php if ($task['status'] === 'Completed'): ?>
                                    <span class="completed-check-icon" title="Completed">
                                        <svg style="width:16px;height:16px;color:#10b981;display:inline-block;vertical-align:-2px;margin-right:4px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                    </span>
                                <?php endif; ?>
                                <?= e($task['title']); ?>
                            </h4>
                            <div style="display:flex; align-items:center; gap:0.35rem;" onclick="event.stopPropagation()">
                                <?= priority_badge($task['priority'] ?? 'Medium'); ?>
                            </div>
                        </div>

                        <div class="task-card-meta">
                            <!-- Quick Status Dropdown (Stops click propagation) -->
                            <div onclick="event.stopPropagation()">
                                <select class="quick-status-select" data-task-id="<?= $task['id']; ?>" data-current-status="<?= e($task['status']); ?>">
                                    <option value="Pending" <?= $task['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>

                            <!-- Due Date Badge -->
                            <?= format_due_badge($task['due_date'], $task['status']); ?>

                            <?php if ($task['status'] === 'Completed'): ?>
                                <span class="completed-badge-pill">
                                    ✓ Completed
                                </span>
                            <?php endif; ?>

                            <?php if ($isAdmin): ?>
                                <span class="role-tag" style="background:var(--bg-subtle); color:var(--text-secondary);" title="Owner: <?= e($task['owner_email']); ?>">
                                    <?= e($task['owner_name']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="task-card-body">
                            <p class="task-card-desc"><?= !empty($task['description']) ? e($task['description']) : '<span style="color:var(--text-subtle); font-style:italic;">No description provided</span>'; ?></p>
                        </div>
                    </div>

                    <div class="task-card-footer">
                        <span style="display:flex; align-items:center; gap:0.35rem;">
                            <span style="font-size:0.75rem;">Created: <?= format_date($task['created_at']); ?></span>
                            <span class="card-edit-hint">&bull; Click to edit</span>
                        </span>

                        <!-- Actions (Stops click propagation) -->
                        <div class="task-actions" onclick="event.stopPropagation()">
                            <button type="button" class="btn-icon" onclick="openEditTaskModal(<?= (int)$task['id']; ?>)" title="View & Edit Task">
                                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                            <button type="button" class="btn-icon" style="color:var(--danger-500);" onclick="confirmDeleteTask(<?= (int)$task['id']; ?>, '<?= e(addslashes($task['title'])); ?>')" title="Delete Task">
                                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 2. TABLE VIEW (Clicking any row opens View & Edit modal) -->
        <div class="table-container" id="tasksTableView" style="display:none;">
            <table class="tasks-table">
                <thead>
                    <tr>
                        <th style="width:140px;">Status</th>
                        <th>Task Title & Description</th>
                        <th style="width:110px;">Priority</th>
                        <th style="width:140px;">Due Date</th>
                        <?php if ($isAdmin): ?>
                            <th style="width:130px;">Owner</th>
                        <?php endif; ?>
                        <th style="width:130px;">Created</th>
                        <th style="width:90px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr class="task-table-row <?= $task['status'] === 'Completed' ? 'is-completed-row' : ''; ?>" onclick="openEditTaskModal(<?= (int)$task['id']; ?>)" title="Click to view & edit task">
                            <td onclick="event.stopPropagation()">
                                <select class="quick-status-select" data-task-id="<?= $task['id']; ?>" data-current-status="<?= e($task['status']); ?>">
                                    <option value="Pending" <?= $task['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </td>
                            <td>
                                <span class="table-task-title <?= $task['status'] === 'Completed' ? 'table-task-completed' : ''; ?>">
                                    <?php if ($task['status'] === 'Completed'): ?>
                                        <svg style="width:14px;height:14px;color:#10b981;display:inline-block;vertical-align:-1px;margin-right:4px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                    <?php endif; ?>
                                    <?= e($task['title']); ?>
                                </span>
                                <?php if (!empty($task['description'])): ?>
                                    <small style="color:var(--text-muted); display:block; margin-top:0.2rem; max-width:480px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?= e($task['description']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td onclick="event.stopPropagation()"><?= priority_badge($task['priority'] ?? 'Medium'); ?></td>
                            <td><?= format_due_badge($task['due_date'], $task['status']); ?></td>
                            <?php if ($isAdmin): ?>
                                <td>
                                    <span style="font-size:0.8rem; font-weight:600; color:var(--text-secondary);"><?= e($task['owner_name']); ?></span>
                                </td>
                            <?php endif; ?>
                            <td style="font-size:0.8rem; color:var(--text-muted);"><?= format_date($task['created_at']); ?></td>
                            <td style="text-align:right;" onclick="event.stopPropagation()">
                                <div class="task-actions" style="justify-content:flex-end;">
                                    <button type="button" class="btn-icon" onclick="openEditTaskModal(<?= (int)$task['id']; ?>)" title="View & Edit Task">
                                        <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>
                                    <button type="button" class="btn-icon" style="color:var(--danger-500);" onclick="confirmDeleteTask(<?= (int)$task['id']; ?>, '<?= e(addslashes($task['title'])); ?>')" title="Delete Task">
                                        <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- =====================================================================
             Pagination Controls (Bonus Feature 1)
             ===================================================================== -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    Showing <strong><?= $offset + 1; ?></strong> to <strong><?= min($offset + $perPage, $filteredCount); ?></strong> of <strong><?= $filteredCount; ?></strong> tasks
                </div>

                <nav class="pagination-nav" aria-label="Tasks pagination">
                    <a href="<?= build_query_url(['page' => $page - 1]); ?>" class="page-btn <?= $page <= 1 ? 'disabled' : ''; ?>" aria-disabled="<?= $page <= 1 ? 'true' : 'false'; ?>">
                        &larr; Prev
                    </a>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="<?= build_query_url(['page' => $i]); ?>" class="page-btn <?= $i === $page ? 'active' : ''; ?>">
                            <?= $i; ?>
                        </a>
                    <?php endfor; ?>

                    <a href="<?= build_query_url(['page' => $page + 1]); ?>" class="page-btn <?= $page >= $totalPages ? 'disabled' : ''; ?>" aria-disabled="<?= $page >= $totalPages ? 'true' : 'false'; ?>">
                        Next &rarr;
                    </a>
                </nav>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
