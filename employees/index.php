<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();

// ── Filters ───────────────────────────────────────────────────────────────
$search    = trim($_GET['search'] ?? '');
$deptId    = (int)($_GET['dept'] ?? 0);
$statusF   = $_GET['status'] ?? '';
$typeF     = $_GET['type']   ?? '';

$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ? OR e.email LIKE ?)";
    $q = "%{$search}%";
    $params   = array_merge($params, [$q, $q, $q, $q]);
}
if ($deptId > 0) {
    $where[]  = "e.department_id = ?";
    $params[] = $deptId;
}
if ($statusF !== '') {
    $where[]  = "e.employment_status = ?";
    $params[] = $statusF;
}
if ($typeF !== '') {
    $where[]  = "e.employment_type = ?";
    $params[] = $typeF;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql  = "SELECT e.id, e.employee_id, e.first_name, e.last_name, e.gender,
                e.contact_number, e.email, e.photo,
                e.employment_status, e.employment_type, e.date_hired,
                d.name AS dept_name, p.title AS position_title
         FROM employees e
         LEFT JOIN departments d ON d.id = e.department_id
         LEFT JOIN positions   p ON p.id = e.position_id
         {$whereSql}
         ORDER BY e.last_name, e.first_name";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

$departments = $db->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();

$title      = 'Employees';
$activePage = 'employees';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page header -->
<div class="page-header">
    <h2><i class="fas fa-users me-2"></i>Employees
        <span class="badge bg-secondary ms-2 fs-6"><?= count($employees) ?></span>
    </h2>
    <div class="d-flex gap-2 no-print">
        <a href="<?= BASE_URL ?>/employees/qr_codes.php" class="btn btn-outline-primary">
            <i class="fas fa-qrcode me-1"></i> Print QR Codes
        </a>
        <a href="<?= BASE_URL ?>/employees/add.php" class="btn btn-primary">
            <i class="fas fa-user-plus me-1"></i> Add Employee
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm mb-3 no-print">
    <div class="card-body py-3">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-sm-4 col-lg-3">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" id="tableSearch"
                           class="form-control form-control-sm"
                           placeholder="Search name, ID, email…"
                           value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-sm-3 col-lg-2">
                <select name="dept" id="deptFilter" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>"
                            <?= $deptId === (int)$d['id'] ? 'selected' : '' ?>>
                        <?= e($d['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 col-lg-2">
                <select name="status" id="statusFilter" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <?php foreach (['Active','Inactive','On Leave','Resigned','Retired'] as $s): ?>
                    <option value="<?= $s ?>" <?= $statusF === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 col-lg-2">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <?php foreach (['Permanent','Job Order/ Contractual','Casual','Coterminous'] as $t): ?>
                    <option value="<?= $t ?>" <?= $typeF === $t ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <a href="<?= BASE_URL ?>/employees/index.php"
                   class="btn btn-outline-secondary btn-sm ms-1">
                    Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Type</th>
                        <th>Date Hired</th>
                        <th>Status</th>
                        <th class="no-print" style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($employees)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="fas fa-users fa-2x d-block mb-2 opacity-25"></i>
                        No employees found.
                        <a href="<?= BASE_URL ?>/employees/add.php">Add the first one →</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($employees as $i => $emp): ?>
                <tr>
                    <td class="text-muted small align-middle"><?= $i + 1 ?></td>
                    <td class="align-middle">
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($emp['photo'] && file_exists(UPLOAD_DIR . $emp['photo'])): ?>
                                <img src="<?= UPLOAD_URL . e($emp['photo']) ?>"
                                     class="emp-photo-sm" alt="">
                            <?php else: ?>
                                <div class="emp-photo-placeholder-sm">
                                    <i class="fas fa-user fa-xs"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <a href="<?= BASE_URL ?>/employees/view.php?id=<?= $emp['id'] ?>"
                                   class="text-decoration-none fw-semibold text-dark">
                                    <?= e($emp['last_name'] . ', ' . $emp['first_name']) ?>
                                </a>
                                <div class="text-muted" style="font-size:.75rem">
                                    <?= e($emp['employee_id']) ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="small align-middle"
                        data-dept="<?= e($emp['dept_name'] ?? '') ?>">
                        <?= e($emp['dept_name'] ?? '—') ?>
                    </td>
                    <td class="small align-middle"><?= e($emp['position_title'] ?? '—') ?></td>
                    <td class="small align-middle"><?= e($emp['employment_type']) ?></td>
                    <td class="small align-middle"><?= e($emp['date_hired']) ?></td>
                    <td class="align-middle"
                        data-status="<?= e($emp['employment_status']) ?>">
                        <?= statusBadge($emp['employment_status']) ?>
                    </td>
                    <td class="align-middle no-print">
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>/employees/view.php?id=<?= $emp['id'] ?>"
                               class="btn btn-sm btn-outline-secondary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/employees/edit.php?id=<?= $emp['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST"
                                  action="<?= BASE_URL ?>/employees/delete.php"
                                  class="form-delete d-inline">
                                <?= csrfInput() ?>
                                <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
