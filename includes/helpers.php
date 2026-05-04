<?php
/**
 * AKSOY GROUP — Yardımcı Fonksiyonlar
 * @package AksoyHolding\Core
 */

declare(strict_types=1);

// ════════════════════════════════════════════════════════
// XSS GÜVENLİĞİ
// ════════════════════════════════════════════════════════

/** HTML escape. */
function h(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** Attribute escape (URL/value içinde). */
function ha(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ════════════════════════════════════════════════════════
// URL & ASSET
// ════════════════════════════════════════════════════════

function url(string $path = ''): string
{
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $full = AG_ROOT . '/' . ltrim($path, '/');
    $version = file_exists($full) ? filemtime($full) : AG_VERSION;
    return url($path) . '?v=' . $version;
}

function uploadUrl(?string $path): string
{
    if (!$path) {
        return url('assets/img/placeholder.svg');
    }
    if (str_starts_with($path, 'http')) {
        return $path;
    }
    return url(AG_PUBLIC_UPLOADS . '/' . ltrim($path, '/'));
}

function redirect(string $path, int $code = 302): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)), true, $code);
    exit;
}

function currentUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
}

// ════════════════════════════════════════════════════════
// SLUG / METİN
// ════════════════════════════════════════════════════════

function slugify(string $text): string
{
    $tr = ['ı','İ','ş','Ş','ğ','Ğ','ü','Ü','ö','Ö','ç','Ç'];
    $en = ['i','i','s','s','g','g','u','u','o','o','c','c'];
    $text = str_replace($tr, $en, $text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text) ?? '';
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text) ?? '';
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text) ?? '';
    return strtolower($text) ?: 'icerik';
}

function truncate(string $text, int $length = 160, string $suffix = '…'): string
{
    $text = trim(strip_tags($text));
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length - mb_strlen($suffix)) . $suffix;
}

function readingTime(string $text, int $wpm = 200): int
{
    $words = str_word_count(strip_tags($text));
    return max(1, (int)ceil($words / $wpm));
}

// ════════════════════════════════════════════════════════
// TARİH
// ════════════════════════════════════════════════════════

function formatDate(?string $date, string $format = 'd M Y'): string
{
    if (!$date || $date === '0000-00-00 00:00:00') {
        return '';
    }
    static $aylar = [
        '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
        '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
        '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık',
    ];
    static $aylarKisa = [
        '01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis',
        '05' => 'May', '06' => 'Haz', '07' => 'Tem', '08' => 'Ağu',
        '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara',
    ];
    try {
        $dt = new DateTime($date);
    } catch (Exception) {
        return '';
    }
    $out = $dt->format($format);
    $out = str_replace($dt->format('M'), $aylarKisa[$dt->format('m')], $out);
    $out = str_replace($dt->format('F'), $aylar[$dt->format('m')], $out);
    return $out;
}

function timeAgo(string $date): string
{
    $diff = time() - strtotime($date);
    if ($diff < 60) return 'az önce';
    if ($diff < 3600) return floor($diff / 60) . ' dk önce';
    if ($diff < 86400) return floor($diff / 3600) . ' sa önce';
    if ($diff < 604800) return floor($diff / 86400) . ' gün önce';
    return formatDate($date);
}

// ════════════════════════════════════════════════════════
// SAYI / FORMAT
// ════════════════════════════════════════════════════════

function formatNumber(int|float $n, int $decimals = 0): string
{
    return number_format($n, $decimals, ',', '.');
}

function formatBytes(int $bytes): string
{
    $units = ['B','KB','MB','GB','TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024; $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// ════════════════════════════════════════════════════════
// AYAR SİSTEMİ
// ════════════════════════════════════════════════════════

/** ag_settings'ten anahtara göre değer; cache'li. */
function setting(string $key, mixed $default = null): mixed
{
    static $cache = null;
    if ($cache === null) {
        try {
            $rows = DB::all("SELECT setting_key, setting_value FROM ag_settings");
            $cache = [];
            foreach ($rows as $r) {
                $cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (Throwable) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

// ════════════════════════════════════════════════════════
// IP & İSTEMCİ
// ════════════════════════════════════════════════════════

function clientIp(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            return trim($ip);
        }
    }
    return '0.0.0.0';
}

function userAgent(): string
{
    return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
}

// ════════════════════════════════════════════════════════
// DOSYA YÜKLEME
// ════════════════════════════════════════════════════════

function uploadImage(array $file, string $subdir = 'genel'): ?string
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }
    $allowed = ['image/jpeg','image/png','image/webp','image/svg+xml','image/gif'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed, true)) {
        return null;
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        return null;
    }
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        'image/gif' => 'gif',
    };
    $dir = AG_UPLOADS . '/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }
    return $subdir . '/' . $name;
}

function uploadFile(array $file, string $subdir, array $allowedMime, int $maxBytes = 10485760): ?string
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedMime, true)) return null;
    if ($file['size'] > $maxBytes) return null;
    $origName = pathinfo($file['name'], PATHINFO_FILENAME);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $dir = AG_UPLOADS . '/' . $subdir;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = slugify($origName) . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    return $subdir . '/' . $name;
}

// ════════════════════════════════════════════════════════
// FLASH MESAJ
// ════════════════════════════════════════════════════════

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $out = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $out;
}

// ════════════════════════════════════════════════════════
// JSON YANIT
// ════════════════════════════════════════════════════════

function jsonResponse(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError(string $msg, int $code = 400): never
{
    jsonResponse(['ok' => false, 'error' => $msg], $code);
}

function jsonOk(array $data = []): never
{
    jsonResponse(['ok' => true, ...$data]);
}

// ════════════════════════════════════════════════════════
// VALIDATION
// ════════════════════════════════════════════════════════

function isEmail(string $email): bool
{
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isPhone(string $phone): bool
{
    $clean = preg_replace('/[^0-9]/', '', $phone) ?? '';
    return strlen($clean) >= 10 && strlen($clean) <= 15;
}

function isUrl(string $url): bool
{
    return (bool)filter_var($url, FILTER_VALIDATE_URL);
}
