<?php $success = $success ?? ($_SESSION['flash_success'] ?? null); ?>
<div class="auth-shell">
    <div class="row g-4 align-items-center">
        <div class="col-12 col-lg-5">
            <div class="pe-lg-4">
                <div class="auth-badge mb-4">Thunder mode active</div>
                <h1 class="hero-title fw-bold mb-3">Fast login for your upgraded matcher.</h1>
                <p class="auth-copy fs-5 mb-0">A sleek, dark interface with electric accents for a sharper sign-in experience.</p>
            </div>
        </div>

        <div class="col-12 col-lg-7 col-xl-6 offset-xl-1">
            <div class="card hero-card auth-panel border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="mb-4">
                        <span class="spark-line mb-3">Sign in</span>
                        <h2 class="h3 fw-bold mb-2">Welcome back</h2>
                        <p class="hero-subtitle mb-0">Log in to upload your CV and view your matches.</p>
                    </div>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars((string) $success) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars((string) $error) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= htmlspecialchars(url_path('/login')) ?>" class="d-grid gap-3">
                        <div>
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control form-control-lg" placeholder="you@example.com" value="<?= htmlspecialchars((string) ($email ?? '')) ?>" required>
                        </div>
                        <div>
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control form-control-lg" placeholder="Enter your password" required>
                        </div>
                        <button type="submit" class="btn btn-dark btn-lg w-100">Login</button>
                    </form>

                    <p class="text-center text-white-50 mt-4 mb-0">
                        No account yet? <a href="<?= htmlspecialchars(url_path('/signup')) ?>" class="fw-semibold text-info text-decoration-none">Create one</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>
