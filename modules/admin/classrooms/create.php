<?php
require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/config/validation.php';

$name = '';
$location = '';
$capacity = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $capacity = $_POST['capacity'] ?? '';

    $errors = validate_classroom($_POST);

    if (empty($errors)) {
        $capacity = (int) $capacity;
        $stmt = $conn->prepare("INSERT INTO class_rooms (name, location, capacity) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $location, $capacity);

        if ($stmt->execute()) {
            redirect_to('modules/admin/classrooms/index.php');
        }

        $errors[] = 'Database error: ' . $stmt->error;
    }
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="container mt-5">
    <h3 class="mb-4">Add Classroom</h3>

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
            <input name="location" class="form-control" value="<?= htmlspecialchars($location); ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Capacity</label>
            <input name="capacity" type="number" min="0" class="form-control" value="<?= htmlspecialchars((string) $capacity); ?>" required>
        </div>

        <button type="submit" class="btn btn-success">Save</button>
        <a href="<?= app_url('modules/admin/classrooms/index.php'); ?>" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>


