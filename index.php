<?php
include 'header.php';
?>
<link rel="stylesheet" href="/Version_2/Assets/Style/custom.css">

<div id="carouselExample" class="carousel slide mt-3 mb-5" data-bs-ride="carousel">
  <div class="carousel-inner">
    <div class="carousel-item active">
      <img src="/Version_2/Assets/Image/slide1.jpg" class="d-block" alt="School Campus">
    </div>
    <div class="carousel-item">
      <img src="/Version_2/Assets/Image/slide2.jpg" class="d-block" alt="Library">
    </div>
    <div class="carousel-item">
      <img src="/Version_2/Assets/Image/slide3.jpg" class="d-block" alt="Students in classroom">
    </div>
  </div>

  <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Previous</span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Next</span>
  </button>
</div>

<div class="row text-center mb-5">
    <div class="col-12">
        <h2>Our Modules</h2>
        <p class="lead text-muted">Manage all aspects of the institution from one place.</p>
    </div>
</div>

<div class="row mb-5">
  <div class="col-md-3">
    <a class="card text-decoration-none text-dark" href="/Version_2/Student">
      <img src="/Version_2/Assets/Image/students.jpg" class="card-img-top module-img" alt="Students">
      <div class="card-body">
        <h5 class="card-title">Student Management</h5>
        <p class="card-text text-muted">Add, view, and manage students.</p>
      </div>
    </a>
  </div>
   <div class="col-md-3">
    <a class="card text-decoration-none text-dark" href="/Version_2/Teacher">
      <img src="/Version_2/Assets/Image/teachers.jpg" class="card-img-top module-img" alt="Teachers">
      <div class="card-body">
        <h5 class="card-title">Teacher Management</h5>
        <p class="card-text text-muted">Manage teacher profiles and subjects.</p>
      </div>
    </a>
  </div>
  <div class="col-md-3">
    <a class="card text-decoration-none text-dark" href="/Version_2/Class_Room">
      <img src="/Version_2/Assets/Image/classes.jpg" class="card-img-top module-img" alt="Classes">
      <div class="card-body">
        <h5 class="card-title">Classroom Management</h5>
        <p class="card-text text-muted">Organize classrooms and capacity.</p>
      </div>
    </a>
  </div>
  <div class="col-md-3">
    <a class="card text-decoration-none text-dark" href="/Version_2/User">
      <img src="/Version_2/Assets/Image/users.jpg" class="card-img-top module-img" alt="Users">
      <div class="card-body">
        <h5 class="card-title">User Management</h5>
        <p class="card-text text-muted">Manage accounts and user roles.</p>
      </div>
    </a>
  </div>
</div>

<?php
include 'footer.php';
?>