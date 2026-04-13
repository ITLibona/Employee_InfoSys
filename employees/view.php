<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect('employees/index.php');
}

$stmt = $db->prepare(
    "SELECT e.*, d.name AS dept_name, p.title AS position_title
     FROM employees e
     LEFT JOIN departments d ON d.id = e.department_id
     LEFT JOIN positions   p ON p.id = e.position_id
     WHERE e.id = ? LIMIT 1"
);
$stmt->execute([$id]);
$emp = $stmt->fetch();

if (!$emp) {
    setFlash('error', 'Employee not found.');
    redirect('employees/index.php');
}

// Age calculation
$age = '';
if ($emp['birthdate']) {
    $born = new DateTime($emp['birthdate']);
    $age  = $born->diff(new DateTime('today'))->y . ' years old';
}

$qrUrl = employeeDynamicQrUrl((int)$emp['id']);

$title      = e($emp['first_name'] . ' ' . $emp['last_name']);
$activePage = 'employees';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-id-card me-2"></i>Employee Profile</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item">
                    <a href="<?= BASE_URL ?>/employees/index.php">Employees</a>
                </li>
                <li class="breadcrumb-item active"><?= e($emp['last_name'] . ', ' . $emp['first_name']) ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 no-print">
        <a href="<?= BASE_URL ?>/employees/edit.php?id=<?= $emp['id'] ?>"
           class="btn btn-primary btn-sm">
            <i class="fas fa-pen me-1"></i> Edit
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-print me-1"></i> Print
        </button>
        <a href="<?= BASE_URL ?>/employees/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-3">
    <!-- Left: Profile card -->
    <div class="col-md-4 col-lg-3">
        <div class="card shadow-sm text-center">
            <div class="profile-header">
                <?php if ($emp['photo'] && file_exists(UPLOAD_DIR . $emp['photo'])): ?>
                    <img src="<?= UPLOAD_URL . e($emp['photo']) ?>"
                         class="emp-photo mb-2" alt="Photo">
                <?php else: ?>
                    <div class="emp-photo-placeholder mx-auto mb-2">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                <h6 class="fw-bold mb-1">
                    <?= e($emp['first_name'] . ' ' . ($emp['middle_name'] ? $emp['middle_name'] . ' ' : '') . $emp['last_name'] . ($emp['suffix'] ? ' ' . $emp['suffix'] : '')) ?>
                </h6>
                <div class="small opacity-75"><?= e($emp['position_title'] ?? 'N/A') ?></div>
                <div class="mt-2"><?= statusBadge($emp['employment_status']) ?></div>
            </div>
            <div class="card-body p-3">
                <div class="text-muted small mb-1">
                    <i class="fas fa-id-badge me-1"></i> <?= e($emp['employee_id']) ?>
                </div>
                <?php if ($emp['email']): ?>
                <div class="text-muted small mb-1 text-truncate">
                    <i class="fas fa-envelope me-1"></i>
                    <a href="mailto:<?= e($emp['email']) ?>" class="text-muted text-decoration-none">
                        <?= e($emp['email']) ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php if ($emp['contact_number']): ?>
                <div class="text-muted small">
                    <i class="fas fa-phone me-1"></i> <?= e($emp['contact_number']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-body text-center qr-card">
                <p class="form-section-title text-start mt-0">QR Code</p>
                <div id="employeeQrCode" class="qr-code-wrap mx-auto mb-3" aria-label="Employee QR Code"></div>
                <div class="small text-muted mb-3">
                    Scan to open this employee profile.
                </div>
                <button type="button" id="downloadQrBtn" class="btn btn-outline-primary btn-sm w-100 no-print">
                    <i class="fas fa-download me-1"></i> Download QR Code
                </button>
            </div>
        </div>
    </div>

    <!-- Right: Details -->
    <div class="col-md-8 col-lg-9">

        <!-- Personal -->
        <div class="card shadow-sm mb-3">
            <div class="card-header">
                <i class="fas fa-user me-2 text-primary"></i>Personal Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Full Name</div>
                            <?= e($emp['first_name'] . ' ' . $emp['middle_name'] . ' ' . $emp['last_name'] . ($emp['suffix'] ? ', ' . $emp['suffix'] : '')) ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Gender</div>
                            <?= e($emp['gender']) ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Birthdate</div>
                            <?= e($emp['birthdate']) ?>
                            <?php if ($age): ?>
                            <span class="badge bg-light text-dark ms-1"><?= $age ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Civil Status</div>
                            <?= e($emp['civil_status']) ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Contact</div>
                            <?= e($emp['contact_number'] ?? '—') ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Email</div>
                            <?= e($emp['email'] ?? '—') ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Address</div>
                            <?= e($emp['address']) ?>
                        </div>
                    </div>
                    <?php if ($emp['height'] || $emp['weight'] || $emp['blood_type']): ?>
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Height</div>
                            <?= e($emp['height'] ?? '—') ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Weight</div>
                            <?= e($emp['weight'] ?? '—') ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Blood Type</div>
                            <?= e($emp['blood_type'] ?? '—') ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Employment -->
        <div class="card shadow-sm mb-3">
            <div class="card-header">
                <i class="fas fa-briefcase me-2 text-primary"></i>Employment Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Employee ID</div>
                            <code><?= e($emp['employee_id']) ?></code>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Department</div>
                            <?= e($emp['dept_name'] ?? '—') ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Position</div>
                            <?= e($emp['position_title'] ?? '—') ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Employment Type</div>
                            <?= e($emp['employment_type']) ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Status</div>
                            <?= statusBadge($emp['employment_status']) ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Date Hired</div>
                            <?= e($emp['date_hired']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Government IDs -->
        <div class="card shadow-sm">
            <div class="card-header">
                <i class="fas fa-id-card me-2 text-primary"></i>Government IDs
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    $govIds = [
                        'TIN Number'     => $emp['tin_number'],
                        'SSS Number'     => $emp['sss_number'],
                        'PhilHealth No.' => $emp['philhealth_number'],
                        'Pag-IBIG No.'   => $emp['pagibig_number'],
                    ];
                    if (in_array($emp['employment_type'], ['Permanent','Casual','Coterminous'])) {
                        $govIds = array_slice($govIds, 0, 2, true)
                               + ['GSIS Number' => $emp['gsis_number']]
                               + array_slice($govIds, 2, null, true);
                    }
                    foreach ($govIds as $label => $value): ?>
                    <div class="col-sm-6 col-lg-3">
                        <div class="profile-info-row">
                            <div class="profile-info-label"><?= $label ?></div>
                            <?= e($value ?? '—') ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($emp['emergency_contact_name']) || !empty($emp['emergency_contact_phone']) || !empty($emp['emergency_contact_address'])): ?>
        <!-- Emergency Contact -->
        <div class="card shadow-sm mt-3">
            <div class="card-header">
                <i class="fas fa-phone-volume me-2 text-primary"></i>In Case of Emergency / Contact Person
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Contact Person</div>
                            <?= e($emp['emergency_contact_name'] ?? '—') ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Cellphone</div>
                            <?= e($emp['emergency_contact_phone'] ?? '—') ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="profile-info-row">
                            <div class="profile-info-label">Address</div>
                            <?= e($emp['emergency_contact_address'] ?? '—') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /right col -->
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const qrTarget = document.getElementById('employeeQrCode');
    const downloadBtn = document.getElementById('downloadQrBtn');
    const qrText = <?= json_encode($qrUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const fileName = <?= json_encode('employee-qr-' . $emp['employee_id'] . '.png') ?>;

    if (!qrTarget) return;

    qrTarget.innerHTML = '';

    try {
        new QRCode(qrTarget, {
            text: qrText,
            width: 220,
            height: 220,
            colorDark: '#1a6b4a',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
    } catch (e) {
        qrTarget.innerHTML = '<div class="text-danger small">Unable to generate QR code.</div>';
    }

    if (downloadBtn) {
        downloadBtn.addEventListener('click', function () {
            const canvas = qrTarget.querySelector('canvas');
            const img = qrTarget.querySelector('img');
            const link = document.createElement('a');
            link.download = fileName;
            if (canvas) {
                link.href = canvas.toDataURL('image/png');
            } else if (img && img.src) {
                link.href = img.src;
            } else {
                return;
            }
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
