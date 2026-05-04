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
$yonetim = DB::all("SELECT * FROM ag_yonetim_kurulu WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 4");

// İstatistikler — DB'den dinamik
$stats = [
    'sektor_sayisi'   => (int)DB::scalar("SELECT COUNT(*) FROM ag_sektorler WHERE is_active = 1"),
    'sirket_sayisi'   => (int)DB::scalar("SELECT COUNT(*) FROM ag_sirketler WHERE durum = 'aktif'"),
    'calisan_sayisi'  => (int)setting('toplam_calisan', '500'),
    'kurulus_yili'    => (int)setting('grup_kurulus_yili', '1992'),
];
$grupYasi = max(1, (int)date('Y') - $stats['kurulus_yili']);

// İletişim
$contactEmail = setting('contact_email', 'info@aksoy.web.tr');
$contactPhone = setting('contact_phone', '+90 (332) 000 00 00');
$contactAddr  = setting('contact_address', 'Konya, Türkiye');

$page = [
    'title'       => null,
    'description' => setting('site_description', 'Aksoy Group — On sektörde faaliyet gösteren Türkiye merkezli hizmetler topluluğu.'),
];
require_once __DIR__ . '/includes/templates/header.php';
?>

<!-- ════════════════════════════════════════════════════
     1. HERO SLIDER
     ════════════════════════════════════════════════════ -->
<section class="hero-slider" id="anasayfa">
    <div class="hero-slide active" data-slide="1">
        <div class="hero-slide-bg" style="background: radial-gradient(ellipse at 20% 80%, rgba(184,151,93,.25), transparent 50%), radial-gradient(ellipse at 80% 20%, rgba(15,44,79,.6), transparent 50%), linear-gradient(135deg, #0F2C4F 0%, #08172E 100%);"></div>
        <div class="container">
            <span class="pretitle">Aksoy Group</span>
            <h1>Yarattığı farkla büyüyen, <em>öncü ve saygın</em> bir Türkiye topluluğu.</h1>
            <p class="lead">On sektörde uzmanlaşmış iştiraklerimizle, Türkiye'nin köklü hizmetler topluluklarından biriyiz. <?= $grupYasi ?>+ yıllık deneyimle geleceği inşa ediyoruz.</p>
            <div class="hero-cta">
                <a href="#hakkimizda" class="btn primary">Topluluğu Tanıyın <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                <a href="#sektorler" class="btn outline">Faaliyet Alanlarımız</a>
            </div>
        </div>
    </div>
    <div class="hero-slide" data-slide="2">
        <div class="hero-slide-bg" style="background: radial-gradient(ellipse at 70% 50%, rgba(111,26,46,.35), transparent 60%), linear-gradient(135deg, #08172E 0%, #1A3A5F 100%);"></div>
        <div class="container">
            <span class="pretitle">Vizyon</span>
            <h1>Endüstriyel derinlik, <em>dijital hız</em>.</h1>
            <p class="lead">Demir-çeliğin ağırlığını yazılımın çevikliğiyle birleştirdiğimiz, on farklı sektörde tek vizyon altında yürüyen bir hizmetler topluluğu.</p>
            <div class="hero-cta">
                <a href="#istirakler" class="btn primary">İştiraklerimiz <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
            </div>
        </div>
    </div>
    <div class="hero-slide" data-slide="3">
        <div class="hero-slide-bg" style="background: radial-gradient(ellipse at 30% 30%, rgba(31,122,77,.30), transparent 60%), linear-gradient(135deg, #0F2C4F 0%, #1F3550 100%);"></div>
        <div class="container">
            <span class="pretitle">Sürdürülebilirlik</span>
            <h1>Bugünden geleceğe, <em>sorumlu üretim</em>.</h1>
            <p class="lead">Çevresel etkimizi azaltmak, sosyal dayanışmayı güçlendirmek ve kurumsal yönetişimi yükseltmek — sadece raporlamak için değil, gerçekten yaşamak için çalışıyoruz.</p>
            <div class="hero-cta">
                <a href="#surdurulebilirlik" class="btn primary">Yaklaşımımız <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
            </div>
        </div>
    </div>
    <div class="hero-slide" data-slide="4">
        <div class="hero-slide-bg" style="background: radial-gradient(ellipse at 80% 70%, rgba(184,151,93,.2), transparent 50%), linear-gradient(135deg, #1A3A5F 0%, #08172E 100%);"></div>
        <div class="container">
            <span class="pretitle">Liderlik</span>
            <h1>Vizyonun arkasındaki <em>liderlik kadrosu</em>.</h1>
            <p class="lead">Aksoy Group'u şekillendiren strateji, uzun vadeli yatırım vizyonu ve kurumsal yönetim disiplini — yönetim kurulu üyelerimizin deneyim ve birikiminin ürünüdür.</p>
            <div class="hero-cta">
                <a href="#yonetim-kurulu" class="btn primary">Yönetim Kurulu <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
            </div>
        </div>
    </div>
    <div class="hero-slide" data-slide="5">
        <div class="hero-slide-bg" style="background: radial-gradient(ellipse at 50% 50%, rgba(184,151,93,.18), transparent 60%), linear-gradient(135deg, #0F2C4F 0%, #08172E 60%, #1A3A5F 100%);"></div>
        <div class="container">
            <span class="pretitle">Kariyer ve Yaşam</span>
            <h1>Geleceği <em>birlikte</em> inşa edelim.</h1>
            <p class="lead">500+ çalışan, 10 sektör, sayısız fırsat. Anadolu'nun üretim merkezi Konya'da, dijital iştiraklerimizle dünyaya açılan bir ailenin parçası olun.</p>
            <div class="hero-cta">
                <a href="#kariyer" class="btn primary">Kariyer Fırsatları <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
            </div>
        </div>
    </div>

    <div class="hero-indicator">
        <span class="num" id="heroIndicatorNum">01</span>
        <span class="total">— 05</span>
    </div>
    <div class="hero-controls">
        <div class="hero-dots" id="heroDots">
            <button class="hero-dot active" data-go="1" aria-label="Slide 1"></button>
            <button class="hero-dot" data-go="2" aria-label="Slide 2"></button>
            <button class="hero-dot" data-go="3" aria-label="Slide 3"></button>
            <button class="hero-dot" data-go="4" aria-label="Slide 4"></button>
            <button class="hero-dot" data-go="5" aria-label="Slide 5"></button>
        </div>
        <div class="hero-arrows">
            <button class="hero-arrow" id="heroPrev" aria-label="Önceki"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg></button>
            <button class="hero-arrow" id="heroNext" aria-label="Sonraki"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg></button>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════
     2. MANİFESTO
     ════════════════════════════════════════════════════ -->
