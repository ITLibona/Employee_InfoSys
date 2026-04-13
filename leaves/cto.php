<?php
require_once dirname(__DIR__) . '/config/config.php';
requireLogin();
$db = getDB();

function leaveBadge(string $status): string {
    $map = ['Pending'=>'warning','Approved'=>'success','Rejected'=>'danger','Cancelled'=>'secondary'];
    return '<span class="badge bg-' . ($map[$status] ?? 'secondary') . ' badge-status">' . e($status) . '</span>';
}

// Handle approve / reject POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $id      = (int)($_POST['request_id'] ?? 0);
        $action  = $_POST['action'] ?? '';
        $remarks = trim($_POST['remarks'] ?? '');

        if ($id && in_array($action, ['approve', 'reject'], true)) {
            $newStatus = $action === 'approve' ? 'Approved' : 'Rejected';
            $stmt = $db->prepare("
                UPDATE cto_requests
                SET status = ?, admin_remarks = ?, reviewed_by = ?, reviewed_at = NOW()
                WHERE id = ? AND status = 'Pending'
            ");
            $stmt->execute([$newStatus, $remarks ?: null, $_SESSION['user_id'], $id]);
            if ($stmt->rowCount()) {
                // Sync the employee's employment status
                $empRow = $db->prepare("SELECT employee_id FROM cto_requests WHERE id = ?");
                $empRow->execute([$id]);
                $empId = (int)$empRow->fetchColumn();
                if ($empId) syncEmployeeLeaveStatus($db, $empId);
            }
            setFlash(
                $stmt->rowCount() ? 'success' : 'error',
                $stmt->rowCount() ? "CTO request {$newStatus}." : 'Unable to update — request may already have been processed.'
            );
        } elseif ($id && $action === 'edit') {
            $newStatus  = $_POST['edit_status'] ?? '';
            $ctoDate    = trim($_POST['edit_cto_date'] ?? '');
            $earnedDate = trim($_POST['edit_earned_date'] ?? '');
            $hoursReq   = (float)($_POST['edit_hours_requested'] ?? 0);
            $editReason = trim($_POST['edit_reason'] ?? '');
            $editRmks   = trim($_POST['edit_admin_remarks'] ?? '');
            if (in_array($newStatus, ['Approved','Rejected','Cancelled'], true) && $ctoDate && $hoursReq > 0 && $hoursReq <= 8) {
                $stmt = $db->prepare("
                    UPDATE cto_requests
                    SET status = ?, cto_date = ?, earned_date = ?, hours_requested = ?,
                        reason = ?, admin_remarks = ?, reviewed_by = ?, reviewed_at = NOW()
                    WHERE id = ? AND status = 'Approved'
                ");
                $stmt->execute([$newStatus, $ctoDate, $earnedDate ?: null, $hoursReq,
                                $editReason ?: null, $editRmks ?: null, $_SESSION['user_id'], $id]);
                if ($stmt->rowCount()) {
                    $empRow = $db->prepare("SELECT employee_id FROM cto_requests WHERE id = ?");
                    $empRow->execute([$id]);
                    $empId = (int)$empRow->fetchColumn();
                    if ($empId) syncEmployeeLeaveStatus($db, $empId);
                }
                setFlash(
                    $stmt->rowCount() ? 'success' : 'error',
                    $stmt->rowCount() ? 'CTO request updated.' : 'Unable to update — request may have changed.'
                );
            }
        }
    }
    header('Location: ' . BASE_URL . '/leaves/cto.php?status=' . urlencode($_POST['status_filter'] ?? 'Pending'));
    exit;
}

// Filters
$validStatuses = ['All', 'Pending', 'Approved', 'Rejected', 'Cancelled'];
$statusFilter  = $_GET['status'] ?? 'Pending';
if (!in_array($statusFilter, $validStatuses, true)) $statusFilter = 'Pending';

$sql    = "
    SELECT cr.id,
           CONCAT(e.first_name, ' ', e.last_name) AS emp_name,
           e.employee_id, COALESCE(d.name,'—') AS department,
           cr.cto_date, cr.earned_date, cr.hours_requested, cr.reason,
           cr.status, cr.admin_remarks, cr.filed_at
    FROM cto_requests cr
    JOIN employees e ON e.id = cr.employee_id
    LEFT JOIN departments d ON d.id = e.department_id
