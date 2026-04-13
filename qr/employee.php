<?php
require_once __DIR__ . '/../config/config.php';

$token      = trim($_GET['t'] ?? '');
$employeeId = parseEmployeeQrToken($token);

if (!$employeeId) {
    http_response_code(400);
    ?><!DOCTYPE html>
    <html lang="en"><head><meta charset="UTF-8"><title>Invalid QR</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    </head><body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
    <div class="text-center"><h4 class="text-danger">Invalid QR Code</h4>
    <p class="text-muted">This QR code is not valid.</p></div></body></html>
    <?php exit;
}

$db   = getDB();
$stmt = $db->prepare(
    "SELECT e.employee_id, e.first_name, e.middle_name, e.last_name, e.suffix,
            e.contact_number, e.email, e.employment_status, e.employment_type,
            e.date_hired, e.photo,
            d.name AS dept_name, p.title AS position_title
     FROM employees e
     LEFT JOIN departments d ON d.id = e.department_id
     LEFT JOIN positions   p ON p.id = e.position_id
     WHERE e.id = ? LIMIT 1"
);
$stmt->execute([$employeeId]);
$emp = $stmt->fetch();

if (!$emp) {
    http_response_code(404);
    ?><!DOCTYPE html>
    <html lang="en"><head><meta charset="UTF-8"><title>Not Found</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    </head><body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
    <div class="text-center"><h4>Employee Not Found</h4>
    <p class="text-muted">This record may have been removed.</p></div></body></html>
    <?php exit;
}

$fullName = trim(
    $emp['first_name'] . ' '
    . (!empty($emp['middle_name']) ? $emp['middle_name'] . ' ' : '')
    . $emp['last_name']
    . (!empty($emp['suffix']) ? ' ' . $emp['suffix'] : '')
);

$statusColors = [
    'Active'   => ['bg' => '#dcfce7', 'txt' => '#166534'],
    'Inactive' => ['bg' => '#f3f4f6', 'txt' => '#374151'],
    'On Leave' => ['bg' => '#fef9c3', 'txt' => '#854d0e'],
    'Resigned' => ['bg' => '#fee2e2', 'txt' => '#991b1b'],
    'Retired'  => ['bg' => '#e0f2fe', 'txt' => '#0c4a6e'],
];
$sc = $statusColors[$emp['employment_status']] ?? ['bg' => '#f3f4f6', 'txt' => '#374151'];

$photoSrc = '';
if (!empty($emp['photo']) && file_exists(UPLOAD_DIR . $emp['photo'])) {
    $photoSrc = UPLOAD_URL . $emp['photo'];
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($fullName) ?> — <?= MUNICIPALITY ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f0f4f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; padding: 1.5rem; }
        .badge-card { max-width: 420px; width: 100%; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 8px 32px rgba(26,107,74,.18); }
        .badge-header { background: linear-gradient(135deg, #1a6b4a 0%, #2c8a5e 100%); padding: 2rem 1.5rem 1.5rem; text-align: center; color: #fff; }
        .badge-header .photo-wrap { width: 96px; height: 96px; border-radius: 50%; border: 4px solid rgba(255,255,255,.7); overflow: hidden; margin: 0 auto 1rem; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; }
        .badge-header .photo-wrap img { width: 100%; height: 100%; object-fit: cover; }
        .badge-header .photo-wrap i { font-size: 2.4rem; color: rgba(255,255,255,.8); }
        .badge-header h4 { font-weight: 700; margin: 0 0 .25rem; font-size: 1.2rem; }
        .badge-header .position { opacity: .85; font-size: .92rem; }
        .badge-body { padding: 1.5rem; }
        .info-row { display: flex; align-items: flex-start; gap: .75rem; padding: .65rem 0; border-bottom: 1px solid #f0f0f0; font-size: .92rem; }
        .info-row:last-child { border-bottom: none; }
        .info-row .icon { width: 28px; text-align: center; color: #1a6b4a; flex-shrink: 0; margin-top: 1px; }
        .info-row .label { color: #888; font-size: .75rem; text-transform: uppercase; letter-spacing: .4px; display: block; }
        .status-pill { display: inline-block; padding: .3em .9em; border-radius: 20px; font-size: .82rem; font-weight: 600; background: <?= e($sc['bg']) ?>; color: <?= e($sc['txt']) ?>; }
        .badge-footer { text-align: center; padding: .85rem 1.5rem 1.2rem; background: #f8faf9; font-size: .75rem; color: #888; border-top: 1px solid #eee; }
        .emp-id-code { font-family: Consolas, monospace; font-size: .82rem; letter-spacing: .5px; color: #1a6b4a; font-weight: 600; }
    </style>
</head>
<body>
<div class="badge-card">
    <div class="badge-header">
        <div class="photo-wrap">
            <?php if ($photoSrc): ?>
                <img src="<?= e($photoSrc) ?>" alt="<?= e($fullName) ?>">
            <?php else: ?>
                <i class="fas fa-user"></i>
            <?php endif; ?>
        </div>
        <h4><?= e($fullName) ?></h4>
        <div class="position"><?= e($emp['position_title'] ?? 'N/A') ?></div>
    </div>

    <div class="badge-body">
        <div class="info-row">
            <div class="icon"><i class="fas fa-id-badge"></i></div>
            <div>
                <span class="label">Employee ID</span>
                <span class="emp-id-code"><?= e($emp['employee_id']) ?></span>
            </div>
        </div>
        <div class="info-row">
            <div class="icon"><i class="fas fa-sitemap"></i></div>
            <div>
                <span class="label">Department</span>
                <?= e($emp['dept_name'] ?? 'N/A') ?>
            </div>
        </div>
        <div class="info-row">
            <div class="icon"><i class="fas fa-briefcase"></i></div>
            <div>
                <span class="label">Employment Type</span>
                <?= e($emp['employment_type']) ?>
            </div>
        </div>
        <div class="info-row">
            <div class="icon"><i class="fas fa-circle-dot"></i></div>
            <div>
                <span class="label">Status</span>
                <span class="status-pill"><?= e($emp['employment_status']) ?></span>
            </div>
        </div>
        <?php if ($emp['contact_number']): ?>
        <div class="info-row">
            <div class="icon"><i class="fas fa-phone"></i></div>
            <div>
                <span class="label">Contact</span>
                <?= e($emp['contact_number']) ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($emp['email']): ?>
        <div class="info-row">
            <div class="icon"><i class="fas fa-envelope"></i></div>
            <div>
                <span class="label">Email</span>
                <a href="mailto:<?= e($emp['email']) ?>" style="color:#1a6b4a;text-decoration:none"><?= e($emp['email']) ?></a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="badge-footer">
        <i class="fas fa-landmark me-1"></i>
        <strong><?= MUNICIPALITY ?></strong><br>
        Employee Information System
    </div>
</div>
</body>
</html>