<section class="manifesto" id="manifesto">
    <div class="container">
        <div class="pretitle">Marka Manifestomuz</div>
        <div class="quote-mark">"</div>
        <h2>Anadolu'nun üretim derinliğini dijital çağın hızıyla harmanlayan; <em>kalite, güven ve sürdürülebilirlik</em> ilkeleriyle yarınları inşa eden bir hizmetler topluluğu olmak.</h2>
        <div class="signature">Aksoy Group · Marka Manifestomuz</div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════
     3. SAYILARLA AKSOY
     ════════════════════════════════════════════════════ -->
<section class="stats-block" id="sayilarla">
    <div class="container">
        <span class="pretitle">Sayılarla Aksoy</span>
        <h3 class="stats-title">Bugünün <em style="color:var(--gold);font-style:italic">öncüsü</em>, yarının mimarı.</h3>
        <div class="stats-grid">
            <div class="stat-cell"><div class="num"><?= $stats['sektor_sayisi'] ?: 10 ?></div><div class="lbl">Faaliyet Sektörü</div><div class="desc">Demir-çelikten yazılıma uzanan derin uzmanlık</div></div>
            <div class="stat-cell"><div class="num"><?= $stats['sirket_sayisi'] ?: 9 ?><em>+</em></div><div class="lbl">İştirak Şirketi</div><div class="desc">Topluluk altında bağımsız markalar</div></div>
            <div class="stat-cell"><div class="num"><?= number_format($stats['calisan_sayisi'], 0, ',', '.') ?><em>+</em></div><div class="lbl">Çalışan</div><div class="desc">Türkiye'nin dört bir yanından profesyoneller</div></div>
            <div class="stat-cell"><div class="num"><?= $grupYasi ?><em>+</em></div><div class="lbl">Yıllık Deneyim</div><div class="desc">Konya merkezli kuruluşumuzdan bu yana</div></div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════
     4. HAKKIMIZDA ÖZETİ — 2 kolon
     ════════════════════════════════════════════════════ -->
