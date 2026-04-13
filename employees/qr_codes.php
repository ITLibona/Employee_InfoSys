<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$employees = $db->query(
    "SELECT e.id, e.employee_id, e.first_name, e.middle_name, e.last_name, e.suffix,
            e.contact_number, e.email, e.employment_status,
            d.name AS dept_name, p.title AS position_title
     FROM employees e
     LEFT JOIN departments d ON d.id = e.department_id
     LEFT JOIN positions p ON p.id = e.position_id
     ORDER BY e.last_name, e.first_name"
)->fetchAll();

$title = 'Employee QR Codes';
$activePage = 'employee-qr';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2><i class="fas fa-qrcode me-2"></i>Employee QR Codes</h2>
        <div class="text-muted small">
            Generate and print QR codes for all employees.
        </div>
    </div>
    <div class="d-flex gap-2 no-print">
        <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">
            <i class="fas fa-print me-1"></i> Print QR Sheet
        </button>
        <a href="<?= BASE_URL ?>/employees/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Employees
        </a>
    </div>
</div>

<?php if (empty($employees)): ?>
<div class="card shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="fas fa-qrcode fa-2x d-block mb-3 opacity-25"></i>
        No employees found. Add employees first to generate QR codes.
    </div>
</div>
<?php else: ?>
<div class="card shadow-sm mb-3 no-print">
    <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <strong><?= count($employees) ?></strong> employee QR codes ready for printing.
        </div>
        <div class="small text-muted">
            Each QR code opens the latest employee profile via secure link.
        </div>
    </div>
</div>

<div class="qr-grid" id="employeeQrGrid">
    <?php foreach ($employees as $employee): ?>
    <div class="qr-sheet-card">
        <div class="qr-sheet-header">
            <div class="fw-semibold">
                <?= e($employee['last_name'] . ', ' . $employee['first_name']) ?>
            </div>
            <div class="qr-employee-id mt-1"><?= e($employee['employee_id']) ?></div>
        </div>
        <div class="qr-sheet-body">
            <div class="qr-code-wrap employee-qr"
                 data-qr="<?= e(base64_encode(employeeDynamicQrUrl((int)$employee['id']))) ?>"
                 aria-label="QR code for <?= e($employee['employee_id']) ?>"></div>
            <div class="qr-employee-meta text-center">
                <div><strong>Position:</strong> <?= e($employee['position_title'] ?? 'N/A') ?></div>
                <div><strong>Department:</strong> <?= e($employee['dept_name'] ?? 'N/A') ?></div>
                <div><strong>Status:</strong> <?= e($employee['employment_status']) ?></div>
            </div>
            <button type="button"
                    class="btn btn-outline-primary btn-sm no-print qr-download-btn"
                    data-file-name="<?= e('employee-qr-' . $employee['employee_id'] . '.png') ?>">
                <i class="fas fa-download me-1"></i> Download QR Code
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function triggerDownload(url, name) {
        const link = document.createElement('a');
        link.href = url;
        link.download = name;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function decodeQrPayload(value) {
        try {
            return window.atob(value || '');
        } catch (e) {
            return '';
        }
    }

    document.querySelectorAll('.employee-qr').forEach(function (element) {
        const qrText = decodeQrPayload(element.getAttribute('data-qr'));
        if (!qrText) {
            element.innerHTML = '<div class="text-muted small p-2">No QR data.</div>';
            return;
        }

        element.innerHTML = '';

        try {
            new QRCode(element, {
                text: qrText,
                width: 220,
                height: 220,
                colorDark: '#1a6b4a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        } catch (e) {
            element.innerHTML = '<div class="text-danger small p-2">Unable to generate QR code.</div>';
        }
    });

    document.querySelectorAll('.qr-download-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const wrap = button.closest('.qr-sheet-body').querySelector('.employee-qr');
            const fileName = button.getAttribute('data-file-name') || 'employee-qr.png';
            if (!wrap) return;

            const canvas = wrap.querySelector('canvas');
            const img = wrap.querySelector('img');

            if (canvas) {
                triggerDownload(canvas.toDataURL('image/png'), fileName);
            } else if (img && img.src) {
                triggerDownload(img.src, fileName);
            }
        });
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>