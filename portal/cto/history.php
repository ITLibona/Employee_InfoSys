<?php
require_once dirname(__DIR__) . '/config.php';
requirePortalLogin();
$emp = portalEmp();
$db  = getDB();

// Handle cancel action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $id   = (int)($_POST['request_id'] ?? 0);
        $stmt = $db->prepare("
            UPDATE cto_requests SET status = 'Cancelled'
            WHERE id = ? AND employee_id = ? AND status = 'Pending'
        ");
        $stmt->execute([$id, $emp['id']]);
        setFlash(
            $stmt->rowCount() ? 'success' : 'error',
            $stmt->rowCount() ? 'CTO application cancelled.' : 'Unable to cancel — it may already have been processed.'
        );
    }
    header('Location: ' . BASE_URL . '/portal/cto/history.php');
    exit;
}

$stmt = $db->prepare("
    SELECT id, cto_date, earned_date, hours_requested, reason,
           status, admin_remarks, filed_at
    FROM cto_requests
    WHERE employee_id = ?
    ORDER BY filed_at DESC
");
$stmt->execute([$emp['id']]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title            = 'My CTO Applications';
$activePortalPage = 'cto-history';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h2><i class="fas fa-clock me-2"></i>My CTO Applications</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/portal/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">CTO Applications</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/portal/cto/apply.php" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>File New CTO
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.875rem">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>CTO Date</th>
                        <th>Overtime Earned</th>
                        <th>Hours</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Admin Remarks</th>
                        <th>Filed</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($requests): ?>
                <?php foreach ($requests as $i => $r): ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td><?= date('M d, Y', strtotime($r['cto_date'])) ?></td>
                    <td><?= $r['earned_date'] ? date('M d, Y', strtotime($r['earned_date'])) : '—' ?></td>
                    <td><?= e($r['hours_requested']) ?>h</td>
                    <td class="text-muted" style="max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        <?= $r['reason'] ? e(mb_strimwidth($r['reason'], 0, 50, '…')) : '—' ?>
                    </td>
                    <td><?= leaveBadge($r['status']) ?></td>
                    <td class="text-muted" style="max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        <?= $r['admin_remarks'] ? e(mb_strimwidth($r['admin_remarks'], 0, 50, '…')) : '—' ?>
                    </td>
                    <td class="text-muted"><?= date('M d, Y', strtotime($r['filed_at'])) ?></td>
                    <td>
                        <?php if ($r['status'] === 'Pending'): ?>
                        <form method="POST" class="d-inline"
                              onsubmit="return confirm('Cancel this CTO application?')">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action"     value="cancel">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm" type="submit" title="Cancel">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="fas fa-clock-rotate-left fa-2x d-block mb-2 opacity-40"></i>
                        No CTO applications found.
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
