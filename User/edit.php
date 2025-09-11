<?php
// User/edit.php
require_once "../conect.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['userName'])) {
    header("Location: /Version_2/User/login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
$stmt = $conect->prepare("SELECT id,username,role FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    include "../header.php";
    echo "<div class='alert alert-danger'>User not found.</div>";
    include "../footer.php";
    exit;
}
$user = $res->fetch_assoc();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? 'student';
    $password = $_POST['password'] ?? '';

    if ($username === '') $errors[] = "Username required.";

    if (empty($errors)) {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $u = $conect->prepare("UPDATE users SET username=?, role=?, password=? WHERE id=?");
            $u->bind_param("sssi", $username, $role, $hash, $id);
        } else {
            $u = $conect->prepare("UPDATE users SET username=?, role=? WHERE id=?");
            $u->bind_param("ssi", $username, $role, $id);
        }
        if ($u->execute()) {
            // if edited current user, update session name
            if ($_SESSION['userId'] == $id) {
                $_SESSION['userName'] = $username;
                $_SESSION['role'] = $role;
            }
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "DB error: " . $u->error;
        }
    }
}

include "../header.php";
?>

<h3>Edit User</h3>
<?php if ($errors): ?><div class="alert alert-danger"><?php echo implode("<br>", $errors); ?></div><?php endif; ?>

<form method="post" class="mb-4">
  <div class="mb-3"><label class="form-label">Username</label><input name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required></div>
  <div class="mb-3"><label class="form-label">Password (leave empty to keep)</label><input name="password" type="password" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Role</label>
    <select name="role" class="form-select">
      <option value="admin" <?php if($user['role']=='admin') echo 'selected'; ?>>Admin</option>
      <option value="teacher" <?php if($user['role']=='teacher') echo 'selected'; ?>>Teacher</option>
      <option value="student" <?php if($user['role']=='student') echo 'selected'; ?>>Student</option>
    </select>
  </div>
  <button class="btn btn-primary">Update</button>
  <a href="index.php" class="btn btn-secondary ms-2">Cancel</a>
</form>

<?php include "../footer.php"; ?>
