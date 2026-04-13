<?php
/**
 * leaves/accounts.php — Manage Employee Portal Accounts
 * Allows HR/Admin to create, reset passwords, and enable/disable portal access.
 */
require_once dirname(__DIR__) . '/config/config.php';
requireLogin();
$db = getDB();

$errors  = [];
$success = '';

// --- Handle POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
        header('Location: ' . BASE_URL . '/leaves/accounts.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Create new portal account
    if ($action === 'create') {
        $empId    = (int)($_POST['employee_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$empId)                      $errors[] = 'No employee selected.';
        if (strlen($username) < 3)        $errors[] = 'Username must be at least 3 characters.';
        if (strlen($password) < 6)        $errors[] = 'Password must be at least 6 characters.';

        if (!$errors) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO employee_portal_accounts (employee_id, username, password)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$empId, $username, password_hash($password, PASSWORD_DEFAULT)]);
                setFlash('success', 'Portal account created successfully.');
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') {
                    setFlash('error', 'That employee or username already has a portal account.');
                } else {
                    setFlash('error', 'Could not create account. Please try again.');
                }
            }
        } else {
            setFlash('error', implode(' ', $errors));
        }
        header('Location: ' . BASE_URL . '/leaves/accounts.php');
        exit;
    }

    // Reset password
    if ($action === 'reset_password') {
        $accountId   = (int)($_POST['account_id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';

        if (!$accountId)               { setFlash('error', 'Invalid account.'); }
        elseif (strlen($newPassword) < 6) { setFlash('error', 'New password must be at least 6 characters.'); }
        else {
            $stmt = $db->prepare("UPDATE employee_portal_accounts SET password = ? WHERE id = ?");
            $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $accountId]);
            setFlash('success', 'Password reset successfully.');
        }
        header('Location: ' . BASE_URL . '/leaves/accounts.php');
        exit;
    }

    // Toggle active / inactive
    if ($action === 'toggle_status') {
        $accountId = (int)($_POST['account_id'] ?? 0);
        if ($accountId) {
            $db->prepare("UPDATE employee_portal_accounts SET is_active = 1 - is_active WHERE id = ?")
               ->execute([$accountId]);
            setFlash('success', 'Account status updated.');
        }
        header('Location: ' . BASE_URL . '/leaves/accounts.php');
        exit;
    }
}

// --- Fetch employees WITH portal accounts ---
$withAccount = $db->query("
    SELECT pa.id AS account_id, pa.username, pa.is_active, pa.created_at,
           e.id AS emp_id, e.employee_id, CONCAT(e.first_name,' ',e.last_name) AS full_name,
           COALESCE(d.name,'—') AS department
    FROM employee_portal_accounts pa
    JOIN employees e ON e.id = pa.employee_id
    LEFT JOIN departments d ON d.id = e.department_id
    ORDER BY e.last_name, e.first_name
")->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch employees WITHOUT portal accounts (for the "create" form) ---
$withoutAccount = $db->query("
    SELECT e.id, e.employee_id, CONCAT(e.first_name,' ',e.last_name) AS full_name,
           COALESCE(d.name,'—') AS department
    FROM employees e
    LEFT JOIN departments d ON d.id = e.department_id
    WHERE e.id NOT IN (SELECT employee_id FROM employee_portal_accounts)
      AND e.employment_status = 'Active'
    ORDER BY e.last_name, e.first_name
")->fetchAll(PDO::FETCH_ASSOC);

$title      = 'Portal Accounts';
$activePage = 'portal-accounts';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2><i class="fas fa-user-key me-2"></i>Employee Portal Accounts</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">Leave Management &rarr; Portal Accounts</li>
            </ol>
        </nav>
    </div>
    <?php if ($withoutAccount): ?>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="fas fa-plus me-1"></i>Create Account
    </button>
    <?php endif; ?>
</div>

<!-- Existing accounts -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-list me-2 text-primary"></i>Existing Portal Accounts
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.875rem">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Username</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($withAccount): ?>
                <?php foreach ($withAccount as $a): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($a['full_name']) ?></div>
                        <div class="text-muted" style="font-size:.75rem"><?= e($a['employee_id']) ?></div>
                    </td>
                    <td><?= e($a['department']) ?></td>
                    <td><code><?= e($a['username']) ?></code></td>
                    <td>
                        <?php if ($a['is_active']): ?>
                        <span class="badge bg-success badge-status">Active</span>
                        <?php else: ?>
                        <span class="badge bg-secondary badge-status">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted"><?= date('M d, Y', strtotime($a['created_at'])) ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-secondary me-1"
                                onclick="openReset(<?= $a['account_id'] ?>, '<?= e(addslashes($a['full_name'])) ?>')">
                            <i class="fas fa-key"></i> Reset PW
                        </button>
                        <form method="POST" class="d-inline">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action"     value="toggle_status">
                            <input type="hidden" name="account_id" value="<?= $a['account_id'] ?>">
                            <button type="submit" class="btn btn-sm <?= $a['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                    title="<?= $a['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                <i class="fas fa-<?= $a['is_active'] ? 'ban' : 'check' ?>"></i>
                                <?= $a['is_active'] ? 'Disable' : 'Enable' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fas fa-user-slash fa-2x d-block mb-2 opacity-40"></i>
                        No portal accounts created yet.
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Employees without accounts -->
<?php if ($withoutAccount): ?>
<div class="card">
    <div class="card-header">
        <i class="fas fa-user-clock me-2 text-warning"></i>Active Employees Without Portal Accounts
        <span class="badge bg-warning text-dark ms-2"><?= count($withoutAccount) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.875rem">
                <thead>
                    <tr><th>Employee</th><th>Employee ID</th><th>Department</th><th class="text-center">Action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($withoutAccount as $e): ?>
                <tr>
                    <td><?= e($e['full_name']) ?></td>
                    <td><code><?= e($e['employee_id']) ?></code></td>
                    <td><?= e($e['department']) ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary"
                                onclick="openCreate(<?= $e['id'] ?>, '<?= e(addslashes($e['full_name'])) ?>', '<?= e($e['employee_id']) ?>')">
                            <i class="fas fa-plus me-1"></i>Create Account
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Create Account Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= csrfInput() ?>
                <input type="hidden" name="action"      value="create">
                <input type="hidden" name="employee_id" id="createEmpId">
                <div class="modal-header">
                    <h5 class="modal-title">Create Portal Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="createEmpLabel"></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="createUsername" class="form-control"
                               placeholder="Default: Employee ID" required>
                        <div class="form-text">Employees will use this to log into the portal.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <input type="text" name="password" class="form-control"
                               placeholder="Minimum 6 characters" required minlength="6">
                        <div class="form-text">Share this password with the employee securely.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Create Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= csrfInput() ?>
                <input type="hidden" name="action"     value="reset_password">
                <input type="hidden" name="account_id" id="resetAccountId">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Portal Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="resetEmpLabel"></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                        <input type="text" name="new_password" class="form-control"
                               placeholder="Minimum 6 characters" required minlength="6">
                        <div class="form-text">Share the new password with the employee securely.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark">
                        <i class="fas fa-key me-1"></i>Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraJs = <<<'JS'
function openCreate(empId, empName, employeeId) {
    document.getElementById('createEmpId').value    = empId;
    document.getElementById('createEmpLabel').textContent = 'Creating account for: ' + empName;
    document.getElementById('createUsername').value = employeeId;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('createModal')).show();
}

function openReset(accountId, empName) {
    document.getElementById('resetAccountId').value    = accountId;
    document.getElementById('resetEmpLabel').textContent = 'Resetting password for: ' + empName;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('resetModal')).show();
}
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
