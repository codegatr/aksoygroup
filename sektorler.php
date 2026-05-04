<?php
/**
 * AKSOY GROUP — Tüm Sektörler
 * Path: /sektorler
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$sektorler = DB::all("SELECT s.*, COUNT(c.id) AS sirket_sayisi
                      FROM ag_sektorler s
                      LEFT JOIN ag_sirketler c ON c.sektor_id = s.id AND c.durum = 'aktif'
                      WHERE s.is_active = 1
                      GROUP BY s.id
                      ORDER BY s.sort_order ASC");

$page = [
    'title'       => 'Sektörler',
    'description' => 'Aksoy Group — On sektörde faaliyet gösteren çok yönlü hizmetler topluluğu.',
];
require_once __DIR__ . '/includes/templates/header.php';
?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="/">Ana Sayfa</a> · Sektörler</div>
        <h1>On sektör. Tek topluluk.</h1>
        <p class="lead">
            Demir-çelikten yazılıma, sigortadan lojistiğe — Aksoy Group'un on sektörel
            faaliyet alanı, her biri kendi alanında özelleşmiş, ortak bir vizyonla yönetilen
            stratejik yatırımlardır.
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="sectors">
            <?php foreach ($sektorler as $s): ?>
            <a href="/sektor/<?= ha($s['slug']) ?>" class="sector-card">
                <div class="roman"><?= h($s['roman_no']) ?></div>
                <h3><?= h($s['ad']) ?></h3>
                <div class="alt"><?= h($s['alt_baslik'] ?? '') ?></div>
                <div class="desc"><?= h(truncate($s['kisa_aciklama'] ?? '', 140)) ?></div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px">
                    <span class="arrow">İncele
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </span>
                    <?php if ((int)$s['sirket_sayisi']): ?>
                    <span style="font-size:11px;color:var(--text-mute);letter-spacing:.1em">
                        <?= (int)$s['sirket_sayisi'] ?> şirket
                    </span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/templates/footer.php'; ?>
