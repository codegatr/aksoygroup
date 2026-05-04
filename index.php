<?php
/**
 * AKSOY GROUP — Ana Sayfa
 * Hero · Sektörler · Şirketler vitrin · Haberler · Zaman çizgisi · İletişim CTA
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

// ── VERİ ──────────────────────────────────────────
$sektorler = DB::all("SELECT * FROM ag_sektorler WHERE is_active = 1 ORDER BY sort_order ASC");
$featuredSektorler = array_filter($sektorler, fn($s) => (int)$s['is_featured'] === 1);
$rakamlar = DB::all("SELECT * FROM ag_rakamlar WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 4");
$featuredCompanies = DB::all("SELECT s.*, sk.ad AS sektor_ad
                              FROM ag_sirketler s
                              LEFT JOIN ag_sektorler sk ON s.sektor_id = sk.id
                              WHERE s.is_featured = 1 AND s.durum = 'aktif'
                              ORDER BY s.sort_order ASC LIMIT 6");
$haberler = DB::all("SELECT h.*, k.ad AS kategori_ad
                     FROM ag_haberler h
                     LEFT JOIN ag_haber_kategori k ON h.kategori_id = k.id
                     WHERE h.is_active = 1 AND (h.yayim_tarihi IS NULL OR h.yayim_tarihi <= NOW())
                     ORDER BY COALESCE(h.yayim_tarihi, h.created_at) DESC LIMIT 3");
$zaman = DB::all("SELECT * FROM ag_zaman_cizgisi WHERE is_active = 1 ORDER BY yil ASC");

$page = [
    'title' => null, // ana sayfada title sadece site title
    'description' => setting('site_description', ''),
];

require_once __DIR__ . '/includes/templates/header.php';
?>

<!-- ════════ HERO ════════ -->
<section class="hero">
    <div class="container hero-content">
        <div class="pretitle fade-up"><?= h(setting('site_tagline', 'Hizmetler Topluluğu')) ?></div>
        <h1 class="fade-up delay-1">
            On sektör.<br>
            Tek <em>vizyon</em>.<br>
            Sınırsız ufuk.
        </h1>
        <p class="lead fade-up delay-2"><?= h(setting('site_description', '')) ?></p>
        <div class="hero-cta fade-up delay-3">
            <a href="/sektorler" class="btn primary">Sektörlerimizi Keşfet</a>
            <a href="/iletisim" class="btn outline">Bize Ulaşın</a>
        </div>

        <?php if ($rakamlar): ?>
        <div class="hero-stats">
            <?php foreach ($rakamlar as $r): ?>
            <div class="hero-stat">
                <div class="num"><?= h(($r['onek'] ?? '') . $r['deger'] . ($r['sufiks'] ?? '')) ?></div>
                <div class="lbl"><?= h($r['etiket']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ════════ SEKTÖRLER ════════ -->
<section class="section" id="sektorler">
    <div class="container">
        <div class="section-head">
            <span class="pretitle">Faaliyet Alanları</span>
            <h2>Demir-çeliğin gücü, <em style="color:var(--gold)">yazılımın zekâsı</em></h2>
            <p class="lead">Endüstriyel üretimden dijital teknolojiye, sigortadan lojistiğe — on sektörde stratejik yatırımlar.</p>
        </div>

        <div class="sectors">
            <?php foreach ($sektorler as $s): ?>
            <a href="/sektor/<?= ha($s['slug']) ?>" class="sector-card" style="<?= $s['vurgu_renk'] ? '--accent:' . h($s['vurgu_renk']) : '' ?>">
                <div class="roman"><?= h($s['roman_no']) ?></div>
                <h3><?= h($s['ad']) ?></h3>
                <div class="alt"><?= h($s['alt_baslik'] ?? '') ?></div>
                <div class="desc"><?= h(truncate($s['kisa_aciklama'] ?? '', 140)) ?></div>
                <span class="arrow">İncele
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ════════ İŞTİRAKLER VİTRİNİ ════════ -->
<?php if ($featuredCompanies): ?>
<section class="section alt">
    <div class="container">
        <div class="section-head">
            <span class="pretitle">İştiraklerimiz</span>
            <h2>Topluluk altında, <em style="color:var(--gold)">bağımsız markalar</em></h2>
            <p class="lead">Her biri kendi sektöründe lider olmaya odaklanmış grup şirketlerimiz.</p>
        </div>

        <div class="companies">
            <?php foreach ($featuredCompanies as $c): ?>
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
                    <span class="sector-tag"><?= h($c['sektor_ad']) ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-lg">
            <a href="/sirketler" class="btn outline">Tüm Şirketler</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ════════ HABERLER ════════ -->
<?php if ($haberler): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="pretitle">Topluluktan Haberler</span>
            <h2>Son <em style="color:var(--gold)">gelişmeler</em></h2>
        </div>

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

        <div class="text-center mt-lg">
            <a href="/haberler" class="btn outline">Tüm Haberler</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ════════ ZAMAN ÇİZGİSİ ════════ -->
<?php if ($zaman): ?>
<section class="section alt">
    <div class="container">
        <div class="section-head">
            <span class="pretitle">Tarihçe</span>
            <h2>Bir <em style="color:var(--gold)">topluluk</em> nasıl doğar?</h2>
        </div>

        <div class="timeline">
            <?php foreach ($zaman as $t): ?>
            <div class="timeline-item">
                <div class="yil"><?= h((string)$t['yil']) ?></div>
                <h4><?= h($t['baslik']) ?></h4>
                <p><?= h($t['aciklama']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ════════ İLETİŞİM CTA ════════ -->
<section class="section dark" style="text-align:center">
    <div class="container">
        <div class="section-head">
            <span class="pretitle">Birlikte Üretelim</span>
            <h2>
                Yatırım, ortaklık ya da işbirliği — <em>konuşalım</em>.
            </h2>
            <p class="lead">
                On sektörde geleceği şekillendiren bir hizmetler topluluğunun parçası olun.
            </p>
        </div>
        <a href="/iletisim" class="btn primary">İletişime Geç</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/templates/footer.php'; ?>
