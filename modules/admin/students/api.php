<?php
require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/config/validation.php';
require_once BASE_PATH . '/config/api.php';

$request = $_SERVER['REQUEST_METHOD'];
$data = api_input();

if ($request === 'GET') {
    $result = $conn->query("SELECT id, name, email, phone, created_at FROM students ORDER BY id DESC");
    api_success('Students loaded successfully.', $result->fetch_all(MYSQLI_ASSOC));
}

if ($request === 'POST') {
    $errors = validate_student($data);

    if (!empty($errors)) {
        api_error($errors, 422);
    }

    $name = trim($data['name']);
    $email = trim($data['email']);
    $phone = trim($data['phone'] ?? '');

    $stmt = $conn->prepare("INSERT INTO students (name, email, phone) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $phone);

    if (!$stmt->execute()) {
        api_error('Database error: ' . $stmt->error, 500);
    }

    api_success('Student created successfully.', ['id' => $stmt->insert_id], 201);
}

if ($request === 'PUT') {
    $id = (int) ($data['id'] ?? 0);

    if ($id <= 0) {
        api_error('Student id is required.', 422);
    }

    $errors = validate_student($data);

    if (!empty($errors)) {
        api_error($errors, 422);
    }

    $name = trim($data['name']);
    $email = trim($data['email']);
    $phone = trim($data['phone'] ?? '');

    $stmt = $conn->prepare("UPDATE students SET name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $email, $phone, $id);

    if (!$stmt->execute()) {
        api_error('Database error: ' . $stmt->error, 500);
    }

    api_success('Student updated successfully.');
}

if ($request === 'DELETE') {
    $id = (int) ($data['id'] ?? 0);

    if ($id <= 0) {
        api_error('Student id is required.', 422);
    }

    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        api_error('Database error: ' . $stmt->error, 500);
    }

    api_success('Student deleted successfully.');
}

api_error('Method not allowed.', 405);

