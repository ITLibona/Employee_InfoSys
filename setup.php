<?php
/**
 * setup.php — One-time installation script
 *
 * Creates the database tables and an admin user.
 * Delete or protect this file after first use.
 *
 * SECURITY: This file disables itself automatically once an admin account exists.
 * To re-run setup (e.g. reset admin password), you must temporarily rename or
 * remove the lock by dropping the users table rows in phpMyAdmin.
 */
require_once __DIR__ . '/config/database.php';

define('APP_NAME',    'Municipal Employee Information System');
define('BASE_URL',    '/Employee_InfoSys');

// ---------------------------------------------------------------------------
// Lock: refuse to run if an admin user already exists in the database
// ---------------------------------------------------------------------------
$alreadyInstalled = false;
try {
    $checkPdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $count = (int)$checkPdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count > 0) {
        $alreadyInstalled = true;
    }
} catch (PDOException $ignored) {
    // DB or table doesn't exist yet — allow setup to proceed
}

if ($alreadyInstalled) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head>
        <meta charset="UTF-8">
        <title>Setup Disabled</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    </head><body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
    <div class="text-center p-4">
        <h4 class="text-danger"><i class="bi bi-shield-lock-fill"></i> Setup Disabled</h4>
        <p class="text-muted">The system has already been installed.<br>
        This page is locked to prevent unauthorized re-configuration.</p>
        <a href="' . BASE_URL . '/login.php" class="btn btn-success mt-2">Go to Login</a>
    </div></body></html>';
    exit;
}

$messages = [];
$success  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $adminPwd = $_POST['admin_password'] ?? 'admin123';
    $adminPwd = trim($adminPwd);

    if (strlen($adminPwd) < 6) {
        $messages[] = ['type' => 'danger', 'text' => 'Password must be at least 6 characters.'];
    } else {
        try {
            // Create the database if it doesn't exist
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";charset=utf8mb4",
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`
                        DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `" . DB_NAME . "`");
            $messages[] = ['type' => 'success', 'text' => 'Database "' . DB_NAME . '" ready.'];

            // Read and execute the SQL schema file
            $sqlFile = __DIR__ . '/database/employee_infosys.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                // Strip the CREATE DATABASE / USE lines (already handled above)
                $sql = preg_replace('/^CREATE DATABASE.*?;/ims', '', $sql);
                $sql = preg_replace('/^USE.*?;/ims', '', $sql);
                // Split on semicolons and run each statement
                foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                    if ($stmt !== '') {
                        $pdo->exec($stmt);
                    }
                }
                $messages[] = ['type' => 'success', 'text' => 'Tables and sample data created.'];
            }

            // Create / update admin user with a properly-hashed password
            $hash = password_hash($adminPwd, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, password, full_name, role)
                 VALUES ('admin', ?, 'System Administrator', 'admin')
                 ON DUPLICATE KEY UPDATE password = VALUES(password)"
            );
            $stmt->execute([$hash]);
            $messages[] = ['type' => 'success',
                           'text' => 'Admin account created/updated. Username: <strong>admin</strong>.'];

            // Create the uploads directory
            $uploadDir = __DIR__ . '/uploads/photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $messages[] = ['type' => 'success', 'text' => 'Upload directory created.'];

            $success = true;

        } catch (Exception $e) {
            $messages[] = ['type' => 'danger', 'text' => 'Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup | <?= APP_NAME ?></title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
        .setup-card { max-width:560px; margin:60px auto; border-radius:14px; overflow:hidden; }
        .setup-header { background:linear-gradient(135deg,#1a3a6b,#2c5282); color:#fff; padding:2rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="setup-card card shadow-lg border-0">
        <div class="setup-header text-center">
            <i class="fas fa-building-columns fa-3x mb-2"></i>
            <h4 class="fw-bold mb-1"><?= APP_NAME ?></h4>
            <p class="mb-0 opacity-75">Installation Setup</p>
        </div>
        <div class="card-body p-4">

            <?php foreach ($messages as $msg): ?>
            <div class="alert alert-<?= $msg['type'] ?> py-2 small">
                <i class="fas fa-<?= $msg['type'] === 'success' ? 'circle-check' : 'triangle-exclamation' ?> me-1"></i>
                <?= $msg['text'] ?>
            </div>
            <?php endforeach; ?>

            <?php if ($success): ?>
            <div class="text-center mt-3">
                <p class="text-success fw-semibold">
                    <i class="fas fa-check-circle me-1"></i> Setup complete!
                </p>
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary mt-2">
                    <i class="fas fa-right-to-bracket me-2"></i>Go to Login
                </a>
                <p class="text-danger small mt-3">
                    <i class="fas fa-shield-halved me-1"></i>
                    <strong>Security:</strong> Delete or rename <code>setup.php</code> after setup.
                </p>
            </div>
            <?php else: ?>
            <p class="text-muted small mb-3">
                This script will create the database tables, insert sample data, and create
                an admin user. Run this <strong>only once</strong>.
            </p>

            <div class="alert alert-warning py-2 small">
                <i class="fas fa-triangle-exclamation me-1"></i>
                Make sure MySQL is running and the credentials in
                <code>config/database.php</code> are correct.
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="install">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">
                        Admin Password <span class="text-muted">(min. 6 characters)</span>
                    </label>
                    <input type="password" name="admin_password" class="form-control"
                           placeholder="Set admin password" value="admin123" minlength="6" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="fas fa-wrench me-2"></i>Run Installation
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
