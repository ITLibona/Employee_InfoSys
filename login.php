<?php
require_once __DIR__ . '/config/config.php';

// Already logged in → go to dashboard
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        // Brute-force check
        $loginStatus = loginAttemptStatus('admin');
        if ($loginStatus['locked']) {
            $mins = ceil($loginStatus['seconds_left'] / 60);
            $error = "Too many failed attempts. Try again in {$mins} minute(s).";
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($username === '' || $password === '') {
                $error = 'Please enter both username and password.';
            } else {
                try {
                    $db   = getDB();
                    $stmt = $db->prepare(
                        "SELECT id, username, password, full_name, role
                         FROM users WHERE username = ? LIMIT 1"
                    );
                    $stmt->execute([$username]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password'])) {
                        loginAttemptReset('admin');
                        // Regenerate session ID to prevent fixation
                        session_regenerate_id(true);
                        $_SESSION['user_id']       = $user['id'];
                        $_SESSION['username']      = $user['username'];
                        $_SESSION['full_name']     = $user['full_name'];
                        $_SESSION['role']          = $user['role'];
                        $_SESSION['last_activity'] = time();
                        $redirectTo = $_SESSION['after_login_redirect'] ?? null;
                        unset($_SESSION['after_login_redirect']);

                        if (is_string($redirectTo) && strpos($redirectTo, BASE_URL . '/') === 0) {
                            header('Location: ' . $redirectTo);
                            exit;
                        }

                        redirect('index.php');
                    } else {
                        loginAttemptFail('admin');
                        $remaining = 5 - ((int)($_SESSION['lf_admin_attempts'] ?? 0));
                        $error = $remaining > 0
                            ? "Invalid username or password. {$remaining} attempt(s) remaining."
                            : 'Too many failed attempts. Account locked for 15 minutes.';
                    }
                } catch (PDOException $e) {
                    $error = 'A system error occurred. Please try again later.';
                    error_log($e->getMessage());
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= APP_NAME ?></title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="login-bg">

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-sm-10 col-md-6 col-lg-4">
            <div class="card login-card shadow-lg border-0">
                <!-- Card header -->
                <div class="text-center py-4 px-4"
                     style="background:linear-gradient(135deg,#1a6b4a,#2c8a5e)">
                    <i class="fas fa-building-columns fa-3x text-white mb-2"></i>
                    <h5 class="text-white fw-bold mb-1"><?= APP_NAME ?></h5>
                    <small class="text-white-50"><?= MUNICIPALITY ?></small>
                </div>

                <!-- Card body -->
                <div class="card-body px-4 py-4">
                    <p class="text-center text-muted small mb-4">
                        Sign in with your official credentials
                    </p>

                    <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small mb-3">
                        <i class="fas fa-circle-exclamation me-1"></i>
                        <?= e($error) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="" autocomplete="on" novalidate>
                        <?= csrfInput() ?>

                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold small">
                                Username
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user text-muted"></i>
                                </span>
                                <input type="text" id="username" name="username"
                                       class="form-control"
                                       value="<?= e($_POST['username'] ?? '') ?>"
                                       placeholder="Enter username"
                                       autocomplete="username"
                                       required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold small">
                                Password
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password" id="password" name="password"
                                       class="form-control"
                                       placeholder="Enter password"
                                       autocomplete="current-password"
                                       required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            <i class="fas fa-right-to-bracket me-2"></i>Sign In
                        </button>
                    </form>
                </div>

                <div class="card-footer text-center py-2 bg-transparent border-0">
                    <small class="text-muted">
                        First time?
                        <a href="<?= BASE_URL ?>/setup.php" class="text-decoration-none">
                            Run Setup
                        </a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
