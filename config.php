<?php
// config.php - Database connection and global settings
session_start();

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
        header('Location: /admin/login.php');
        exit;
    }
}

function esc_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
