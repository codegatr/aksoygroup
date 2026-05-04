<?php
/**
 * AKSOY GROUP — Kurulum Sihirbazı (v1.0.0-fix2)
 * --------------------------------------------
 * https://aksoy.web.tr/setup.php açın.
 * KURULUMDAN SONRA BU DOSYAYI SUNUCUDAN SİLİN!
 * --------------------------------------------
 */

declare(strict_types=1);
session_start();
ini_set('display_errors', '1');
error_reporting(E_ALL);

$lockFile = __DIR__ . '/includes/.installed.lock';
if (file_exists($lockFile) && empty($_GET['force'])) {
    die(<<<HTML
<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>Zaten kurulu</title>
<style>body{font-family:system-ui,sans-serif;background:#0A0E1A;color:#F5F1E8;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:40px;text-align:center}
.box{max-width:520px;border:1px solid #2A2E3A;padding:40px;border-radius:8px;background:#0F1424}
h1{color:#C9A961;font-weight:300;letter-spacing:.05em;margin-bottom:16px}
p{color:#8c8a82;line-height:1.7}
a{color:#C9A961;text-decoration:none;border-bottom:1px solid #C9A961}
.warn{margin-top:30px;padding:16px;background:#3a1a1a;border-left:3px solid #c83a3a;border-radius:4px;color:#ff9999;font-size:13px}
</style></head><body><div class="box">
<h1>Sistem zaten kurulu</h1>
<p>Aksoy Group platformu kurulumu tamamlandı. <br><br>
<a href="/">Siteye Git</a> &nbsp;·&nbsp; <a href="/yonetim/">Yönetim Paneli</a></p>
<div class="warn">⚠️ Güvenlik için <code>setup.php</code> dosyasını sunucudan silin.</div>
</div></body></html>
HTML);
}

// ════════════════════════════════════════════════════════
// AKILLI SQL SPLITTER (FIX!)
// String/yorum sınırlarını anlayan parser. `'a; b'` içindeki
// noktalı virgülü statement sınırı sanmaz; `''` escape'lerini
// doğru hallederek `Group''un` gibi yapıları korur.
// ════════════════════════════════════════════════════════
function ag_split_sql(string $sql): array
{
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    $len = strlen($sql);
    $i = 0;

    while ($i < $len) {
        $ch = $sql[$i];

        if ($inString) {
            if ($ch === '\\' && $i + 1 < $len) {
                $current .= $ch . $sql[$i + 1];
                $i += 2; continue;
            }
            if ($ch === $stringChar) {
                if ($i + 1 < $len && $sql[$i + 1] === $stringChar) {
                    $current .= $ch . $sql[$i + 1];
                    $i += 2; continue;
                }
                $inString = false;
            }
            $current .= $ch;
            $i++; continue;
        }

        // -- yorum
        if ($ch === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
            while ($i < $len && $sql[$i] !== "\n") $i++;
            continue;
        }
        // /* ... */
        if ($ch === '/' && $i + 1 < $len && $sql[$i + 1] === '*') {
            $i += 2;
            while ($i + 1 < $len && !($sql[$i] === '*' && $sql[$i + 1] === '/')) $i++;
            $i += 2; continue;
        }
        // String başlangıcı
        if ($ch === "'" || $ch === '"' || $ch === '`') {
            $inString = true; $stringChar = $ch;
            $current .= $ch;
            $i++; continue;
        }
        // Statement bitimi
        if ($ch === ';') {
            $stmt = trim($current);
            if ($stmt !== '') $statements[] = $stmt;
            $current = '';
            $i++; continue;
        }
        $current .= $ch;
        $i++;
    }
    $stmt = trim($current);
    if ($stmt !== '') $statements[] = $stmt;
    return $statements;
}

$step = (int)($_GET['step'] ?? 1);
$error = '';
$success = '';

// ── ADIM 1: DB BİLGİLERİ ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 1) {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $name = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = (string)($_POST['db_pass'] ?? '');

    if (!$name || !$user) {
        $error = 'Veritabanı adı ve kullanıcı zorunlu.';
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $_SESSION['ag_setup'] = compact('host','name','user','pass');
            header('Location: ?step=2');
            exit;
        } catch (PDOException $e) {
            $error = 'Bağlantı hatası: ' . $e->getMessage();
        }
    }
}

// ── ADIM 2: MİGRASYON ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $db = $_SESSION['ag_setup'] ?? null;
    if (!$db) { header('Location: ?step=1'); exit; }
    try {
        $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4", $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $sqlFile = __DIR__ . '/migrations/v1.0.0.sql';
        if (!file_exists($sqlFile)) {
            $error = 'migrations/v1.0.0.sql bulunamadı.';
        } else {
            $sql = file_get_contents($sqlFile);
            $statements = ag_split_sql($sql);  // ★ AKILLI SPLITTER
            $count = 0;
            foreach ($statements as $stmt) {
                if ($stmt === '') continue;
                $pdo->exec($stmt);
                $count++;
            }
            $_SESSION['ag_setup']['stmt_count'] = $count;
            header('Location: ?step=3');
            exit;
        }
    } catch (PDOException $e) {
        $error = 'Migration hatası: ' . $e->getMessage();
    }
}

// ── ADIM 3: ADMİN OLUŞTUR ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 3) {
    $db = $_SESSION['ag_setup'] ?? null;
    if (!$db) { header('Location: ?step=1'); exit; }

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (!$fullName || !$username || !$email || !$password) {
        $error = 'Tüm alanlar zorunludur.';
    } elseif (strlen($password) < 10) {
        $error = 'Şifre en az 10 karakter olmalı.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçersiz e-posta.';
    } else {
        try {
            $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4", $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $hash = password_hash($password, PASSWORD_ARGON2ID);
            // Mevcut kullanıcı varsa güncelle (idempotent)
            $stmt = $pdo->prepare("SELECT id FROM ag_users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $pdo->prepare("UPDATE ag_users SET password_hash = ?, full_name = ?, role = 'superadmin', is_active = 1, password_changed_at = NOW() WHERE id = ?")
                    ->execute([$hash, $fullName, $row['id']]);
            } else {
                $pdo->prepare("INSERT INTO ag_users (username, email, password_hash, full_name, role, is_active, password_changed_at)
                               VALUES (?, ?, ?, ?, 'superadmin', 1, NOW())")
                    ->execute([$username, $email, $hash, $fullName]);
            }

            $secret = bin2hex(random_bytes(32));
            $configContent = <<<PHP
<?php
declare(strict_types=1);

define('AG_ENV', 'production');
define('AG_DEBUG', false);
define('AG_VERSION', '1.0.0');

define('DB_HOST', '{$db['host']}');
define('DB_NAME', '{$db['name']}');
define('DB_USER', '{$db['user']}');
define('DB_PASS', '{$db['pass']}');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX', 'ag_');

define('SITE_URL', 'https://aksoy.web.tr');
define('SITE_NAME', 'AKSOY GROUP');
define('SITE_LANG', 'tr');
define('SITE_TIMEZONE', 'Europe/Istanbul');

define('AG_ROOT', dirname(__DIR__));
define('AG_INCLUDES', AG_ROOT . '/includes');
define('AG_UPLOADS', AG_ROOT . '/uploads');
define('AG_LOGS', AG_ROOT . '/logs');
define('AG_MIGRATIONS', AG_ROOT . '/migrations');
define('AG_PUBLIC_UPLOADS', '/uploads');

define('AG_SECRET_KEY', '{$secret}');
define('AG_CSRF_LIFETIME', 7200);
define('AG_SESSION_LIFETIME', 28800);
define('AG_MAX_LOGIN_ATTEMPTS', 5);
define('AG_LOGIN_LOCKOUT_MINUTES', 15);
define('AG_PASSWORD_MIN_LENGTH', 10);

define('AG_GITHUB_OWNER', 'codegatr');
define('AG_GITHUB_REPO', 'aksoygroup');
define('AG_GITHUB_BRANCH', 'main');

define('AG_MAIL_FROM_NAME', 'AKSOY GROUP');
define('AG_MAIL_FROM_EMAIL', 'noreply@aksoy.web.tr');

if (AG_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', AG_ROOT . '/logs/php-error.log');
}
date_default_timezone_set(SITE_TIMEZONE);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty(\$_SERVER['HTTPS'])) ini_set('session.cookie_secure', '1');
    session_name('AGSESSID');
    session_start();
}
PHP;
            file_put_contents(__DIR__ . '/includes/config.php', $configContent);
            @chmod(__DIR__ . '/includes/config.php', 0640);
            file_put_contents(__DIR__ . '/includes/.installed.lock', date('c') . " | by {$username}");

            unset($_SESSION['ag_setup']);
            $success = "Kurulum tamamlandı! Yönetim paneline yönlendiriliyorsunuz...";
            header('refresh:3; url=/yonetim/login.php');
        } catch (PDOException $e) {
            $error = 'Hata: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Aksoy Group — Kurulum</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@200;300;400&family=Manrope:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Manrope', system-ui, sans-serif;
    background: #0A0E1A; color: #F5F1E8; min-height: 100vh; padding: 40px 20px;
    background-image: radial-gradient(ellipse at top, rgba(201,169,97,.06) 0%, transparent 60%),
                      radial-gradient(ellipse at bottom, rgba(139,0,0,.04) 0%, transparent 60%);
}
.wrap { max-width: 640px; margin: 40px auto; }
.brand { text-align: center; margin-bottom: 50px; }
.brand h1 { font-family: 'Fraunces', serif; font-weight: 200; font-size: 56px; letter-spacing: .12em; color: #C9A961; }
.brand p { color: #8c8a82; margin-top: 8px; font-size: 13px; letter-spacing: .15em; text-transform: uppercase; }
.steps { display: flex; gap: 8px; margin-bottom: 30px; justify-content: center; flex-wrap: wrap; }
.step { display: flex; align-items: center; gap: 8px; padding: 8px 16px; border: 1px solid #2A2E3A; border-radius: 100px; font-size: 12px; color: #6c6a62; letter-spacing: .05em; }
.step.active { background: #C9A961; color: #0A0E1A; border-color: #C9A961; }
.step.done { color: #C9A961; border-color: #C9A961; }
.step .num { width: 20px; height: 20px; border-radius: 50%; background: #1A1E2A; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 600; }
.step.active .num { background: #0A0E1A; color: #C9A961; }
.step.done .num { background: #C9A961; color: #0A0E1A; }
.card { background: #0F1424; border: 1px solid #2A2E3A; border-radius: 12px; padding: 40px; box-shadow: 0 24px 80px -20px rgba(0,0,0,.5); }
h2 { font-family: 'Fraunces', serif; font-weight: 300; font-size: 28px; margin-bottom: 8px; letter-spacing: .01em; }
.subtitle { color: #8c8a82; margin-bottom: 30px; font-size: 14px; line-height: 1.6; }
.field { margin-bottom: 18px; }
.field label { display: block; font-size: 11px; letter-spacing: .12em; color: #C9A961; text-transform: uppercase; margin-bottom: 8px; }
.field input { width: 100%; padding: 12px 14px; background: #0A0E1A; border: 1px solid #2A2E3A; color: #F5F1E8; border-radius: 6px; font-family: inherit; font-size: 14px; transition: all .2s; }
.field input:focus { outline: none; border-color: #C9A961; background: #0F1428; }
.field .hint { font-size: 11px; color: #6c6a62; margin-top: 6px; }
button { width: 100%; padding: 14px; background: #C9A961; color: #0A0E1A; border: none; border-radius: 6px; font-family: 'Fraunces', serif; font-size: 14px; font-weight: 400; letter-spacing: .15em; text-transform: uppercase; cursor: pointer; transition: all .2s; margin-top: 14px; }
button:hover { background: #d6b870; transform: translateY(-1px); }
.alert { padding: 14px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; line-height: 1.5; word-break: break-word; }
.alert.error { background: #2a1414; color: #ff9999; border-left: 3px solid #c83a3a; }
.alert.success { background: #142a1a; color: #99ffaa; border-left: 3px solid #3ac850; }
.alert.info { background: #142028; color: #99c8ff; border-left: 3px solid #3a78c8; }
ul.summary { list-style: none; }
ul.summary li { padding: 10px 0; border-bottom: 1px solid #2A2E3A; display: flex; justify-content: space-between; font-size: 13px; }
ul.summary li:last-child { border: 0; }
ul.summary li span:first-child { color: #8c8a82; }
ul.summary li span:last-child { color: #F5F1E8; font-family: 'Fraunces', serif; }
.row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 540px) { .row { grid-template-columns: 1fr; } .card { padding: 24px; } }
.note { margin-top: 24px; font-size: 12px; color: #6c6a62; text-align: center; line-height: 1.6; }
</style>
</head>
<body>
<div class="wrap">
    <div class="brand">
        <h1>AKSOY</h1>
        <p>Group Platform Setup · v1.0.0-fix2</p>
    </div>
    <div class="steps">
        <div class="step <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>"><span class="num">1</span> Veritabanı</div>
        <div class="step <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>"><span class="num">2</span> Migrasyon</div>
        <div class="step <?= $step >= 3 ? 'active' : '' ?>"><span class="num">3</span> Yönetici</div>
    </div>
    <div class="card">
        <?php if ($error): ?><div class="alert error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>

        <?php if ($step === 1): ?>
            <h2>Veritabanı Bilgileri</h2>
            <p class="subtitle">DirectAdmin → MySQL Management bölümünden alınan verileri girin. Aksoy Group platformu bu veritabanı üzerine kurulacak.</p>
            <form method="post">
                <div class="field"><label>Sunucu</label><input type="text" name="db_host" value="localhost" required></div>
                <div class="field"><label>Veritabanı Adı</label><input type="text" name="db_name" placeholder="aksoyweb_holding" required></div>
                <div class="field"><label>Kullanıcı Adı</label><input type="text" name="db_user" required></div>
                <div class="field"><label>Şifre</label><input type="password" name="db_pass"></div>
                <button type="submit">Bağlantıyı Test Et &nbsp;→</button>
            </form>
        <?php elseif ($step === 2): ?>
            <h2>Veritabanı Migrasyonu</h2>
            <p class="subtitle">23 tablo oluşturulacak ve varsayılan içerikler (9 sektör, 9 iştirak, ayarlar) yüklenecek. Migrasyon idempotenttir; tekrar çalıştırılması güvenlidir.</p>
            <ul class="summary">
                <li><span>Sunucu</span><span><?= htmlspecialchars($_SESSION['ag_setup']['host'] ?? '') ?></span></li>
                <li><span>Veritabanı</span><span><?= htmlspecialchars($_SESSION['ag_setup']['name'] ?? '') ?></span></li>
                <li><span>Kullanıcı</span><span><?= htmlspecialchars($_SESSION['ag_setup']['user'] ?? '') ?></span></li>
                <li><span>Sürüm</span><span>v1.0.0 — Genesis</span></li>
            </ul>
            <form method="post" style="margin-top: 24px;"><button type="submit">Migrasyonu Çalıştır &nbsp;→</button></form>
        <?php elseif ($step === 3): ?>
            <h2>Yönetici Hesabı</h2>
            <p class="subtitle">Süper yönetici (superadmin) hesabınızı oluşturun. Bu hesapla tüm sisteme erişebileceksiniz.</p>
            <?php if (!empty($_SESSION['ag_setup']['stmt_count'])): ?>
                <div class="alert info">✓ <?= (int)$_SESSION['ag_setup']['stmt_count'] ?> SQL ifadesi başarıyla çalıştırıldı.</div>
            <?php endif; ?>
            <form method="post">
                <div class="field"><label>Ad Soyad</label><input type="text" name="full_name" required></div>
                <div class="row">
                    <div class="field"><label>Kullanıcı Adı</label><input type="text" name="username" required></div>
                    <div class="field"><label>E-posta</label><input type="email" name="email" required></div>
                </div>
                <div class="field"><label>Şifre</label><input type="password" name="password" minlength="10" required>
                    <div class="hint">En az 10 karakter; büyük harf, küçük harf, rakam içermelidir.</div>
                </div>
                <button type="submit">Kurulumu Tamamla &nbsp;→</button>
            </form>
        <?php endif; ?>
    </div>
    <p class="note">CODEGA × AKSOY GROUP · Setup v1.0.0-fix2</p>
</div>
</body>
</html>
