<?php
require_once __DIR__ . '/config.php';

$keys = [
    'portal_id', 'portal_emp_id', 'portal_employee_id',
    'portal_full_name', 'portal_department', 'portal_position',
    'portal_last_activity',
];
foreach ($keys as $k) {
    unset($_SESSION[$k]);
}

header('Location: ' . BASE_URL . '/portal/login.php');
exit;
