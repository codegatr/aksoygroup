<?php
/**
 * AKSOY GROUP — Admin Master Layout
 * Kullanım: define('AG_ADMIN', true); $adminTitle='...'; $adminMenu='...'; require '_layout.php';
 */

declare(strict_types=1);
if (!defined('AG_ADMIN')) { die('Direkt erişim engellendi.'); }

require_once __DIR__ . '/../includes/bootstrap.php';
Auth::require();

require_once __DIR__ . '/_helpers.php';

$adminTitle = $adminTitle ?? 'Yönetim Paneli';
$adminMenu = $adminMenu ?? '';
$adminBreadcrumb = $adminBreadcrumb ?? null;
$user = Auth::user();
$unreadMessages = (int)DB::scalar("SELECT COUNT(*) FROM ag_iletisim_mesajlari WHERE okundu = 0");
$pendingApplications = (int)DB::scalar("SELECT COUNT(*) FROM ag_kariyer_basvuru WHERE durum = 'yeni'");

$menuItems = [
    ['section' => 'Pano'],
    ['key' => 'dashboard',   'label' => 'Genel Bakış',     'href' => '/yonetim/',                     'icon' => 'home'],

    ['section' => 'İçerik'],
    ['key' => 'sektorler',   'label' => 'Sektörler',       'href' => '/yonetim/modules/sektorler.php','icon' => 'briefcase'],
    ['key' => 'sirketler',   'label' => 'Şirketler',       'href' => '/yonetim/modules/sirketler.php','icon' => 'building'],
    ['key' => 'haberler',    'label' => 'Haberler',        'href' => '/yonetim/modules/haberler.php', 'icon' => 'newspaper'],
    ['key' => 'pages',       'label' => 'Sayfalar',        'href' => '/yonetim/modules/pages.php',    'icon' => 'file-text'],

    ['section' => 'İletişim'],
    ['key' => 'iletisim',    'label' => 'Mesajlar',        'href' => '/yonetim/modules/iletisim.php', 'icon' => 'mail', 'badge' => $unreadMessages ?: null],

    ['section' => 'Sistem'],
    ['key' => 'kullanicilar','label' => 'Kullanıcılar',    'href' => '/yonetim/modules/kullanicilar.php', 'icon' => 'users'],
    ['key' => 'ayarlar',     'label' => 'Site Ayarları',   'href' => '/yonetim/modules/ayarlar.php',   'icon' => 'settings'],
    ['key' => 'audit-log',   'label' => 'Aktivite Logu',   'href' => '/yonetim/modules/audit-log.php', 'icon' => 'activity'],
    ['key' => 'update',      'label' => 'Güncelleme Merkezi','href' => '/yonetim/modules/update-center.php','icon' => 'rocket'],
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title><?= h($adminTitle) ?> — Aksoy Group Yönetim</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@200;300;400;500&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar" id="sidebar">
        <div class="brand-block">
            <div class="brand">AKSOY</div>
            <div class="sub">Group · Yönetim</div>
        </div>

        <?php foreach ($menuItems as $item):
            if (isset($item['section'])): ?>
                <div class="nav-section-title" style="padding-top:18px"><?= h($item['section']) ?></div>
            <?php else: ?>
                <a href="<?= ha($item['href']) ?>" class="nav-link <?= $adminMenu === $item['key'] ? 'active' : '' ?>">
                    <?= icon($item['icon']) ?>
                    <span><?= h($item['label']) ?></span>
                    <?php if (!empty($item['badge'])): ?>
                        <span class="badge"><?= (int)$item['badge'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endif;
        endforeach; ?>

        <div class="sidebar-footer">
            <div>v<?= h(setting('current_version', AG_VERSION)) ?></div>
            <div style="margin-top:4px">CODEGA × AKSOY</div>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div style="display:flex;align-items:center;gap:14px">
                <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><?= icon('menu') ?></button>
                <div>
                    <small><?= $adminBreadcrumb ? h($adminBreadcrumb) : 'Yönetim Paneli' ?></small>
                    <div class="page-title"><?= h($adminTitle) ?></div>
                </div>
            </div>
            <div class="user-menu">
                <a href="/" target="_blank" class="btn ghost btn-sm" title="Siteyi Görüntüle">
                    <?= icon('eye', 16) ?> Siteyi Gör
                </a>
                <div class="avatar"><?= h(mb_substr($user['full_name'] ?? 'A', 0, 1)) ?></div>
                <div class="info">
                    <b><?= h($user['full_name'] ?? '') ?></b>
                    <span><?= h(ucfirst($user['role'] ?? '')) ?></span>
                </div>
                <a href="/yonetim/logout.php" class="btn ghost btn-sm" title="Çıkış"><?= icon('logout', 16) ?></a>
            </div>
        </header>

        <main class="content">
            <?php foreach (flashes() as $f): ?>
                <div class="alert <?= h($f['type']) ?>"><?= h($f['message']) ?></div>
            <?php endforeach; ?>
