<?php
require_once dirname(__DIR__) . '/config.php';
requirePortalLogin();
$emp = portalEmp();
$db  = getDB();

// Fetch active leave types
$leaveTypes = $db->query(
    "SELECT id, name, max_days FROM leave_types WHERE is_active = 1 ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$data   = [
    'leave_type_id' => '',
    'date_from'     => '',
    'date_to'       => '',
    'total_days'    => '',
    'reason'        => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $data['leave_type_id'] = (int)($_POST['leave_type_id'] ?? 0);
        $data['date_from']     = trim($_POST['date_from'] ?? '');
        $data['date_to']       = trim($_POST['date_to'] ?? '');
        $data['total_days']    = (float)($_POST['total_days'] ?? 0);
        $data['reason']        = trim($_POST['reason'] ?? '');

        if (!$data['leave_type_id'])   $errors[] = 'Please select a leave type.';
        if (!$data['date_from'])       $errors[] = 'Please enter the start date.';
        if (!$data['date_to'])         $errors[] = 'Please enter the end date.';
        if ($data['date_from'] && $data['date_to'] && $data['date_to'] < $data['date_from'])
            $errors[] = 'End date cannot be before start date.';
        if ($data['total_days'] <= 0) $errors[] = 'Number of days must be greater than 0.';

        if (!$errors) {
            // Check for overlap with existing pending/approved requests
            $chk = $db->prepare("
                SELECT COUNT(*) FROM leave_requests
                WHERE employee_id = ?
                  AND status IN ('Pending','Approved')
                  AND date_from <= ? AND date_to >= ?
            ");
            $chk->execute([$emp['id'], $data['date_to'], $data['date_from']]);
            if ($chk->fetchColumn() > 0) {
                $errors[] = 'You have an existing application that overlaps with the selected dates.';
            }
        }

        if (!$errors) {
            $stmt = $db->prepare("
                INSERT INTO leave_requests
                    (employee_id, leave_type_id, date_from, date_to, total_days, reason)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $emp['id'],
                $data['leave_type_id'],
                $data['date_from'],
                $data['date_to'],
                $data['total_days'],
                $data['reason'] ?: null,
            ]);
            setFlash('success', 'Your leave application has been submitted successfully.');
            header('Location: ' . BASE_URL . '/portal/leave/history.php');
            exit;
        }
    }
}

$title            = 'File a Leave';
$activePortalPage = 'leave-apply';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h2><i class="fas fa-calendar-plus me-2"></i>File a Leave Application</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/portal/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/portal/leave/history.php">Leave Applications</a></li>
                <li class="breadcrumb-item active">File Leave</li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger mb-4">
    <i class="fas fa-triangle-exclamation me-2"></i>
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $err): ?>
        <li><?= e($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-file-pen me-2 text-primary"></i>Leave Request Form
            </div>
            <div class="card-body p-4">
                <form method="POST" novalidate>
                    <?= csrfInput() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type_id" class="form-select" required>
                            <option value="">— Select Leave Type —</option>
                            <?php foreach ($leaveTypes as $lt): ?>
                            <option value="<?= $lt['id'] ?>"
                                    data-max="<?= (int)$lt['max_days'] ?>"
                                    <?= (int)$data['leave_type_id'] === (int)$lt['id'] ? 'selected' : '' ?>>
                                <?= e($lt['name']) ?><?= $lt['max_days'] ? ' (max ' . $lt['max_days'] . ' days)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date From <span class="text-danger">*</span></label>
                            <input type="date" name="date_from" id="dateFrom" class="form-control"
                                   value="<?= e($data['date_from']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date To <span class="text-danger">*</span></label>
                            <input type="date" name="date_to" id="dateTo" class="form-control"
                                   value="<?= e($data['date_to']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Number of Days <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="total_days" id="totalDays" class="form-control"
                                   value="<?= e($data['total_days'] ?: '') ?>"
                                   min="0.5" max="365" step="0.5" required placeholder="Auto-calculated">
                            <span class="input-group-text">day(s)</span>
                        </div>
                        <div class="form-text">Automatically calculated from selected dates (weekdays only). Adjust to 0.5 for a half-day.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Reason / Purpose</label>
                        <textarea name="reason" class="form-control" rows="3"
                                  placeholder="Optional — briefly describe reason for this leave..."><?= e($data['reason']) ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i>Submit Application
                        </button>
                        <a href="<?= BASE_URL ?>/portal/leave/history.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = <<<'JS'
(function () {
    const dFrom  = document.getElementById('dateFrom');
    const dTo    = document.getElementById('dateTo');
    const dDays  = document.getElementById('totalDays');

    function countWorkdays(from, to) {
        let count = 0;
        const cur = new Date(from + 'T00:00:00');
        const end = new Date(to   + 'T00:00:00');
        while (cur <= end) {
            const d = cur.getDay();
            if (d !== 0 && d !== 6) count++;
            cur.setDate(cur.getDate() + 1);
        }
        return count;
    }

    function recalc() {
        if (dFrom.value && dTo.value && dTo.value >= dFrom.value) {
            const days = countWorkdays(dFrom.value, dTo.value);
            dDays.value = days > 0 ? days : '';
        }
    }

    dFrom.addEventListener('change', recalc);
    dTo.addEventListener('change', recalc);
})();
JS;
require_once dirname(__DIR__) . '/includes/footer.php';
?>