<section class="section" id="hakkimizda">
    <div class="container">
        <div class="about-snippet">
            <div class="about-snippet-img">
                <div class="badge-overlay">Konya · Türkiye</div>
            </div>
            <div class="about-snippet-text">
                <div class="pretitle">Hakkımızda</div>
                <h2>On sektörde, <em>tek vizyon</em> altında.</h2>
                <p>Aksoy Group, on farklı sektörde uzmanlaşmış iştirakleriyle Türkiye'nin köklü hizmetler topluluklarından biridir. Konya merkezli faaliyet gösteren topluluğumuz; üretim, teknoloji, finans ve hizmet sektörlerinde verdiği değerle sürdürülebilir büyümeyi hedefler.</p>
                <p>Tekcan Metal'den CODEGA Yazılım'a, SBB Sigorta'dan XNews medyasına kadar uzanan iştirak portföyümüzle, geleneksel sektörlerin dijital dönüşümüne öncülük ediyoruz.</p>
                <div class="signature-quote">"Geleneksel sektörlerin derinliğini, dijital çağın çevikliğiyle birleştiriyoruz."</div>
                <div style="margin-top:32px"><a href="/hakkimizda" class="btn outline">Detaylı Hakkımızda <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a></div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════
     5. VİZYON · MİSYON · DEĞERLER
     ════════════════════════════════════════════════════ -->
<section class="section alt" id="vizyon-misyon">
    <div class="container">
        <div class="section-head">
            <span class="pretitle">Bizim İçin Önemli Olan</span>
            <h2>Vizyon, misyon ve <em>değerlerimiz</em></h2>
            <p class="lead">Aksoy Group olarak iş yapış biçimimizi belirleyen üç temel taş.</p>
        </div>
        <div class="vmd-grid">
            <div class="vmd-card">
                <div class="ico"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg></div>
                <div class="label">Vizyon</div>
                <h3>Öncü ve saygın bir topluluk</h3>
                <p>Endüstriyel üretim derinliğini dijital çağın hızıyla buluşturan, Türkiye'nin en güvenilir ve sürdürülebilir hizmetler topluluğu olmak.</p>
            </div>
            <div class="vmd-card">
                <div class="ico"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><polygon points="12 2 15 9 22 9 17 14 19 21 12 17 5 21 7 14 2 9 9 9"/></svg></div>
                <div class="label">Misyon</div>
                <h3>Uzun vadeli değer üretmek</h3>
                <p>Müşterilerimize, çalışanlarımıza ve paydaşlarımıza uzun vadeli değer üretmek için her sektörde kaliteden ödün vermeden, sürdürülebilir ve teknolojik dönüşüme açık şekilde çalışmak.</p>
            </div>
            <div class="vmd-card">
                <div class="ico"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 2L4 7v10l8 5 8-5V7l-8-5z"/></svg></div>
                <div class="label">Değerlerimiz</div>
                <h3>Dört temel ilke</h3>
                <ul class="values-list">
                    <li><strong style="color:var(--navy)">Şeffaflık</strong> · Açık iletişim, verilen sözün arkasında durmak</li>
                    <li><strong style="color:var(--navy)">Mükemmellik</strong> · Sürekli iyileştirme, kaliteyi yükseltmek</li>
                    <li><strong style="color:var(--navy)">Sürdürülebilirlik</strong> · Çevresel ve sosyal sorumluluk</li>
                    <li><strong style="color:var(--navy)">Yenilikçilik</strong> · Dijital dönüşüm ve Ar-Ge yatırımı</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════
     6. SEKTÖRLER
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
                <?php if (!empty($s['alt_baslik'])): ?><div class="alt"><?= h($s['alt_baslik']) ?></div><?php endif; ?>
                <div class="desc"><?= h($s['kisa_aciklama'] ?? '') ?></div>
                <span class="arrow">Detay <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════
     7. İŞTİRAKLER
     ════════════════════════════════════════════════════ -->
<?php if ($featuredCompanies): ?>
<section class="section alt" id="istirakler">
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
                <?php if (!empty($c['slogan'])): ?><div class="slogan"><?= h($c['slogan']) ?></div><?php endif; ?>
                <?php if (!empty($c['sektor_ad'])): ?><div class="sector-tag"><?= h($c['sektor_ad']) ?></div><?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:48px"><a href="/sirketler" class="btn outline">Tüm İştiraklerimiz <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a></div>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════
     8. YÖNETİM KURULU önizlemesi
     ════════════════════════════════════════════════════ -->
