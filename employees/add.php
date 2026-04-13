<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db          = getDB();
$departments = $db->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();
$positions   = $db->query("SELECT id, title, department_id FROM positions ORDER BY title")->fetchAll();

$errors = [];
$data   = [
    'employee_id'       => generateEmployeeId(),
    'first_name'        => '',
    'last_name'         => '',
    'middle_name'       => '',
    'suffix'            => '',
    'gender'            => '',
    'birthdate'         => '',
    'civil_status'      => '',
    'address'           => '',
    'contact_number'    => '',
    'email'             => '',
    'department_id'     => '',
    'position_id'       => '',
    'employment_status' => 'Active',
    'employment_type'   => '',
    'date_hired'        => date('Y-m-d'),
    'tin_number'                 => '',
    'sss_number'                 => '',
    'gsis_number'                => '',
    'philhealth_number'          => '',
    'pagibig_number'             => '',
    'height'                     => '',
    'weight'                     => '',
    'blood_type'                 => '',
    'emergency_contact_name'     => '',
    'emergency_contact_address'  => '',
    'emergency_contact_phone'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        // Collect & sanitize
        foreach (array_keys($data) as $key) {
            if ($key !== 'employee_id') {
                $data[$key] = trim($_POST[$key] ?? '');
            }
        }

        // Validate required fields
        $required = ['first_name','last_name','gender','birthdate','civil_status',
                     'address','employment_type','date_hired'];
        foreach ($required as $f) {
            if ($data[$f] === '') {
                $errors[] = ucwords(str_replace('_', ' ', $f)) . ' is required.';
            }
        }

        // Email format
        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }

        // Duplicate employee_id check (race condition guard)
        if ($data['employee_id'] !== '') {
            $chk = $db->prepare("SELECT COUNT(*) FROM employees WHERE employee_id = ?");
            $chk->execute([$data['employee_id']]);
            if ($chk->fetchColumn() > 0) {
                $data['employee_id'] = generateEmployeeId(); // regenerate
            }
        }

        // Photo upload
        $photoFilename = null;
        if (!empty($_FILES['photo']['name'])) {
            $allowedMimes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];
            $maxSize = 10 * 1024 * 1024; // 10 MB
            $file    = $_FILES['photo'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Photo upload error. Please try again.';
            } elseif ($file['size'] > $maxSize) {
                $errors[] = 'Photo file size must not exceed 10 MB.';
            } else {
                // Use finfo to detect real MIME — not the browser-supplied type
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $realMime = $finfo->file($file['tmp_name']);
                if (!array_key_exists($realMime, $allowedMimes)) {
                    $errors[] = 'Photo must be a real JPEG, PNG, GIF, or WebP image.';
                } else {
                    if (!is_dir(UPLOAD_DIR)) {
                        mkdir(UPLOAD_DIR, 0755, true);
                    }
                    // Extension derived from actual MIME — never from the original filename
                    $ext           = $allowedMimes[$realMime];
                    $photoFilename = bin2hex(random_bytes(16)) . '.' . $ext;
                    move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $photoFilename);
                }
            }
        }

        if (empty($errors)) {
            $stmt = $db->prepare(
                "INSERT INTO employees
                    (employee_id, first_name, last_name, middle_name, suffix,
                     gender, birthdate, civil_status, address, contact_number,
                     email, department_id, position_id, employment_status,
                     employment_type, date_hired, photo,
                     tin_number, sss_number, gsis_number, philhealth_number, pagibig_number,
                     height, weight, blood_type,
                     emergency_contact_name, emergency_contact_address, emergency_contact_phone)
                 VALUES
                    (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->execute([
                $data['employee_id'],
                $data['first_name'],
                $data['last_name'],
                $data['middle_name'] ?: null,
                $data['suffix']      ?: null,
                $data['gender'],
                $data['birthdate'],
                $data['civil_status'],
                $data['address'],
                $data['contact_number'] ?: null,
                $data['email']          ?: null,
                $data['department_id']  ?: null,
                $data['position_id']    ?: null,
                $data['employment_status'],
                $data['employment_type'],
                $data['date_hired'],
                $photoFilename,
                $data['tin_number']        ?: null,
                $data['sss_number']        ?: null,
                $data['gsis_number']       ?: null,
                $data['philhealth_number'] ?: null,
                $data['pagibig_number']    ?: null,
                $data['height']                    ?: null,
                $data['weight']                    ?: null,
                $data['blood_type']                ?: null,
                $data['emergency_contact_name']    ?: null,
                $data['emergency_contact_address'] ?: null,
                $data['emergency_contact_phone']   ?: null,
            ]);
            setFlash('success', 'Employee "' . $data['first_name'] . ' ' . $data['last_name'] . '" added successfully.');
            redirect('employees/index.php');
        }
    }
}

