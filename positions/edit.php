<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db          = getDB();
$id          = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0)  { redirect('positions/index.php'); }

$stmt = $db->prepare("SELECT * FROM positions WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$pos  = $stmt->fetch();
if (!$pos) {
    setFlash('error', 'Position not found.');
    redirect('positions/index.php');
}

$departments = $db->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();
$errors = [];
$data   = $pos;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        foreach (['title','department_id','salary_grade','description'] as $k) {
            $data[$k] = trim($_POST[$k] ?? '');
        }
        if ($data['title'] === '') { $errors[] = 'Position title is required.'; }
        if ($data['salary_grade'] !== '' && (!ctype_digit($data['salary_grade']) || (int)$data['salary_grade'] < 1 || (int)$data['salary_grade'] > 33)) {
            $errors[] = 'Salary grade must be between 1 and 33.';
        }

        if (empty($errors)) {
            $stmt = $db->prepare(
                "UPDATE positions SET title=?, department_id=?, salary_grade=?, description=?
                 WHERE id=?"
            );
            $stmt->execute([
                $data['title'],
                $data['department_id'] ?: null,
                $data['salary_grade']  ?: null,
                $data['description']   ?: null,
                $id
            ]);
            setFlash('success', 'Position updated successfully.');
            redirect('positions/index.php');
        }
    }
}

$title      = 'Edit Position';
$activePage = 'positions';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2><i class="fas fa-pen me-2"></i>Edit Position</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item">
                    <a href="<?= BASE_URL ?>/positions/index.php">Positions</a>
                </li>
                <li class="breadcrumb-item active"><?= e($pos['title']) ?></li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger mb-3">
    <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="" novalidate>
                    <?= csrfInput() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Position Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                               value="<?= e($data['title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">—Select Department—</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"
                                    <?= $data['department_id'] == $d['id'] ? 'selected' : '' ?>>
                                <?= e($d['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Salary Grade</label>
                        <input type="number" name="salary_grade" class="form-control"
                               min="1" max="33"
                               value="<?= e($data['salary_grade'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($data['description'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                        <a href="<?= BASE_URL ?>/positions/index.php"
                           class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
