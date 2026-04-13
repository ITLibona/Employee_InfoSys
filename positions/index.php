<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$positions = $db->query(
    "SELECT p.*, d.name AS dept_name, COUNT(e.id) AS employee_count
     FROM positions p
     LEFT JOIN departments d ON d.id = p.department_id
     LEFT JOIN employees   e ON e.position_id = p.id
     GROUP BY p.id
     ORDER BY d.name, p.title"
)->fetchAll();

$title      = 'Positions';
$activePage = 'positions';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-briefcase me-2"></i>Positions
        <span class="badge bg-secondary ms-2 fs-6"><?= count($positions) ?></span>
    </h2>
    <a href="<?= BASE_URL ?>/positions/add.php" class="btn btn-primary no-print">
        <i class="fas fa-plus me-1"></i> Add Position
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Position Title</th>
                        <th>Department</th>
                        <th>Salary Grade</th>
                        <th>Employees</th>
                        <th class="no-print" style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($positions)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No positions yet. <a href="<?= BASE_URL ?>/positions/add.php">Add one →</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($positions as $i => $pos): ?>
                <tr>
                    <td class="text-muted small align-middle"><?= $i + 1 ?></td>
                    <td class="fw-semibold align-middle"><?= e($pos['title']) ?></td>
                    <td class="small align-middle"><?= e($pos['dept_name'] ?? '—') ?></td>
                    <td class="align-middle">
                        <?php if ($pos['salary_grade']): ?>
                        <span class="badge bg-info text-dark">SG-<?= $pos['salary_grade'] ?></span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="align-middle">
                        <span class="badge bg-primary"><?= $pos['employee_count'] ?></span>
                    </td>
                    <td class="align-middle no-print">
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>/positions/edit.php?id=<?= $pos['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST"
                                  action="<?= BASE_URL ?>/positions/delete.php"
                                  class="form-delete d-inline">
                                <?= csrfInput() ?>
                                <input type="hidden" name="id" value="<?= $pos['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        title="Delete"
                                        <?= $pos['employee_count'] > 0 ? 'disabled title="Cannot delete: has employees"' : '' ?>>
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
