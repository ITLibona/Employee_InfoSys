<?php
require_once __DIR__ . '/config.php';
requirePortalLogin();
$emp = portalEmp();
$db  = getDB();

// Fetch full employee record
$stmtEmp = $db->prepare(
    "SELECT e.*, d.name AS dept_name, p.title AS position_title
     FROM employees e
     LEFT JOIN departments d ON d.id = e.department_id
     LEFT JOIN positions   p ON p.id = e.position_id
     WHERE e.id = ? LIMIT 1"
);
$stmtEmp->execute([$emp['id']]);
$empFull = $stmtEmp->fetch(PDO::FETCH_ASSOC);

$age = '';
if (!empty($empFull['birthdate'])) {
    $age = (new DateTime($empFull['birthdate']))->diff(new DateTime('today'))->y;
}

// Recent leave applications (last 5)
$stmtL = $db->prepare("
    SELECT lr.id, lt.name AS leave_type,
           lr.date_from, lr.date_to, lr.total_days, lr.status, lr.filed_at
    FROM leave_requests lr
    JOIN leave_types lt ON lt.id = lr.leave_type_id
    WHERE lr.employee_id = ?
    ORDER BY lr.filed_at DESC
    LIMIT 5
");
$stmtL->execute([$emp['id']]);
$recentLeaves = $stmtL->fetchAll(PDO::FETCH_ASSOC);

// Recent CTO applications (last 5)
$stmtC = $db->prepare("
    SELECT id, cto_date, hours_requested, status, filed_at
    FROM cto_requests
    WHERE employee_id = ?
    ORDER BY filed_at DESC
    LIMIT 5
");
$stmtC->execute([$emp['id']]);
$recentCtos = $stmtC->fetchAll(PDO::FETCH_ASSOC);

// Leave stats
$stmtLS = $db->prepare("
    SELECT
        SUM(status = 'Pending')  AS leave_pending,
        SUM(status = 'Approved') AS leave_approved
    FROM leave_requests WHERE employee_id = ?
");
$stmtLS->execute([$emp['id']]);
$lstats = $stmtLS->fetch(PDO::FETCH_ASSOC);

// CTO stats
$stmtCS = $db->prepare("
    SELECT
        SUM(status = 'Pending')  AS cto_pending,
        SUM(status = 'Approved') AS cto_approved
    FROM cto_requests WHERE employee_id = ?
");
$stmtCS->execute([$emp['id']]);
$cstats = $stmtCS->fetch(PDO::FETCH_ASSOC);

$title           = 'Dashboard';
$activePortalPage = 'dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h2><i class="fas fa-gauge-high me-2"></i>My Dashboard</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">Employee Portal</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Employee profile card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-0">
        <!-- Green header band -->
        <div class="d-flex align-items-center gap-4 p-4"
             style="background:linear-gradient(135deg,#1a6b4a,#2c8a5e);border-radius:.5rem .5rem 0 0">
            <div class="flex-shrink-0">
                <?php if (!empty($empFull['photo']) && file_exists(UPLOAD_DIR . $empFull['photo'])): ?>
                    <img src="<?= UPLOAD_URL . e($empFull['photo']) ?>"
                         class="rounded-circle border border-3 border-white shadow"
                         style="width:90px;height:90px;object-fit:cover" alt="Photo">
                <?php else: ?>
                    <div class="rounded-circle border border-3 border-white shadow d-flex align-items-center justify-content-center bg-white"
                         style="width:90px;height:90px">
                        <i class="fas fa-user fa-2x" style="color:#1a6b4a"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex-grow-1">
                <h4 class="text-white fw-bold mb-1">
                    <?= e($empFull['first_name'] . ' '
                        . ($empFull['middle_name'] ? $empFull['middle_name'] . ' ' : '')
                        . $empFull['last_name']
                        . ($empFull['suffix'] ? ' ' . $empFull['suffix'] : '')) ?>
                </h4>
                <div class="text-white-50 small">
                    <?= e($empFull['position_title'] ?? '') ?>
                    <?= !empty($empFull['dept_name']) ? ' &mdash; ' . e($empFull['dept_name']) : '' ?>
                </div>
                <div class="mt-2">
                    <?php
                    $smap = ['Active'=>'success','Inactive'=>'secondary','On Leave'=>'warning',
                             'Resigned'=>'danger','Retired'=>'info'];
                    $sc = $smap[$empFull['employment_status'] ?? ''] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $sc ?>"><?= e($empFull['employment_status'] ?? '') ?></span>
                    <span class="badge bg-light text-dark ms-1"><?= e($empFull['employment_type'] ?? '') ?></span>
                </div>
            </div>
            <div class="d-none d-md-flex gap-2 flex-wrap justify-content-end">
                <a href="<?= BASE_URL ?>/portal/leave/apply.php" class="btn btn-light btn-sm">
                    <i class="fas fa-calendar-plus me-1"></i>File Leave
                </a>
                <a href="<?= BASE_URL ?>/portal/cto/apply.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-clock me-1"></i>File CTO
                </a>
            </div>
        </div>

        <!-- Detail rows -->
        <div class="p-4">
            <div class="row g-0">

                <!-- PERSONAL -->
                <div class="col-12 mb-3">
                    <div class="text-uppercase fw-semibold small text-muted mb-2" style="letter-spacing:.05em">
                        <i class="fas fa-user me-1"></i> Personal Information
                    </div>
                    <div class="row g-2">
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Employee ID</div>
                                <div class="fw-semibold small font-monospace"><?= e($empFull['employee_id']) ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Gender</div>
                                <div class="fw-semibold small"><?= e($empFull['gender']) ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Birthdate</div>
                                <div class="fw-semibold small">
                                    <?= $empFull['birthdate'] ? date('M d, Y', strtotime($empFull['birthdate'])) : '—' ?>
                                    <?= $age ? "<span class='text-muted'>({$age} yrs)</span>" : '' ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Civil Status</div>
                                <div class="fw-semibold small"><?= e($empFull['civil_status'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Height</div>
                                <div class="fw-semibold small"><?= e($empFull['height'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Weight</div>
                                <div class="fw-semibold small"><?= e($empFull['weight'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Blood Type</div>
                                <div class="fw-semibold small"><?= e($empFull['blood_type'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Contact Number</div>
                                <div class="fw-semibold small"><?= e($empFull['contact_number'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-6">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Email</div>
                                <div class="fw-semibold small"><?= e($empFull['email'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-6">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Address</div>
                                <div class="fw-semibold small"><?= e($empFull['address'] ?? '—') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12"><hr class="my-2"></div>

                <!-- EMPLOYMENT -->
                <div class="col-12 mb-3">
                    <div class="text-uppercase fw-semibold small text-muted mb-2" style="letter-spacing:.05em">
                        <i class="fas fa-briefcase me-1"></i> Employment Information
                    </div>
                    <div class="row g-2">
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Department</div>
                                <div class="fw-semibold small"><?= e($empFull['dept_name'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Position</div>
                                <div class="fw-semibold small"><?= e($empFull['position_title'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Employment Type</div>
                                <div class="fw-semibold small"><?= e($empFull['employment_type'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Date Hired</div>
                                <div class="fw-semibold small">
                                    <?= !empty($empFull['date_hired']) ? date('M d, Y', strtotime($empFull['date_hired'])) : '—' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GOVERNMENT IDs -->
                <?php
                $govIds = [
                    'TIN Number'     => $empFull['tin_number']        ?? null,
                    'SSS Number'     => $empFull['sss_number']        ?? null,
                    'PhilHealth No.' => $empFull['philhealth_number'] ?? null,
                    'Pag-IBIG No.'   => $empFull['pagibig_number']    ?? null,
                ];
                if (in_array($empFull['employment_type'] ?? '', ['Permanent','Casual','Coterminous'])) {
                    $govIds = array_slice($govIds,0,2,true)
                            + ['GSIS Number' => $empFull['gsis_number'] ?? null]
                            + array_slice($govIds,2,null,true);
                }
                $hasGovId = array_filter($govIds, fn($v) => $v !== null && $v !== '');
                if ($hasGovId): ?>
                <div class="col-12"><hr class="my-2"></div>
                <div class="col-12 mb-3">
                    <div class="text-uppercase fw-semibold small text-muted mb-2" style="letter-spacing:.05em">
                        <i class="fas fa-id-card me-1"></i> Government IDs
                    </div>
                    <div class="row g-2">
                        <?php foreach ($govIds as $label => $value): if ($value): ?>
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem"><?= $label ?></div>
                                <div class="fw-semibold small font-monospace"><?= e($value) ?></div>
                            </div>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- EMERGENCY CONTACT -->
                <div class="col-12"><hr class="my-2"></div>
                <div class="col-12">
                    <div class="text-uppercase fw-semibold small text-muted mb-2" style="letter-spacing:.05em">
                        <i class="fas fa-phone-volume me-1"></i> In Case of Emergency / Contact Person
                    </div>
                    <div class="row g-2">
                        <div class="col-sm-6 col-lg-4">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Contact Person</div>
                                <div class="fw-semibold small"><?= e($empFull['emergency_contact_name'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Cellphone</div>
                                <div class="fw-semibold small"><?= e($empFull['emergency_contact_phone'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="col-sm-12 col-lg-4">
                            <div class="p-2 rounded" style="background:#f8fffe">
                                <div class="text-muted" style="font-size:.72rem">Address</div>
                                <div class="fw-semibold small"><?= e($empFull['emergency_contact_address'] ?? '—') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Mobile quick-action buttons -->
<div class="d-flex d-md-none gap-2 mb-4">
    <a href="<?= BASE_URL ?>/portal/leave/apply.php" class="btn btn-primary btn-sm flex-fill">
        <i class="fas fa-calendar-plus me-1"></i>File Leave
    </a>
    <a href="<?= BASE_URL ?>/portal/cto/apply.php" class="btn btn-outline-primary btn-sm flex-fill">
        <i class="fas fa-clock me-1"></i>File CTO
    </a>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box" style="background:rgba(255,193,7,.15)">
                    <i class="fas fa-hourglass-half text-warning"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= (int)($lstats['leave_pending'] ?? 0) ?></div>
                    <div class="text-muted small">Pending Leave</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box" style="background:rgba(25,135,84,.15)">
                    <i class="fas fa-calendar-check text-success"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= (int)($lstats['leave_approved'] ?? 0) ?></div>
                    <div class="text-muted small">Approved Leave</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box" style="background:rgba(255,193,7,.15)">
                    <i class="fas fa-hourglass-half text-warning"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= (int)($cstats['cto_pending'] ?? 0) ?></div>
                    <div class="text-muted small">Pending CTO</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box" style="background:rgba(25,135,84,.15)">
                    <i class="fas fa-clock text-success"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= (int)($cstats['cto_approved'] ?? 0) ?></div>
                    <div class="text-muted small">Approved CTO</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent applications -->
<div class="row g-4">

    <!-- Leave -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-calendar-minus me-2 text-primary"></i>Recent Leave Applications</span>
                <a href="<?= BASE_URL ?>/portal/leave/history.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if ($recentLeaves): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr>
                            <th>Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($recentLeaves as $r): ?>
                        <tr>
                            <td class="small"><?= e($r['leave_type']) ?></td>
                            <td class="small"><?= date('M d, Y', strtotime($r['date_from'])) ?></td>
                            <td class="small"><?= date('M d, Y', strtotime($r['date_to'])) ?></td>
                            <td class="small"><?= e($r['total_days']) ?></td>
                            <td><?= leaveBadge($r['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-calendar-xmark fa-2x mb-2 d-block opacity-40"></i>
                    No leave applications yet.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- CTO -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-clock me-2 text-primary"></i>Recent CTO Applications</span>
                <a href="<?= BASE_URL ?>/portal/cto/history.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if ($recentCtos): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr>
                            <th>CTO Date</th><th>Hours</th><th>Status</th><th>Filed</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($recentCtos as $r): ?>
                        <tr>
                            <td class="small"><?= date('M d, Y', strtotime($r['cto_date'])) ?></td>
                            <td class="small"><?= e($r['hours_requested']) ?>h</td>
                            <td><?= leaveBadge($r['status']) ?></td>
                            <td class="text-muted small"><?= date('M d, Y', strtotime($r['filed_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-clock-rotate-left fa-2x mb-2 d-block opacity-40"></i>
                    No CTO applications yet.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
