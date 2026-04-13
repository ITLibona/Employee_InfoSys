<?php
/**
 * includes/header.php
 *
 * Expected variables set by the calling page BEFORE including:
 *   $title      (string) — browser tab title
 *   $activePage (string) — 'dashboard' | 'employees' | 'departments' | 'positions'
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'MEIS') ?> | <?= APP_NAME ?></title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Custom styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- ═══════════════════════════════════════════════
     TOP NAVBAR
═══════════════════════════════════════════════ -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="topNavbar">
    <div class="container-fluid">

        <!-- Sidebar toggle -->
        <button class="btn btn-link text-white p-0 me-3 border-0"
                id="sidebarToggle" title="Toggle Sidebar">
            <i class="fas fa-bars fa-lg"></i>
        </button>

        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center fw-bold" href="<?= BASE_URL ?>/index.php">
            <i class="fas fa-building-columns me-2"></i>
            <span class="d-none d-md-inline"><?= APP_NAME ?></span>
            <span class="d-inline d-md-none"><?= APP_SHORT ?></span>
        </a>

        <!-- Right controls -->
        <div class="ms-auto d-flex align-items-center gap-2">
            <span class="d-none d-md-inline text-white-50 small">
                <?= date('l, F j, Y') ?>
            </span>

            <div class="dropdown">
                <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center gap-1"
                        type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle"></i>
                    <span class="d-none d-sm-inline"><?= e($_SESSION['full_name'] ?? 'User') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <span class="dropdown-item-text">
                            <small class="text-muted text-capitalize">
                                <i class="fas fa-shield-halved me-1"></i>
                                <?= e($_SESSION['role'] ?? '') ?>
                            </small>
                        </span>
                    </li>
                    <li><hr class="dropdown-divider m-1"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
                            <i class="fas fa-right-from-bracket me-2"></i>Sign Out
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- ═══════════════════════════════════════════════
     SIDEBAR
═══════════════════════════════════════════════ -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header text-center py-3 px-3">
        <i class="fas fa-landmark fa-2x text-white mb-1"></i>
        <div class="fw-semibold text-white small"><?= MUNICIPALITY ?></div>
        <div class="text-white-50" style="font-size:.72rem;">Employee Information System</div>
    </div>

    <nav>
        <ul class="nav flex-column mt-1">
            <li class="nav-item">
                <a class="nav-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>/index.php">
                    <i class="fas fa-gauge-high me-2"></i> Dashboard
                </a>
            </li>

            <li class="nav-section-label">Records</li>

            <li class="nav-item">
                <a class="nav-link <?= ($activePage ?? '') === 'employees' ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>/employees/index.php">
                    <i class="fas fa-users me-2"></i> Employees
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($activePage ?? '') === 'departments' ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>/departments/index.php">
                    <i class="fas fa-sitemap me-2"></i> Departments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($activePage ?? '') === 'positions' ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>/positions/index.php">
                    <i class="fas fa-briefcase me-2"></i> Positions
                </a>
            </li>

            <li class="nav-section-label">Quick Actions</li>

            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>/employees/add.php">
                    <i class="fas fa-user-plus me-2"></i> Add Employee
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($activePage ?? '') === 'employee-qr' ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>/employees/qr_codes.php">
                    <i class="fas fa-qrcode me-2"></i> Employee QR Codes
                </a>
            </li>

            <li class="nav-section-label">Leave Management</li>

            <li class="nav-item">
                <a class="nav-link <?= ($activePage ?? '') === 'leaves' ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>/leaves/index.php">
                    <i class="fas fa-calendar-check me-2"></i> Leave Requests
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($activePage ?? '') === 'cto' ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>/leaves/cto.php">
                    <i class="fas fa-clock me-2"></i> CTO Requests
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($activePage ?? '') === 'portal-accounts' ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>/leaves/accounts.php">
                    <i class="fas fa-user-key me-2"></i> Portal Accounts
                </a>
            </li>

            <li class="nav-section-label">Links</li>

            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>/portal/login.php" target="_blank">
                    <i class="fas fa-arrow-up-right-from-square me-2"></i> Employee Portal
                </a>
            </li>
        </ul>
    </nav>
</div>

<!-- ═══════════════════════════════════════════════
     MAIN CONTENT WRAPPER
═══════════════════════════════════════════════ -->
<div class="main-content" id="mainContent">

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : 'warning') ?> alert-dismissible fade show mx-4 mt-3 mb-0"
         role="alert" data-auto-dismiss>
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'circle-check' : 'triangle-exclamation' ?> me-2"></i>
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="container-fluid py-4 px-4">
