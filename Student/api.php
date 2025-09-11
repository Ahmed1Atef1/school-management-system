<?php

include_once "../Conect.php";

$request = $_SERVER['REQUEST_METHOD'];

if ($request == "GET") {
    $result = $conn->query("SELECT * FROM students");
    $students = $result->fetch_all(MYSQLI_ASSOC);
    print json_encode($students);
}

if ($request == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $name = $data['full_name'] ?? '';
    $email = $data['email'] ?? '';
    $gender = $data['gender'] ?? '';

    $stmt = $conn->prepare("INSERT INTO students (full_name, email, gender) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $gender);

    if ($stmt->execute()) {
        print json_encode(["message" => "student add success"]);
    } else {
        print json_encode(["message" => "error"]);
    }
}

if ($request == "PUT") {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'] ?? null;
    $name = $data['full_name'] ?? '';
    $email = $data['email'] ?? '';
    $gender = $data['gender'] ?? '';

    if ($id) {
        $stmt = $conn->prepare("UPDATE students SET full_name = ?, email = ?, gender = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $gender, $id);
        if ($stmt->execute()) {
            print json_encode(["message" => "student update success"]);
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
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
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