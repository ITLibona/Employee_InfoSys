<?php
require_once __DIR__ . '/database.php';

// ---------------------------------------------------------------------------
// Application constants
// ---------------------------------------------------------------------------
define('APP_NAME',    'Municipal Employee Information System');
define('APP_SHORT',   'MEIS');
define('MUNICIPALITY','Municipality of Libona');

$baseUrlEnv = getenv('BASE_URL');
if ($baseUrlEnv !== false) {
    $resolvedBaseUrl = rtrim($baseUrlEnv, '/');
} else {
    // Auto-detect base path from current script; works for both subfolder and root deployments.
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $resolvedBaseUrl = ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/');
}
define('BASE_URL', $resolvedBaseUrl);
define('APP_URL',     getenv('APP_URL') ?: 'http://192.168.1.66/Employee_InfoSys'); // Production: full domain, Dev: IP/localhost
define('ROOT_PATH',   dirname(__DIR__));
define('UPLOAD_DIR',  ROOT_PATH . '/uploads/photos/');
define('UPLOAD_URL',  BASE_URL . '/uploads/photos/');
define('QR_SECRET',   'meis-libona-qr-secret-v1');

// Session lifetime: 30 minutes of inactivity
define('SESSION_TIMEOUT', 1800);

// Determine if we're in production (HTTPS required)
$isProduction = (getenv('ENVIRONMENT') === 'production') || 
                (getenv('APP_ENV') === 'production') ||
                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

// ---------------------------------------------------------------------------
// Session bootstrap
// ---------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isProduction,   // true only on production HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ---------------------------------------------------------------------------
// Security headers (sent on every page load)
// ---------------------------------------------------------------------------
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;");
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

// ---------------------------------------------------------------------------
// Auth helpers
// ---------------------------------------------------------------------------

/**
 * Brute-force protection — session-based.
 * Returns ['locked' => bool, 'seconds_left' => int, 'attempts' => int].
 */
function loginAttemptStatus(string $key = 'admin'): array {
    $attempts  = (int)($_SESSION["lf_{$key}_attempts"]  ?? 0);
    $lockedAt  = (int)($_SESSION["lf_{$key}_locked_at"] ?? 0);
    $lockSecs  = 15 * 60; // 15-minute lockout
    if ($lockedAt > 0) {
        $left = $lockSecs - (time() - $lockedAt);
        if ($left > 0) {
            return ['locked' => true, 'seconds_left' => $left, 'attempts' => $attempts];
        }
        // Lockout expired — reset
        unset($_SESSION["lf_{$key}_attempts"], $_SESSION["lf_{$key}_locked_at"]);
        $attempts = 0;
    }
    return ['locked' => false, 'seconds_left' => 0, 'attempts' => $attempts];
}

function loginAttemptFail(string $key = 'admin'): void {
    $attempts = (int)($_SESSION["lf_{$key}_attempts"] ?? 0) + 1;
    $_SESSION["lf_{$key}_attempts"] = $attempts;
    if ($attempts >= 5) {
        $_SESSION["lf_{$key}_locked_at"] = time();
    }
}

