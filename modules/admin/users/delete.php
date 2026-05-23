<?php
require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/config/profile_sync.php';
require_role('admin');

$id = (int) ($_GET['id'] ?? 0);

if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $id) {
    redirect_to('modules/admin/users/index.php');
}

if ($id > 0) {
    $lookup = $conn->prepare("SELECT email, role FROM users WHERE id = ? LIMIT 1");
    $lookup->bind_param("i", $id);
    $lookup->execute();
    $user = $lookup->get_result()->fetch_assoc();

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute() && $user) {
        delete_role_profile($conn, $user['role'], $user['email']);
    }
}

redirect_to('modules/admin/users/index.php');


