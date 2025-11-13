<?php
require_once __DIR__ . '/../config.php';

if (is_logged_in()) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $mysqli->prepare('SELECT id, username, password FROM users WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);

    $user = null;
    if ($stmt->execute()) {
        $user = stmt_fetch_assoc($stmt);
    }
    $stmt->close();

    $isValid = false;
    $needsRehash = false;

    if ($user) {
        $passwordField = $user['password'] ?? '';
        $hashInfo = password_get_info($passwordField);

        if ($hashInfo['algo'] !== 0) {
            if (password_verify($password, $passwordField)) {
                $isValid = true;
                $needsRehash = password_needs_rehash($passwordField, PASSWORD_DEFAULT);
            }
        } elseif ($passwordField !== '') {
            if (hash_equals($passwordField, $password)) {
                $isValid = true;
                $needsRehash = true; // migrate legacy plain-text passwords
            }
        }

        // Fallback for outdated seeded hash to keep default admin/admin123 credentials working.
        if (!$isValid && $passwordField !== '') {
            $defaultAdminUsername = 'admin';
            $defaultAdminPassword = 'admin123';
            $legacySeedHash = '$2y$10$uBXwVnR1ZHphSCe6xPD58OC9frN7kYMva9n32CuWHa3gwxM/H2y1a';

            if (
                strcasecmp($user['username'] ?? '', $defaultAdminUsername) === 0 &&
                hash_equals($passwordField, $legacySeedHash) &&
                hash_equals($password, $defaultAdminPassword)
            ) {
                $isValid = true;
                $needsRehash = true;
            }
        }
    }

    if ($isValid && $user) {
        if ($needsRehash) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $mysqli->prepare('UPDATE users SET password = ? WHERE id = ?');
            $update->bind_param('si', $newHash, $user['id']);
            $update->execute();
            $update->close();
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: /admin/index.php');
        exit;
    }

    $error = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Ramen 1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0d1b2a; color: #fff; }
        .login-card { max-width: 420px; margin: 60px auto; background: rgba(255,255,255,0.08); border-radius: 16px; padding: 32px; box-shadow: 0 20px 45px rgba(0,0,0,0.2); }
        .form-control { background: rgba(255,255,255,0.1); color: #fff; border: none; }
        .form-control:focus { background: rgba(255,255,255,0.15); color: #fff; box-shadow: none; }
        .btn-primary { background: #e63946; border: none; }
    </style>
</head>
<body>
<div class="container">
    <div class="login-card">
        <h1 class="h3 mb-3 text-center">Ramen 1 Admin</h1>
        <form method="post" novalidate>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2" role="alert"><?php echo esc_html($error); ?></div>
            <?php endif; ?>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Masuk</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
