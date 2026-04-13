<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$db = getDB();

// ── Stats ─────────────────────────────────────────────────────────────────
$totalEmployees   = $db->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$activeEmployees  = $db->query("SELECT COUNT(*) FROM employees WHERE employment_status = 'Active'")->fetchColumn();
$totalDepts       = $db->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$totalPositions   = $db->query("SELECT COUNT(*) FROM positions")->fetchColumn();
$newThisMonth     = $db->query("SELECT COUNT(*) FROM employees
                                WHERE YEAR(date_hired)=YEAR(CURDATE())
                                  AND MONTH(date_hired)=MONTH(CURDATE())")->fetchColumn();

// ── Employment type breakdown ─────────────────────────────────────────────
$typeRows = $db->query("SELECT employment_type, COUNT(*) AS cnt
                         FROM employees GROUP BY employment_type")->fetchAll();
$typeLabels = $typeCounts = [];
foreach ($typeRows as $r) {
    $typeLabels[] = $r['employment_type'];
    $typeCounts[] = (int)$r['cnt'];
}

// ── Status breakdown ─────────────────────────────────────────────────────
$statusRows = $db->query("SELECT employment_status, COUNT(*) AS cnt
                           FROM employees GROUP BY employment_status")->fetchAll();

// ── Employees per department ──────────────────────────────────────────────
$deptRows = $db->query("SELECT d.name, COUNT(e.id) AS cnt
                         FROM departments d
                         LEFT JOIN employees e ON e.department_id = d.id
                         GROUP BY d.id, d.name
                         ORDER BY cnt DESC
                         LIMIT 10")->fetchAll();
$deptLabels = $deptCounts = [];
foreach ($deptRows as $r) {
    $deptLabels[] = $r['name'];
    $deptCounts[] = (int)$r['cnt'];
}

// ── Recent employees ──────────────────────────────────────────────────────
$recentEmployees = $db->query("SELECT e.id, e.employee_id, e.first_name, e.last_name,
                                      e.photo, e.employment_status, e.date_hired,
                                      d.name AS dept_name, p.title AS position_title
                               FROM employees e
                               LEFT JOIN departments d ON d.id = e.department_id
                               LEFT JOIN positions   p ON p.id = e.position_id
                               ORDER BY e.created_at DESC
                               LIMIT 8")->fetchAll();

$title      = 'Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page header -->
<div class="page-header">
    <h2><i class="fas fa-gauge-high me-2"></i>Dashboard</h2>
    <a href="<?= BASE_URL ?>/employees/add.php" class="btn btn-primary btn-sm no-print">
        <i class="fas fa-user-plus me-1"></i> Add Employee
    </a>
</div>

<!-- ── Stat cards ───────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">

    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box" style="background:#e8f0fe">
                    <i class="fas fa-users" style="color:#1a3a6b"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold lh-1"><?= number_format($totalEmployees) ?></div>
                    <div class="text-muted small">Total Employees</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box" style="background:#d1fae5">
                    <i class="fas fa-user-check" style="color:#065f46"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold lh-1"><?= number_format($activeEmployees) ?></div>
                    <div class="text-muted small">Active</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box" style="background:#fef3c7">
                    <i class="fas fa-sitemap" style="color:#92400e"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold lh-1"><?= number_format($totalDepts) ?></div>
                    <div class="text-muted small">Departments</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon-box" style="background:#fde8e8">
                    <i class="fas fa-user-plus" style="color:#9b1c1c"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold lh-1"><?= number_format($newThisMonth) ?></div>
                    <div class="text-muted small">Hired This Month</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Charts row ───────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">

    <!-- Employment type pie -->
    <div class="col-md-5">
        <div class="card h-100 shadow-sm">
            <div class="card-header">
                <i class="fas fa-chart-pie me-2 text-primary"></i>Employment Type
            </div>
            <div class="card-body d-flex justify-content-center align-items-center">
                <canvas id="typeChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Employees per department bar -->
    <div class="col-md-7">
        <div class="card h-100 shadow-sm">
            <div class="card-header">
                <i class="fas fa-chart-bar me-2 text-primary"></i>Employees per Department
            </div>
            <div class="card-body">
                <canvas id="deptChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ── Status summary + recent employees ────────────────────────────────── -->
<div class="row g-3">

    <!-- Status breakdown -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <i class="fas fa-list-check me-2 text-primary"></i>Status Breakdown
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($statusRows as $row): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= e($row['employment_status']) ?>
                        <span class="badge bg-primary rounded-pill"><?= $row['cnt'] ?></span>
                    </li>
                    <?php endforeach; ?>
                    <?php if (empty($statusRows)): ?>
                    <li class="list-group-item text-muted small text-center py-3">No data yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Recent employees -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-clock-rotate-left me-2 text-primary"></i>Recently Added</span>
                <a href="<?= BASE_URL ?>/employees/index.php" class="btn btn-sm btn-outline-primary no-print">
                    View All
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Date Hired</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentEmployees as $emp): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($emp['photo'] && file_exists(UPLOAD_DIR . $emp['photo'])): ?>
                                        <img src="<?= BASE_URL ?>/uploads/photos/<?= e($emp['photo']) ?>"
                                             class="emp-photo-sm" alt="">
                                    <?php else: ?>
                                        <div class="emp-photo-placeholder-sm">
                                            <i class="fas fa-user fa-xs"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <a href="<?= BASE_URL ?>/employees/view.php?id=<?= $emp['id'] ?>"
                                           class="text-decoration-none fw-semibold text-dark small">
                                            <?= e($emp['last_name'] . ', ' . $emp['first_name']) ?>
                                        </a>
                                        <div class="text-muted" style="font-size:.75rem">
                                            <?= e($emp['employee_id']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="small align-middle"><?= e($emp['dept_name'] ?? '—') ?></td>
                            <td class="small align-middle"><?= e($emp['date_hired']) ?></td>
                            <td class="align-middle"><?= statusBadge($emp['employment_status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentEmployees)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3 small">
                                No employees yet.
                                <a href="<?= BASE_URL ?>/employees/add.php">Add one →</a>
                            </td>
                        </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = "
const typeLabels = " . json_encode($typeLabels) . ";
const typeCounts = " . json_encode($typeCounts) . ";
const deptLabels = " . json_encode($deptLabels) . ";
const deptCounts = " . json_encode($deptCounts) . ";

const palette = ['#1a3a6b','#2c5282','#3182ce','#63b3ed','#bee3f8'];

// Pie chart — Employment type
new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: {
        labels  : typeLabels,
        datasets: [{ data: typeCounts, backgroundColor: palette, borderWidth: 2, borderColor:'#fff' }]
    },
    options: { plugins: { legend: { position:'bottom' } }, cutout:'60%' }
});

// Bar chart — Per department
new Chart(document.getElementById('deptChart'), {
    type: 'bar',
    data: {
        labels  : deptLabels,
        datasets: [{
            label          : 'Employees',
            data           : deptCounts,
            backgroundColor: '#2c5282',
            borderRadius   : 6,
        }]
    },
    options: {
        indexAxis : 'y',
        plugins   : { legend: { display: false } },
        scales    : { x: { ticks: { stepSize:1 }, beginAtZero: true } },
        responsive: true
    }
});
";
require_once __DIR__ . '/includes/footer.php';
