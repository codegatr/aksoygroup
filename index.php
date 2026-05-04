<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$sektorler = DB::all("SELECT * FROM ag_sektorler WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 10");
$featuredCompanies = DB::all("SELECT s.*, sk.ad AS sektor_ad, sk.slug AS sektor_slug
                              FROM ag_sirketler s
                              LEFT JOIN ag_sektorler sk ON s.sektor_id = sk.id
                              WHERE s.is_featured = 1 AND s.durum = 'aktif'
                              ORDER BY s.sort_order ASC LIMIT 6");
$haberler = DB::all("SELECT h.*, k.ad AS kategori_ad
                     FROM ag_haberler h
                     LEFT JOIN ag_haber_kategori k ON h.kategori_id = k.id
                     WHERE h.is_active = 1 AND (h.yayim_tarihi IS NULL OR h.yayim_tarihi <= NOW())
                     ORDER BY COALESCE(h.yayim_tarihi, h.created_at) DESC LIMIT 4");
$zaman = DB::all("SELECT * FROM ag_zaman_cizgisi WHERE is_active = 1 ORDER BY yil ASC");

// İstatistikler — DB'den dinamik
$stats = [
    'sektor_sayisi'   => (int)DB::scalar("SELECT COUNT(*) FROM ag_sektorler WHERE is_active = 1"),
    'sirket_sayisi'   => (int)DB::scalar("SELECT COUNT(*) FROM ag_sirketler WHERE durum = 'aktif'"),
    'calisan_sayisi'  => (int)setting('toplam_calisan', '500'),
    'kurulus_yili'    => (int)setting('grup_kurulus_yili', '1992'),
];
$grupYasi = max(1, (int)date('Y') - $stats['kurulus_yili']);

$page = [
    'title'       => null,
    'description' => setting('site_description', 'Aksoy Group — On sektörde faaliyet gösteren Türkiye merkezli hizmetler topluluğu.'),
];
require_once __DIR__ . '/includes/templates/header.php';
?>

<!-- ════════════════════════════════════════════════════
     HERO SLIDER — 5 frame, otomatik geçiş
     ════════════════════════════════════════════════════ -->
<section class="hero-slider" id="heroSlider">

    <!-- Slide 1: Manifesto -->
    <div class="hero-slide active" data-slide="1">
        <div class="hero-slide-bg" style="background:
            radial-gradient(ellipse at 20% 80%, rgba(184,151,93,.25), transparent 50%),
            radial-gradient(ellipse at 80% 20%, rgba(15,44,79,.6), transparent 50%),
            linear-gradient(135deg, #0F2C4F 0%, #08172E 100%);"></div>
        <div class="container">
            <span class="pretitle">Aksoy Group</span>
            <h1>Yarattığı farkla büyüyen, <em>öncü ve saygın</em> bir Türkiye topluluğu.</h1>
            <p class="lead">On sektörde uzmanlaşmış iştiraklerimizle, Türkiye'nin köklü hizmetler topluluklarından biriyiz. <?= $grupYasi ?>+ yıllık deneyimle geleceği inşa ediyoruz.</p>
            <div class="hero-cta">
                <a href="/hakkimizda" class="btn primary">Topluluğu Tanıyın <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                <a href="/sektorler" class="btn outline">Faaliyet Alanlarımız</a>
            </div>
        </div>
    </div>

    <!-- Slide 2: Vizyon -->
    <div class="hero-slide" data-slide="2">
        <div class="hero-slide-bg" style="background:
            radial-gradient(ellipse at 70% 50%, rgba(111,26,46,.35), transparent 60%),
            linear-gradient(135deg, #08172E 0%, #1A3A5F 100%);"></div>
        <div class="container">
            <span class="pretitle">Vizyon</span>
            <h1>Endüstriyel derinlik, <em>dijital hız</em>.</h1>
            <p class="lead">Demir-çeliğin ağırlığını yazılımın çevikliğiyle birleştirdiğimiz, on farklı sektörde tek vizyon altında yürüyen bir hizmetler topluluğu.</p>
            <div class="hero-cta">
                <a href="/sirketler" class="btn primary">İştiraklerimiz <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
            </div>
        </div>
    </div>

    <!-- Slide 3: Sürdürülebilirlik -->
    <div class="hero-slide" data-slide="3">
        <div class="hero-slide-bg" style="background:
            radial-gradient(ellipse at 30% 30%, rgba(31,122,77,.30), transparent 60%),
            linear-gradient(135deg, #0F2C4F 0%, #1F3550 100%);"></div>
        <div class="container">
            <span class="pretitle">Sürdürülebilirlik</span>
            <h1>Bugünden geleceğe, <em>sorumlu üretim</em>.</h1>
            <p class="lead">Çevresel etkimizi azaltmak, sosyal dayanışmayı güçlendirmek ve kurumsal yönetişimi yükseltmek — sadece raporlamak için değil, gerçekten yaşamak için çalışıyoruz.</p>
            <div class="hero-cta">
                <a href="/surdurulebilirlik" class="btn primary">Yaklaşımımız <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
            </div>
        </div>
    </div>

    <!-- Slide 4: Yönetim Kurulu -->
    <div class="hero-slide" data-slide="4">
        <div class="hero-slide-bg" style="background:
            radial-gradient(ellipse at 80% 70%, rgba(184,151,93,.2), transparent 50%),
            linear-gradient(135deg, #1A3A5F 0%, #08172E 100%);"></div>
        <div class="container">
            <span class="pretitle">Liderlik</span>
            <h1>Vizyonun arkasındaki <em>liderlik kadrosu</em>.</h1>
            <p class="lead">Aksoy Group'u şekillendiren strateji, uzun vadeli yatırım vizyonu ve kurumsal yönetim disiplini — yönetim kurulu üyelerimizin deneyim ve birikiminin ürünüdür.</p>
            <div class="hero-cta">
                <a href="/yonetim-kurulu" class="btn primary">Yönetim Kurulu <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
            </div>
        </div>
    </div>

    <!-- Slide 5: Kariyer -->
    <div class="hero-slide" data-slide="5">
        <div class="hero-slide-bg" style="background:
            radial-gradient(ellipse at 50% 50%, rgba(184,151,93,.18), transparent 60%),
            linear-gradient(135deg, #0F2C4F 0%, #08172E 60%, #1A3A5F 100%);"></div>
        <div class="container">
            <span class="pretitle">Kariyer ve Yaşam</span>
            <h1>Geleceği <em>birlikte</em> inşa edelim.</h1>
            <p class="lead">500+ çalışan, 10 sektör, sayısız fırsat. Anadolu'nun üretim merkezi Konya'da, dijital iştiraklerimizle dünyaya açılan bir ailenin parçası olun.</p>
            <div class="hero-cta">
                <a href="/kariyer" class="btn primary">Kariyer Fırsatları <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
            </div>
        </div>
    </div>

    <!-- Slide indicator -->
    <div class="hero-indicator">
        <span class="num" id="heroIndicatorNum">01</span>
        <span class="total">— 05</span>
    </div>

    <!-- Kontroller -->
    <div class="hero-controls">
        <div class="hero-dots" id="heroDots">
            <button class="hero-dot active" data-go="1" aria-label="Slide 1"></button>
            <button class="hero-dot" data-go="2" aria-label="Slide 2"></button>
            <button class="hero-dot" data-go="3" aria-label="Slide 3"></button>
            <button class="hero-dot" data-go="4" aria-label="Slide 4"></button>
            <button class="hero-dot" data-go="5" aria-label="Slide 5"></button>
        </div>
        <div class="hero-arrows">
            <button class="hero-arrow" id="heroPrev" aria-label="Önceki">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
            <button class="hero-arrow" id="heroNext" aria-label="Sonraki">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
            </button>
        </div>
    </div>
</section>

<script>
(function() {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');
    const indicator = document.getElementById('heroIndicatorNum');
    let current = 0;
    let timer = null;

    function go(i) {
        slides.forEach((s, idx) => s.classList.toggle('active', idx === i));
        dots.forEach((d, idx) => d.classList.toggle('active', idx === i));
        indicator.textContent = String(i + 1).padStart(2, '0');
        current = i;
    }
    function next() { go((current + 1) % slides.length); }
    function prev() { go((current - 1 + slides.length) % slides.length); }
    function startAuto() { timer = setInterval(next, 6500); }
    function stopAuto() { clearInterval(timer); }

    dots.forEach((d, i) => d.addEventListener('click', () => { stopAuto(); go(i); startAuto(); }));
    document.getElementById('heroPrev').addEventListener('click', () => { stopAuto(); prev(); startAuto(); });
    document.getElementById('heroNext').addEventListener('click', () => { stopAuto(); next(); startAuto(); });

    // Keyboard
    document.addEventListener('keydown', e => {
        if (document.activeElement.tagName === 'INPUT') return;
        if (e.key === 'ArrowLeft') { stopAuto(); prev(); startAuto(); }
        if (e.key === 'ArrowRight') { stopAuto(); next(); startAuto(); }
    });

    startAuto();
})();
</script>

<!-- ════════════════════════════════════════════════════
     MANİFESTO BLOK
     ════════════════════════════════════════════════════ -->
<section class="manifesto">
    <div class="container">
        <div class="pretitle">Marka Manifestomuz</div>
        <div class="quote-mark">"</div>
        <h2>
            Anadolu'nun üretim derinliğini dijital çağın hızıyla harmanlayan;
            <em>kalite, güven ve sürdürülebilirlik</em> ilkeleriyle yarınları inşa eden bir hizmetler topluluğu olmak.
        </h2>
        <div class="signature">Aksoy Group · Marka Manifestomuz</div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════
     SAYILARLA AKSOY (dramatic stats block)
     ════════════════════════════════════════════════════ -->
<section class="stats-block">
    <div class="container">
        <span class="pretitle">Sayılarla Aksoy</span>
        <h3 class="stats-title">Bugünün <em style="color:var(--gold);font-style:italic">öncüsü</em>, yarının mimarı.</h3>
        <div class="stats-grid">
            <div class="stat-cell">
                <div class="num"><?= $stats['sektor_sayisi'] ?: 10 ?></div>
                <div class="lbl">Faaliyet Sektörü</div>
                <div class="desc">Demir-çelikten yazılıma uzanan derin uzmanlık</div>
            </div>
            <div class="stat-cell">
                <div class="num"><?= $stats['sirket_sayisi'] ?: 9 ?><em>+</em></div>
                <div class="lbl">İştirak Şirketi</div>
                <div class="desc">Topluluk altında bağımsız markalar</div>
            </div>
            <div class="stat-cell">
                <div class="num"><?= number_format($stats['calisan_sayisi'], 0, ',', '.') ?><em>+</em></div>
                <div class="lbl">Çalışan</div>
                <div class="desc">Türkiye'nin dört bir yanından profesyoneller</div>
            </div>
            <div class="stat-cell">
                <div class="num"><?= $grupYasi ?><em>+</em></div>
                <div class="lbl">Yıllık Deneyim</div>
                <div class="desc">Konya merkezli kuruluşumuzdan bu yana</div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════
     SEKTÖRLER (Faaliyet Grupları)
     ════════════════════════════════════════════════════ -->
<section class="section" id="sektorler">
    <div class="container">
        <div class="section-head">
            <span class="pretitle">Faaliyet Alanlarımız</span>
            <h2>Demir-çeliğin gücü, <em>yazılımın zekâsı</em></h2>
            <p class="lead">Geleneksel sektörlerin derinliğini dijital dönüşümün çevikliğiyle birleştiren on farklı uzmanlık alanı.</p>
        </div>
        <div class="sectors">
            <?php foreach ($sektorler as $s): ?>
            <a href="/sektor/<?= ha($s['slug']) ?>" class="sector-card">
                <div class="roman"><?= h($s['roman_no'] ?? 'I') ?></div>
                <h3><?= h($s['ad']) ?></h3>
                <?php if (!empty($s['alt_baslik'])): ?>
                <div class="alt"><?= h($s['alt_baslik']) ?></div>
                <?php endif; ?>
                <div class="desc"><?= h($s['kisa_aciklama'] ?? '') ?></div>
                <span class="arrow">Detay <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════
     İŞTİRAKLER VİTRİNİ
     ════════════════════════════════════════════════════ -->
<?php if ($featuredCompanies): ?>
<section class="section alt">
    <div class="container">
        <div class="section-head">
            <span class="pretitle">İştiraklerimiz</span>
            <h2>Topluluk altında, <em>bağımsız markalar</em></h2>
            <p class="lead">Her biri kendi sektöründe öncü, ortak değerler etrafında birleşen şirketlerimiz.</p>
        </div>
        <div class="companies">
            <?php foreach ($featuredCompanies as $c): ?>
            <a href="/sirket/<?= ha($c['slug']) ?>" class="company-card">
                <div class="logo-wrap">
                    <?php if (!empty($c['logo'])): ?>
                        <img src="<?= h(uploadUrl($c['logo'])) ?>" alt="<?= ha($c['kisa_unvan'] ?? $c['unvan']) ?>">
                    <?php else: ?>
                        <span class="initial"><?= h(strtoupper(mb_substr($c['kisa_unvan'] ?? $c['unvan'], 0, 1))) ?></span>
                    <?php endif; ?>
                </div>
                <h4><?= h($c['kisa_unvan'] ?? $c['unvan']) ?></h4>
                <?php if (!empty($c['slogan'])): ?>
                <div class="slogan"><?= h($c['slogan']) ?></div>
                <?php endif; ?>
                <?php if (!empty($c['sektor_ad'])): ?>
                <div class="sector-tag"><?= h($c['sektor_ad']) ?></div>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:48px">
            <a href="/sirketler" class="btn outline">Tüm İştiraklerimiz <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════
     HABERLER
     ════════════════════════════════════════════════════ -->
<?php if ($haberler): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="pretitle">Bizden Haberler</span>
            <h2>Son <em>gelişmeler</em></h2>
        </div>
        <div class="news-grid">
            <?php foreach (array_slice($haberler, 0, 4) as $h): ?>
            <a href="/haber/<?= ha($h['slug']) ?>" class="news-card">
                <?php if (!empty($h['kapak_gorsel'])): ?>
                    <div class="cover"><img src="<?= h(uploadUrl($h['kapak_gorsel'])) ?>" alt="<?= ha($h['baslik']) ?>"></div>
                <?php else: ?>
                    <div class="cover" style="display:flex;align-items:center;justify-content:center;color:var(--gold);font-family:'Fraunces',serif;font-weight:300;font-size:80px;background:var(--bg-2)">A</div>
                <?php endif; ?>
                <div class="body">
                    <div class="meta"><?= h($h['kategori_ad'] ?? 'Haber') ?> · <?= h(formatDate($h['yayim_tarihi'] ?? $h['created_at'], 'd M Y')) ?></div>
                    <h4><?= h($h['baslik']) ?></h4>
                    <?php if (!empty($h['ozet'])): ?>
                    <p class="ozet"><?= h(truncate($h['ozet'], 120)) ?></p>
                    <?php endif; ?>
                    <span class="read-more">Devamı <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:48px">
            <a href="/haberler" class="btn outline">Tüm Haberler</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════
     TARİHÇE (Dark dramatic)
     ════════════════════════════════════════════════════ -->
<?php if ($zaman): ?>
<section class="section dark">
    <div class="container">
        <div class="section-head">
            <span class="pretitle">Tarihçe</span>
            <h2>Bir <em>topluluk</em> nasıl doğar?</h2>
            <p class="lead">Konya'da küçük bir atölyeden, on sektörde yatırımları olan bir hizmetler topluluğuna uzanan yolculuğumuz.</p>
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

<!-- ════════════════════════════════════════════════════
     CTA
     ════════════════════════════════════════════════════ -->
<section class="section tight" style="text-align:center;background:var(--bg-2)">
    <div class="container">
        <div class="section-head" style="margin-bottom:32px">
            <span class="pretitle">Birlikte Üretelim</span>
            <h2>Yatırım, ortaklık ya da işbirliği — <em>konuşalım</em>.</h2>
            <p class="lead">On sektörde geleceği şekillendiren bir hizmetler topluluğunun parçası olun.</p>
        </div>
        <a href="/iletisim" class="btn primary">İletişime Geç <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/templates/footer.php'; ?>
