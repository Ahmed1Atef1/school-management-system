<?php
require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/config/validation.php';

$id = (int) ($_GET['id'] ?? 0);
$errors = [];

$stmt = $conn->prepare("SELECT id, name, email, phone, subject FROM teachers WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

if (!$teacher) {
    require_once BASE_PATH . '/includes/header.php';
    echo "<div class='alert alert-danger'>Teacher not found.</div>";
    require_once BASE_PATH . '/includes/footer.php';
    exit;
}

$name = $teacher['name'];
$email = $teacher['email'];
$phone = $teacher['phone'];

// Fetch all courses for the multi-select
$courses = $conn->query("SELECT id, name, color FROM courses ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch currently assigned courses for this teacher
$stmtAssigned = $conn->prepare("SELECT id FROM courses WHERE teacher_id = ?");
$stmtAssigned->bind_param("i", $id);
$stmtAssigned->execute();
$assignedResult = $stmtAssigned->get_result()->fetch_all(MYSQLI_ASSOC);
$currentCourseIds = array_column($assignedResult, 'id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $selectedCourses = $_POST['courses'] ?? []; // Array of course IDs

    $errors = validate_teacher($_POST);

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $subject = 'N/A'; // Legacy fallback
            $update = $conn->prepare("UPDATE teachers SET name = ?, email = ?, phone = ?, subject = ? WHERE id = ?");
            $update->bind_param("ssssi", $name, $email, $phone, $subject, $id);
            $update->execute();

            // Unassign all courses currently taught by this teacher
            $stmtUnassign = $conn->prepare("UPDATE courses SET teacher_id = NULL WHERE teacher_id = ?");
            $stmtUnassign->bind_param("i", $id);
            $stmtUnassign->execute();

            // Assign newly selected courses
            if (!empty($selectedCourses)) {
                $stmtCourse = $conn->prepare("UPDATE courses SET teacher_id = ? WHERE id = ?");
                foreach ($selectedCourses as $cId) {
                    $stmtCourse->bind_param("ii", $id, $cId);
                    $stmtCourse->execute();
                }
            }

            $conn->commit();
            redirect_to('modules/admin/teachers/index.php');
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

require_once BASE_PATH . '/includes/header.php';
?>

<style>
.custom-checkbox-card {
    transition: all 0.2s ease;
    cursor: pointer;
}
.custom-checkbox-card:hover {
    background: var(--app-surface) !important;
    border-color: var(--bs-primary) !important;
    transform: translateY(-1px);
}
.form-check-input:checked + .form-check-label {
    color: var(--bs-primary) !important;
    font-weight: 600 !important;
}
</style>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-sm border-0" style="background: var(--app-surface); border: 1px solid var(--app-border) !important; border-radius: 18px;">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0 fw-bold">Edit Teacher</h3>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger rounded-3">
                            <?= implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Full name</label>
                            <input name="name" class="form-control form-control-lg fs-6" value="<?= htmlspecialchars($name); ?>" required>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email address</label>
                                <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($email); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone number</label>
                                <input name="phone" class="form-control" value="<?= htmlspecialchars($phone); ?>">
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="form-label fw-semibold mb-3">Assigned Courses</label>
                            <div class="row g-3">
                                <?php foreach ($courses as $course): 
                                    $color = htmlspecialchars($course['color'] ?: 'primary');
                                    $isChecked = in_array($course['id'], $currentCourseIds) ? 'checked' : '';
                                ?>
                                    <div class="col-md-6">
                                        <label class="form-check custom-checkbox-card p-3 border rounded-3 d-flex align-items-center mb-0 m-0" style="background: var(--app-surface-soft); border-color: var(--app-border) !important;">
                                            <input class="form-check-input mt-0 me-3 shadow-none border-<?= $color ?>" type="checkbox" name="courses[]" value="<?= $course['id']; ?>" <?= $isChecked; ?>>
                                            <span class="form-check-label w-100 m-0 text-truncate" style="color: var(--app-text);">
                                                <i class="bi bi-circle-fill text-<?= $color ?> me-2" style="font-size: 0.6rem;"></i>
                                                <?= htmlspecialchars($course['name']); ?>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="d-flex gap-2 pt-2 border-top" style="border-color: var(--app-border) !important;">
                            <button type="submit" class="btn btn-primary px-4 py-2 mt-3 rounded-pill fw-medium">Update Teacher</button>
                            <a href="<?= app_url('modules/admin/teachers/index.php'); ?>" class="btn btn-light px-4 py-2 mt-3 rounded-pill fw-medium">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>


