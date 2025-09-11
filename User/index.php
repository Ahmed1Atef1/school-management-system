<?php
// User/index.php
require_once "../conect.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['userName'])) {
    header("Location: /Version_2/User/login.php");
    exit;
}
include "../header.php";

$stmt = $conect->prepare("SELECT id, username, role, created_at FROM users ORDER BY id DESC");
$stmt->execute();
$res = $stmt->get_result();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Users</h3>
  <a href="add.php" class="btn btn-success btn-sm">+ Add User</a>
</div>

<table class="table table-bordered table-hover">
  <thead class="table-light">
    <tr><th>ID</th><th>Username</th><th>Role</th><th>Added</th><th>Actions</th></tr>
  </thead>
  <tbody>
    <?php while($row = $res->fetch_assoc()): ?>
      <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo htmlspecialchars($row['username']); ?></td>
        <td><?php echo htmlspecialchars($row['role']); ?></td>
        <td><?php echo $row['created_at']; ?></td>
        <td>
          <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
          <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include "../footer.php"; ?>
