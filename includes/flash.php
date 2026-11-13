<?php
/**
 * RTIH Task Tracker - Flash Notifications & Message Alerts
 * Implements Level 1 & Level 2 user feedback requirements.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Set a flash message for the next request.
 *
 * @param string $type 'success', 'error', 'info', or 'warning'
 * @param string $message
 */
function set_flash($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear all queued flash messages.
 *
 * @return array
 */
function get_flash_messages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    // Check legacy single-variable flash messages if present
    if (isset($_SESSION['flash_success'])) {
        $messages[] = ['type' => 'success', 'message' => $_SESSION['flash_success']];
        unset($_SESSION['flash_success']);
    }
    if (isset($_SESSION['flash_error'])) {
        $messages[] = ['type' => 'error', 'message' => $_SESSION['flash_error']];
        unset($_SESSION['flash_error']);
    }

    return $messages;
}

/**
 * Render flash messages as modern, accessible toast/alert components.
 *
 * @return string
 */
function render_flash_messages() {
    $messages = get_flash_messages();
    if (empty($messages)) {
        return '';
    }

    $html = '<div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true">';
    foreach ($messages as $msg) {
        $type = htmlspecialchars($msg['type']);
        $text = htmlspecialchars($msg['message']);
        
        $icon = '';
        if ($type === 'success') {
            $icon = '<svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
        } elseif ($type === 'error') {
            $icon = '<svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
        } elseif ($type === 'warning') {
            $icon = '<svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
        } else {
            $icon = '<svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
        }

        $html .= '
        <div class="toast-card toast-' . $type . '" role="alert">
            <div class="toast-icon">' . $icon . '</div>
            <div class="toast-body">' . $text . '</div>
            <button type="button" class="toast-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
        </div>';
    }
    $html .= '</div>';

    return $html;
}
