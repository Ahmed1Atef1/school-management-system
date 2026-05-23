<?php
require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM class_rooms WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

redirect_to('modules/admin/classrooms/index.php');


