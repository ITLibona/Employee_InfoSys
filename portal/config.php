<?php
/**
 * portal/config.php — Employee Portal helpers
 * Pulls in the core app config (DB, session, CSRF, flash, e()).
 */
require_once dirname(__DIR__) . '/config/config.php';

// ---------------------------------------------------------------------------
// Portal auth helpers
// ---------------------------------------------------------------------------
function isPortalLoggedIn(): bool {
    return isset($_SESSION['portal_id'], $_SESSION['portal_last_activity'])
        && (time() - $_SESSION['portal_last_activity']) < SESSION_TIMEOUT;
}

function requirePortalLogin(): void {
    if (!isPortalLoggedIn()) {
        header('Location: ' . BASE_URL . '/portal/login.php');
        exit;
    }
    $_SESSION['portal_last_activity'] = time();
}

/** Shorthand accessor for the logged-in portal employee's session data */
function portalEmp(): array {
    return [
        'id'          => (int)($_SESSION['portal_emp_id']   ?? 0),
        'employee_id' => $_SESSION['portal_employee_id']    ?? '',
        'full_name'   => $_SESSION['portal_full_name']      ?? '',
        'department'  => $_SESSION['portal_department']     ?? '',
        'position'    => $_SESSION['portal_position']       ?? '',
    ];
}

/** Bootstrap badge for leave / CTO request status */
function leaveBadge(string $status): string {
    $map = [
        'Pending'   => 'warning',
        'Approved'  => 'success',
        'Rejected'  => 'danger',
        'Cancelled' => 'secondary',
    ];
    return '<span class="badge bg-' . ($map[$status] ?? 'secondary') . ' badge-status">'
         . e($status) . '</span>';
}
