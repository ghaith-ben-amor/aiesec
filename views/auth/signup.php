<div class="auth-shell">
    <div class="row g-4 align-items-center">
        <div class="col-12 col-lg-5 order-lg-2">
            <div class="ps-lg-4">
                <div class="auth-badge mb-4">Join the storm</div>
                <h1 class="hero-title fw-bold mb-3">Create your account in a sharper, modern style.</h1>
                <p class="auth-copy fs-5 mb-0">The signup flow matches the login page with a darker visual identity, electric light, and a cleaner premium feel.</p>
            </div>
        </div>

        <div class="col-12 col-lg-7 col-xl-6">
            <div class="card hero-card auth-panel border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="mb-4">
                        <span class="spark-line mb-3">Create account</span>
                        <h2 class="h3 fw-bold mb-2">Sign up for AIESEC Matcher</h2>
                        <p class="hero-subtitle mb-0">Create your account, then log in to continue.</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars((string) $error) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= htmlspecialchars(url_path('/signup')) ?>" class="d-grid gap-3">
                        <div>
                            <label class="form-label">Role</label>
                            <select name="role" id="signup-role" class="form-select form-select-lg">
                                <option value="member" <?= ($role ?? 'member') === 'member' ? 'selected' : '' ?>>Member</option>
                                <option value="admin" <?= ($role ?? 'member') === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Full name</label>
                            <input type="text" name="name" class="form-control form-control-lg" placeholder="Your full name" value="<?= htmlspecialchars((string) ($name ?? '')) ?>" required>
                        </div>
                        <div>
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control form-control-lg" placeholder="you@example.com" value="<?= htmlspecialchars((string) ($email ?? '')) ?>" required>
                        </div>
                        <div>
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control form-control-lg" placeholder="At least 8 characters" minlength="8" required>
                        </div>
                        <div>
                            <label class="form-label">Confirm password</label>
                            <input type="password" name="password_confirmation" class="form-control form-control-lg" placeholder="Repeat your password" minlength="8" required>
                        </div>
                        <div id="signup-admin-code-wrap" style="display: <?= ($role ?? 'member') === 'admin' ? 'block' : 'none' ?>;">
                            <label class="form-label">Admin Code</label>
                            <input type="password" name="admin_code" class="form-control form-control-lg" placeholder="Enter admin code">
                        </div>
                        <button type="submit" class="btn btn-dark btn-lg w-100">Create account</button>
                    </form>

                    <p class="text-center text-white-50 mt-4 mb-0">
                        Already have an account? <a href="<?= htmlspecialchars(url_path('/login')) ?>" class="fw-semibold text-info text-decoration-none">Log in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const roleSelect = document.getElementById('signup-role');
            const adminWrap = document.getElementById('signup-admin-code-wrap');
            if (!roleSelect || !adminWrap) return;
            const sync = () => {
                adminWrap.style.display = roleSelect.value === 'admin' ? 'block' : 'none';
            };
            roleSelect.addEventListener('change', sync);
            sync();
        })();
    </script>
</div>