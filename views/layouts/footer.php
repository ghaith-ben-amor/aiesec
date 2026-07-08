    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const storageKey = 'aiesec-theme';
            const body = document.body;
            const toggle = document.getElementById('theme-toggle');
            const label = document.getElementById('theme-toggle-label');

            if (!body || !toggle || !label) return;

            const applyTheme = (theme) => {
                body.setAttribute('data-theme', theme);
                const isLight = theme === 'light';
                label.textContent = isLight ? 'Dark mode' : 'Light mode';
                toggle.setAttribute('aria-pressed', String(isLight));
            };

            const savedTheme = localStorage.getItem(storageKey);
            const prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
            applyTheme(savedTheme === 'light' || savedTheme === 'dark' ? savedTheme : (prefersLight ? 'light' : 'dark'));

            toggle.addEventListener('click', function () {
                const nextTheme = body.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
                localStorage.setItem(storageKey, nextTheme);
                applyTheme(nextTheme);
            });
        })();
    </script>
</body>
</html>
