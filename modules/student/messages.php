<?php
require_once '../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';

require_role('student');
$userId = current_user_id();

// Mark messages as read for this user where receiver_id = $userId
$stmtRead = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0");
$stmtRead->bind_param('i', $userId);
$stmtRead->execute();
$stmtRead->close();

// Fetch messages (both direct and course announcements)
// Course announcements: message course_id IN (select course_id from enrollments where user_id = ?)
// Direct messages: receiver_id = ?
$query = "
    SELECT m.id, m.title, m.message, m.created_at, m.receiver_id,
           t.username AS sender_name,
           c.name AS course_name, c.color AS course_color
    FROM messages m
    JOIN users t ON t.id = m.sender_id
    LEFT JOIN courses c ON c.id = m.course_id
    WHERE m.receiver_id = ?
       OR m.course_id IN (SELECT course_id FROM enrollments WHERE user_id = ?)
    ORDER BY m.created_at DESC
";
$stmtMsg = $conn->prepare($query);
$stmtMsg->bind_param('ii', $userId, $userId);
$stmtMsg->execute();
$messages = $stmtMsg->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtMsg->close();

require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-hero mb-4">
    <div class="dashboard-hero-text">
        <h1 class="dashboard-title">Announcements & Messages</h1>
        <p class="text-muted mb-0">View broadcasts from your teachers and direct communications.</p>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($messages)): ?>
        <div class="col-12">
            <div class="dashboard-card py-5 text-center border-0 shadow-sm">
                <div class="position-relative">
                    <i class="bi bi-envelope-open text-muted mb-3" style="font-size: 3rem;"></i>
                    <h4 class="fw-bold">Your inbox is empty</h4>
                    <p class="text-muted">You have no messages or announcements at this time.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-12">
            <div class="row g-4">
                <?php foreach ($messages as $msg): 
                    $isDirect = ($msg['receiver_id'] == $userId);
                    $icon = $isDirect ? 'bi-person-fill' : 'bi-megaphone-fill';
                    $badgeText = $isDirect ? 'Direct Message' : 'Announcement';
                    $badgeColor = $isDirect ? 'info' : ($msg['course_color'] ?: 'primary');
                ?>
                <div class="col-12 col-xl-6">
                    <div class="dashboard-card h-100 border-0 shadow-sm" style="border-left: 4px solid var(--bs-<?= $badgeColor; ?>) !important; padding: 1.5rem;">
                        <div class="position-relative">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-<?= $badgeColor; ?>-subtle text-<?= $badgeColor; ?> d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                        <i class="bi <?= $icon; ?> fs-5"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($msg['sender_name']); ?></h6>
                                        <small class="text-muted"><?= date('M d, Y - h:i A', strtotime($msg['created_at'])); ?></small>
                                    </div>
                                </div>
                                <span class="badge bg-<?= $badgeColor; ?>-subtle text-<?= $badgeColor; ?> rounded-pill px-3">
                                    <?= htmlspecialchars($isDirect ? $badgeText : $msg['course_name']); ?>
                                </span>
                            </div>
                            
                            <h5 class="fw-bold mb-2"><?= htmlspecialchars($msg['title']); ?></h5>
                            <div class="text-muted" style="white-space: pre-line; line-height: 1.6;">
                                <?= htmlspecialchars($msg['message']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
