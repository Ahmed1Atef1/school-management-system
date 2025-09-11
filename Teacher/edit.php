<?php
// Teacher/edit.php
require_once "../conect.php";

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['userName'])) {
    header("Location: /Version_2/User/login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
$stmt = $conect->prepare("SELECT id, name, email, phone, subject FROM teachers WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    include "../header.php";
    echo "<div class='alert alert-danger'>Teacher not found.</div>";
    include "../footer.php";
    exit;
}
$teacher = $res->fetch_assoc();

include "../header.php";

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');

    if ($name === '') $errors[] = "Name is required.";
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email is invalid.";

    if (empty($errors)) {
        $u = $conect->prepare("UPDATE teachers SET name=?, email=?, phone=?, subject=? WHERE id=?");
        $u->bind_param("ssssi", $name, $email, $phone, $subject, $id);
        if ($u->execute()) {
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "DB error: " . $u->error;
        }
    }
}
?>

<h3>Edit Teacher</h3>

<?php if ($errors): ?><div class="alert alert-danger"><?php echo implode("<br>", $errors); ?></div><?php endif; ?>

<form method="post" class="mb-4">
  <div class="mb-3"><label class="form-label">Full name</label><input name="name" class="form-control" value="<?php echo htmlspecialchars($teacher['name']); ?>" required></div>
  <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="<?php echo htmlspecialchars($teacher['email']); ?>"></div>
  <div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?php echo htmlspecialchars($teacher['phone']); ?>"></div>
  <div class="mb-3"><label class="form-label">Subject</label><input name="subject" class="form-control" value="<?php echo htmlspecialchars($teacher['subject']); ?>"></div>
  <button type="submit" class="btn btn-primary">Update</button>
  <a href="index.php" class="btn btn-secondary ms-2">Cancel</a>
</form>

<?php include "../footer.php"; ?>