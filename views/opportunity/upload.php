<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card hero-card p-4 p-md-5">
            <div class="card-body">
                <h1 class="display-6 fw-bold mb-3">Find the best AIESEC opportunity from your CV</h1>
                <p class="text-secondary mb-4">Upload a PDF CV and we will extract your skills, search opportunities, and rank the best matches.</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="<?= htmlspecialchars(url_path('/upload')) ?>" method="post" enctype="multipart/form-data" class="row g-4">

                    <!-- CV File -->
                    <div class="col-12">
                        <label for="cv_pdf" class="form-label fw-semibold">PDF CV</label>
                        <input type="file" name="cv_pdf" id="cv_pdf" class="form-control form-control-lg" accept="application/pdf" required>
                    </div>

                    <!-- Programme Filter -->
                    <div class="col-12">
                        <label class="form-label fw-semibold">Programme type</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php
                            $programmes = [
                                ''    => ['label' => 'All programmes',      'desc' => 'GV + GT combined',          'icon' => '🔎'],
                                'GV'  => ['label' => 'Global Volunteer',    'desc' => '4–8 weeks, volunteer work', 'icon' => '🌍'],
                                'GTa' => ['label' => 'Global Talent',       'desc' => 'Paid internships abroad',   'icon' => '💼'],
                                'GTe' => ['label' => 'Global Teacher',      'desc' => 'Teaching exchange',         'icon' => '🎓'],
                            ];
                            foreach ($programmes as $val => $info): ?>
                                <label class="prog-opt" style="cursor:pointer; flex:1; min-width:140px; max-width:200px;">
                                    <input type="radio" name="programme_filter" value="<?= htmlspecialchars($val) ?>"
                                           class="d-none prog-radio" <?= $val === '' ? 'checked' : '' ?>>
                                    <div class="prog-pill border rounded-3 p-3 text-center h-100">
                                        <div style="font-size:1.5rem; line-height:1; margin-bottom:4px;"><?= $info['icon'] ?></div>
                                        <div class="fw-semibold small"><?= htmlspecialchars($info['label']) ?></div>
                                        <div class="text-secondary" style="font-size:0.7rem; margin-top:2px;"><?= htmlspecialchars($info['desc']) ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Country Filter -->
                    <div class="col-md-6">
                        <label for="country_filter" class="form-label fw-semibold">Country (optional)</label>
                        <select id="country_filter" name="country_filter" class="form-select">
                            <option value="">🌐 All countries</option>
                            <?php
                            $aiesecCountries = [
                                'Albania','Algeria','Argentina','Armenia','Australia','Austria',
                                'Azerbaijan','Bahrain','Bangladesh','Belarus','Belgium','Benin',
                                'Bolivia','Bosnia and Herzegovina','Botswana','Brazil','Bulgaria',
                                'Burkina Faso','Cameroon','Canada','Chile','China','Colombia',
                                'Costa Rica','Côte d\'Ivoire','Croatia','Czech Republic',
                                'Democratic Republic of Congo','Denmark','Dominican Republic',
                                'Ecuador','Egypt','El Salvador','Estonia','Ethiopia','Finland',
                                'France','Georgia','Germany','Ghana','Greece','Guatemala','Guinea',
                                'Honduras','Hong Kong','Hungary','Iceland','India','Indonesia',
                                'Iran','Iraq','Ireland','Israel','Italy','Jamaica','Japan','Jordan',
                                'Kazakhstan','Kenya','Kosovo','Kuwait','Kyrgyzstan','Latvia',
                                'Lebanon','Liberia','Libya','Lithuania','Luxembourg','Madagascar',
                                'Malawi','Malaysia','Mali','Malta','Mauritius','Mexico','Moldova',
                                'Mongolia','Montenegro','Morocco','Mozambique','Myanmar','Nepal',
                                'Netherlands','New Zealand','Nicaragua','Niger','Nigeria',
                                'North Macedonia','Norway','Oman','Pakistan','Palestine','Panama',
                                'Paraguay','Peru','Philippines','Poland','Portugal','Qatar',
                                'Romania','Russia','Rwanda','Saudi Arabia','Senegal','Serbia',
                                'Sierra Leone','Singapore','Slovakia','Slovenia','South Africa',
                                'South Korea','Spain','Sri Lanka','Sudan','Sweden','Switzerland',
                                'Syria','Taiwan','Tajikistan','Tanzania','Thailand','Togo',
                                'Trinidad and Tobago','Tunisia','Turkey','Uganda','Ukraine',
                                'United Arab Emirates','United Kingdom','United States',
                                'Uruguay','Uzbekistan','Venezuela','Vietnam','Zambia','Zimbabwe',
                            ];
                            foreach ($aiesecCountries as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Duration Filter -->
                    <div class="col-md-6">
                        <label for="duration_filter" class="form-label fw-semibold">Duration (optional)</label>
                        <select id="duration_filter" name="duration_filter" class="form-select">
                            <option value="">All durations</option>
                            <?php $opts = $filterOptions ?? ['durations'=>[]]; ?>
                            <?php foreach ($opts['durations'] as $d): ?>
                                <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Submit -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            Analyze CV &amp; Find Opportunities
                        </button>
                    </div>

                </form>

                <div class="mt-4 small text-secondary">
                    <strong>Supported:</strong> PDF only, up to <?= number_format(($config['max_upload_size'] ?? 0) / 1024 / 1024, 0) ?> MB.
                </div>
                <div class="mt-3">
                    <a class="btn btn-outline-light" href="<?= htmlspecialchars(url_path('/cv-builder')) ?>">Open CV Builder</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.prog-pill {
    background: rgba(255,255,255,0.04);
    border-color: rgba(255,255,255,0.12) !important;
    transition: all 0.18s ease;
}
.prog-pill:hover {
    border-color: rgba(99,102,241,0.55) !important;
    background: rgba(99,102,241,0.08);
}
.prog-radio:checked + .prog-pill {
    border-color: #6366f1 !important;
    background: rgba(99,102,241,0.18) !important;
    color: #c7d2fe;
}
.prog-radio:checked + .prog-pill .text-secondary {
    color: #a5b4fc !important;
}
</style>

<script>
// Highlight selected programme pill on page load (for the checked radio)
document.querySelectorAll('.prog-radio').forEach(function(r) {
    if (r.checked) r.dispatchEvent(new Event('change'));
    r.addEventListener('change', function() {
        // CSS :checked handles styling, no JS needed
    });
});
</script>
