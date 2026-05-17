<?php $config = $config ?? config(); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($config['app_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f8fafc, #eef2ff); min-height: 100vh; }
        .hero-card { background: rgba(255,255,255,.85); backdrop-filter: blur(8px); border: 0; box-shadow: 0 20px 60px rgba(15,23,42,.08); }
        .score-badge { font-size: .95rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= htmlspecialchars(url_path('/upload')) ?>"><?= htmlspecialchars($config['app_name']) ?></a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="<?= htmlspecialchars(url_path('/upload')) ?>">Upload CV</a>
            <a class="nav-link" href="<?= htmlspecialchars(url_path('/results')) ?>">Results</a>
            <a class="nav-link" href="<?= htmlspecialchars(url_path('/dashboard')) ?>">Dashboard</a>
        </div>
    </div>
</nav>
<main class="container py-5">