<?php if ($yonetim): ?>
<section class="section" id="yonetim-kurulu">
    <div class="container">
        <div class="section-head">
            <span class="pretitle">Liderlik</span>
            <h2>Vizyonun arkasındaki <em>kadro</em></h2>
            <p class="lead">Aksoy Group'u şekillendiren strateji ve kurumsal yönetim disiplini, yönetim kurulu üyelerimizin birikiminin ürünüdür.</p>
        </div>
        <div class="board-preview">
            <?php foreach ($yonetim as $m): ?>
            <a href="/yonetim-kurulu?slug=<?= ha($m['slug']) ?>" class="board-preview-card">
                <div class="photo">
                    <?php if (!empty($m['fotograf'])): ?>
                        <img src="<?= h(uploadUrl($m['fotograf'])) ?>" alt="<?= ha($m['ad_soyad']) ?>">
                    <?php else: ?>
                        <?= h(strtoupper(mb_substr($m['ad_soyad'], 0, 1))) ?>
                    <?php endif; ?>
                </div>
                <h4><?= h($m['ad_soyad']) ?></h4>
                <div class="role"><?= h($m['unvan']) ?></div>
            </a>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:48px"><a href="/yonetim-kurulu" class="btn outline">Tüm Yönetim Kurulu <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a></div>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════
     9. SÜRDÜRÜLEBİLİRLİK
     ════════════════════════════════════════════════════ -->
<section class="section alt" id="surdurulebilirlik">
    <div class="container">
        <div class="section-head">
            <span class="pretitle">Sürdürülebilirlik</span>
            <h2>Bugünden geleceğe, <em>sorumlu üretim</em></h2>
            <p class="lead">Çevresel, sosyal ve ekonomik sürdürülebilirlik iş süreçlerimizin merkezindedir.</p>
        </div>
        <div class="sustain-grid">
            <div class="sustain-card">
                <div class="num">01</div>
                <h3>Çevresel Sorumluluk</h3>
                <p>Demir-çelik tesislerimizden e-ticaret operasyonlarımıza kadar tüm faaliyet alanlarımızda karbon ayak izimizi düzenli ölçer ve azaltma hedeflerimizi şeffaf paylaşırız. Geri dönüşüm ve döngüsel ekonomi prensiplerini iş modelimize entegre ettik.</p>
            </div>
            <div class="sustain-card">
                <div class="num">02</div>
                <h3>Sosyal Etki</h3>
                <p>500+ çalışanımız, her gün ailelerine ekmek götürmenin ötesinde; kariyerlerini geliştirebilecekleri, kendilerini güvende hissedebilecekleri bir iş ortamında görev yapar. Çalışan eğitimine ve fırsat eşitliğine yatırımımız sürekli artmaktadır.</p>
            </div>
            <div class="sustain-card">
                <div class="num">03</div>
                <h3>Yönetişim</h3>
                <p>Şeffaf yönetişim; etik kurallarımız, iç denetim mekanizmalarımız ve bağımsız yönetim kurulu üyelerimizle desteklenir. Tedarikçi seçiminden rekabet uyumuna kadar tüm süreçlerimizde uluslararası kurumsal yönetim standartlarını referans alırız.</p>
            </div>
        </div>
        <div style="text-align:center;margin-top:48px"><a href="/surdurulebilirlik" class="btn outline">Sürdürülebilirlik Yaklaşımımız <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a></div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════
     10. HABERLER
     ════════════════════════════════════════════════════ -->
<?php if ($haberler): ?>
<section class="section" id="haberler">
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
                    <?php if (!empty($h['ozet'])): ?><p class="ozet"><?= h(truncate($h['ozet'], 120)) ?></p><?php endif; ?>
                    <span class="read-more">Devamı <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:48px"><a href="/haberler" class="btn outline">Tüm Haberler</a></div>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════
     11. TARİHÇE (dark)
     ════════════════════════════════════════════════════ -->
<?php if ($zaman): ?>
<section class="section dark" id="tarihce">
    <div class="container">
        <div class="section-head">
            <span class="pretitle">Tarihçe</span>
            <h2>Bir <em>topluluk</em> nasıl doğar?</h2>
            <p class="lead">Konya'da küçük bir atölyeden, on sektörde yatırımları olan bir hizmetler topluluğuna uzanan yolculuk.</p>
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
     12. KARİYER BANNER (dark)
     ════════════════════════════════════════════════════ -->
