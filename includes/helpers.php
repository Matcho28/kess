<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

/**
 * Escapes user-facing output in HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Builds URLs that respect the project folder path (BASE_URL).
 */
function baseUrl(string $path = ''): string
{
    if ($path === '' || $path === '/') {
        return BASE_URL !== '' ? BASE_URL : '/';
    }

    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Redirect helper.
 */
function redirect(string $path): void
{
    header('Location: ' . baseUrl($path));
    exit;
}

/**
 * Returns a JSON response and exits the script.
 */
function jsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}
