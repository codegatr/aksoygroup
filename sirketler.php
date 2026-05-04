<?php
/**
 * AKSOY GROUP — Tüm Şirketler
 * Path: /sirketler
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$sektorSlug = $_GET['sektor'] ?? '';
$where = "s.durum = 'aktif'";
$params = [];
$selectedSektor = null;

if ($sektorSlug) {
    $selectedSektor = DB::row("SELECT * FROM ag_sektorler WHERE slug = ?", [$sektorSlug]);
    if ($selectedSektor) {
        $where .= " AND s.sektor_id = ?";
        $params[] = $selectedSektor['id'];
    }
}

$sirketler = DB::all("SELECT s.*, sk.ad AS sektor_ad, sk.slug AS sektor_slug, sk.roman_no
                      FROM ag_sirketler s
                      LEFT JOIN ag_sektorler sk ON s.sektor_id = sk.id
                      WHERE $where
                      ORDER BY sk.sort_order ASC, s.sort_order ASC", $params);

$sektorler = DB::all("SELECT sk.*, COUNT(c.id) AS adet
                      FROM ag_sektorler sk
                      LEFT JOIN ag_sirketler c ON c.sektor_id = sk.id AND c.durum = 'aktif'
                      WHERE sk.is_active = 1
                      GROUP BY sk.id
                      HAVING adet > 0
                      ORDER BY sk.sort_order");

$page = [
    'title'       => $selectedSektor ? $selectedSektor['ad'] . ' Şirketleri' : 'Tüm Şirketler',
    'description' => 'Aksoy Group iştirakleri — bağımsız ama uyumlu çalışan grup şirketleri.',
];
require_once __DIR__ . '/includes/templates/header.php';
?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Ana Sayfa</a> ·
            <?php if ($selectedSektor): ?>
                <a href="/sirketler">Şirketler</a> · <?= h($selectedSektor['ad']) ?>
            <?php else: ?>
                Şirketler
            <?php endif; ?>
        </div>
        <h1><?= h($selectedSektor ? $selectedSektor['ad'] . ' İştirakleri' : 'İştiraklerimiz') ?></h1>
        <p class="lead">
            <?= $selectedSektor
                ? 'Aksoy Group ' . h($selectedSektor['ad']) . ' sektöründeki şirketleri.'
                : 'Topluluğumuz altında bağımsız markalar olarak faaliyet gösteren grup şirketleri.' ?>
        </p>
    </div>
</section>

<section class="content-section">
    <div class="container">
        <!-- Sektör filtresi -->
        <?php if ($sektorler): ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:48px;justify-content:center">
            <a href="/sirketler" class="btn <?= !$selectedSektor ? 'primary' : 'outline' ?>" style="padding:8px 18px;font-size:11px">Tümü</a>
            <?php foreach ($sektorler as $sk): ?>
            <a href="/sirketler?sektor=<?= ha($sk['slug']) ?>"
               class="btn <?= ($selectedSektor['id'] ?? 0) === $sk['id'] ? 'primary' : 'outline' ?>"
               style="padding:8px 18px;font-size:11px">
                <?= h($sk['roman_no']) ?>. <?= h($sk['ad']) ?>
                <span style="opacity:.7;margin-left:4px">(<?= (int)$sk['adet'] ?>)</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Şirket grid -->
        <?php if ($sirketler): ?>
        <div class="companies">
            <?php foreach ($sirketler as $c): ?>
            <a href="/sirket/<?= ha($c['slug']) ?>" class="company-card">
                <div class="logo-wrap">
                    <?php if (!empty($c['logo'])): ?>
                        <img src="<?= h(uploadUrl($c['logo'])) ?>" alt="<?= ha($c['kisa_unvan']) ?>">
                    <?php else: ?>
                        <span class="initial"><?= h(mb_substr($c['kisa_unvan'] ?? $c['unvan'], 0, 1)) ?></span>
                    <?php endif; ?>
                </div>
                <h4><?= h($c['kisa_unvan'] ?? $c['unvan']) ?></h4>
                <?php if (!empty($c['slogan'])): ?>
                    <div class="slogan">"<?= h($c['slogan']) ?>"</div>
                <?php endif; ?>
                <?php if (!empty($c['sektor_ad'])): ?>
                    <span class="sector-tag"><?= h($c['roman_no']) ?>. <?= h($c['sektor_ad']) ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:80px 20px">
            <div class="serif" style="font-size:120px;font-weight:200;color:var(--gold-dark);line-height:1">?</div>
            <p style="color:var(--text-soft);margin-top:24px">Bu sektörde henüz şirket kaydı yok.</p>
            <a href="/sirketler" class="btn outline" style="margin-top:32px">Tüm Şirketler</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/templates/footer.php'; ?>
