<?php
/**
 * portal/includes/header.php
 * Expected variables from the calling page:
 *   $title           (string) — browser tab title
 *   $activePortalPage(string) — 'dashboard' | 'leave-apply' | 'leave-history' | 'cto-apply' | 'cto-history'
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Portal') ?> | <?= e(MUNICIPALITY) ?> Employee Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/portal.css">
</head>
<body class="portal-body">

<!-- TOP NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="topNavbar">
    <div class="container-fluid">

        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/portal/dashboard.php">
            <i class="fas fa-building-columns me-2"></i>
            <span class="d-none d-md-inline"><?= e(MUNICIPALITY) ?> &mdash; Employee Portal</span>
            <span class="d-md-none">Employee Portal</span>
        </a>

        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#portalNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="portalNav">
            <ul class="navbar-nav me-auto ms-3">
                <li class="nav-item">
                    <a class="nav-link <?= ($activePortalPage ?? '') === 'dashboard' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/portal/dashboard.php">
                        <i class="fas fa-gauge-high me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($activePortalPage ?? '', ['leave-apply','leave-history']) ? 'active' : '' ?>"
                       href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-calendar-minus me-1"></i> Leave
                    </a>
                    <ul class="dropdown-menu shadow-sm">
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/portal/leave/apply.php">
                                <i class="fas fa-plus me-2 text-primary"></i>File a Leave
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/portal/leave/history.php">
                                <i class="fas fa-clock-rotate-left me-2 text-secondary"></i>My Applications
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($activePortalPage ?? '', ['cto-apply','cto-history']) ? 'active' : '' ?>"
                       href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-clock me-1"></i> CTO
                    </a>
                    <ul class="dropdown-menu shadow-sm">
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/portal/cto/apply.php">
                                <i class="fas fa-plus me-2 text-primary"></i>File a CTO
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/portal/cto/history.php">
                                <i class="fas fa-clock-rotate-left me-2 text-secondary"></i>My Applications
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <span class="text-white-50 small d-none d-lg-inline"><?= date('l, F j, Y') ?></span>
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center gap-1"
                            type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i>
                        <span class="d-none d-sm-inline"><?= e($_SESSION['portal_full_name'] ?? '') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <span class="dropdown-item-text small text-muted">
                                <i class="fas fa-id-badge me-1"></i>
                                <?= e($_SESSION['portal_employee_id'] ?? '') ?>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider m-1"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/portal/logout.php">
                                <i class="fas fa-right-from-bracket me-2"></i>Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</nav>

<div class="portal-main">
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : 'warning') ?> alert-dismissible fade show mx-4 mt-3 mb-0"
         role="alert" data-auto-dismiss>
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'circle-check' : 'triangle-exclamation' ?> me-2"></i>
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <div class="container-fluid py-4 px-4">
