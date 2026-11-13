<?php
/**
 * RTIH Task Tracker - User Registration
 * Level 3 Requirement: Registration, password_hash(), CSRF defense.
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

$errors = [];
$nameInput = '';
$emailInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    $nameInput = $name;
    $emailInput = $email;

    // Validation
    if (empty($name)) {
        $errors['name'] = "Full name is required.";
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['name'] = "Name must be between 2 and 100 characters.";
    }

    if (empty($email)) {
        $errors['email'] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please provide a valid email address.";
    }

    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters long.";
    }

    if ($password !== $passwordConfirm) {
        $errors['password_confirm'] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $pdo = get_db_connection();

        // Check if email already exists (Prepared Statement)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors['email'] = "An account with this email address already exists.";
        } else {
            // Hash password with bcrypt
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            try {
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (name, email, password_hash, role, created_at)
                    VALUES (:name, :email, :password_hash, 'user', CURRENT_TIMESTAMP)
                ");

                $insertStmt->execute([
                    ':name'          => $name,
                    ':email'         => $email,
                    ':password_hash' => $passwordHash
                ]);

                $newUserId = (int)$pdo->lastInsertId();

                // Auto-login newly registered user
                login_user([
                    'id'    => $newUserId,
                    'name'  => $name,
                    'email' => $email,
                    'role'  => 'user'
                ]);

                // Create initial welcome task
                $welcomeStmt = $pdo->prepare("
                    INSERT INTO tasks (user_id, title, description, status, priority, due_date, created_at)
                    VALUES (:user_id, 'Welcome to Task Tracker! 🎉', 'Explore the dashboard by trying out search filters, changing task status, or adding your first project milestone.', 'In Progress', 'High', CURRENT_DATE, CURRENT_TIMESTAMP)
                ");
                $welcomeStmt->execute([':user_id' => $newUserId]);

                set_flash('success', 'Your account has been created successfully! Welcome to Task Tracker.');
                header('Location: index.php');
                exit;

            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $errors['general'] = "A server error occurred during registration. Please try again.";
            }
        }
    }
}

$pageTitle = "Create Account";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand-logo-mark" style="margin: 0 auto; width: 3rem; height: 3rem; font-size: 1.35rem;">
                <svg style="width:26px;height:26px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>
            <h1 class="auth-title">Create an Account</h1>
            <p class="auth-desc">Register to organize and prioritize your tasks</p>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="toast-card toast-error" style="margin-bottom: 1.25rem;">
                <div class="toast-icon">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </div>
                <div class="toast-body"><?= e($errors['general']); ?></div>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" autocomplete="off">
            <?= csrf_field(); ?>

            <div class="form-group">
                <label class="form-label required" for="regName">Full Name</label>
                <input type="text" name="name" id="regName" class="form-control <?= isset($errors['name']) ? 'is-invalid' : ''; ?>" value="<?= e($nameInput); ?>" required placeholder="e.g. Hemanth Kumar" autofocus>
                <?php if (isset($errors['name'])): ?>
                    <div class="invalid-feedback"><?= e($errors['name']); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label required" for="regEmail">Email Address</label>
                <input type="email" name="email" id="regEmail" class="form-control <?= isset($errors['email']) ? 'is-invalid' : ''; ?>" value="<?= e($emailInput); ?>" required placeholder="name@domain.com">
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?= e($errors['email']); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required" for="regPassword">Password</label>
                    <input type="password" name="password" id="regPassword" class="form-control <?= isset($errors['password']) ? 'is-invalid' : ''; ?>" required placeholder="Min 6 characters">
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?= e($errors['password']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label required" for="regConfirm">Confirm</label>
                    <input type="password" name="password_confirm" id="regConfirm" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : ''; ?>" required placeholder="Repeat password">
                    <?php if (isset($errors['password_confirm'])): ?>
                        <div class="invalid-feedback"><?= e($errors['password_confirm']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; margin-top: 0.5rem;">
                Complete Registration
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
            Already have an account? <a href="login.php" style="color: var(--primary-600); font-weight: 600; text-decoration: none;">Sign in here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
