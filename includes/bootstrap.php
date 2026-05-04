<?php
/**
 * AKSOY GROUP — Bootstrap
 * Tüm sayfaların tek girişi. Sıralama önemlidir.
 */

declare(strict_types=1);

// 1. Konfigürasyon (config.php yoksa setup'a yönlendir)
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    if (basename($_SERVER['PHP_SELF']) !== 'setup.php') {
        header('Location: /setup.php');
        exit;
    }
} else {
    require_once $configFile;
}

// Otomatik üretilen version.php (Updater tarafından yazılır)
$versionFile = dirname(__DIR__) . '/version.php';
if (file_exists($versionFile)) {
    require_once $versionFile;
}

// 2. Çekirdek sınıflar
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/updater.php';

// 3. Bakım modu kontrolü (admin paneli hariç)
if (file_exists($configFile)) {
    $isAdminArea = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/yonetim');
    if (!$isAdminArea && setting('maintenance_mode') === '1') {
        http_response_code(503);
        header('Retry-After: 3600');
        require __DIR__ . '/templates/maintenance.php';
        exit;
    }
}
