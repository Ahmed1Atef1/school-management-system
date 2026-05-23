<?php

function api_input(): array
{
    $input = json_decode(file_get_contents('php://input'), true);

    return is_array($input) ? $input : [];
}

function api_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function api_success(string $message, array $data = [], int $statusCode = 200): void
{
    api_response([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ], $statusCode);
}

function api_error(string|array $message, int $statusCode = 400): void
{
    api_response([
        'success' => false,
        'errors' => (array) $message,
    ], $statusCode);
}
