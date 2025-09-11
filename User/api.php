<?php

include_once "../Conect.php";

$request = $_SERVER['REQUEST_METHOD'];

if ($request == "GET") {
    $result = $conn->query("SELECT id, username, email, role FROM users");
    $users = $result->fetch_all(MYSQLI_ASSOC);
    print json_encode($users);
}

if ($request == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? '';

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        print json_encode(["message" => "user add success"]);
    } else {
        print json_encode(["message" => "error"]);
    }
}

if ($request == "PUT") {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'] ?? null;
    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $role = $data['role'] ?? '';

    if ($id) {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssi", $username, $email, $role, $id);
        if ($stmt->execute()) {
            print json_encode(["message" => "user update success"]);
        } else {
            print json_encode(["message" => "error"]);
        }
    } else {
        print json_encode(["message" => "error: id is required"]);
    }
}

if ($request == "DELETE") {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'] ?? null;

    if ($id) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            print json_encode(["message" => "delete success"]);
        } else {
            print json_encode(["message" => "error"]);
        }
    } else {
        print json_encode(["message" => "error: id is required"]);
    }
}

$conn->close();
?>