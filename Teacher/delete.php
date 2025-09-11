<?php
// Teacher/delete.php
require_once "../conect.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['userName'])) {
    header("Location: /Version_2/User/login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $conect->prepare("DELETE FROM teachers WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
header("Location: index.php");
exit;
?>