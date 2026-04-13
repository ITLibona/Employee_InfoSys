<?php
require_once dirname(__DIR__) . '/config/config.php';
requireLogin();
$db = getDB();

// Badge helper (mirrors portal/config.php leaveBadge)
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
                UPDATE leave_requests
                SET status = ?, admin_remarks = ?, reviewed_by = ?, reviewed_at = NOW()
                WHERE id = ? AND status = 'Pending'
            ");
            $stmt->execute([$newStatus, $remarks ?: null, $_SESSION['user_id'], $id]);
            if ($stmt->rowCount()) {
                // Sync the employee's employment status
                $empRow = $db->prepare("SELECT employee_id FROM leave_requests WHERE id = ?");
                $empRow->execute([$id]);
                $empId = (int)$empRow->fetchColumn();
                if ($empId) syncEmployeeLeaveStatus($db, $empId);
            }
            setFlash(
                $stmt->rowCount() ? 'success' : 'error',
                $stmt->rowCount() ? "Leave request {$newStatus}." : 'Unable to update — request may already have been processed.'
            );
        } elseif ($id && $action === 'edit') {
            $newStatus  = $_POST['edit_status'] ?? '';
            $typeId     = (int)($_POST['edit_leave_type_id'] ?? 0);
            $dateFrom   = trim($_POST['edit_date_from'] ?? '');
            $dateTo     = trim($_POST['edit_date_to'] ?? '');
            $totalDays  = (float)($_POST['edit_total_days'] ?? 0);
            $editReason = trim($_POST['edit_reason'] ?? '');
            $editRmks   = trim($_POST['edit_admin_remarks'] ?? '');
            if (in_array($newStatus, ['Approved','Rejected','Cancelled'], true) && $typeId && $dateFrom && $dateTo && $totalDays > 0) {
                $stmt = $db->prepare("
                    UPDATE leave_requests
                    SET status = ?, leave_type_id = ?, date_from = ?, date_to = ?,
                        total_days = ?, reason = ?, admin_remarks = ?,
                        reviewed_by = ?, reviewed_at = NOW()
                    WHERE id = ? AND status = 'Approved'
                ");
                $stmt->execute([$newStatus, $typeId, $dateFrom, $dateTo, $totalDays,
                                $editReason ?: null, $editRmks ?: null, $_SESSION['user_id'], $id]);
                if ($stmt->rowCount()) {
                    $empRow = $db->prepare("SELECT employee_id FROM leave_requests WHERE id = ?");
                    $empRow->execute([$id]);
                    $empId = (int)$empRow->fetchColumn();
                    if ($empId) syncEmployeeLeaveStatus($db, $empId);
                }
                setFlash(
                    $stmt->rowCount() ? 'success' : 'error',
                    $stmt->rowCount() ? 'Leave request updated.' : 'Unable to update — request may have changed.'
                );
            }
        }
    }
    header('Location: ' . BASE_URL . '/leaves/index.php?status=' . urlencode($_POST['status_filter'] ?? 'Pending'));
    exit;
}

// Filters
$validStatuses  = ['All', 'Pending', 'Approved', 'Rejected', 'Cancelled'];
$statusFilter   = $_GET['status'] ?? 'Pending';
if (!in_array($statusFilter, $validStatuses, true)) $statusFilter = 'Pending';

$sql    = "
    SELECT lr.id,
           CONCAT(e.first_name, ' ', e.last_name) AS emp_name,
           e.employee_id, COALESCE(d.name,'—') AS department,
           lt.name AS leave_type, lr.leave_type_id,
           lr.date_from, lr.date_to, lr.total_days, lr.reason,
           lr.status, lr.admin_remarks, lr.filed_at
    FROM leave_requests lr
    JOIN employees e ON e.id = lr.employee_id
    LEFT JOIN departments d ON d.id = e.department_id
    JOIN leave_types lt ON lt.id = lr.leave_type_id
";
$params = [];
if ($statusFilter !== 'All') {
    $sql .= " WHERE lr.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY lr.filed_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$leaveTypes = $db->query(
    "SELECT id, name FROM leave_types WHERE is_active = 1 ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

// Counts per status for badge display
$counts = $db->query("
    SELECT status, COUNT(*) AS cnt FROM leave_requests GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$title      = 'Leave Requests';
$activePage = 'leaves';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2><i class="fas fa-calendar-check me-2"></i>Leave Requests</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">Leave Management</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/leaves/cto.php" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-clock me-1"></i>CTO Requests
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
                        <th>Leave Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Days</th>
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
                    <td><?= e($r['leave_type']) ?></td>
                    <td><?= date('M d, Y', strtotime($r['date_from'])) ?></td>
                    <td><?= date('M d, Y', strtotime($r['date_to'])) ?></td>
                    <td><?= e($r['total_days']) ?></td>
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
                                onclick="openEdit(<?= $r['id'] ?>, <?= (int)$r['leave_type_id'] ?>, '<?= e($r['date_from']) ?>', '<?= e($r['date_to']) ?>', <?= (float)$r['total_days'] ?>, <?= json_encode($r['reason'] ?? '') ?>, <?= json_encode($r['admin_remarks'] ?? '') ?>, <?= json_encode($r['emp_name']) ?>)">
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
                        <i class="fas fa-calendar-xmark fa-2x d-block mb-2 opacity-40"></i>
                        No leave requests found.
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
                    <h5 class="modal-title" id="modalTitle">Review Leave Request</h5>
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

<!-- Edit Modal (Approved requests) -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <?= csrfInput() ?>
                <input type="hidden" name="request_id"    id="editRequestId">
                <input type="hidden" name="action"        value="edit">
                <input type="hidden" name="status_filter" value="<?= e($statusFilter) ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-pen me-2"></i>Edit Leave Request</h5>
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
                            <label class="form-label fw-semibold">Leave Type</label>
                            <select name="edit_leave_type_id" id="editLeaveType" class="form-select">
                                <?php foreach ($leaveTypes as $lt): ?>
                                <option value="<?= $lt['id'] ?>"><?= e($lt['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Date From</label>
                            <input type="date" name="edit_date_from" id="editDateFrom" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Date To</label>
                            <input type="date" name="edit_date_to" id="editDateTo" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Total Days</label>
                            <input type="number" name="edit_total_days" id="editTotalDays" class="form-control" step="0.5" min="0.5" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Reason <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea name="edit_reason" id="editReason" class="form-control" rows="2" placeholder="Reason for leave..."></textarea>
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
        isApprove ? 'Approve Leave Request' : 'Reject Leave Request';
    document.getElementById('modalBody').textContent  =
        'Employee: ' + empName + '. ' +
        (isApprove ? 'Confirm approval of this leave request?' : 'You are rejecting this leave request. Please add a remark below.');

    const btn = document.getElementById('modalSubmitBtn');
    btn.textContent = isApprove ? 'Approve' : 'Reject';
    btn.className   = 'btn ' + (isApprove ? 'btn-success' : 'btn-danger');

    bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewModal')).show();
}

function openEdit(id, leaveTypeId, dateFrom, dateTo, totalDays, reason, adminRemarks, empName) {
    document.getElementById('editRequestId').value  = id;
    document.getElementById('editEmpName').textContent = 'Employee: ' + empName;
    document.getElementById('editLeaveType').value  = leaveTypeId;
    document.getElementById('editDateFrom').value   = dateFrom;
    document.getElementById('editDateTo').value     = dateTo;
    document.getElementById('editTotalDays').value  = totalDays;
    document.getElementById('editReason').value     = reason;
    document.getElementById('editAdminRemarks').value = adminRemarks;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
}
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
