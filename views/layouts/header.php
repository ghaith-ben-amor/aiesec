<?php $config = $config ?? config(); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($config['app_name']) ?></title>
    <link rel="icon" type="image/jpeg" href="<?= htmlspecialchars(url_path('/assets/images/site-icon.jpg')) ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars(url_path('/assets/images/site-icon.jpg')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(url_path('/assets/css/theme.css')) ?>">
    <style>
        .score-badge { font-size: .95rem; }
    </style>
</head>
<body data-theme="dark">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= htmlspecialchars(url_path('/')) ?>"><?= htmlspecialchars($config['app_name']) ?></a>
        <div class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
            <?php if (is_authenticated()): ?>
                <?php $activeUser = current_user(); ?>
                <span class="navbar-text text-white-50 me-3">
                    <?= htmlspecialchars((string) ($activeUser['name'] ?? 'User')) ?>
                </span>
                <?php if (($activeUser['role'] ?? '') === 'admin'): ?>
                    <a class="nav-link" href="<?= htmlspecialchars(url_path('/admin')) ?>">Admin</a>
                    <a class="nav-link" href="<?= htmlspecialchars(url_path('/ep-management')) ?>">EP Management</a>
                    <a class="nav-link" href="<?= htmlspecialchars(url_path('/ep-management#register-ep')) ?>">Add EP</a>
                <?php endif; ?>
                <a class="nav-link" href="<?= htmlspecialchars(url_path('/cv-builder')) ?>">CV Builder</a>
                <a class="nav-link" href="<?= htmlspecialchars(url_path('/upload')) ?>">Upload CV</a>
                <a class="nav-link" href="<?= htmlspecialchars(url_path('/results')) ?>">Results</a>
                <a class="nav-link" href="<?= htmlspecialchars(url_path('/dashboard')) ?>">Dashboard</a>
                <a class="nav-link" href="<?= htmlspecialchars(url_path('/logout')) ?>">Logout</a>
            <?php else: ?>
                <a class="nav-link" href="<?= htmlspecialchars(url_path('/login')) ?>">Login</a>
                <a class="nav-link" href="<?= htmlspecialchars(url_path('/signup')) ?>">Sign up</a>
            <?php endif; ?>
            <button class="btn btn-sm btn-theme-toggle ms-lg-2" type="button" id="theme-toggle" aria-label="Toggle color theme">
                <span id="theme-toggle-label">Light mode</span>
            </button>
        </div>
    </div>
</nav>
<main class="container py-5">
