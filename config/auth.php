<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/app.php';

function request_expects_json(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

    return str_contains($accept, 'application/json') || str_ends_with($scriptName, '/api.php');
}

if (empty($_SESSION['username'])) {
    if (request_expects_json()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'errors' => ['Authentication is required.'],
        ]);
        exit;
    }

    redirect_to('modules/admin/users/login.php');
}

function current_user_role(): string
{
    return $_SESSION['role'] ?? '';
}

function current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function user_has_role(string|array $roles): bool
{
    $roles = (array) $roles;

    return in_array(current_user_role(), $roles, true);
}

function require_role(string|array $roles): void
{
    if (!user_has_role($roles)) {
        http_response_code(403);
        require_once BASE_PATH . '/includes/header.php';
        echo "<div class='alert alert-danger'>You do not have permission to access this page.</div>";
        require_once BASE_PATH . '/includes/footer.php';
        exit;
    }
}

