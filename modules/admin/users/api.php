<?php
require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/config/validation.php';
require_once BASE_PATH . '/config/api.php';
require_once BASE_PATH . '/config/profile_sync.php';

if (!user_has_role('admin')) {
    api_error('You do not have permission to access this API.', 403);
}

$request = $_SERVER['REQUEST_METHOD'];
$data = api_input();

if ($request === 'GET') {
    $result = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY id DESC");
    api_success('Users loaded successfully.', $result->fetch_all(MYSQLI_ASSOC));
}

if ($request === 'POST') {
    $errors = validate_user($data);

    if (!empty($errors)) {
        api_error($errors, 422);
    }

    $username = trim($data['username']);
    $email = trim($data['email']);
    $password = $data['password'];
    $role = $data['role'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();

    if ($stmt->get_result()->fetch_assoc()) {
        api_error('Username or email is already registered.', 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hash, $role);

    if (!$stmt->execute()) {
        api_error('Database error: ' . $stmt->error, 500);
    }

    sync_user_profile($conn, $username, $email, $role);

    api_success('User created successfully.', ['id' => $stmt->insert_id], 201);
}

if ($request === 'PUT') {
    $id = (int) ($data['id'] ?? 0);

    if ($id <= 0) {
        api_error('User id is required.', 422);
    }

    $stmt = $conn->prepare("SELECT email, role FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $existingUser = $stmt->get_result()->fetch_assoc();

    if (!$existingUser) {
        api_error('User not found.', 404);
    }

    $errors = validate_user($data, false);

    if (!empty($errors)) {
        api_error($errors, 422);
    }

    $username = trim($data['username']);
    $email = trim($data['email']);
    $role = $data['role'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? LIMIT 1");
    $stmt->bind_param("ssi", $username, $email, $id);
    $stmt->execute();

    if ($stmt->get_result()->fetch_assoc()) {
        api_error('Username or email is already registered.', 409);
    }

    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $email, $role, $id);

    if (!$stmt->execute()) {
        api_error('Database error: ' . $stmt->error, 500);
    }

    sync_user_profile($conn, $username, $email, $role, $existingUser['email'], $existingUser['role']);

    api_success('User updated successfully.');
}

if ($request === 'DELETE') {
    $id = (int) ($data['id'] ?? 0);

    if ($id <= 0) {
        api_error('User id is required.', 422);
    }

    if (current_user_id() === $id) {
        api_error('You cannot delete your own account.', 422);
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        api_error('Database error: ' . $stmt->error, 500);
    }

    api_success('User deleted successfully.');
}

api_error('Method not allowed.', 405);

