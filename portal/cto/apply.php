<?php
require_once dirname(__DIR__) . '/config.php';
requirePortalLogin();
$emp = portalEmp();
$db  = getDB();

$errors = [];
$data   = [
    'cto_date'        => '',
    'earned_date'     => '',
    'hours_requested' => '',
    'reason'          => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $data['cto_date']        = trim($_POST['cto_date'] ?? '');
        $data['earned_date']     = trim($_POST['earned_date'] ?? '');
        $data['hours_requested'] = (float)($_POST['hours_requested'] ?? 0);
        $data['reason']          = trim($_POST['reason'] ?? '');

        if (!$data['cto_date'])            $errors[] = 'Please enter the date you will use your CTO.';
        if ($data['hours_requested'] <= 0) $errors[] = 'Hours requested must be greater than 0.';
        if ($data['hours_requested'] > 8)  $errors[] = 'Hours requested cannot exceed 8 hours per filing.';

        if (!$errors) {
            $stmt = $db->prepare("
                INSERT INTO cto_requests
                    (employee_id, cto_date, earned_date, hours_requested, reason)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $emp['id'],
                $data['cto_date'],
                $data['earned_date'] ?: null,
                $data['hours_requested'],
                $data['reason'] ?: null,
            ]);
            setFlash('success', 'Your CTO application has been submitted successfully.');
            header('Location: ' . BASE_URL . '/portal/cto/history.php');
            exit;
        }
    }
}

$title            = 'File a CTO';
$activePortalPage = 'cto-apply';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h2><i class="fas fa-clock me-2"></i>File a Compensatory Time-Off (CTO)</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/portal/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/portal/cto/history.php">CTO Applications</a></li>
                <li class="breadcrumb-item active">File CTO</li>
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

        <div class="card border-0 mb-3" style="background:rgba(13,202,240,.1)">
            <div class="card-body py-2">
                <i class="fas fa-circle-info text-info me-2"></i>
                <small><strong>What is CTO?</strong> Compensatory Time-Off is a privilege granted to government
                employees who rendered overtime work. One hour of overtime = one hour of CTO credit.
                Maximum of 8 hours per filing.</small>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-file-pen me-2 text-primary"></i>CTO Request Form
            </div>
            <div class="card-body p-4">
                <form method="POST" novalidate>
                    <?= csrfInput() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date CTO Will Be Used <span class="text-danger">*</span></label>
                            <input type="date" name="cto_date" class="form-control"
                                   value="<?= e($data['cto_date']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date Overtime Was Earned</label>
                            <input type="date" name="earned_date" class="form-control"
                                   value="<?= e($data['earned_date']) ?>">
                            <div class="form-text">Optional — enter the date the overtime was rendered.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hours Requested <span class="text-danger">*</span></label>
                        <div class="input-group" style="max-width:260px">
                            <input type="number" name="hours_requested" class="form-control"
                                   value="<?= e($data['hours_requested'] ?: '') ?>"
                                   min="0.5" max="8" step="0.5" required placeholder="e.g. 4">
                            <span class="input-group-text">hour(s)</span>
                        </div>
                        <div class="form-text">For a whole-day CTO, enter 8.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Reason / Purpose</label>
                        <textarea name="reason" class="form-control" rows="3"
                                  placeholder="Optional — briefly describe the purpose of your CTO..."><?= e($data['reason']) ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i>Submit CTO Request
                        </button>
                        <a href="<?= BASE_URL ?>/portal/cto/history.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
