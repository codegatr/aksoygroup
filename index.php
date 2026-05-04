<?php
/**
 * AKSOY GROUP — Ana Sayfa
 * Faz 3'te tam editoryal frontend ile değiştirilecek.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

// Sektörleri çek
$sektorler = DB::all("SELECT * FROM ag_sektorler WHERE is_active = 1 ORDER BY sort_order ASC");
$rakamlar  = DB::all("SELECT * FROM ag_rakamlar WHERE is_active = 1 ORDER BY sort_order ASC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title><?= h(setting('site_title', 'AKSOY GROUP')) ?> — <?= h(setting('site_tagline', '')) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= ha(setting('site_description', '')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@200;300;400;500&family=Manrope:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Manrope',sans-serif; background:#0A0E1A; color:#F5F1E8; }
.placeholder {
    min-height: 100vh;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    text-align: center; padding: 40px 20px;
    background-image: radial-gradient(ellipse at top, rgba(201,169,97,.08) 0%, transparent 60%);
}
h1 { font-family:'Fraunces',serif; font-weight:200; font-size:clamp(64px,12vw,160px); letter-spacing:.12em; color:#C9A961; }
.tag { color:#8c8a82; margin-top:20px; letter-spacing:.2em; text-transform:uppercase; font-size:13px; }
.grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1px; background:#2A2E3A; max-width:1200px; margin:60px auto 0; }
.grid > div { background:#0A0E1A; padding:30px; }
.grid h3 { font-family:'Fraunces',serif; font-weight:300; font-size:20px; color:#F5F1E8; margin-bottom:8px; }
.grid h3 .num { color:#C9A961; font-size:13px; letter-spacing:.2em; display:block; margin-bottom:6px; }
.grid p { color:#8c8a82; font-size:13px; line-height:1.6; }
.note { margin-top:60px; padding:20px 30px; border:1px solid #2A2E3A; border-radius:6px; max-width:540px; }
.note p { color:#8c8a82; font-size:13px; line-height:1.7; }
.note a { color:#C9A961; text-decoration:none; border-bottom:1px solid #C9A961; }
</style>
</head>
<body>
<div class="placeholder">
    <h1>AKSOY</h1>
    <p class="tag"><?= h(setting('site_tagline', 'Türkiye\'nin Çok Sektörlü Vizyon Holding\'i')) ?></p>

    <div class="grid">
        <?php foreach ($sektorler as $s): ?>
            <div>
                <h3>
                    <span class="num"><?= h($s['roman_no']) ?></span>
                    <?= h($s['ad']) ?>
                </h3>
                <p><?= h($s['alt_baslik']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="note">
        <p><strong>v<?= h(setting('current_version', AG_VERSION)) ?></strong> — Genesis Launch · Bu, geçici bir karşılama sayfasıdır. Tam editoryal ön yüz Faz 3 ile gelecek.</p>
        <p style="margin-top:12px;">Yönetim Paneli: <a href="/yonetim/">/yonetim/</a></p>
    </div>
</div>
</body>
</html>
