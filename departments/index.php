<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$departments = $db->query(
    "SELECT d.*, COUNT(e.id) AS employee_count
     FROM departments d
     LEFT JOIN employees e ON e.department_id = d.id
     GROUP BY d.id
     ORDER BY d.name"
)->fetchAll();

$title      = 'Departments';
$activePage = 'departments';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-sitemap me-2"></i>Departments
        <span class="badge bg-secondary ms-2 fs-6"><?= count($departments) ?></span>
    </h2>
    <a href="<?= BASE_URL ?>/departments/add.php" class="btn btn-primary no-print">
        <i class="fas fa-plus me-1"></i> Add Department
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Department Name</th>
                        <th>Code</th>
                        <th>Department Head</th>
                        <th>Employees</th>
                        <th class="no-print" style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($departments)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No departments yet. <a href="<?= BASE_URL ?>/departments/add.php">Add one →</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($departments as $i => $dept): ?>
                <tr>
                    <td class="text-muted small align-middle"><?= $i + 1 ?></td>
                    <td class="fw-semibold align-middle"><?= e($dept['name']) ?></td>
                    <td class="align-middle">
                        <span class="badge bg-light text-dark border font-monospace">
                            <?= e($dept['code']) ?>
                        </span>
                    </td>
                    <td class="small align-middle"><?= e($dept['head_name'] ?? '—') ?></td>
                    <td class="align-middle">
                        <a href="<?= BASE_URL ?>/employees/index.php?dept=<?= $dept['id'] ?>"
                           class="badge bg-primary text-decoration-none">
                            <?= $dept['employee_count'] ?> employee<?= $dept['employee_count'] != 1 ? 's' : '' ?>
                        </a>
                    </td>
                    <td class="align-middle no-print">
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>/departments/edit.php?id=<?= $dept['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST"
                                  action="<?= BASE_URL ?>/departments/delete.php"
                                  class="form-delete d-inline">
                                <?= csrfInput() ?>
                                <input type="hidden" name="id" value="<?= $dept['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        title="Delete"
                                        <?= $dept['employee_count'] > 0 ? 'disabled title="Cannot delete: has employees"' : '' ?>>
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
