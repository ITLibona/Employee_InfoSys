<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('positions/index.php'); }
if (!validateCSRF($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request.');
    redirect('positions/index.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { redirect('positions/index.php'); }

$db   = getDB();
$stmt = $db->prepare("SELECT id, title FROM positions WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$pos  = $stmt->fetch();

if (!$pos) {
    setFlash('error', 'Position not found.');
    redirect('positions/index.php');
}

// Prevent deleting if employees hold this position
$empCount = $db->prepare("SELECT COUNT(*) FROM employees WHERE position_id = ?");
$empCount->execute([$id]);
if ($empCount->fetchColumn() > 0) {
    setFlash('error', 'Cannot delete "' . $pos['title'] . '": employees are assigned to this position.');
    redirect('positions/index.php');
}

$db->prepare("DELETE FROM positions WHERE id = ?")->execute([$id]);
setFlash('success', 'Position "' . $pos['title'] . '" deleted.');
redirect('positions/index.php');
