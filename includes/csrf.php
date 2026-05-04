<?php
/**
 * AKSOY GROUP — CSRF Koruması
 * HMAC tabanlı, stateless. Token = base64(timestamp.nonce.hmac)
 * @package AksoyHolding\Core
 */

declare(strict_types=1);

final class CSRF
{
    public static function token(): string
    {
        $ts = time();
        $nonce = bin2hex(random_bytes(8));
        $payload = $ts . '.' . $nonce;
        $hmac = hash_hmac('sha256', $payload, AG_SECRET_KEY);
        return base64_encode($payload . '.' . $hmac);
    }

    public static function check(?string $token): bool
    {
        if (!$token) return false;
        $decoded = base64_decode($token, true);
        if ($decoded === false) return false;
        $parts = explode('.', $decoded);
        if (count($parts) !== 3) return false;
        [$ts, $nonce, $hmac] = $parts;
        if (!ctype_digit($ts)) return false;
        if (time() - (int)$ts > AG_CSRF_LIFETIME) return false;
        $expected = hash_hmac('sha256', $ts . '.' . $nonce, AG_SECRET_KEY);
        return hash_equals($expected, $hmac);
    }

    /** Form içine yerleştirilecek hidden input. */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::token() . '">';
    }

    /** POST request'lerde otomatik kontrol; başarısızsa 419 + exit. */
    public static function require(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!self::check($token)) {
            http_response_code(419);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'json')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Oturum süresi doldu, sayfayı yenileyin.']);
            } else {
                echo '<h1>419 — Sayfa süresi doldu</h1><p>Lütfen sayfayı yenileyip tekrar deneyin.</p>';
            }
            exit;
        }
    }
}
