<?php
require_once '../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/includes/notifications_helper.php';

require_role('student');
$userId = current_user_id();

// Handle "Mark All as Read" action
if (isset($_POST['mark_read'])) {
    mark_all_notifications_read($conn, $userId);
    // Redirect to clear POST data
    header('Location: ' . app_url('modules/student/notifications.php'));
    exit;
}

// Handle single "Mark as Read" action (if via AJAX or individual post, but for simplicity we mark all viewed as read)
// We will automatically mark all displayed notifications as read after fetching them
$notifications = get_notifications($conn, $userId, 50);

// Mark as read after we fetched them to display properly
if (count($notifications) > 0) {
    mark_all_notifications_read($conn, $userId);
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-hero mb-4">
    <div class="dashboard-hero-text">
        <h1 class="dashboard-title">Notifications</h1>
        <p class="text-muted mb-0">Stay updated on your grades, assignments, and achievements.</p>
    </div>
</div>

<div class="dashboard-card mb-4" style="padding: 0; overflow: hidden;">
    <div class="d-flex justify-content-between align-items-center border-bottom p-4 bg-transparent">
        <h5 class="mb-0 fw-bold">Recent Activity</h5>
        <?php if (count($notifications) > 0): ?>
            <form method="POST" class="m-0">
                <button type="submit" name="mark_read" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-check2-all me-1"></i> Mark All as Read
                </button>
            </form>
        <?php endif; ?>
    </div>
    <div class="p-0">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5">
                <i class="bi bi-bell-slash text-muted mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-muted">No notifications yet</h5>
                <p class="text-muted">When teachers update your grades or attendance, you'll see it here.</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $n): 
                    $isUnread = !(bool)$n['is_read'];
                    $color = $n['color'] ?: 'primary';
                    $icon = $n['icon'] ?: 'bi-bell-fill';
                ?>
                    <a href="<?= htmlspecialchars($n['action_url'] ?? '#'); ?>" class="list-group-item list-group-item-action p-4 text-body <?= $isUnread ? 'bg-primary bg-opacity-10' : ''; ?>" style="border-left: 4px solid var(--bs-<?= $color; ?>); background: <?= $isUnread ? '' : 'transparent'; ?>;">
                        <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                            <h6 class="mb-0 fw-bold" style="color: var(--app-text);">
                                <i class="bi <?= $icon; ?> text-<?= $color; ?> me-2"></i>
                                <?= htmlspecialchars($n['title']); ?>
                            </h6>
                            <small class="text-muted d-flex align-items-center">
                                <i class="bi bi-clock me-1"></i> 
                                <?php 
                                    $time = strtotime($n['created_at']);
                                    $diff = time() - $time;
                                    if ($diff < 60) echo "Just now";
                                    elseif ($diff < 3600) echo floor($diff/60) . " mins ago";
                                    elseif ($diff < 86400) echo floor($diff/3600) . " hours ago";
                                    else echo floor($diff/86400) . " days ago";
                                ?>
                            </small>
                        </div>
                        <p class="mb-0 text-muted ms-4 ps-2">
                            <?= htmlspecialchars($n['message']); ?>
                        </p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
