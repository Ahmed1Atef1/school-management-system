<?php
require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/config/validation.php';

$id = (int) ($_GET['id'] ?? 0);
$errors = [];

// Get student details
$stmt = $conn->prepare("SELECT id, name, email, phone FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    require_once BASE_PATH . '/includes/header.php';
    echo "<div class='alert alert-danger'>Student not found.</div>";
    require_once BASE_PATH . '/includes/footer.php';
    exit;
}

$name = $student['name'];
$email = $student['email'];
$phone = $student['phone'];

// We need the user_id from the users table for enrollments
$stmtUser = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'student' LIMIT 1");
$stmtUser->bind_param("s", $email);
$stmtUser->execute();
$userResult = $stmtUser->get_result()->fetch_assoc();
$userId = $userResult['id'] ?? 0;

// Fetch all courses for the multi-select
$courses = $conn->query("SELECT id, name, color FROM courses ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch currently enrolled courses
$currentCourseIds = [];
if ($userId > 0) {
    $stmtEnrolled = $conn->prepare("SELECT course_id FROM enrollments WHERE user_id = ?");
    $stmtEnrolled->bind_param("i", $userId);
    $stmtEnrolled->execute();
    $enrolledResult = $stmtEnrolled->get_result()->fetch_all(MYSQLI_ASSOC);
    $currentCourseIds = array_column($enrolledResult, 'course_id');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $selectedCourses = $_POST['courses'] ?? [];

    $errors = validate_student($_POST);

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Update Student Profile
            $update = $conn->prepare("UPDATE students SET name = ?, email = ?, phone = ? WHERE id = ?");
            $update->bind_param("sssi", $name, $newEmail, $phone, $id);
            $update->execute();
            
            // If email changed, we must update the users table too
            if ($newEmail !== $email && $userId > 0) {
                $updateUser = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                $updateUser->bind_param("si", $newEmail, $userId);
                $updateUser->execute();
            }

            if ($userId > 0) {
                // Clear existing enrollments
                $stmtClear = $conn->prepare("DELETE FROM enrollments WHERE user_id = ?");
                $stmtClear->bind_param("i", $userId);
                $stmtClear->execute();

                // Insert new enrollments
                if (!empty($selectedCourses)) {
                    $stmtEnroll = $conn->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
                    foreach ($selectedCourses as $cId) {
                        $stmtEnroll->bind_param("ii", $userId, $cId);
                        $stmtEnroll->execute();
                    }
                }
            }

            $conn->commit();
            redirect_to('modules/admin/students/index.php');
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
                        <h3 class="mb-0 fw-bold">Edit Student</h3>
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
                                <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone number</label>
                                <input name="phone" class="form-control" value="<?= htmlspecialchars($phone); ?>">
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="form-label fw-semibold mb-3">Enrolled Courses</label>
                            
                            <?php if ($userId === 0): ?>
                                <div class="alert alert-warning py-2 mb-3">
                                    <small><i class="bi bi-exclamation-triangle-fill me-2"></i>Warning: No system login account found for this student. Enrollments may not save correctly.</small>
                                </div>
                            <?php endif; ?>

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
                            <button type="submit" class="btn btn-primary px-4 py-2 mt-3 rounded-pill fw-medium">Update Student</button>
                            <a href="<?= app_url('modules/admin/students/index.php'); ?>" class="btn btn-light px-4 py-2 mt-3 rounded-pill fw-medium">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>


