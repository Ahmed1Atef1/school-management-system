<?php
// modules/admin/courses/delete.php
require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';

if (!user_has_role(['admin'])) redirect_to('home.php');

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    // Delete related records first or use ON DELETE CASCADE in schema
    // In this system, deleting a course deletes its assignments and grades if CASCADE is set.
    // We will just execute the delete.
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "Course deleted successfully.";
    }
    $stmt->close();
}

redirect_to('modules/admin/courses/index.php');


