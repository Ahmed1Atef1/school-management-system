<?php
// Class_Room/edit.php
require_once "../conect.php";

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['userName'])) {
    header("Location: /Version_2/User/login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
$stmt = $conect->prepare("SELECT id, name, location, capacity FROM class_rooms WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    include "../header.php";
    echo "<div class='alert alert-danger'>Classroom not found.</div>";
    include "../footer.php";
    exit;
}
$classroom = $res->fetch_assoc();

include "../header.php";

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $capacity = filter_var($_POST['capacity'] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);

    if ($name === '') $errors[] = "Classroom name is required.";
    if ($capacity === false) $errors[] = "Capacity must be a valid positive number.";

    if (empty($errors)) {
        $u = $conect->prepare("UPDATE class_rooms SET name=?, location=?, capacity=? WHERE id=?");
        $u->bind_param("ssii", $name, $location, $capacity, $id);
        if ($u->execute()) {
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "DB error: " . $u->error;
        }
    }
}
?>

<h3>Edit Classroom</h3>

<?php if ($errors): ?><div class="alert alert-danger"><?php echo implode("<br>", $errors); ?></div><?php endif; ?>

<form method="post" class="mb-4">
  <div class="mb-3"><label class="form-label">Name / Room Number</label><input name="name" class="form-control" value="<?php echo htmlspecialchars($classroom['name']); ?>" required></div>
  <div class="mb-3"><label class="form-label">Location</label><input name="location" class="form-control" value="<?php echo htmlspecialchars($classroom['location']); ?>"></div>
  <div class="mb-3"><label class="form-label">Capacity</label><input name="capacity" type="number" min="0" class="form-control" value="<?php echo htmlspecialchars($classroom['capacity']); ?>"></div>
  <button type="submit" class="btn btn-primary">Update</button>
  <a href="index.php" class="btn btn-secondary ms-2">Cancel</a>
</form>

<?php include "../footer.php"; ?>