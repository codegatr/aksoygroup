<?php
/**
 * AKSOY GROUP — Haber Listesi
 * Path: /haberler veya /haber/{slug} (detay haber/detay.php'de)
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$kategoriSlug = $_GET['kategori'] ?? '';
$page_no = max(1, (int)($_GET['p'] ?? 1));
$perPage = 12;
$offset = ($page_no - 1) * $perPage;

$where = "h.is_active = 1 AND (h.yayim_tarihi IS NULL OR h.yayim_tarihi <= NOW())";
$params = [];
$selectedKategori = null;

if ($kategoriSlug) {
    $selectedKategori = DB::row("SELECT * FROM ag_haber_kategori WHERE slug = ?", [$kategoriSlug]);
    if ($selectedKategori) {
        $where .= " AND h.kategori_id = ?";
        $params[] = $selectedKategori['id'];
    }
}

$total = (int)DB::scalar("SELECT COUNT(*) FROM ag_haberler h WHERE $where", $params);
$totalPages = max(1, (int)ceil($total / $perPage));

$haberler = DB::all("SELECT h.*, k.ad AS kategori_ad, k.slug AS kategori_slug
                     FROM ag_haberler h
                     LEFT JOIN ag_haber_kategori k ON h.kategori_id = k.id
                     WHERE $where
                     ORDER BY COALESCE(h.yayim_tarihi, h.created_at) DESC
                     LIMIT $perPage OFFSET $offset", $params);

$kategoriler = DB::all("SELECT k.*, COUNT(h.id) AS adet
                        FROM ag_haber_kategori k
                        LEFT JOIN ag_haberler h ON h.kategori_id = k.id AND h.is_active = 1
                        WHERE k.is_active = 1
                        GROUP BY k.id
                        ORDER BY k.sort_order");

$page = [
    'title'       => $selectedKategori ? $selectedKategori['ad'] . ' Haberleri' : 'Haberler',
    'description' => 'Aksoy Group ve iştiraklerinden son haberler, basın bültenleri ve kurumsal duyurular.',
];
require_once __DIR__ . '/../includes/templates/header.php';
?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Ana Sayfa</a> ·
            <?php if ($selectedKategori): ?>
                <a href="/haberler">Haberler</a> · <?= h($selectedKategori['ad']) ?>
            <?php else: ?>
                Haberler
            <?php endif; ?>
        </div>
        <h1><?= h($selectedKategori ? $selectedKategori['ad'] . ' Haberleri' : 'Haberler & Basın') ?></h1>
        <p class="lead">
            <?= $selectedKategori
                ? h($selectedKategori['aciklama'] ?? '')
                : 'Topluluğumuzdan son gelişmeler, basın bültenleri, sektörel duyurular.' ?>
        </p>
    </div>
</section>

<section class="content-section">
    <div class="container">
        <!-- Kategori filtresi -->
        <?php if ($kategoriler): ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:48px;justify-content:center">
            <a href="/haberler" class="btn <?= !$selectedKategori ? 'primary' : 'outline' ?>" style="padding:8px 18px;font-size:11px">Tümü</a>
            <?php foreach ($kategoriler as $k): ?>
            <a href="/haberler?kategori=<?= ha($k['slug']) ?>"
               class="btn <?= ($selectedKategori['id'] ?? 0) === $k['id'] ? 'primary' : 'outline' ?>"
               style="padding:8px 18px;font-size:11px">
                <?= h($k['ad']) ?>
                <?php if ($k['adet']): ?>
                    <span style="opacity:.7;margin-left:6px">(<?= (int)$k['adet'] ?>)</span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Haber grid -->
        <?php if ($haberler): ?>
        <div class="news-grid">
            <?php foreach ($haberler as $hb): ?>
            <a href="/haber/<?= ha($hb['slug']) ?>" class="news-card">
                <?php if (!empty($hb['kapak_gorsel'])): ?>
                <div class="cover"><img src="<?= h(uploadUrl($hb['kapak_gorsel'])) ?>" alt="<?= ha($hb['baslik']) ?>"></div>
                <?php else: ?>
                <div class="cover" style="display:flex;align-items:center;justify-content:center;color:var(--gold-dark);font-family:'Fraunces',serif;font-weight:200;font-size:80px">A</div>
                <?php endif; ?>
                <div class="body">
                    <div class="meta">
                        <?= h($hb['kategori_ad'] ?? 'Kurumsal') ?>
                        · <?= h(formatDate($hb['yayim_tarihi'] ?? $hb['created_at'])) ?>
                    </div>
                    <h4><?= h($hb['baslik']) ?></h4>
                    <p class="ozet"><?= h(truncate($hb['ozet'] ?? '', 130)) ?></p>
                    <span class="read-more">Devamı
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div style="display:flex;justify-content:center;gap:8px;margin-top:64px;flex-wrap:wrap">
            <?php if ($page_no > 1): ?>
            <a href="?<?= http_build_query(array_filter(['kategori' => $kategoriSlug, 'p' => $page_no - 1])) ?>"
               class="btn outline" style="padding:10px 18px;font-size:11px">← Önceki</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if (abs($i - $page_no) <= 2 || $i === 1 || $i === $totalPages): ?>
                    <a href="?<?= http_build_query(array_filter(['kategori' => $kategoriSlug, 'p' => $i])) ?>"
                       class="btn <?= $i === $page_no ? 'primary' : 'outline' ?>"
                       style="padding:10px 16px;font-size:12px;min-width:42px;justify-content:center"><?= $i ?></a>
                <?php elseif (abs($i - $page_no) === 3): ?>
                    <span style="padding:10px;color:var(--text-mute)">…</span>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page_no < $totalPages): ?>
            <a href="?<?= http_build_query(array_filter(['kategori' => $kategoriSlug, 'p' => $page_no + 1])) ?>"
               class="btn outline" style="padding:10px 18px;font-size:11px">Sonraki →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div style="text-align:center;padding:80px 20px">
            <div class="serif" style="font-size:120px;font-weight:200;color:var(--gold-dark);line-height:1">?</div>
            <p style="color:var(--text-soft);margin-top:24px">Bu kategoride henüz haber yok.</p>
            <a href="/haberler" class="btn outline" style="margin-top:32px">Tüm Haberlere Dön</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/templates/footer.php'; ?>
