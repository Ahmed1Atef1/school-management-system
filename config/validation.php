<?php

function validate_student(array $data): array
{
    $errors = [];
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email format is invalid.';
    }

    return $errors;
}

function validate_teacher(array $data): array
{
    $errors = [];
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email format is invalid.';
    }

    return $errors;
}

function validate_classroom(array $data): array
{
    $errors = [];
    $name = trim($data['name'] ?? '');
    $capacity = $data['capacity'] ?? '';

    if ($name === '') {
        $errors[] = 'Classroom name or number is required.';
    }

    if ($capacity === '' || filter_var($capacity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
        $errors[] = 'Capacity must be a valid positive number.';
    }

    return $errors;
}

function validate_user(array $data, bool $passwordRequired = true): array
{
    $errors = [];
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? '';

    if ($username === '') {
        $errors[] = 'Username is required.';
    }

    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email format is invalid.';
    }

    if ($passwordRequired && $password === '') {
        $errors[] = 'Password is required.';
    }

    if (!in_array($role, ['admin', 'teacher', 'student'], true)) {
        $errors[] = 'Please choose a valid role.';
    }

    return $errors;
}
