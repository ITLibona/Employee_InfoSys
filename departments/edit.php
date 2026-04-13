<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db  = getDB();
$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) { redirect('departments/index.php'); }

$stmt = $db->prepare("SELECT * FROM departments WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$dept = $stmt->fetch();
if (!$dept) {
    setFlash('error', 'Department not found.');
    redirect('departments/index.php');
}

$errors = [];
$data   = $dept;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        foreach (['name','code','description','head_name'] as $k) {
            $data[$k] = trim($_POST[$k] ?? '');
        }
        if ($data['name'] === '') { $errors[] = 'Department name is required.'; }
        if ($data['code'] === '') { $errors[] = 'Department code is required.'; }

        if (empty($errors)) {
            // Uniqueness check (exclude self)
            $chk = $db->prepare("SELECT COUNT(*) FROM departments WHERE (name=? OR code=?) AND id != ?");
            $chk->execute([$data['name'], strtoupper($data['code']), $id]);
            if ($chk->fetchColumn() > 0) {
                $errors[] = 'Another department with that name or code already exists.';
            }
        }

        if (empty($errors)) {
            $stmt = $db->prepare(
                "UPDATE departments SET name=?, code=?, description=?, head_name=? WHERE id=?"
            );
            $stmt->execute([
                $data['name'],
                strtoupper($data['code']),
                $data['description'] ?: null,
                $data['head_name']   ?: null,
                $id
            ]);
            setFlash('success', 'Department updated successfully.');
            redirect('departments/index.php');
        }
    }
}

$title      = 'Edit Department';
$activePage = 'departments';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2><i class="fas fa-pen me-2"></i>Edit Department</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item">
                    <a href="<?= BASE_URL ?>/departments/index.php">Departments</a>
                </li>
                <li class="breadcrumb-item active"><?= e($dept['name']) ?></li>
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
                        <label class="form-label fw-semibold">Department Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= e($data['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control text-uppercase"
                               maxlength="20" value="<?= e($data['code']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department Head</label>
                        <input type="text" name="head_name" class="form-control"
                               value="<?= e($data['head_name'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($data['description'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                        <a href="<?= BASE_URL ?>/departments/index.php"
                           class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
