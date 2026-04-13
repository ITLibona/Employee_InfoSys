<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('departments/index.php'); }
if (!validateCSRF($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request.');
    redirect('departments/index.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { redirect('departments/index.php'); }

$db   = getDB();
$stmt = $db->prepare("SELECT id, name FROM departments WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$dept = $stmt->fetch();

if (!$dept) {
    setFlash('error', 'Department not found.');
    redirect('departments/index.php');
}

// Prevent deleting if employees are assigned
$empCount = $db->prepare("SELECT COUNT(*) FROM employees WHERE department_id = ?");
$empCount->execute([$id]);
if ($empCount->fetchColumn() > 0) {
    setFlash('error', 'Cannot delete "' . $dept['name'] . '": it still has employees assigned.');
    redirect('departments/index.php');
}

$db->prepare("DELETE FROM departments WHERE id = ?")->execute([$id]);
setFlash('success', 'Department "' . $dept['name'] . '" deleted.');
redirect('departments/index.php');
