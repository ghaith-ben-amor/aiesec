<div class="row g-4">
    <div class="col-12">
        <h1 class="h3 fw-bold mb-1">Dashboard</h1>
        <p class="text-secondary mb-0">Recent CV uploads, opportunities, and match records.</p>
    </div>

    <div class="col-lg-4">
        <div class="card hero-card h-100">
            <div class="card-body">
                <h2 class="h5 fw-semibold">Recent CVs</h2>
                <?php foreach ($cvs as $cv): ?>
                    <div class="border-bottom py-2 small"><?= htmlspecialchars($cv['file_path']) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card hero-card h-100">
            <div class="card-body">
                <h2 class="h5 fw-semibold">Recent Opportunities</h2>
                <?php foreach ($opportunities as $opportunity): ?>
                    <div class="border-bottom py-2 small">
                        <div class="fw-semibold"><?= htmlspecialchars($opportunity['title']) ?></div>
                        <div class="text-secondary"><?= htmlspecialchars($opportunity['location']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card hero-card h-100">
            <div class="card-body">
                <h2 class="h5 fw-semibold">Recent Matches</h2>
                <?php foreach ($matches as $match): ?>
                    <div class="border-bottom py-2 small">
                        <div class="fw-semibold"><?= htmlspecialchars($match['title'] ?? 'Opportunity') ?></div>
                        <div class="text-secondary"><?= htmlspecialchars((string) ($match['score'] ?? 0)) ?>%</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