$title      = 'Add Employee';
$activePage = 'employees';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-user-plus me-2"></i>Add Employee</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item">
                    <a href="<?= BASE_URL ?>/employees/index.php">Employees</a>
                </li>
                <li class="breadcrumb-item active">Add</li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger mb-3">
    <strong><i class="fas fa-circle-exclamation me-1"></i>Please fix the following:</strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($errors as $err): ?>
        <li><?= e($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" novalidate>
    <?= csrfInput() ?>

    <div class="row g-3">

        <!-- Left column -->
        <div class="col-lg-8">
            <!-- Personal Information -->
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <p class="form-section-title">Personal Information</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">
                                First Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="first_name" class="form-control form-control-sm"
                                   value="<?= e($data['first_name']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">
                                Last Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="last_name" class="form-control form-control-sm"
                                   value="<?= e($data['last_name']) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control form-control-sm"
                                   value="<?= e($data['middle_name']) ?>">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label form-label-sm fw-semibold">Suffix</label>
                            <input type="text" name="suffix" class="form-control form-control-sm"
                                   placeholder="Jr." value="<?= e($data['suffix']) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold">
                                Gender <span class="text-danger">*</span>
                            </label>
                            <select name="gender" class="form-select form-select-sm" required>
                                <option value="">—Select—</option>
                                <?php foreach (['Male','Female'] as $g): ?>
                                <option value="<?= $g ?>" <?= $data['gender'] === $g ? 'selected' : '' ?>>
                                    <?= $g ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold">
                                Birthdate <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="birthdate" name="birthdate"
                                   class="form-control form-control-sm"
                                   value="<?= e($data['birthdate']) ?>" required>
                            <small id="ageDisplay" class="text-muted"></small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold">
                                Civil Status <span class="text-danger">*</span>
                            </label>
                            <select name="civil_status" class="form-select form-select-sm" required>
                                <option value="">—Select—</option>
                                <?php foreach (['Single','Married','Widowed','Separated','Divorced'] as $cs): ?>
                                <option value="<?= $cs ?>" <?= $data['civil_status'] === $cs ? 'selected' : '' ?>>
                                    <?= $cs ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label form-label-sm fw-semibold">
                                Address <span class="text-danger">*</span>
                            </label>
                            <textarea name="address" class="form-control form-control-sm" rows="2"
                                      required><?= e($data['address']) ?></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control form-control-sm"
                                   placeholder="09XXXXXXXXX"
                                   value="<?= e($data['contact_number']) ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label form-label-sm fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm"
                                   value="<?= e($data['email']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">Height</label>
                            <input type="text" name="height" class="form-control form-control-sm"
                                   placeholder="e.g. 5'7&quot; or 170 cm"
                                   value="<?= e($data['height']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">Weight</label>
                            <input type="text" name="weight" class="form-control form-control-sm"
                                   placeholder="e.g. 70 kg"
                                   value="<?= e($data['weight']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">Blood Type</label>
                            <select name="blood_type" class="form-select form-select-sm">
                                <option value="">— Select —</option>
                                <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                                <option value="<?= $bt ?>" <?= $data['blood_type'] === $bt ? 'selected' : '' ?>><?= $bt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employment Information -->
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <p class="form-section-title">Employment Information</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm fw-semibold">Department</label>
                            <select name="department_id" class="form-select form-select-sm">
                                <option value="">—Select—</option>
                                <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"
                                        <?= $data['department_id'] == $d['id'] ? 'selected' : '' ?>>
                                    <?= e($d['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label form-label-sm fw-semibold">Position</label>
                            <select name="position_id" class="form-select form-select-sm">
                                <option value="">—Select—</option>
                                <?php foreach ($positions as $p): ?>
                                <option value="<?= $p['id'] ?>"
                                        <?= $data['position_id'] == $p['id'] ? 'selected' : '' ?>>
                                    <?= e($p['title']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">
                                Employment Type <span class="text-danger">*</span>
                            </label>
                            <select name="employment_type" class="form-select form-select-sm" required>
                                <option value="">—Select—</option>
                                <?php foreach (['Permanent','Job Order/ Contractual','Casual','Coterminous'] as $et): ?>
                                <option value="<?= $et ?>" <?= $data['employment_type'] === $et ? 'selected' : '' ?>>
                                    <?= $et ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">
                                Employment Status
                            </label>
                            <select name="employment_status" class="form-select form-select-sm">
                                <?php foreach (['Active','Inactive','On Leave','Resigned','Retired'] as $es): ?>
                                <option value="<?= $es ?>" <?= $data['employment_status'] === $es ? 'selected' : '' ?>>
                                    <?= $es ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">
                                Date Hired <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="date_hired" class="form-control form-control-sm"
                                   value="<?= e($data['date_hired']) ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Government IDs -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <p class="form-section-title">Government IDs</p>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold">TIN Number</label>
                            <input type="text" name="tin_number" class="form-control form-control-sm"
                                   value="<?= e($data['tin_number']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold">SSS Number</label>
                            <input type="text" name="sss_number" class="form-control form-control-sm"
                                   value="<?= e($data['sss_number']) ?>">
                        </div>
                        <div class="col-md-3" id="gsisField"
                             style="<?= in_array($data['employment_type'], ['Permanent','Casual','Coterminous']) ? '' : 'display:none' ?>">
                            <label class="form-label form-label-sm fw-semibold">
                                GSIS Number
                            </label>
                            <input type="text" name="gsis_number" id="gsis_number"
                                   class="form-control form-control-sm"
                                   value="<?= e($data['gsis_number']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold">PhilHealth No.</label>
                            <input type="text" name="philhealth_number" class="form-control form-control-sm"
                                   value="<?= e($data['philhealth_number']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold">Pag-IBIG No.</label>
                            <input type="text" name="pagibig_number" class="form-control form-control-sm"
                                   value="<?= e($data['pagibig_number']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="card shadow-sm mt-3">
                <div class="card-body">
                    <p class="form-section-title"><i class="fas fa-phone-volume me-2"></i>In Case of Emergency / Contact Person</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm fw-semibold">Contact Person Name</label>
                            <input type="text" name="emergency_contact_name" class="form-control form-control-sm"
                                   placeholder="Full name"
                                   value="<?= e($data['emergency_contact_name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label form-label-sm fw-semibold">Cellphone Number</label>
                            <input type="text" name="emergency_contact_phone" class="form-control form-control-sm"
                                   placeholder="09XXXXXXXXX"
                                   value="<?= e($data['emergency_contact_phone']) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label form-label-sm fw-semibold">Address</label>
                            <textarea name="emergency_contact_address" class="form-control form-control-sm"
                                      rows="2"><?= e($data['emergency_contact_address']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right column -->
        <div class="col-lg-4">
            <!-- Photo -->
            <div class="card shadow-sm mb-3">
                <div class="card-body text-center">
                    <p class="form-section-title text-start">Photo</p>
                    <div class="mb-3">
                        <div class="emp-photo-placeholder mx-auto d-flex" id="photoPlaceholder">
                            <i class="fas fa-user"></i>
                        </div>
                        <img id="photoPreview" src="#" class="emp-photo mx-auto d-block"
                             style="display:none!important" alt="Preview">
                    </div>
                    <input type="file" id="photo" name="photo"
                           class="form-control form-control-sm"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <small class="text-muted">Max 10 MB · JPEG, PNG or WebP</small>
                </div>
            </div>

            <!-- Employee ID -->
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <p class="form-section-title">Employee ID</p>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                        <input type="text" name="employee_id" class="form-control form-control-sm font-monospace"
                               value="<?= e($data['employee_id']) ?>" readonly>
                    </div>
                    <small class="text-muted">Auto-generated. You may edit if needed.</small>
                </div>
            </div>

            <!-- Submit -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Employee
                </button>
                <a href="<?= BASE_URL ?>/employees/index.php"
                   class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Cancel
                </a>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    const typeSelect = document.querySelector('select[name="employment_type"]');
    const gsisField  = document.getElementById('gsisField');
    const gsisInput  = document.getElementById('gsis_number');
    function toggleGsis() {
        const showGsis = ['Permanent','Casual','Coterminous'].includes(typeSelect.value);
        gsisField.style.display = showGsis ? '' : 'none';
        if (!showGsis) gsisInput.value = '';
    }
    typeSelect.addEventListener('change', toggleGsis);
    toggleGsis();
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
