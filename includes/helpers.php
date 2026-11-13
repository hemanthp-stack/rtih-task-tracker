<?php
/**
 * RTIH Task Tracker - Utility & View Helper Functions
 * Includes XSS output escaping, formatting, badges, and validation logic.
 */

/**
 * Escape string for safe HTML output (XSS defense).
 *
 * @param string|null $value
 * @return string
 */
function e($value) {
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Format timestamp into readable human date.
 *
 * @param string|null $timestamp
 * @param bool $showTime
 * @return string
 */
function format_date($timestamp, $showTime = false) {
    if (empty($timestamp)) {
        return '—';
    }
    $time = strtotime($timestamp);
    if (!$time) {
        return e($timestamp);
    }
    return date($showTime ? 'M d, Y · h:i A' : 'M d, Y', $time);
}

/**
 * Format due date with smart urgency indicator (Overdue, Today, Tomorrow, etc.)
 *
 * @param string|null $dueDate
 * @param string $status
 * @return string
 */
function format_due_badge($dueDate, $status = '') {
    if (empty($dueDate)) {
        return '<span class="text-xs text-slate-400">No due date</span>';
    }

    $due = strtotime($dueDate);
    $today = strtotime(date('Y-m-d'));
    $diffDays = round(($due - $today) / 86400);

    if ($status === 'Completed') {
        return '<span class="due-pill due-completed"><svg class="w-3.5 h-3.5 mr-1 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>' . date('M d', $due) . '</span>';
    }

    if ($diffDays < 0) {
        $daysOverdue = abs($diffDays);
        return '<span class="due-pill due-overdue" title="Overdue by ' . $daysOverdue . ' days"><svg class="w-3.5 h-3.5 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Overdue (' . date('M d', $due) . ')</span>';
    } elseif ($diffDays === 0.0 || $diffDays === 0) {
        return '<span class="due-pill due-today"><svg class="w-3.5 h-3.5 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Due Today</span>';
    } elseif ($diffDays === 1.0 || $diffDays === 1) {
        return '<span class="due-pill due-soon">Tomorrow</span>';
    } else {
        return '<span class="due-pill due-normal">' . date('M d, Y', $due) . '</span>';
    }
}

/**
 * Render visual status badge.
 *
 * @param string $status
 * @return string
 */
function status_badge($status) {
    switch ($status) {
        case 'Completed':
            return '<span class="status-badge status-completed"><span class="badge-dot"></span>Completed</span>';
        case 'In Progress':
            return '<span class="status-badge status-in-progress"><span class="badge-dot"></span>In Progress</span>';
        case 'Pending':
        default:
            return '<span class="status-badge status-pending"><span class="badge-dot"></span>Pending</span>';
    }
}

/**
 * Render visual priority badge.
 *
 * @param string $priority
 * @return string
 */
function priority_badge($priority) {
    switch ($priority) {
        case 'Urgent':
            return '<span class="priority-badge priority-urgent">Urgent</span>';
        case 'High':
            return '<span class="priority-badge priority-high">High</span>';
        case 'Medium':
            return '<span class="priority-badge priority-medium">Medium</span>';
        case 'Low':
        default:
            return '<span class="priority-badge priority-low">Low</span>';
    }
}

/**
 * Validate task input fields for Level 1, Level 2, and Bonus requirements.
 *
 * @param array $data
 * @return array Array of validation error strings (empty if valid)
 */
function validate_task_input($data) {
    $errors = [];

    // Title validation (Required, min 2 chars, max 255 chars)
    $title = trim($data['title'] ?? '');
    if ($title === '') {
        $errors['title'] = 'Task title is required and cannot be empty.';
    } elseif (mb_strlen($title) < 2) {
        $errors['title'] = 'Task title must be at least 2 characters long.';
    } elseif (mb_strlen($title) > 255) {
        $errors['title'] = 'Task title cannot exceed 255 characters.';
    }

    // Status validation
    $validStatuses = ['Pending', 'In Progress', 'Completed'];
    $status = $data['status'] ?? 'Pending';
    if (!in_array($status, $validStatuses, true)) {
        $errors['status'] = 'Invalid status selected.';
    }

    // Priority validation
    $validPriorities = ['Low', 'Medium', 'High', 'Urgent'];
    $priority = $data['priority'] ?? 'Medium';
    if (!empty($priority) && !in_array($priority, $validPriorities, true)) {
        $errors['priority'] = 'Invalid priority selected.';
    }

    // Due date validation (optional, but must be valid YYYY-MM-DD if provided)
    if (!empty($data['due_date'])) {
        $d = DateTime::createFromFormat('Y-m-d', $data['due_date']);
        if (!$d || $d->format('Y-m-d') !== $data['due_date']) {
            $errors['due_date'] = 'Due date must be a valid date in YYYY-MM-DD format.';
        }
    }

    return $errors;
}

/**
 * Build URL query string preserving existing filter and search parameters.
 *
 * @param array $params
 * @return string
 */
function build_query_url($params = []) {
    $current = $_GET;
    foreach ($params as $k => $v) {
        if ($v === null) {
            unset($current[$k]);
        } else {
            $current[$k] = $v;
        }
    }
    $query = http_build_query($current);
    return 'index.php' . ($query ? '?' . $query : '');
}
