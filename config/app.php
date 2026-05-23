<?php

define('BASE_PATH', dirname(__DIR__));

// Shared app helpers.

function app_base_url(): string
{
    static $baseUrl = null;

    if ($baseUrl !== null) {
        return $baseUrl;
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $projectFolder = basename(BASE_PATH);
    $projectPath = '/' . $projectFolder;

    $position = stripos($scriptName, $projectPath);

    if ($position !== false) {
        $baseUrl = substr($scriptName, 0, $position + strlen($projectPath));
    } else {
        $baseUrl = '';
    }

    return rtrim($baseUrl, '/');
}

function app_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $baseUrl = app_base_url();

    if ($path === '') {
        return $baseUrl === '' ? '/' : $baseUrl . '/';
    }

    return ($baseUrl === '' ? '' : $baseUrl) . '/' . $path;
}

function redirect_to(string $path): void
{
    header('Location: ' . app_url($path));
    exit;
}

/**
 * Returns a human-readable label for a role.
 */
function role_label(string $role = ''): string
{
    return match ($role) {
        'admin'   => 'Administrator',
        'teacher' => 'Teacher',
        'student' => 'Student',
        default   => ucfirst($role ?: 'User'),
    };
}

/**
 * Returns the CSS suffix used for role badge/accent classes.
 * Maps to .role-badge-{suffix} and .sidebar-accent-{suffix} in custom.css.
 */
function role_color(string $role = ''): string
{
    return match ($role) {
        'admin'   => 'admin',
        'teacher' => 'teacher',
        'student' => 'student',
        default   => 'muted',
    };
}

/**
 * Returns a Bootstrap icon class representing a role.
 */
function role_icon(string $role = ''): string
{
    return match ($role) {
        'admin'   => 'bi-shield-fill-check',
        'teacher' => 'bi-person-video3',
        'student' => 'bi-mortarboard-fill',
        default   => 'bi-person-fill',
    };
}