function loginAttemptReset(string $key = 'admin'): void {
    unset($_SESSION["lf_{$key}_attempts"], $_SESSION["lf_{$key}_locked_at"]);
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id'], $_SESSION['last_activity'])
        && (time() - $_SESSION['last_activity']) < SESSION_TIMEOUT;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function redirect(string $path): void {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

// ---------------------------------------------------------------------------
// Output helpers
// ---------------------------------------------------------------------------

/** Safely escape a value for HTML output */
function e(?string $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Store a one-time flash message in the session */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Retrieve and clear the flash message */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ---------------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------------
function generateCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRF(string $token): bool {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    $valid = hash_equals($_SESSION['csrf_token'], $token);
    if ($valid) {
        // Rotate token after each successful validation to prevent replay
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $valid;
}

function csrfInput(): string {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRF() . '">';
}

// ---------------------------------------------------------------------------
// Misc helpers
// ---------------------------------------------------------------------------

/** Generate the next sequential employee ID: EMP-YYYY-NNN */
function generateEmployeeId(): string {
    $db   = getDB();
    $year = date('Y');
    $stmt = $db->prepare("SELECT COUNT(*) FROM employees WHERE employee_id LIKE ?");
    $stmt->execute(["EMP-{$year}-%"]);
    $count = (int)$stmt->fetchColumn();
    return 'EMP-' . $year . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

/** Return a Bootstrap badge class for an employment status */
function statusBadge(string $status): string {
    $map = [
        'Active'   => 'success',
        'Inactive' => 'secondary',
        'On Leave' => 'warning',
        'Resigned' => 'danger',
        'Retired'  => 'info',
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $class . ' badge-status">' . e($status) . '</span>';
}

function employeeQrPayload(array $employee): string {
    $fullName = trim(
        ($employee['first_name'] ?? '') . ' '
        . (!empty($employee['middle_name']) ? $employee['middle_name'] . ' ' : '')
        . ($employee['last_name'] ?? '')
        . (!empty($employee['suffix']) ? ' ' . $employee['suffix'] : '')
    );

    return implode("\n", array_filter([
        MUNICIPALITY,
        'Employee Information',
        !empty($employee['employee_id']) ? 'Employee ID: ' . $employee['employee_id'] : null,
        $fullName !== '' ? 'Name: ' . $fullName : null,
        !empty($employee['dept_name']) ? 'Department: ' . $employee['dept_name'] : null,
        !empty($employee['position_title']) ? 'Position: ' . $employee['position_title'] : null,
        !empty($employee['employment_status']) ? 'Status: ' . $employee['employment_status'] : null,
        !empty($employee['contact_number']) ? 'Contact: ' . $employee['contact_number'] : null,
        !empty($employee['email']) ? 'Email: ' . $employee['email'] : null,
    ]));
}

function appBaseUrl(): string {
    if (defined('APP_URL') && APP_URL !== '') {
        return rtrim(APP_URL, '/');
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . BASE_URL;
}

function appUrl(string $path = ''): string {
    return rtrim(appBaseUrl(), '/') . '/' . ltrim($path, '/');
}

function base64UrlEncode(string $value): string {
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function base64UrlDecode(string $value): string {
    $raw = strtr($value, '-_', '+/');
    $padding = strlen($raw) % 4;
    if ($padding > 0) {
        $raw .= str_repeat('=', 4 - $padding);
    }
    $decoded = base64_decode($raw, true);
    return $decoded === false ? '' : $decoded;
}

function generateEmployeeQrToken(int $employeeDbId): string {
    $id = (string)$employeeDbId;
    $signature = hash_hmac('sha256', $id, QR_SECRET);
    return base64UrlEncode($id . '|' . $signature);
}

function parseEmployeeQrToken(string $token): ?int {
    $decoded = base64UrlDecode($token);
    if ($decoded === '' || strpos($decoded, '|') === false) {
        return null;
    }

    [$id, $signature] = explode('|', $decoded, 2);
    if (!ctype_digit($id) || $signature === '') {
        return null;
    }

    $expected = hash_hmac('sha256', $id, QR_SECRET);
    if (!hash_equals($expected, $signature)) {
        return null;
    }

    return (int)$id;
}

function employeeDynamicQrUrl(int $employeeDbId): string {
    return appUrl('qr/employee.php?t=' . urlencode(generateEmployeeQrToken($employeeDbId)));
}

/**
 * Sync an employee's employment_status based on active approved leave/CTO records.
 * - Sets status to 'On Leave' if there is at least one Approved leave_request or cto_request.
 * - Reverts to 'Active' if there are none (only when currently 'On Leave').
 */
function syncEmployeeLeaveStatus(PDO $db, int $employeeId): void {
    $hasApproved = $db->prepare("
        SELECT 1 FROM leave_requests
        WHERE employee_id = ? AND status = 'Approved'
        UNION ALL
        SELECT 1 FROM cto_requests
        WHERE employee_id = ? AND status = 'Approved'
        LIMIT 1
    ");
    $hasApproved->execute([$employeeId, $employeeId]);

    if ($hasApproved->fetchColumn() !== false) {
        // At least one approved request → mark On Leave
        $db->prepare("
            UPDATE employees SET employment_status = 'On Leave'
            WHERE id = ? AND employment_status = 'Active'
        ")->execute([$employeeId]);
    } else {
        // No approved requests remain → revert to Active
        $db->prepare("
            UPDATE employees SET employment_status = 'Active'
            WHERE id = ? AND employment_status = 'On Leave'
        ")->execute([$employeeId]);
    }
}
