<?php
// Student/add.php
require_once "../conect.php";

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['userName'])) {
    header("Location: /Version_2/User/login.php");
    exit;
}

include "../header.php";

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '') $errors[] = "Name is required.";
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email is invalid.";

    if (empty($errors)) {
        $stmt = $conect->prepare("INSERT INTO students (name, email, phone) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $phone);
        if ($stmt->execute()) {
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "DB error: " . $stmt->error;
        }
    }
}
?>

<h3>Add Student</h3>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?php echo implode("<br>", $errors); ?></div>
<?php endif; ?>

<form method="post" class="mb-4">
  <div class="mb-3"><label class="form-label">Full name</label><input name="name" class="form-control" required></div>
  <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
  <button class="btn btn-success">Save</button>
  <a href="index.php" class="btn btn-secondary ms-2">Cancel</a>
</form>

<?php include "../footer.php"; ?>
