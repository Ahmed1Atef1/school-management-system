<?php
require_once "conect.php";
session_start();
if (empty($_SESSION['username'])) {
    header("Location: User/login.php");
    exit;
}
include "header.php";
?>

<div class="text-center">
  <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
  <p class="lead text-muted">Use the navigation to access modules.</p>
</div>

<?php include "footer.php"; ?>
