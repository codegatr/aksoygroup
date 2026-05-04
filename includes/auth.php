<?php
/**
 * AKSOY GROUP — Admin Kimlik Doğrulama
 * @package AksoyHolding\Core
 */

declare(strict_types=1);

final class Auth
{
    /** Login dene, sonuç dizi olarak. */
    public static function attempt(string $username, string $password): array
    {
        $ip = clientIp();

        // Rate limit kontrolü (IP bazlı)
        if (self::isRateLimited($ip)) {
            return ['ok' => false, 'error' => 'Çok fazla başarısız deneme. ' . AG_LOGIN_LOCKOUT_MINUTES . ' dakika sonra tekrar deneyin.'];
        }

        $user = DB::row(
            "SELECT * FROM ag_users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1",
            [$username, $username]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::logAttempt($username, $ip, false);
            return ['ok' => false, 'error' => 'Kullanıcı adı veya şifre hatalı.'];
        }

        // Hesap kilitli mi?
        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            return ['ok' => false, 'error' => 'Hesap geçici olarak kilitli.'];
        }

        // Başarılı login
        self::logAttempt($username, $ip, true);
        DB::exec(
            "UPDATE ag_users SET last_login_at = NOW(), last_login_ip = ?, failed_attempts = 0, locked_until = NULL WHERE id = ?",
            [$ip, $user['id']]
        );

        // Session
        session_regenerate_id(true);
        $_SESSION['user_id']   = (int)$user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['login_at']  = time();
        $_SESSION['login_ip']  = $ip;

        Audit::log('login_success', 'user', (int)$user['id'], null, ['ip' => $ip]);
        return ['ok' => true, 'user' => $user];
    }

    public static function logout(): void
    {
        if (!empty($_SESSION['user_id'])) {
            Audit::log('logout', 'user', (int)$_SESSION['user_id']);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        if (empty($_SESSION['user_id'])) return false;
        if (empty($_SESSION['login_at'])) return false;
        // Session timeout
        if (time() - (int)$_SESSION['login_at'] > AG_SESSION_LIFETIME) {
            self::logout();
            return false;
        }
        return true;
    }

    public static function require(): void
    {
        if (!self::check()) {
            redirect('/yonetim/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/yonetim/'));
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::require();
        $current = $_SESSION['user_role'] ?? '';
        if (!in_array($current, $roles, true)) {
            http_response_code(403);
            die('<h1>403 — Yetkisiz erişim</h1>');
        }
    }

    public static function user(): ?array
    {
        if (!self::check()) return null;
        static $cache = null;
        if ($cache === null) {
            $cache = DB::row("SELECT id, username, email, full_name, role, avatar FROM ag_users WHERE id = ?", [$_SESSION['user_id']]);
        }
        return $cache;
    }

    public static function userId(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    public static function role(): string
    {
        return $_SESSION['user_role'] ?? '';
    }

    public static function isSuperAdmin(): bool
    {
        return self::role() === 'superadmin';
    }

    public static function isAdmin(): bool
    {
        return in_array(self::role(), ['superadmin', 'admin'], true);
    }

    private static function isRateLimited(string $ip): bool
    {
        $count = (int)DB::scalar(
            "SELECT COUNT(*) FROM ag_login_attempts
             WHERE ip_adresi = ? AND success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$ip, AG_LOGIN_LOCKOUT_MINUTES]
        );
        return $count >= AG_MAX_LOGIN_ATTEMPTS;
    }

    private static function logAttempt(string $username, string $ip, bool $success): void
    {
        try {
            DB::insert('ag_login_attempts', [
                'username'   => substr($username, 0, 100),
                'ip_adresi'  => $ip,
                'success'    => $success ? 1 : 0,
                'user_agent' => userAgent(),
            ]);
        } catch (Throwable) {
            // sessizce yut
        }
    }

    /** Şifre güvenlik kontrolü. */
    public static function isStrongPassword(string $pw): bool
    {
        if (strlen($pw) < AG_PASSWORD_MIN_LENGTH) return false;
        if (!preg_match('/[A-Z]/', $pw)) return false;
        if (!preg_match('/[a-z]/', $pw)) return false;
        if (!preg_match('/[0-9]/', $pw)) return false;
        return true;
    }

    public static function hashPassword(string $pw): string
    {
        return password_hash($pw, PASSWORD_ARGON2ID);
    }
}
