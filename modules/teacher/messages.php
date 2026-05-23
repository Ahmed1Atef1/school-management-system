<?php
require_once '../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/includes/notifications_helper.php';

if (!user_has_role(['admin', 'teacher'])) redirect_to('home.php');

$userId = current_user_id();
$success = $error = '';

// Fetch courses taught by this teacher (or all if admin)
if ($_SESSION['role'] === 'admin') {
    $courses = $conn->query("SELECT id, name FROM courses ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
} else {
    $stmtC = $conn->prepare("SELECT id, name FROM courses WHERE teacher_id = ? ORDER BY name ASC");
    $stmtC->bind_param('i', $userId);
    $stmtC->execute();
    $courses = $stmtC->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtC->close();
}

// Fetch all students for the "Direct Message" option (or just filter via AJAX, but for now we'll load them all)
$students = $conn->query("SELECT id, username FROM users WHERE role = 'student' ORDER BY username ASC")->fetch_all(MYSQLI_ASSOC);

// Handle Send Message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $msgType = $_POST['msg_type'] ?? 'direct'; // 'direct' or 'course'
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $receiverId = (int) ($_POST['receiver_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($title) || empty($message)) {
        $error = 'Title and Message are required.';
    } elseif ($msgType === 'course' && !$courseId) {
        $error = 'Please select a course for the announcement.';
    } elseif ($msgType === 'direct' && !$receiverId) {
        $error = 'Please select a student to message.';
    } else {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, course_id, title, message) VALUES (?, ?, ?, ?, ?)");
        
        $recVal = ($msgType === 'direct') ? $receiverId : null;
        $crsVal = ($msgType === 'course') ? $courseId : null;
        
        $stmt->bind_param('iiiss', $userId, $recVal, $crsVal, $title, $message);
        if ($stmt->execute()) {
            $success = "Message sent successfully!";
            
            // Send Notification
            if ($msgType === 'direct') {
                send_notification($conn, $receiverId, 'system', "New Message: {$title}", "You received a direct message from " . $_SESSION['username'], 'bi-envelope-fill', 'info', app_url('modules/student/messages.php'));
            } elseif ($msgType === 'course') {
                // Fetch all enrolled students
                $stmtEn = $conn->prepare("SELECT user_id FROM enrollments WHERE course_id = ?");
                $stmtEn->bind_param('i', $courseId);
                $stmtEn->execute();
                $enrolled = $stmtEn->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmtEn->close();
                
                // Get course name
                $cName = 'Course';
                foreach ($courses as $c) { if ((int)$c['id'] === $courseId) $cName = $c['name']; }
                
                foreach ($enrolled as $en) {
                    send_notification($conn, $en['user_id'], 'system', "Announcement: {$title}", "New announcement in {$cName}.", 'bi-megaphone-fill', 'primary', app_url('modules/student/messages.php'));
                }
            }
        } else {
            $error = "Failed to send message.";
        }
        $stmt->close();
    }
}

// Fetch recently sent messages
$stmtHistory = $conn->prepare("
    SELECT m.title, m.created_at, 
           c.name AS course_name, u.username AS receiver_name
    FROM messages m
    LEFT JOIN courses c ON c.id = m.course_id
    LEFT JOIN users u ON u.id = m.receiver_id
    WHERE m.sender_id = ?
    ORDER BY m.created_at DESC LIMIT 10
");
$stmtHistory->bind_param('i', $userId);
$stmtHistory->execute();
$sentMessages = $stmtHistory->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtHistory->close();

require_once BASE_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Announcements & Messages</h2>
        <p class="text-muted small mb-0">Broadcast announcements to a class or message students directly.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success rounded-3"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm" style="background:var(--app-surface);border:1px solid var(--app-border)!important;border-radius:18px;">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4"><i class="bi bi-send-fill text-primary me-2"></i>Compose Message</h6>
                <form method="POST">
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold small d-block">Message Type</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="msg_type" id="type_course" value="course" checked onchange="toggleType()">
                            <label class="btn btn-outline-primary" for="type_course"><i class="bi bi-megaphone-fill me-2"></i>Course Announcement</label>

                            <input type="radio" class="btn-check" name="msg_type" id="type_direct" value="direct" onchange="toggleType()">
                            <label class="btn btn-outline-primary" for="type_direct"><i class="bi bi-person-fill me-2"></i>Direct Message</label>
                        </div>
                    </div>

                    <div class="mb-3" id="course_select_div">
                        <label class="form-label fw-semibold small">Select Course *</label>
                        <select name="course_id" class="form-select">
                            <option value="">— Select course to broadcast to —</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted mt-1 d-block">This will notify all enrolled students.</small>
                    </div>

                    <div class="mb-3 d-none" id="student_select_div">
                        <label class="form-label fw-semibold small">Select Student *</label>
                        <select name="receiver_id" class="form-select">
                            <option value="">— Select student —</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id']; ?>"><?= htmlspecialchars($s['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Title / Subject *</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Midterm Exam Prep" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Message Content *</label>
                        <textarea name="message" class="form-control" rows="5" placeholder="Write your message here..." required></textarea>
                    </div>

                    <div class="text-end">
                        <button type="submit" name="send_message" class="btn btn-primary rounded-pill px-5">
                            <i class="bi bi-send-fill me-2"></i>Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100" style="background:var(--app-surface);border:1px solid var(--app-border)!important;border-radius:18px;">
            <div class="card-header border-bottom-0 pt-4 pb-2 bg-transparent">
                <h6 class="fw-bold mb-0">Recently Sent</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($sentMessages)): ?>
                    <div class="text-center p-5 text-muted">
                        <i class="bi bi-envelope-paper" style="font-size:2.5rem;"></i>
                        <p class="mt-2 mb-0 small">No messages sent yet.</p>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($sentMessages as $sm): 
                            $isDirect = !empty($sm['receiver_name']);
                        ?>
                        <li class="list-group-item p-3 px-4 border-bottom" style="background:transparent;">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-0 fw-bold text-truncate" style="max-width:200px; color: var(--app-text);">
                                    <?= htmlspecialchars($sm['title']); ?>
                                </h6>
                                <small class="text-muted" style="font-size:0.75rem;">
                                    <?= date('M d, H:i', strtotime($sm['created_at'])); ?>
                                </small>
                            </div>
                            <?php if ($isDirect): ?>
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                                    <i class="bi bi-person-fill me-1"></i>To: <?= htmlspecialchars($sm['receiver_name']); ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-primary-subtle text-primary rounded-pill">
                                    <i class="bi bi-megaphone-fill me-1"></i><?= htmlspecialchars($sm['course_name']); ?>
                                </span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleType() {
    var type = document.querySelector('input[name="msg_type"]:checked').value;
    if (type === 'course') {
        document.getElementById('course_select_div').classList.remove('d-none');
        document.getElementById('student_select_div').classList.add('d-none');
    } else {
        document.getElementById('course_select_div').classList.add('d-none');
        document.getElementById('student_select_div').classList.remove('d-none');
    }
}
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
