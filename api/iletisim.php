<?php
/**
 * AKSOY GROUP — İletişim Formu Handler
 * POST /api/iletisim.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/iletisim');
}

try { CSRF::require(); }
catch (Throwable $e) { flash('error', 'Güvenlik doğrulaması başarısız. Lütfen formu yenileyin.'); redirect('/iletisim'); }

// Honeypot kontrolü (bot tespiti)
if (!empty($_POST['website'])) {
    // Bot — sessizce başarılı görünelim, DB'ye yazma, mail atma
    $_SESSION['_iletisim_sent'] = true;
    redirect('/iletisim');
}

// Veri al
$adSoyad  = trim($_POST['ad_soyad'] ?? '');
$email    = trim($_POST['email'] ?? '');
$telefon  = trim($_POST['telefon'] ?? '');
$konu     = trim($_POST['konu'] ?? '');
$mesaj    = trim($_POST['mesaj'] ?? '');

// Validasyon
$errors = [];
if (mb_strlen($adSoyad) < 2 || mb_strlen($adSoyad) > 120) $errors[] = 'Ad Soyad geçersiz.';
if (!isEmail($email))                                     $errors[] = 'E-posta adresi geçersiz.';
if ($telefon && !isPhone($telefon))                       $errors[] = 'Telefon numarası geçersiz.';
if (!in_array($konu, ['yatirim','tedarik','basin','kariyer','genel'], true)) $errors[] = 'Konu seçimi geçersiz.';
if (mb_strlen($mesaj) < 10 || mb_strlen($mesaj) > 4000)   $errors[] = 'Mesaj 10–4000 karakter arası olmalı.';

if ($errors) {
    flash('error', implode(' ', $errors));
    redirect('/iletisim');
}

// IP rate limit (basit: aynı IP'den son 1 dakikada en fazla 1 mesaj)
$ip = clientIp();
try {
    $recent = (int)DB::scalar(
        "SELECT COUNT(*) FROM ag_iletisim_mesajlari
         WHERE ip_adresi = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)",
        [$ip]
    );
    if ($recent > 0) {
        flash('error', 'Çok hızlı gönderiyorsunuz. Lütfen bir dakika bekleyin.');
        redirect('/iletisim');
    }
} catch (Throwable $e) {}

// DB'ye kaydet
$mesajId = 0;
try {
    $mesajId = DB::insert('ag_iletisim_mesajlari', [
        'ad_soyad'   => $adSoyad,
        'email'      => $email,
        'telefon'    => $telefon ?: null,
        'konu'       => $konu,
        'mesaj'      => $mesaj,
        'ip_adresi'  => $ip,
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
        'okundu'     => 0,
    ]);
} catch (Throwable $e) {
    error_log('[iletisim] DB insert: ' . $e->getMessage());
    flash('error', 'Mesaj kaydedilemedi. Lütfen daha sonra tekrar deneyin.');
    redirect('/iletisim');
}

// Bildirim e-postası gönder (sessiz fail — DB'ye zaten yazıldı)
try {
    $notifyTo = setting('notify_email') ?: setting('contact_email');
    if ($notifyTo) {
        $konuLabels = [
            'yatirim'  => 'Yatırım & Ortaklık',
            'tedarik'  => 'Tedarik & İş Geliştirme',
            'basin'    => 'Basın & Medya',
            'kariyer'  => 'Kariyer',
            'genel'    => 'Genel Bilgi',
        ];
        $konuLabel = $konuLabels[$konu] ?? $konu;

        $body = "<p><strong>Yeni iletişim mesajı geldi.</strong></p>"
              . "<table style='border-collapse:collapse;width:100%;font-family:Arial,sans-serif;font-size:14px'>"
              . "<tr><td style='padding:8px;background:#f5f5f5;border:1px solid #ddd;width:140px'><strong>Ad Soyad</strong></td><td style='padding:8px;border:1px solid #ddd'>" . h($adSoyad) . "</td></tr>"
              . "<tr><td style='padding:8px;background:#f5f5f5;border:1px solid #ddd'><strong>E-posta</strong></td><td style='padding:8px;border:1px solid #ddd'><a href='mailto:" . h($email) . "'>" . h($email) . "</a></td></tr>"
              . ($telefon ? "<tr><td style='padding:8px;background:#f5f5f5;border:1px solid #ddd'><strong>Telefon</strong></td><td style='padding:8px;border:1px solid #ddd'>" . h($telefon) . "</td></tr>" : '')
              . "<tr><td style='padding:8px;background:#f5f5f5;border:1px solid #ddd'><strong>Konu</strong></td><td style='padding:8px;border:1px solid #ddd'>" . h($konuLabel) . "</td></tr>"
              . "<tr><td style='padding:8px;background:#f5f5f5;border:1px solid #ddd;vertical-align:top'><strong>Mesaj</strong></td><td style='padding:8px;border:1px solid #ddd;white-space:pre-wrap'>" . nl2br(h($mesaj)) . "</td></tr>"
              . "<tr><td style='padding:8px;background:#f5f5f5;border:1px solid #ddd'><strong>IP</strong></td><td style='padding:8px;border:1px solid #ddd;color:#888'>" . h($ip) . "</td></tr>"
              . "</table>"
              . "<p style='margin-top:24px'><a href='https://aksoy.web.tr/yonetim/?m=iletisim' style='background:#C9A961;color:#0A0E1A;padding:10px 22px;text-decoration:none;font-size:13px;letter-spacing:.1em'>Yönetim Panelinde Aç</a></p>";

        Mailer::send(
            $notifyTo,
            '[Aksoy Group] Yeni İletişim Mesajı: ' . $konuLabel,
            $body,
            ['reply_to' => $email]
        );
    }
} catch (Throwable $e) {
    error_log('[iletisim] mail: ' . $e->getMessage());
    // sessiz devam — kullanıcıya başarılı görünsün
}

// Audit log
try { Audit::log('contact_form_submit', 'message', $mesajId, null, ['konu' => $konu, 'email' => $email]); } catch (Throwable $e) {}

$_SESSION['_iletisim_sent'] = true;
redirect('/iletisim');
