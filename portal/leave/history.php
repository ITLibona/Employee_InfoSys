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
            UPDATE leave_requests SET status = 'Cancelled'
            WHERE id = ? AND employee_id = ? AND status = 'Pending'
        ");
        $stmt->execute([$id, $emp['id']]);
        setFlash(
            $stmt->rowCount() ? 'success' : 'error',
            $stmt->rowCount() ? 'Leave application cancelled.' : 'Unable to cancel — it may already have been processed.'
        );
    }
    header('Location: ' . BASE_URL . '/portal/leave/history.php');
    exit;
}

$stmt = $db->prepare("
    SELECT lr.id, lt.name AS leave_type,
           lr.date_from, lr.date_to, lr.total_days, lr.reason,
           lr.status, lr.admin_remarks, lr.filed_at
    FROM leave_requests lr
    JOIN leave_types lt ON lt.id = lr.leave_type_id
    WHERE lr.employee_id = ?
    ORDER BY lr.filed_at DESC
");
$stmt->execute([$emp['id']]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title            = 'My Leave Applications';
$activePortalPage = 'leave-history';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h2><i class="fas fa-calendar-minus me-2"></i>My Leave Applications</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/portal/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Leave Applications</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/portal/leave/apply.php" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>File New Leave
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.875rem">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Leave Type</th>
                        <th>Date From</th>
                        <th>Date To</th>
                        <th>Days</th>
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
                    <td><?= e($r['leave_type']) ?></td>
                    <td><?= date('M d, Y', strtotime($r['date_from'])) ?></td>
                    <td><?= date('M d, Y', strtotime($r['date_to'])) ?></td>
                    <td><?= e($r['total_days']) ?></td>
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
                              onsubmit="return confirm('Cancel this leave application?')">
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
                    <td colspan="10" class="text-center py-5 text-muted">
                        <i class="fas fa-calendar-xmark fa-2x d-block mb-2 opacity-40"></i>
                        No leave applications found.
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
