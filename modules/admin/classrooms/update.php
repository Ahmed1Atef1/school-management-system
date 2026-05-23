<?php
require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/config/validation.php';

$id = (int) ($_GET['id'] ?? 0);
$errors = [];

$stmt = $conn->prepare("SELECT id, name, location, capacity FROM class_rooms WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$classroom = $stmt->get_result()->fetch_assoc();

if (!$classroom) {
    require_once BASE_PATH . '/includes/header.php';
    echo "<div class='alert alert-danger'>Classroom not found.</div>";
    require_once BASE_PATH . '/includes/footer.php';
    exit;
}

$name = $classroom['name'];
$location = $classroom['location'];
$capacity = $classroom['capacity'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $capacity = $_POST['capacity'] ?? '';

    $errors = validate_classroom($_POST);

    if (empty($errors)) {
        $capacity = (int) $capacity;
        $update = $conn->prepare("UPDATE class_rooms SET name = ?, location = ?, capacity = ? WHERE id = ?");
        $update->bind_param("ssii", $name, $location, $capacity, $id);

        if ($update->execute()) {
            redirect_to('modules/admin/classrooms/index.php');
        }

        $errors[] = 'Database error: ' . $update->error;
    }
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="container mt-5">
    <h3 class="mb-4">Edit Classroom</h3>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?= implode('<br>', array_map('htmlspecialchars', $errors)); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="mb-4">
        <div class="mb-3">
            <label class="form-label">Name / Room Number</label>
            <input name="name" class="form-control" value="<?= htmlspecialchars($name); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Location</label>
            <input name="location" class="form-control" value="<?= htmlspecialchars((string) $location); ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Capacity</label>
            <input name="capacity" type="number" min="0" class="form-control" value="<?= htmlspecialchars((string) $capacity); ?>" required>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="<?= app_url('modules/admin/classrooms/index.php'); ?>" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>


