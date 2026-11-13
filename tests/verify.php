<?php
/**
 * RTIH Task Tracker - Automated Verification Suite
 * Tests Level 1, Level 2, Level 3 security and CRUD operations.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = get_db_connection();

echo "========================================================\n";
echo "  RTIH TASK TRACKER - AUTOMATED TEST VERIFICATION SUITE \n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assert_test($description, $condition) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  ✅ PASS: $description\n";
        $passCount++;
    } else {
        echo "  ❌ FAIL: $description\n";
        $failCount++;
    }
}

// --------------------------------------------------------------------------
// TEST GROUP 1: Database & Seed Verification (Level 1)
// --------------------------------------------------------------------------
echo "[Group 1: Database & Seed Integrity]\n";

$usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
assert_test("Users table populated with initial seed records", $usersCount >= 2);

$tasksCount = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
assert_test("Tasks table populated with sample tasks", $tasksCount >= 5);

// --------------------------------------------------------------------------
// TEST GROUP 2: Authentication & Password Hashing (Level 3)
// --------------------------------------------------------------------------
echo "\n[Group 2: Authentication & Cryptographic Hashing]\n";

$userStmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$userStmt->execute([':email' => 'user@rtih.local']);
$user = $userStmt->fetch();

assert_test("Standard user account exists (user@rtih.local)", !empty($user));
assert_test("Password verify succeeds for 'User@123'", password_verify('User@123', $user['password_hash']));
assert_test("Password verify rejects invalid password", !password_verify('WrongPassword', $user['password_hash']));

// --------------------------------------------------------------------------
// TEST GROUP 3: Task Validation Logic (Level 2)
// --------------------------------------------------------------------------
echo "\n[Group 3: Input Validation & Sanitization]\n";

$emptyTitleErrors = validate_task_input(['title' => '']);
assert_test("Validation rejects empty title", isset($emptyTitleErrors['title']));

$shortTitleErrors = validate_task_input(['title' => 'A']);
assert_test("Validation rejects title shorter than 2 chars", isset($shortTitleErrors['title']));

$invalidStatusErrors = validate_task_input(['title' => 'Valid Title', 'status' => 'UnknownStatus']);
assert_test("Validation rejects illegal status value", isset($invalidStatusErrors['status']));

$validErrors = validate_task_input(['title' => 'Valid Title', 'status' => 'Pending', 'priority' => 'High']);
assert_test("Validation accepts fully valid payload", empty($validErrors));

// Output Escaping (XSS Prevention)
$xssPayload = "<script>alert('XSS')</script>";
$escaped = e($xssPayload);
assert_test("e() escapes <script> tag to &lt;script&gt;", strpos($escaped, '<script>') === false && strpos($escaped, '&lt;script&gt;') !== false);

// --------------------------------------------------------------------------
// TEST GROUP 4: CSRF Protection Verification (Level 3)
// --------------------------------------------------------------------------
echo "\n[Group 4: CSRF Protection]\n";

$token = csrf_token();
assert_test("CSRF token generated (32 bytes / 64 hex chars)", strlen($token) === 64);
assert_test("CSRF token verification succeeds with correct token", verify_csrf_token($token));
assert_test("CSRF token verification fails with forged token", !verify_csrf_token("invalid_token_123"));

// --------------------------------------------------------------------------
// TEST GROUP 5: Authorization & IDOR Defense (Level 3)
// --------------------------------------------------------------------------
echo "\n[Group 5: Authorization & Ownership (IDOR Prevention)]\n";

// Simulate standard user session (ID = 1)
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'user';

// Task 1 belongs to user 1
$task1Auth = authorize_task_access(1, $pdo);
assert_test("User 1 is authorized to access own task (#1)", !empty($task1Auth));

// Task 7 belongs to user 2 (Admin)
$task7Auth = authorize_task_access(7, $pdo);
assert_test("User 1 is strictly BLOCKED from accessing User 2's task (#7)", $task7Auth === false);

// Switch session to Admin
$_SESSION['user_id'] = 2;
$_SESSION['user_role'] = 'admin';
$adminTask7Auth = authorize_task_access(7, $pdo);
assert_test("Admin is authorized to access any task (#7)", !empty($adminTask7Auth));

// --------------------------------------------------------------------------
// TEST GROUP 6: CRUD Operations (Level 1 & Level 2)
// --------------------------------------------------------------------------
echo "\n[Group 6: CRUD Operations via Prepared Statements]\n";

// CREATE
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'user';
$createStmt = $pdo->prepare("
    INSERT INTO tasks (user_id, title, description, status, priority, due_date, created_at)
    VALUES (1, 'Test CRUD Milestone', 'Temporary task for verification', 'Pending', 'High', date('now', '+3 days'), CURRENT_TIMESTAMP)
");
$createStmt->execute();
$newTaskId = (int)$pdo->lastInsertId();
assert_test("Task created successfully (ID: $newTaskId)", $newTaskId > 0);

// READ
$readStmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
$readStmt->execute([':id' => $newTaskId]);
$createdTask = $readStmt->fetch();
assert_test("Task read successfully and verified", $createdTask['title'] === 'Test CRUD Milestone');

// UPDATE (Title, Description, Status)
$updateStmt = $pdo->prepare("UPDATE tasks SET title = :title, status = :status WHERE id = :id");
$updateStmt->execute([
    ':title'  => 'Test CRUD Milestone - Updated',
    ':status' => 'Completed',
    ':id'     => $newTaskId
]);
$verifyUpdate = $pdo->query("SELECT * FROM tasks WHERE id = $newTaskId")->fetch();
assert_test("Task updated successfully (Status changed to Completed)", $verifyUpdate['status'] === 'Completed' && $verifyUpdate['title'] === 'Test CRUD Milestone - Updated');

// DELETE
$deleteStmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
$deleteStmt->execute([':id' => $newTaskId]);
$verifyDelete = $pdo->query("SELECT * FROM tasks WHERE id = $newTaskId")->fetch();
assert_test("Task deleted successfully", $verifyDelete === false);

echo "\n========================================================\n";
echo "  TEST SUMMARY: $passCount Passed, $failCount Failed\n";
echo "========================================================\n";

if ($failCount === 0) {
    echo "  🎉 ALL TECHNICAL REQUIREMENTS VERIFIED AND PASSING!\n\n";
    exit(0);
} else {
    echo "  ❌ SOME TESTS FAILED!\n\n";
    exit(1);
}
