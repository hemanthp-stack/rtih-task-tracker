<?php
/**
 * RTIH Task Tracker - Application Header Component
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/flash.php';

$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="<?= e(csrf_token()); ?>">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' · ' . APP_NAME : APP_NAME . ' · RTIH Task Tracker'; ?></title>
    
    <!-- Design System CSS -->
    <link rel="stylesheet" href="assets/css/app.css?v=<?= time(); ?>">
    
    <!-- Favicon (Inline SVG Data URI) -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z'/></svg>">
</head>
<body>

<header class="app-header">
    <div class="app-container">
        <div class="header-inner">
            <a href="index.php" class="brand-wrapper">
                <div class="brand-logo-mark">
                    <svg class="w-6 h-6" style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                </div>
                <div class="brand-text">
                    <span class="brand-title"><?= APP_NAME; ?></span>
                    <span class="brand-subtitle">RTIH Amaravati Foundation</span>
                </div>
            </a>

            <?php if ($currentUser): ?>
            <div class="user-nav">
                <div class="user-pill">
                    <div class="user-avatar" title="<?= e($currentUser['email']); ?>">
                        <?= strtoupper(substr($currentUser['name'], 0, 1)); ?>
                    </div>
                    <span style="font-weight:600;"><?= e($currentUser['name']); ?></span>
                    <span class="role-tag role-<?= e($currentUser['role']); ?>">
                        <?= strtoupper(e($currentUser['role'])); ?>
                    </span>
                </div>

                <a href="logout.php" class="btn btn-secondary btn-sm" title="Sign Out">
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
            <?php else: ?>
            <div class="user-nav">
                <a href="login.php" class="btn btn-secondary btn-sm">Sign In</a>
                <a href="register.php" class="btn btn-primary btn-sm">Create Account</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</header>