<section class="career-banner" id="kariyer">
    <div class="container">
        <div class="career-banner-grid">
            <div>
                <div class="pretitle">Kariyer ve Yaşam</div>
                <h2>Geleceği <em>birlikte</em> inşa edelim.</h2>
                <p>Konya merkezli üretim gücümüz, dijital iştiraklerimizle dünyaya açılan vizyonumuz ve Ar-Ge'ye süregelen yatırımımızla; alanında uzmanlaşmak isteyen yetenekli profesyonelleri her zaman bekliyoruz.</p>
            </div>
            <div class="actions">
                <a href="/kariyer" class="btn primary">Açık Pozisyonlar <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                <a href="/iletisim" class="btn outline">İK ile İletişime Geç</a>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════
     13. İLETİŞİM
     ════════════════════════════════════════════════════ -->
<section class="section" id="iletisim">
    <div class="container">
        <div class="section-head">
            <span class="pretitle">İletişim</span>
            <h2>Yatırım, ortaklık ya da işbirliği — <em>konuşalım</em>.</h2>
            <p class="lead">On sektörde geleceği şekillendiren bir hizmetler topluluğunun parçası olun.</p>
        </div>
        <div class="contact-grid">
            <div class="contact-info">
                <h3>İletişim Bilgileri</h3>
                <div class="contact-info-line">
                    <div class="ico"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                    <div><div class="lbl">Adres</div><div class="val"><?= nl2br(h($contactAddr)) ?></div></div>
                </div>
                <div class="contact-info-line">
                    <div class="ico"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.33 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
                    <div><div class="lbl">Telefon</div><a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $contactPhone)) ?>" class="val"><?= h($contactPhone) ?></a></div>
                </div>
                <div class="contact-info-line">
                    <div class="ico"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
                    <div><div class="lbl">E-posta</div><a href="mailto:<?= h($contactEmail) ?>" class="val"><?= h($contactEmail) ?></a></div>
                </div>
                <div class="contact-info-line">
                    <div class="ico"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                    <div><div class="lbl">Çalışma Saatleri</div><div class="val">Pzt – Cum · 09:00 — 18:00</div></div>
                </div>
            </div>

            <div>
                <form class="contact-form" method="post" action="/api/iletisim.php">
                    <?= CSRF::field() ?>
                    <div class="field"><label>Ad Soyad *</label><input type="text" name="ad_soyad" required maxlength="150"></div>
                    <div class="field"><label>E-posta *</label><input type="email" name="email" required maxlength="150"></div>
                    <div class="field"><label>Telefon</label><input type="tel" name="telefon" maxlength="30"></div>
                    <div class="field"><label>Konu</label>
                        <select name="konu">
                            <option value="genel">Genel Bilgi</option>
                            <option value="yatirim">Yatırım / Ortaklık</option>
                            <option value="kariyer">Kariyer Başvurusu</option>
                            <option value="basin">Basın Talebi</option>
                            <option value="diger">Diğer</option>
                        </select>
                    </div>
                    <div class="field"><label>Mesajınız *</label><textarea name="mesaj" required minlength="10"></textarea></div>
                    <button type="submit" class="btn primary">Mesajı Gönder <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Hero slider JS -->
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

    document.addEventListener('keydown', e => {
        if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') return;
        if (e.key === 'ArrowLeft') { stopAuto(); prev(); startAuto(); }
        if (e.key === 'ArrowRight') { stopAuto(); next(); startAuto(); }
    });

    startAuto();

    // Active anchor section detection
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.site-nav a[href^="#"], .site-nav a[href^="/#"]');
    function updateActiveNav() {
        const scrollY = window.scrollY + 120;
        let current = '';
        sections.forEach(s => { if (scrollY >= s.offsetTop) current = s.id; });
        navLinks.forEach(a => {
            const href = a.getAttribute('href');
            const slug = href.replace(/^\/?#/, '');
            a.classList.toggle('active', slug === current);
        });
    }
    window.addEventListener('scroll', updateActiveNav, { passive: true });
})();
</script>

<?php require_once __DIR__ . '/includes/templates/footer.php'; ?>
