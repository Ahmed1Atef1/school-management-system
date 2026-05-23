<?php
require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/config/validation.php';

$name = '';
$email = '';
$phone = '';
$errors = [];
$generatedCredentials = null;

// Fetch all courses for the multi-select
$courses = $conn->query("SELECT id, name, color FROM courses ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $selectedCourses = $_POST['courses'] ?? []; // Array of course IDs

    $errors = validate_teacher($_POST);

    if (empty($errors)) {
        // Auto-generate Username from email prefix
        $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0]);
        if (empty($baseUsername)) $baseUsername = 'teacher';
        $username = $baseUsername;
        
        // Ensure username is unique
        $suffix = 1;
        while (true) {
            $stmtCheck = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmtCheck->bind_param("s", $username);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows === 0) break;
            $username = $baseUsername . $suffix;
            $suffix++;
        }

        // Check if email already exists
        $stmtCheckEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmtCheckEmail->bind_param("s", $email);
        $stmtCheckEmail->execute();
        if ($stmtCheckEmail->get_result()->num_rows > 0) {
            $errors[] = "A user with this Email already exists.";
        } else {
            // Auto-generate Password
            $rawPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$'), 0, 8);
            
            $conn->begin_transaction();
            try {
                // 1. Create User Account
                $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
                $role = 'teacher';
                $stmtUser = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmtUser->bind_param("ssss", $username, $email, $hashedPassword, $role);
                $stmtUser->execute();

                // 2. Create Teacher Profile
                $subject = 'N/A'; // Legacy fallback
                $stmtTeacher = $conn->prepare("INSERT INTO teachers (name, email, phone, subject) VALUES (?, ?, ?, ?)");
                $stmtTeacher->bind_param("ssss", $name, $email, $phone, $subject);
                $stmtTeacher->execute();
                $teacherId = $conn->insert_id; // Get the newly created teacher's ID

                // 3. Assign Selected Courses
                if (!empty($selectedCourses)) {
                    $stmtCourse = $conn->prepare("UPDATE courses SET teacher_id = ? WHERE id = ?");
                    foreach ($selectedCourses as $cId) {
                        $stmtCourse->bind_param("ii", $teacherId, $cId);
                        $stmtCourse->execute();
                    }
                }

                $conn->commit();
                
                // Show credentials to admin
                $generatedCredentials = [
                    'name' => $name,
                    'username' => $username,
                    'password' => $rawPassword
                ];
                
                // Clear form
                $name = $email = $phone = '';
                
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = 'Database error: ' . $e->getMessage();
            }
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
                    <h3 class="mb-4 fw-bold">Add Teacher</h3>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger rounded-3">
                            <?= implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($generatedCredentials): ?>
                        <div class="alert alert-success rounded-3 mb-4">
                            <h5 class="alert-heading fw-bold"><i class="bi bi-check-circle-fill me-2"></i>Teacher Created!</h5>
                            <p class="mb-2">The system has automatically generated a secure login account for <strong><?= htmlspecialchars($generatedCredentials['name']); ?></strong>.</p>
                            <hr>
                            <div class="bg-white bg-opacity-50 p-3 rounded border border-success border-opacity-25 text-dark" style="font-family: monospace;">
                                <div><strong>Username:</strong> <?= htmlspecialchars($generatedCredentials['username']); ?></div>
                                <div><strong>Password:</strong> <?= htmlspecialchars($generatedCredentials['password']); ?></div>
                            </div>
                            <p class="mt-2 mb-0 small"><em>Please copy these credentials and provide them to the teacher. The password cannot be recovered once you leave this page.</em></p>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input name="name" class="form-control form-control-lg fs-6" placeholder="Jane Doe" value="<?= htmlspecialchars($name); ?>" required>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Address</label>
                                <input name="email" type="email" class="form-control" placeholder="jane@example.com" value="<?= htmlspecialchars($email); ?>" required>
                                <small class="text-muted mt-1">Used to generate unique login ID.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone Number</label>
                                <input name="phone" class="form-control" placeholder="+1234567890" value="<?= htmlspecialchars($phone); ?>">
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="form-label fw-semibold mb-3">Assign Courses</label>
                            <div class="row g-3">
                                <?php foreach ($courses as $course): 
                                    $color = htmlspecialchars($course['color'] ?: 'primary');
                                ?>
                                    <div class="col-md-6">
                                        <label class="form-check custom-checkbox-card p-3 border rounded-3 d-flex align-items-center mb-0 m-0" style="background: var(--app-surface-soft); border-color: var(--app-border) !important;">
                                            <input class="form-check-input mt-0 me-3 shadow-none border-<?= $color ?>" type="checkbox" name="courses[]" value="<?= $course['id']; ?>">
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
                            <button type="submit" class="btn btn-primary px-4 py-2 mt-3 rounded-pill fw-medium">Save Teacher</button>
                            <a href="<?= app_url('modules/admin/teachers/index.php'); ?>" class="btn btn-light px-4 py-2 mt-3 rounded-pill fw-medium">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>


