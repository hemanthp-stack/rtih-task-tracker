<?php
/**
 * RTIH Task Tracker - Logout Handler
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';

logout_user();

set_flash('info', 'You have been safely signed out. See you soon!');
header('Location: login.php');
exit;
