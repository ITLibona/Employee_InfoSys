/* =======================================================
   Municipal Employee Information System — Main JS
   ======================================================= */

document.addEventListener('DOMContentLoaded', function () {

    // ── Sidebar toggle ────────────────────────────────────
    const toggleBtn   = document.getElementById('sidebarToggle');
    const sidebar     = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('open');
            } else {
                sidebar.classList.toggle('collapsed');
                if (mainContent) mainContent.classList.toggle('sidebar-collapsed');
            }
        });

        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 768 &&
                !sidebar.contains(e.target) &&
                !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // ── Auto-dismiss alerts after 5 s ─────────────────────
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (el) {
        setTimeout(function () {
            const inst = bootstrap.Alert.getOrCreateInstance(el);
            if (inst) inst.close();
        }, 5000);
    });

    // ── Delete confirmation ───────────────────────────────
    document.querySelectorAll('form.form-delete').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm('Are you sure you want to delete this record?\nThis action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // ── Photo preview on file select ──────────────────────
    const photoInput = document.getElementById('photo');
    if (photoInput) {
        photoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                alert('Please select a valid image file.');
                this.value = '';
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                alert('Image size must be 10 MB or less.');
                this.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function (ev) {
                const preview = document.getElementById('photoPreview');
                if (preview) {
                    preview.src = ev.target.result;
                    preview.style.display = 'block';
                }
            };
            reader.readAsDataURL(file);
        });
    }

    // ── Datatable-style live search ───────────────────────
    const tableSearch = document.getElementById('tableSearch');
    if (tableSearch) {
        tableSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('table tbody tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    // ── Compute age from birthdate ────────────────────────
    const birthdateInput = document.getElementById('birthdate');
    const ageDisplay     = document.getElementById('ageDisplay');
    if (birthdateInput && ageDisplay) {
        function updateAge() {
            const val = birthdateInput.value;
            if (!val) { ageDisplay.textContent = ''; return; }
            const born  = new Date(val);
            const today = new Date();
            let age = today.getFullYear() - born.getFullYear();
            const m = today.getMonth() - born.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < born.getDate())) age--;
            ageDisplay.textContent = age >= 0 ? age + ' years old' : '';
        }
        birthdateInput.addEventListener('change', updateAge);
        updateAge();
    }

    // ── Filter by department select ───────────────────────
    const deptFilter = document.getElementById('deptFilter');
    if (deptFilter) {
        deptFilter.addEventListener('change', function () {
            const val = this.value.toLowerCase();
            document.querySelectorAll('table tbody tr').forEach(function (row) {
                const deptCell = row.querySelector('[data-dept]');
                if (!val || !deptCell) {
                    row.style.display = '';
                } else {
                    row.style.display =
                        deptCell.getAttribute('data-dept').toLowerCase().includes(val) ? '' : 'none';
                }
            });
        });
    }

    // ── Status filter ─────────────────────────────────────
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function () {
            const val = this.value.toLowerCase();
            document.querySelectorAll('table tbody tr').forEach(function (row) {
                const statusCell = row.querySelector('[data-status]');
                if (!val || !statusCell) {
                    row.style.display = '';
                } else {
                    row.style.display =
                        statusCell.getAttribute('data-status').toLowerCase() === val ? '' : 'none';
                }
            });
        });
    }
});
