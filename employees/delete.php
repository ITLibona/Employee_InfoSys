<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

// Only accept POST requests with CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('employees/index.php');
}
if (!validateCSRF($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request.');
    redirect('employees/index.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    redirect('employees/index.php');
}

$db   = getDB();
$stmt = $db->prepare("SELECT id, employee_id, first_name, last_name, photo FROM employees WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$emp  = $stmt->fetch();

if (!$emp) {
    setFlash('error', 'Employee not found.');
    redirect('employees/index.php');
}

// Delete photo file if it exists
if ($emp['photo'] && file_exists(UPLOAD_DIR . $emp['photo'])) {
    unlink(UPLOAD_DIR . $emp['photo']);
}

$del = $db->prepare("DELETE FROM employees WHERE id = ?");
$del->execute([$id]);

setFlash('success', 'Employee "' . $emp['first_name'] . ' ' . $emp['last_name'] . '" has been deleted.');
redirect('employees/index.php');
