<?php
require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/config/validation.php';
require_once BASE_PATH . '/config/profile_sync.php';
require_role('admin');

$username = '';
$email = '';
$role = '';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    $errors = validate_user($_POST);

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();

        if ($exists) {
            $errors[] = 'Username or email is already registered.';
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);

        if ($stmt->execute()) {
            sync_user_profile($conn, $username, $email, $role);
            $success = 'User created successfully.';
            $username = '';
            $email = '';
            $role = '';
        } else {
            $errors[] = 'Database error: ' . $stmt->error;
        }
    }
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="container mt-5" style="max-width: 720px;">
    <h2 class="mb-4">Create User</h2>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?= implode('<br>', array_map('htmlspecialchars', $errors)); ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select" required>
                <option value="" disabled <?= $role === '' ? 'selected' : ''; ?>>Select Role</option>
                <option value="student" <?= $role === 'student' ? 'selected' : ''; ?>>Student</option>
                <option value="teacher" <?= $role === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                <option value="admin" <?= $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Save</button>
        <a href="<?= app_url('modules/admin/users/index.php'); ?>" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>


