<?php
// Class_Room/add.php
require_once "../conect.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['userName'])) {
    header("Location: /Version_2/User/login.php");
    exit;
}

include "../header.php";

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $capacity = filter_var($_POST['capacity'] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);

    if ($name === '') $errors[] = "Classroom name or number is required.";
    if ($capacity === false) $errors[] = "Capacity must be a valid positive number.";

    if (empty($errors)) {
        $stmt = $conect->prepare("INSERT INTO class_rooms (name, location, capacity) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $location, $capacity);
        if ($stmt->execute()) {
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "DB error: " . $stmt->error;
        }
    }
}
?>

<h3>Add Classroom</h3>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?php echo implode("<br>", $errors); ?></div>
<?php endif; ?>

<form method="post" class="mb-4">
  <div class="mb-3"><label class="form-label">Name / Room Number</label><input name="name" class="form-control" required></div>
  <div class="mb-3"><label class="form-label">Location (e.g., Building A, 2nd Floor)</label><input name="location" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Capacity</label><input name="capacity" type="number" min="0" class="form-control"></div>
  <button type="submit" class="btn btn-success">Save</button>
  <a href="index.php" class="btn btn-secondary ms-2">Cancel</a>
</form>

<?php include "../footer.php"; ?>