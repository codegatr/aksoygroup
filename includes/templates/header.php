<?php
/** @var array $page Sayfa meta verisi */
$siteTitle = setting('site_title', 'AKSOY GROUP');
$siteDesc  = setting('site_description', '');
$pageTitle = $page['title'] ?? null;
$pageDesc  = $page['description'] ?? $siteDesc;
$fullTitle = $pageTitle ? $pageTitle . ' — ' . $siteTitle : $siteTitle;
$ogImage   = $page['og_image'] ?? null;

// Header menüsü: ag_pages tablosundaki menu_konumu = header / her_ikisi
$headerPages = DB::all("SELECT slug, baslik FROM ag_pages
                       WHERE is_active = 1 AND menu_konumu IN ('header','her_ikisi')
                         AND slug NOT IN ('hakkimizda','yonetim-kurulu','surdurulebilirlik','kariyer','basin','iletisim')
                       ORDER BY sort_order ASC");

// Kurumsal alt-menü için sabit liste (her zaman aynı sırada görünsün)
$kurumsalPages = DB::all("SELECT slug, baslik FROM ag_pages
                          WHERE is_active = 1
                            AND slug IN ('hakkimizda','surdurulebilirlik','kariyer','basin')
                          ORDER BY FIELD(slug,'hakkimizda','surdurulebilirlik','kariyer','basin')");
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
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@200;300;400;500&family=Manrope:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/site.css') ?>">
<link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/favicon.svg') ?>">
</head>
<body>
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
            <div class="nav-dropdown">
                <button class="nav-dropdown-trigger" type="button">
                    Kurumsal
                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-left:5px"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="nav-dropdown-menu">
                    <?php foreach ($kurumsalPages as $kp): ?>
                        <a href="/<?= ha($kp['slug']) ?>"><?= h($kp['baslik']) ?></a>
                    <?php endforeach; ?>
                    <a href="/yonetim-kurulu">Yönetim Kurulu</a>
                </div>
            </div>
            <a href="/sektorler">Sektörler</a>
            <a href="/sirketler">Şirketler</a>
            <a href="/haberler">Haberler</a>
            <?php foreach ($headerPages as $p): ?>
                <a href="/<?= ha($p['slug']) ?>"><?= h($p['baslik']) ?></a>
            <?php endforeach; ?>
            <a href="/iletisim" class="cta">İletişim</a>
        </nav>
    </div>
</header>
<main>
