<?php
// User/login.php
require_once "../conect.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Fill username and password.";
    } else {
        $stmt = $conect->prepare("SELECT id,username,password,role FROM users WHERE username=? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $u = $res->fetch_assoc();
            $hash = $u['password'];

            // support hashed passwords; if legacy plain match, upgrade to hash
            if (password_verify($password, $hash) || $password === $hash) {
                if (!password_verify($password, $hash)) {
                    // upgrade plain to hash
                    $newhash = password_hash($password, PASSWORD_DEFAULT);
                    $up = $conect->prepare("UPDATE users SET password=? WHERE id=?");
                    $up->bind_param("si", $newhash, $u['id']);
                    $up->execute();
                }
                // set session and redirect
                $_SESSION['userName'] = $u['username'];
                $_SESSION['userId'] = $u['id'];
                $_SESSION['role'] = $u['role'];
                header("Location: /Version_2/index.php");
                exit;
            } else {
                $error = "Invalid credentials.";
            }
        } else {
            $error = "Invalid credentials.";
        }
    }
}

// show form
include "../header.php";
?>

<div class="container mt-4">
    <h2>Login</h2>
    <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <form method="POST">
        <div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>

<?php include "../footer.php"; ?>
