<?php

function profile_table_for_role(string $role): ?string
{
    return match ($role) {
        'student' => 'students',
        'teacher' => 'teachers',
        default => null,
    };
}

function delete_role_profile(mysqli $conn, string $role, string $email): void
{
    $table = profile_table_for_role($role);

    if ($table === null || $email === '') {
        return;
    }

    $stmt = $conn->prepare("DELETE FROM {$table} WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
}

function upsert_role_profile(mysqli $conn, string $username, string $email, string $role): void
{
    $table = profile_table_for_role($role);

    if ($table === null || $email === '') {
        return;
    }

    $stmt = $conn->prepare("SELECT id FROM {$table} WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();

    if ($profile) {
        $update = $conn->prepare("UPDATE {$table} SET name = ? WHERE id = ?");
        $update->bind_param("si", $username, $profile['id']);
        $update->execute();
        return;
    }

    if ($role === 'teacher') {
        $subject = '';
        $insert = $conn->prepare("INSERT INTO teachers (name, email, subject) VALUES (?, ?, ?)");
        $insert->bind_param("sss", $username, $email, $subject);
        $insert->execute();
        return;
    }

    $phone = '';
    $insert = $conn->prepare("INSERT INTO students (name, email, phone) VALUES (?, ?, ?)");
    $insert->bind_param("sss", $username, $email, $phone);
    $insert->execute();
}

function sync_user_profile(
    mysqli $conn,
    string $username,
    string $email,
    string $role,
    ?string $oldEmail = null,
    ?string $oldRole = null
): void {
    if ($oldEmail !== null && $oldRole !== null && ($oldEmail !== $email || $oldRole !== $role)) {
        delete_role_profile($conn, $oldRole, $oldEmail);
    }

    upsert_role_profile($conn, $username, $email, $role);
}

function sync_all_user_profiles(mysqli $conn): void
{
    $result = $conn->query("SELECT username, email, role FROM users WHERE role IN ('student', 'teacher')");

    while ($user = $result->fetch_assoc()) {
        upsert_role_profile($conn, $user['username'], $user['email'], $user['role']);
    }
}
