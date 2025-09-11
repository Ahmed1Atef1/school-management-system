<?php
// User/delete.php
require_once "../conect.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['userName'])) {
    header("Location: /Version_2/User/login.php");
    exit;
}
$id = intval($_GET['id'] ?? 0);

// optionally prevent deleting your own account:
if (isset($_SESSION['userId']) && $_SESSION['userId'] == $id) {
    header("Location: index.php");
    exit;
}

$stmt = $conect->prepare("DELETE FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
header("Location: index.php");
exit;
