<?php
// config.php - Database connection and global settings
session_start();

$documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$projectRoot = rtrim(str_replace('\\', '/', realpath(__DIR__)), '/');
$basePath = '';
if ($documentRoot && str_starts_with($projectRoot, $documentRoot)) {
    $basePath = substr($projectRoot, strlen($documentRoot));
}
$basePath = '/' . ltrim($basePath, '/');
if ($basePath === '/') {
    $basePath = '';
}
define('APP_BASE_PATH', $basePath);

function url_for(string $path = ''): string
{
    $path = ltrim($path, '/');
    $base = APP_BASE_PATH;
    if ($path === '') {
        return $base === '' ? '/' : $base;
    }
    if ($base === '') {
        return '/' . $path;
    }
    return rtrim($base, '/') . '/' . $path;
}

function public_url(?string $path): string
{
    if (!$path) {
        return '';
    }
    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }
    return url_for(ltrim($path, '/'));
}

function asset_url(string $path): string
{
    return url_for('assets/' . ltrim($path, '/'));
}

function absolute_url(string $path = ''): string
{
    $relative = url_for($path);
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return $relative;
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $host . $relative;
}

// Update these variables based on your MySQL configuration
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'ramen1';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_errno) {
    die('Failed to connect to MySQL: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Fetch a single associative row from a prepared statement without relying on mysqlnd.
 */
function stmt_fetch_assoc(mysqli_stmt $stmt): ?array
{
    if (method_exists($stmt, 'get_result')) {
        $result = $stmt->get_result();
        if ($result === false) {
            return null;
        }
        $row = $result->fetch_assoc() ?: null;
        $result->free();
        return $row;
    }

    $stmt->store_result();
    $meta = $stmt->result_metadata();
    if (!$meta) {
        $stmt->free_result();
        return null;
    }

    $fields = [];
    $row = [];
    $bindValues = [];
    while ($field = $meta->fetch_field()) {
        $fields[] = $field->name;
        $row[$field->name] = null;
        $bindValues[] = &$row[$field->name];
    }
    $meta->free();

    if ($bindValues) {
        $stmt->bind_result(...$bindValues);
    }

    $assoc = null;
    if ($stmt->fetch()) {
        $assoc = [];
        foreach ($fields as $field) {
            $assoc[$field] = $row[$field];
        }
    }

    $stmt->free_result();
    return $assoc;
}

/**
 * Fetch all rows from a prepared statement as associative arrays without mysqlnd.
 *
 * @return array<int,array<string,mixed>>
 */
function stmt_fetch_all_assoc(mysqli_stmt $stmt): array
{
    if (method_exists($stmt, 'get_result')) {
        $result = $stmt->get_result();
        if ($result === false) {
            return [];
        }
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        return $rows ?: [];
    }

    $stmt->store_result();
    $meta = $stmt->result_metadata();
    if (!$meta) {
        $stmt->free_result();
        return [];
    }

    $fields = [];
    $row = [];
    $bindValues = [];
    while ($field = $meta->fetch_field()) {
        $fields[] = $field->name;
        $row[$field->name] = null;
        $bindValues[] = &$row[$field->name];
    }
    $meta->free();

    if ($bindValues) {
        $stmt->bind_result(...$bindValues);
    }

    $rows = [];
    while ($stmt->fetch()) {
        $assoc = [];
        foreach ($fields as $field) {
            $assoc[$field] = $row[$field];
        }
        $rows[] = $assoc;
    }

    $stmt->free_result();
    return $rows;
}

/**
 * Fetch all rows from a mysqli_result into an array of associative arrays.
 *
 * @param mysqli_result $result
 * @return array<int,array<string,mixed>>
 */
function result_fetch_all_assoc(mysqli_result $result): array
{
    $rows = [];
    if (!$result) {
        return $rows;
    }
    while ($row = $result->fetch_assoc()) {
        if ($row === null) {
            break;
        }
        $rows[] = $row;
    }
    $result->free();
    return $rows;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . url_for('admin/login.php'));
        exit;
    }
}

function esc_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
