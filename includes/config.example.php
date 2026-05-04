<?php
/**
 * AKSOY GROUP — Konfigürasyon
 * --------------------------------------------------------
 * Bu dosyayı `config.php` olarak kopyalayın ve doldurun.
 * `config.php` GİT'E PUSH EDİLMEZ (.gitignore'da).
 * --------------------------------------------------------
 * @package    AksoyHolding
 * @author     CODEGA
 * @license    Proprietary
 */

declare(strict_types=1);

// ── ORTAM ────────────────────────────────────────────────
define('AG_ENV', 'production');           // production | development
define('AG_DEBUG', false);                // true → hatalar ekranda
define('AG_VERSION', '1.0.0');

// ── VERİTABANI ───────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'aksoyweb_xxx');        // ← DirectAdmin'den al
define('DB_USER', 'aksoyweb_xxx');        // ← DirectAdmin'den al
define('DB_PASS', '*****');               // ← DirectAdmin'den al
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX', 'ag_');

// ── SİTE ─────────────────────────────────────────────────
define('SITE_URL', 'https://aksoy.web.tr');
define('SITE_NAME', 'AKSOY GROUP');
define('SITE_LANG', 'tr');
define('SITE_TIMEZONE', 'Europe/Istanbul');

// ── DİZİN YAPISI ─────────────────────────────────────────
define('AG_ROOT', dirname(__DIR__));
define('AG_INCLUDES', AG_ROOT . '/includes');
define('AG_UPLOADS', AG_ROOT . '/uploads');
define('AG_LOGS', AG_ROOT . '/logs');
define('AG_MIGRATIONS', AG_ROOT . '/migrations');
define('AG_PUBLIC_UPLOADS', '/uploads');

// ── GÜVENLİK ─────────────────────────────────────────────
// Aşağıdaki anahtarı RASTGELE 64 karakter ile değiştirin
// PHP CLI: php -r "echo bin2hex(random_bytes(32));"
define('AG_SECRET_KEY', 'CHANGE_THIS_TO_A_64_CHAR_HEX_STRING_USING_random_bytes_32');
define('AG_CSRF_LIFETIME', 7200);          // 2 saat
define('AG_SESSION_LIFETIME', 28800);      // 8 saat
define('AG_MAX_LOGIN_ATTEMPTS', 5);
define('AG_LOGIN_LOCKOUT_MINUTES', 15);
define('AG_PASSWORD_MIN_LENGTH', 10);

// ── GITHUB GÜNCELLEME ────────────────────────────────────
define('AG_GITHUB_OWNER', 'codegatr');
define('AG_GITHUB_REPO', 'aksoygroup');
define('AG_GITHUB_BRANCH', 'main');
// NOT: GitHub PAT veritabanında ag_settings.github_token alanında saklanır.

// ── E-POSTA (SMTP) ───────────────────────────────────────
// Production'da ag_settings tablosundan okunur; bu değerler fallback.
define('AG_MAIL_FROM_NAME', 'AKSOY GROUP');
define('AG_MAIL_FROM_EMAIL', 'noreply@aksoy.web.tr');

// ── HATA RAPORLAMA ───────────────────────────────────────
if (AG_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', AG_ROOT . '/logs/php-error.log');
}

// ── ZAMAN DİLİMİ ─────────────────────────────────────────
date_default_timezone_set(SITE_TIMEZONE);

// ── OTURUM ───────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS'])) {
        ini_set('session.cookie_secure', '1');
    }
    session_name('AGSESSID');
    session_start();
}
