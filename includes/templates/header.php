<?php
/** @var array $page Sayfa meta verisi */
$siteTitle = setting('site_title', 'AKSOY GROUP');
$siteDesc  = setting('site_description', '');
$pageTitle = $page['title'] ?? null;
$pageDesc  = $page['description'] ?? $siteDesc;
$fullTitle = $pageTitle ? $pageTitle . ' — ' . $siteTitle : $siteTitle;
$ogImage   = $page['og_image'] ?? null;

// Header menü için DB sorguları
$kurumsalPages = DB::all("SELECT slug, baslik FROM ag_pages
                          WHERE is_active = 1
                            AND slug IN ('hakkimizda','surdurulebilirlik','kariyer','basin')
                          ORDER BY FIELD(slug,'hakkimizda','surdurulebilirlik','kariyer','basin')");

// Megamenu için sektörler — featured 7 tane
$megaSektorler = DB::all("SELECT slug, ad, alt_baslik FROM ag_sektorler
                          WHERE is_active = 1
                          ORDER BY sort_order ASC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($fullTitle) ?></title>
<meta name="description" content="<?= ha($pageDesc) ?>">
<meta name="keywords" content="<?= h(setting('seo_meta_keywords', '')) ?>">

<!-- Open Graph -->
<meta property="og:title" content="<?= ha($fullTitle) ?>">
<meta property="og:description" content="<?= ha($pageDesc) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="tr_TR">
<meta property="og:site_name" content="<?= ha($siteTitle) ?>">
<?php if ($ogImage): ?><meta property="og:image" content="<?= ha($ogImage) ?>"><?php endif; ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@300;400;500&family=Inter:wght@400;500;600&family=Manrope:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/site.css') ?>">
<link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/favicon.svg') ?>">
</head>
<body>

<!-- ════════════════════════════════════════════════════
     HEADER — Logo + Mega Menu + Search + TR/EN
     ════════════════════════════════════════════════════ -->
<header class="site-header" id="siteHeader">
    <div class="container">
        <a href="/" class="site-logo">
            AKSOY
            <small>Hizmetler Topluluğu</small>
        </a>
        <button class="menu-btn" onclick="document.getElementById('siteNav').classList.toggle('open')" aria-label="Menü">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
        </button>
        <nav class="site-nav" id="siteNav">
            <a href="/">Ana Sayfa</a>

            <!-- Kurumsal Dropdown — anchor'lar -->
            <div class="nav-dropdown" data-mega="kurumsal">
                <button class="nav-dropdown-trigger" type="button">
                    Kurumsal
                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-left:5px"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="nav-dropdown-menu">
                    <a href="/#hakkimizda">Hakkımızda</a>
                    <a href="/#vizyon-misyon">Vizyon &amp; Değerler</a>
                    <a href="/#yonetim-kurulu">Yönetim Kurulu</a>
                    <a href="/#tarihce">Tarihçe</a>
                    <a href="/#surdurulebilirlik">Sürdürülebilirlik</a>
                </div>
            </div>

            <!-- Faaliyet Alanları -->
            <div class="nav-dropdown" data-mega="faaliyet">
                <button class="nav-dropdown-trigger" type="button">
                    Faaliyet Alanları
                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-left:5px"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="nav-dropdown-menu" style="min-width:280px">
                    <a href="/#sektorler">Tüm Sektörler</a>
                    <?php foreach (array_slice($megaSektorler, 0, 5) as $ms): ?>
                        <a href="/sektor/<?= ha($ms['slug']) ?>"><?= h($ms['ad']) ?></a>
                    <?php endforeach; ?>
                    <a href="/sektorler" style="border-top:1px solid var(--line);margin-top:8px;color:var(--burgundy);font-weight:600">Detaylı sayfa →</a>
                </div>
            </div>

            <a href="/#istirakler">İştirakler</a>
            <a href="/#haberler">Haberler</a>
            <a href="/#kariyer">Kariyer</a>
            <a href="/#iletisim" class="cta">İletişim</a>

            <!-- Search + Language toggle -->
            <div class="header-utils">
                <button onclick="document.getElementById('searchOverlay').classList.add('open');setTimeout(()=>document.getElementById('searchInput').focus(),100)" aria-label="Ara" title="Ara">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                </button>
                <button class="lang-toggle active" title="Türkçe">TR</button>
            </div>
        </nav>
    </div>
</header>

<!-- ════════════════════════════════════════════════════
     ARAMA OVERLAY
     ════════════════════════════════════════════════════ -->
<div class="search-overlay" id="searchOverlay">
    <button class="search-close" onclick="document.getElementById('searchOverlay').classList.remove('open')" aria-label="Kapat">×</button>
    <div class="search-box">
        <form method="get" action="/arama">
            <input type="search" name="q" id="searchInput" placeholder="Aksoy Group içinde ara…" autocomplete="off">
            <div style="margin-top:24px;display:flex;gap:14px;flex-wrap:wrap">
                <a href="/sektorler" style="font-size:12px;color:rgba(255,255,255,.5);letter-spacing:.15em;text-transform:uppercase">→ Sektörler</a>
                <a href="/sirketler" style="font-size:12px;color:rgba(255,255,255,.5);letter-spacing:.15em;text-transform:uppercase">→ İştirakler</a>
                <a href="/haberler" style="font-size:12px;color:rgba(255,255,255,.5);letter-spacing:.15em;text-transform:uppercase">→ Haberler</a>
                <a href="/yonetim-kurulu" style="font-size:12px;color:rgba(255,255,255,.5);letter-spacing:.15em;text-transform:uppercase">→ Yönetim Kurulu</a>
            </div>
        </form>
    </div>
</div>

<script>
// ESC ile arama kapansın
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const ov = document.getElementById('searchOverlay');
        if (ov && ov.classList.contains('open')) ov.classList.remove('open');
    }
});
// Sticky header scroll efekti
window.addEventListener('scroll', function() {
    const h = document.getElementById('siteHeader');
    if (window.scrollY > 30) h.classList.add('scrolled');
    else h.classList.remove('scrolled');
}, { passive: true });
</script>

<main>
