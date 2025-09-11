<?php
// header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>School Management</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <link rel="stylesheet" href="/Version_2/Assets/Style/custom.css">
</head>
<body>
<div class="container">
  <nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
      <a class="navbar-brand" href="/Version_2">
        <?php echo isset($_SESSION['userName']) ? htmlspecialchars($_SESSION['userName']) : "School Management"; ?>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
              aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link active" aria-current="page" href="/Version_2">Home</a></li>

          <?php if (isset($_SESSION['userName'])): ?>
            <li class="nav-item"><a class="nav-link" href="/Version_2/Student">Student</a></li>
            <li class="nav-item"><a class="nav-link" href="/Version_2/Teacher">Teacher</a></li>
            <li class="nav-item"><a class="nav-link" href="/Version_2/Class_Room">Class Room</a></li>
          <?php endif; ?>

          <li class="nav-item"><a class="nav-link" href="/Version_2/User">User</a></li>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Auth</a>
            <ul class="dropdown-menu">
              <?php if (!isset($_SESSION['userName'])): ?>
                <li><a class="dropdown-item" href="/Version_2/User/login.php">Login</a></li>
                <li><a class="dropdown-item" href="/Version_2/User/add.php">Registration</a></li>
              <?php else: ?>
                <li><a class="dropdown-item" href="/Version_2/User/logout.php">Logout</a></li>
              <?php endif; ?>
            </ul>
          </li>

          <li class="nav-item"><a class="nav-link disabled" aria-disabled="true">Disabled</a></li>
        </ul>

        <form class="d-flex" role="search" action="/Version_2/User/index.php">
          <input class="form-control me-2" type="search" name="q" placeholder="Search" aria-label="Search"/>
          <button class="btn btn-outline-success" type="submit">Search</button>
        </form>
      </div>
    </div>
  </nav>

  <div class="mt-3"></div>
