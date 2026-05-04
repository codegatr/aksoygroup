<?php
/**
 * AKSOY GROUP — Sektör Detay
 * Path: /sektor/{slug}
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) { http_response_code(404); exit('Sektör bulunamadı.'); }

$sektor = DB::row("SELECT * FROM ag_sektorler WHERE slug = ? AND is_active = 1", [$slug]);
if (!$sektor) {
    http_response_code(404);
    require_once __DIR__ . '/../includes/templates/header.php';
    echo '<section class="section" style="text-align:center;padding-top:200px"><div class="container"><h1 class="serif" style="font-size:64px;font-weight:200;color:var(--gold)">404</h1><p class="muted" style="margin-top:16px">Sektör bulunamadı.</p><a href="/sektorler" class="btn outline" style="margin-top:32px">Tüm Sektörler</a></div></section>';
    require_once __DIR__ . '/../includes/templates/footer.php';
    exit;
}

// Bu sektörün şirketleri
$sirketler = DB::all("SELECT * FROM ag_sirketler
                      WHERE sektor_id = ? AND durum = 'aktif'
                      ORDER BY sort_order ASC", [$sektor['id']]);

// Bu sektörle ilgili haberler
$haberler = DB::all("SELECT h.*, k.ad AS kategori_ad
                     FROM ag_haberler h
                     LEFT JOIN ag_haber_kategori k ON h.kategori_id = k.id
                     LEFT JOIN ag_sirketler s ON h.sirket_id = s.id
                     WHERE s.sektor_id = ? AND h.is_active = 1
                     ORDER BY COALESCE(h.yayim_tarihi, h.created_at) DESC LIMIT 3", [$sektor['id']]);

$page = [
    'title'       => $sektor['ad'],
    'description' => $sektor['kisa_aciklama'] ?? $sektor['ad'] . ' — Aksoy Group sektörel faaliyeti',
];
require_once __DIR__ . '/../includes/templates/header.php';
?>

<!-- ════ HERO ════ -->
<section class="page-hero" style="<?= $sektor['vurgu_renk'] ? '--accent:' . h($sektor['vurgu_renk']) : '' ?>">
    <div class="roman-bg"><?= h($sektor['roman_no']) ?></div>
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Ana Sayfa</a> · <a href="/sektorler">Sektörler</a> · <?= h($sektor['ad']) ?>
        </div>
        <div style="display:flex;align-items:baseline;gap:24px;flex-wrap:wrap">
            <span class="serif" style="font-size:48px;font-weight:300;color:var(--gold);line-height:1"><?= h($sektor['roman_no']) ?>.</span>
            <h1 style="margin-bottom:0"><?= h($sektor['ad']) ?></h1>
        </div>
        <?php if (!empty($sektor['alt_baslik'])): ?>
        <div style="font-family:'Fraunces',serif;font-style:italic;color:var(--gold);font-size:22px;margin-top:12px"><?= h($sektor['alt_baslik']) ?></div>
        <?php endif; ?>
        <?php if (!empty($sektor['kisa_aciklama'])): ?>
        <p class="lead" style="margin-top:24px"><?= h($sektor['kisa_aciklama']) ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- ════ İÇERİK + YAN PANEL ════ -->
<section class="content-section">
    <div class="container">
        <div class="content-grid">
            <article class="prose">
                <?php if (!empty($sektor['icerik'])): ?>
                    <?= $sektor['icerik'] ?>
                <?php else: ?>
                    <h2><?= h($sektor['ad']) ?></h2>
                    <p>Bu sektörde Aksoy Group; sürdürülebilir yatırım, kalite odaklı üretim ve uzun vadeli müşteri ilişkileri ilkeleriyle faaliyet göstermektedir.</p>
                    <p>Sektördeki iştiraklerimiz, kendi alanlarında yenilikçi çözümler üretmek ve müşterilerimize ölçeklenebilir hizmetler sunmak için çalışmaktadır.</p>
                <?php endif; ?>

                <?php if ($haberler): ?>
                <h2 style="margin-top:64px">Bu Sektörden Haberler</h2>
                <div class="news-grid" style="margin-top:24px">
                    <?php foreach ($haberler as $hb): ?>
                    <a href="/haber/<?= ha($hb['slug']) ?>" class="news-card">
                        <?php if (!empty($hb['kapak_gorsel'])): ?>
                        <div class="cover"><img src="<?= h(uploadUrl($hb['kapak_gorsel'])) ?>" alt="<?= ha($hb['baslik']) ?>"></div>
                        <?php endif; ?>
                        <div class="body">
                            <div class="meta"><?= h($hb['kategori_ad'] ?? '—') ?> · <?= h(formatDate($hb['yayim_tarihi'] ?? $hb['created_at'])) ?></div>
                            <h4 style="font-size:18px"><?= h($hb['baslik']) ?></h4>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </article>

            <aside>
                <?php if ($sirketler): ?>
                <div class="info-card">
                    <h4>Bu Sektördeki Şirketler</h4>
                    <ul>
                        <?php foreach ($sirketler as $s): ?>
                        <li>
                            <a href="/sirket/<?= ha($s['slug']) ?>" class="val" style="text-align:left;flex:1">
                                <strong><?= h($s['kisa_unvan'] ?? $s['unvan']) ?></strong>
                                <?php if (!empty($s['slogan'])): ?>
                                    <div style="font-size:12px;color:var(--text-mute);font-style:italic;margin-top:2px"><?= h($s['slogan']) ?></div>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="info-card">
                    <h4>İlgileniyor musunuz?</h4>
                    <p style="color:var(--text-soft);font-size:14px;line-height:1.7;margin-bottom:18px">
                        Bu sektörde <?= h($sektor['ad']) ?> alanında işbirliği, tedarik veya iş ortaklığı için iletişime geçin.
                    </p>
                    <a href="/iletisim" class="btn primary" style="width:100%;justify-content:center">İletişime Geç</a>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/templates/footer.php'; ?>
