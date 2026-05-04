<?php
/**
 * AKSOY GROUP — Dinamik Sayfa
 * Path: /sayfa/{slug} veya /{slug}
 * ag_pages tablosundan içerik çeker.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) { http_response_code(404); exit; }

$sayfa = DB::row("SELECT * FROM ag_pages WHERE slug = ? AND is_active = 1", [$slug]);
if (!$sayfa) {
    http_response_code(404);
    require_once __DIR__ . '/../includes/templates/header.php';
    echo '<section class="section" style="text-align:center;padding-top:200px"><div class="container"><h1 class="serif" style="font-size:64px;font-weight:200;color:var(--gold)">404</h1><p>Sayfa bulunamadı.</p><a href="/" class="btn outline" style="margin-top:32px">Ana Sayfa</a></div></section>';
    require_once __DIR__ . '/../includes/templates/footer.php';
    exit;
}

$page = [
    'title'       => $sayfa['baslik'],
    'description' => $sayfa['meta_description'] ?? truncate(strip_tags($sayfa['icerik'] ?? ''), 160),
];
require_once __DIR__ . '/../includes/templates/header.php';
?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="/">Ana Sayfa</a> · <?= h($sayfa['baslik']) ?></div>
        <h1><?= h($sayfa['baslik']) ?></h1>
        <?php if (!empty($sayfa['alt_baslik'])): ?>
        <p class="lead"><?= h($sayfa['alt_baslik']) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="content-section">
    <div class="container" style="max-width:840px">
        <article class="prose">
            <?= $sayfa['icerik'] ?? '' ?>
        </article>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/templates/footer.php'; ?>
