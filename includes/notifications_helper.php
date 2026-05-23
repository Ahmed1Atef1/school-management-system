<?php
/**
 * includes/notifications_helper.php
 * 
 * Centralized system for sending and managing user notifications.
 */

/**
 * Send a notification to a specific user.
 *
 * @param mysqli $conn
 * @param int $userId
 * @param string $type e.g., 'grade', 'attendance', 'assignment', 'achievement', 'system'
 * @param string $title Short title
 * @param string $message Detailed message
 * @param string $icon Bootstrap icon class (e.g., 'bi-bell-fill')
 * @param string $color Bootstrap theme color (e.g., 'primary', 'success', 'danger')
 * @param string|null $actionUrl Optional URL for the notification to link to
 * @return bool
 */
function send_notification($conn, $userId, $type, $title, $message, $icon = 'bi-bell', $color = 'primary', $actionUrl = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, icon, color, action_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) return false;
    $stmt->bind_param('issssss', $userId, $type, $title, $message, $icon, $color, $actionUrl);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Get the count of unread notifications for a user.
 */
function get_unread_notification_count($conn, $userId) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE user_id = ? AND is_read = 0");
    if (!$stmt) return 0;
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) ($res['unread'] ?? 0);
}

/**
 * Fetch a list of notifications for a user.
 */
function get_notifications($conn, $userId, $limit = 50) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    if (!$stmt) return [];
    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}

/**
 * Mark all unread notifications as read for a user.
 */
function mark_all_notifications_read($conn, $userId) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    if (!$stmt) return false;
    $stmt->bind_param('i', $userId);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}
