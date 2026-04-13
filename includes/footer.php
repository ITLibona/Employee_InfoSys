    </div><!-- /container-fluid -->
</div><!-- /main-content -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js (available if a page needs it) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<!-- Custom JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

<?php if (!empty($extraJs)): ?>
<script>
<?= $extraJs ?>
</script>
<?php endif; ?>
</body>
</html>
