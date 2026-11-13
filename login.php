<?php
/**
 * RTIH Task Tracker - User Authentication / Login
 * Implements Level 3: password_verify(), session fixation defense, and CSRF protection.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/flash.php';

// Redirect if already authenticated
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$emailInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $emailInput = $email;

    if (empty($email) || empty($password)) {
        $error = "Please enter both your email address and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // Verify password using password_verify()
        if ($user && password_verify($password, $user['password_hash'])) {
            login_user($user);
            set_flash('success', 'Welcome back, ' . e($user['name']) . '!');
            header('Location: index.php');
            exit;
        } else {
            $error = "Invalid email or password. Please check your credentials.";
        }
    }
}

$pageTitle = "Sign In";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand-logo-mark" style="margin: 0 auto; width: 3rem; height: 3rem; font-size: 1.35rem;">
                <svg style="width:26px;height:26px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
            </div>
            <h1 class="auth-title">Welcome to <?= APP_NAME; ?></h1>
            <p class="auth-desc">Sign in to manage and track your technical tasks</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="toast-card toast-error" style="margin-bottom: 1.25rem;">
                <div class="toast-icon">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </div>
                <div class="toast-body"><?= e($error); ?></div>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="off">
            <?= csrf_field(); ?>

            <div class="form-group">
                <label class="form-label required" for="loginEmail">Email Address</label>
                <input type="email" name="email" id="loginEmail" class="form-control" value="<?= e($emailInput); ?>" required placeholder="user@rtih.local" autofocus>
            </div>

            <div class="form-group">
                <label class="form-label required" for="loginPassword">Password</label>
                <input type="password" name="password" id="loginPassword" class="form-control" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; margin-top: 0.5rem;">
                Sign In to Dashboard
            </button>
        </form>

        <!-- Convenient 1-Click Demo Accounts for Assignment Reviewers -->
        <div class="demo-account-box">
            <div class="demo-account-title">
                <span>Quick Fill Demo Credentials</span>
                <span style="font-size:0.7rem; color:var(--text-subtle);">Click to populate</span>
            </div>
            <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem; flex-wrap: wrap;">
                <button type="button" class="demo-pill" onclick="fillCredentials('user@rtih.local', 'User@123')">
                    Standard User (Hemanth)
                </button>
                <button type="button" class="demo-pill" onclick="fillCredentials('admin@rtih.local', 'Admin@123')">
                    Admin User (RTIH)
                </button>
            </div>
        </div>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
            Don't have an account yet? <a href="register.php" style="color: var(--primary-600); font-weight: 600; text-decoration: none;">Create one here</a>
        </div>
    </div>
</div>

<script>
function fillCredentials(email, password) {
    document.getElementById('loginEmail').value = email;
    document.getElementById('loginPassword').value = password;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