";
$params = [];
if ($statusFilter !== 'All') {
    $sql .= " WHERE cr.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY cr.filed_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$counts = $db->query("
    SELECT status, COUNT(*) AS cnt FROM cto_requests GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$title      = 'CTO Requests';
$activePage = 'cto';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2><i class="fas fa-clock me-2"></i>CTO Requests</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">Leave Management</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/leaves/index.php" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-calendar-check me-1"></i>Leave Requests
    </a>
</div>

<!-- Status filter tabs -->
<div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
    <?php foreach ($validStatuses as $s): ?>
    <a href="?status=<?= urlencode($s) ?>"
       class="btn btn-sm <?= $statusFilter === $s ? 'btn-primary' : 'btn-outline-secondary' ?>">
        <?= e($s) ?>
        <?php if ($s !== 'All' && isset($counts[$s])): ?>
        <span class="badge bg-white text-dark ms-1"><?= $counts[$s] ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.875rem">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>CTO Date</th>
                        <th>OT Earned</th>
                        <th>Hours</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Filed</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($requests): ?>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($r['emp_name']) ?></div>
                        <div class="text-muted" style="font-size:.75rem"><?= e($r['employee_id']) ?></div>
                    </td>
                    <td><?= e($r['department']) ?></td>
                    <td><?= date('M d, Y', strtotime($r['cto_date'])) ?></td>
                    <td><?= $r['earned_date'] ? date('M d, Y', strtotime($r['earned_date'])) : '—' ?></td>
                    <td><?= e($r['hours_requested']) ?>h</td>
                    <td class="text-muted" style="max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        <?= $r['reason'] ? e(mb_strimwidth($r['reason'], 0, 40, '…')) : '—' ?>
                    </td>
                    <td><?= leaveBadge($r['status']) ?></td>
                    <td class="text-muted"><?= date('M d, Y', strtotime($r['filed_at'])) ?></td>
                    <td class="text-center">
                        <?php if ($r['status'] === 'Pending'): ?>
                        <button class="btn btn-sm btn-outline-success me-1"
                                onclick="openReview(<?= $r['id'] ?>, 'approve', '<?= e(addslashes($r['emp_name'])) ?>')">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="openReview(<?= $r['id'] ?>, 'reject', '<?= e(addslashes($r['emp_name'])) ?>')">
                            <i class="fas fa-xmark"></i> Reject
                        </button>
                        <?php elseif ($r['status'] === 'Approved'): ?>
                        <button class="btn btn-sm btn-outline-primary"
                                onclick="openEdit(<?= $r['id'] ?>, '<?= e($r['cto_date']) ?>', '<?= $r['earned_date'] ? e($r['earned_date']) : '' ?>', <?= (float)$r['hours_requested'] ?>, <?= json_encode($r['reason'] ?? '') ?>, <?= json_encode($r['admin_remarks'] ?? '') ?>, <?= json_encode($r['emp_name']) ?>)">
                            <i class="fas fa-pen me-1"></i>Edit
                        </button>
                        <?php else: ?>
                        <?php if ($r['admin_remarks']): ?>
                        <span class="text-muted small" title="<?= e($r['admin_remarks']) ?>">
                            <i class="fas fa-comment-dots me-1"></i><?= e(mb_strimwidth($r['admin_remarks'], 0, 30, '…')) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="fas fa-clock-rotate-left fa-2x d-block mb-2 opacity-40"></i>
                        No CTO requests found.
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= csrfInput() ?>
                <input type="hidden" name="request_id"    id="modalRequestId">
                <input type="hidden" name="action"        id="modalAction">
                <input type="hidden" name="status_filter" value="<?= e($statusFilter) ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Review CTO Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="modalBody" class="text-muted small mb-3"></p>
                    <label class="form-label fw-semibold">Admin Remarks <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea name="remarks" class="form-control" rows="3"
                              placeholder="Add a note or reason for your decision..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="modalSubmitBtn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal (Approved CTO requests) -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <?= csrfInput() ?>
                <input type="hidden" name="request_id"    id="editRequestId">
                <input type="hidden" name="action"        value="edit">
                <input type="hidden" name="status_filter" value="<?= e($statusFilter) ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-pen me-2"></i>Edit CTO Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="editEmpName" class="text-muted small mb-3"></p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="edit_status" id="editStatus" class="form-select">
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">CTO Date</label>
                            <input type="date" name="edit_cto_date" id="editCtoDate" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">OT Earned Date <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="date" name="edit_earned_date" id="editEarnedDate" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Hours Requested</label>
                            <input type="number" name="edit_hours_requested" id="editHoursRequested" class="form-control" step="0.5" min="0.5" max="8" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Reason <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea name="edit_reason" id="editReason" class="form-control" rows="2" placeholder="Reason for CTO..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Admin Remarks <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea name="edit_admin_remarks" id="editAdminRemarks" class="form-control" rows="2" placeholder="Notes or remarks..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraJs = <<<'JS'
function openReview(id, action, empName) {
    document.getElementById('modalRequestId').value = id;
    document.getElementById('modalAction').value    = action;

    const isApprove = action === 'approve';
    document.getElementById('modalTitle').textContent =
        isApprove ? 'Approve CTO Request' : 'Reject CTO Request';
    document.getElementById('modalBody').textContent  =
        'Employee: ' + empName + '. ' +
        (isApprove ? 'Confirm approval of this CTO request?' : 'You are rejecting this CTO request. Please add a remark below.');

    const btn = document.getElementById('modalSubmitBtn');
    btn.textContent = isApprove ? 'Approve' : 'Reject';
    btn.className   = 'btn ' + (isApprove ? 'btn-success' : 'btn-danger');

    bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewModal')).show();
}

function openEdit(id, ctoDate, earnedDate, hoursRequested, reason, adminRemarks, empName) {
    document.getElementById('editRequestId').value      = id;
    document.getElementById('editEmpName').textContent  = 'Employee: ' + empName;
    document.getElementById('editCtoDate').value        = ctoDate;
    document.getElementById('editEarnedDate').value     = earnedDate || '';
    document.getElementById('editHoursRequested').value = hoursRequested;
    document.getElementById('editReason').value         = reason;
    document.getElementById('editAdminRemarks').value   = adminRemarks;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
}
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
