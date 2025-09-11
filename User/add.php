<?php

session_start();               
include("../conect.php");       
include("../header.php");       

$username = $email = $role = "";
$errors = array();
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];  
    $role     = $_POST["role"];

    
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    if (empty($role)) {
        $errors[] = "Role is required.";
    } elseif (!in_array($role, array('admin','teacher','student'))) {
        $errors[] = "Invalid role selected.";
    }

    
    if (empty($errors)) {
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Username is already taken.";
        }
        $stmt->close();

        
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Email is already registered.";
            }
            $stmt->close();
        }

        
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);  

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $success = "Registration successful!"; 
                
                $username = $email = $role = "";
            } else {
                $errors[] = "Error: Could not execute query. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>

<div class="container mt-5">
    <h2 class="mb-4">Register New User</h2>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <p>You can now <a href="login.php">Login here</a>.</p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error) {
                echo $error . "<br>";
            } ?>
        </div>
    <?php endif; ?>

    <?php if (empty($success)): ?>
        <form action="add.php" method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username" 
                    value="<?php echo htmlspecialchars($username); ?>" 
                    required
                >
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input 
                    type="email" 
                    class="form-control" 
                    id="email" 
                    name="email" 
                    value="<?php echo htmlspecialchars($email); ?>" 
                    required
                >
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="password" 
                    name="password" 
                    required
                >
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select 
                    id="role" 
                    name="role" 
                    class="form-select" 
                    required
                >
                    <option value="" disabled <?php echo empty($role) ? 'selected' : ''; ?>>Select Role</option>
                    <option value="student" <?php echo ($role == 'student') ? 'selected' : ''; ?>>Student</option>
                    <option value="teacher" <?php echo ($role == 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                    <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
    <?php endif; ?>
</div>

<?php include("../footer.php"); // Include the footer ?>
