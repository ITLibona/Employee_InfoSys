<?php
require_once __DIR__ . '/config.php';

if (isPortalLoggedIn()) {
    header('Location: ' . BASE_URL . '/portal/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Brute-force check (keyed per submitted username to limit per-account hammering)
        $usernameKey = 'portal_' . preg_replace('/[^a-z0-9]/i', '', strtolower(trim($_POST['username'] ?? 'x')));
        $loginStatus = loginAttemptStatus($usernameKey);
        if ($loginStatus['locked']) {
            $mins  = ceil($loginStatus['seconds_left'] / 60);
            $error = "Too many failed attempts. Try again in {$mins} minute(s).";
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($username === '' || $password === '') {
                $error = 'Please enter your Employee ID and password.';
            } else {
                $db   = getDB();
                $stmt = $db->prepare("
                    SELECT pa.id, pa.password, pa.is_active,
                           e.id AS emp_id, e.employee_id,
                           CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                           COALESCE(d.name, '') AS department,
                           COALESCE(p.title,  '') AS position
                    FROM employee_portal_accounts pa
                    JOIN employees e ON e.id = pa.employee_id
                    LEFT JOIN departments d ON d.id = e.department_id
                    LEFT JOIN positions   p ON p.id = e.position_id
                    WHERE pa.username = ?
                    LIMIT 1
                ");
                $stmt->execute([$username]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row && $row['is_active'] && password_verify($password, $row['password'])) {
                    loginAttemptReset($usernameKey);
                    session_regenerate_id(true);
                    $_SESSION['portal_id']            = $row['id'];
                    $_SESSION['portal_emp_id']        = $row['emp_id'];
                    $_SESSION['portal_employee_id']   = $row['employee_id'];
                    $_SESSION['portal_full_name']     = $row['full_name'];
                    $_SESSION['portal_department']    = $row['department'];
                    $_SESSION['portal_position']      = $row['position'];
                    $_SESSION['portal_last_activity'] = time();
                    header('Location: ' . BASE_URL . '/portal/dashboard.php');
                    exit;
                }
                loginAttemptFail($usernameKey);
                $error = 'Invalid credentials or account is inactive.';
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
    <title>Employee Portal Login | <?= e(MUNICIPALITY) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body {
            background : url('<?= BASE_URL ?>/assets/images/municipal.jpg') center center / cover no-repeat fixed;
            min-height : 100vh;
            display    : flex;
            align-items: center;
            position   : relative;
        }
        body::before {
            content   : '';
            position  : fixed;
            inset     : 0;
            background: linear-gradient(135deg, rgba(26,107,74,.78) 0%, rgba(15,69,48,.65) 100%);
            z-index   : 0;
        }
        .container   { position: relative; z-index: 1; }
        .login-card  { border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,.4); }
        .login-icon  {
            width: 72px; height: 72px;
            background: rgba(63,191,127,.15);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card login-card border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="login-icon">
                        <i class="fas fa-user-clock fa-2x text-primary"></i>
                    </div>
                    <h4 class="text-center fw-bold mb-1" style="color:#1a6b4a">Employee Portal</h4>
                    <p class="text-center text-muted small mb-4"><?= e(MUNICIPALITY) ?></p>

                    <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="fas fa-triangle-exclamation me-1"></i><?= e($error) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <?= csrfInput() ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Employee ID / Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-badge text-muted"></i></span>
                                <input type="text" name="username" class="form-control"
                                       placeholder="e.g. EMP-2022-001"
                                       value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" name="password" class="form-control"
                                       placeholder="Password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            <i class="fas fa-right-to-bracket me-2"></i>Sign In
                        </button>
                    </form>

                    <hr class="my-3">
                    <p class="text-center small text-muted mb-0">
                        <a href="<?= BASE_URL ?>/login.php" class="text-decoration-none">
                            <i class="fas fa-shield-halved me-1"></i>Admin / HR Login
                        </a>
                    </p>
                </div>
            </div>
            <p class="text-center text-white-50 small mt-3">
                Contact the HRMO if you need portal access.
            </p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
