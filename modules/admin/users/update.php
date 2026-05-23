<?php
require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/config/validation.php';
require_once BASE_PATH . '/config/profile_sync.php';
require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$errors = [];

$stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    require_once BASE_PATH . '/includes/header.php';
    echo "<div class='alert alert-danger'>User not found.</div>";
    require_once BASE_PATH . '/includes/footer.php';
    exit;
}

$username = $user['username'];
$email = $user['email'];
$role = $user['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'student';
    $password = $_POST['password'] ?? '';

    $errors = validate_user($_POST, false);

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? LIMIT 1");
        $stmt->bind_param("ssi", $username, $email, $id);
        $stmt->execute();

        if ($stmt->get_result()->fetch_assoc()) {
            $errors[] = 'Username or email is already registered.';
        }
    }

    if (empty($errors)) {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ?, password = ? WHERE id = ?");
            $update->bind_param("ssssi", $username, $email, $role, $hash, $id);
        } else {
            $update = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
            $update->bind_param("sssi", $username, $email, $role, $id);
        }

        if ($update->execute()) {
            sync_user_profile($conn, $username, $email, $role, $user['email'], $user['role']);

            if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $id) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
            }

            redirect_to('modules/admin/users/index.php');
        }

        $errors[] = 'Database error: ' . $update->error;
    }
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="container mt-5" style="max-width: 720px;">
    <h3 class="mb-4">Edit User</h3>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?= implode('<br>', array_map('htmlspecialchars', $errors)); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="mb-4">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input name="username" class="form-control" value="<?= htmlspecialchars($username); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($email); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password (leave empty to keep current)</label>
            <input name="password" type="password" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
                <option value="admin" <?= $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="teacher" <?= $role === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                <option value="student" <?= $role === 'student' ? 'selected' : ''; ?>>Student</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="<?= app_url('modules/admin/users/index.php'); ?>" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>


