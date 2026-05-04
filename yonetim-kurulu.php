<?php
/**
 * AKSOY GROUP — Yönetim Kurulu (Public)
 * Path: /yonetim-kurulu (slug üzerinden detay: /yonetim-kurulu?slug=xxx)
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$slug = trim($_GET['slug'] ?? '');

// Detay görünümü
if ($slug) {
    $member = DB::row("SELECT * FROM ag_yonetim_kurulu WHERE slug = ? AND is_active = 1", [$slug]);
    if (!$member) {
        http_response_code(404);
        require_once __DIR__ . '/includes/templates/header.php';
        echo '<section class="section" style="text-align:center;padding-top:200px"><div class="container"><h1 class="serif" style="font-size:64px;font-weight:200;color:var(--gold-dark)">404</h1><p>Üye bulunamadı.</p><a href="/yonetim-kurulu" class="btn outline" style="margin-top:32px">Yönetim Kuruluna Dön</a></div></section>';
        require_once __DIR__ . '/includes/templates/footer.php';
        exit;
    }

    $page = [
        'title'       => $member['ad_soyad'] . ' — ' . $member['unvan'],
        'description' => $member['kisa_biyografi'] ?? ($member['ad_soyad'] . ' — Aksoy Group ' . $member['unvan']),
    ];
    require_once __DIR__ . '/includes/templates/header.php';
    ?>
    <section class="page-hero">
        <div class="container">
            <div class="breadcrumb"><a href="/">Ana Sayfa</a> · <a href="/yonetim-kurulu">Yönetim Kurulu</a> · <?= h($member['ad_soyad']) ?></div>
            <div style="display:flex;align-items:flex-start;gap:48px;flex-wrap:wrap">
                <?php if (!empty($member['fotograf'])): ?>
                <div style="flex-shrink:0">
                    <img src="<?= h(uploadUrl($member['fotograf'])) ?>" alt="<?= ha($member['ad_soyad']) ?>"
                         style="width:220px;height:220px;border-radius:50%;object-fit:cover;border:1px solid var(--line-2);box-shadow:0 16px 40px rgba(15,20,36,.08)">
                </div>
                <?php endif; ?>
                <div style="flex:1;min-width:280px">
                    <div class="pretitle" style="font-size:11px;letter-spacing:.3em;text-transform:uppercase;color:var(--gold-dark);margin-bottom:18px;font-weight:600"><?= h($member['unvan']) ?></div>
                    <h1 style="margin-bottom:12px"><?= h($member['ad_soyad']) ?></h1>
                    <?php if (!empty($member['pozisyon'])): ?>
                    <div style="font-family:'Fraunces',serif;font-style:italic;color:var(--gold-dark);font-size:22px;margin-bottom:18px"><?= h($member['pozisyon']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($member['kisa_biyografi'])): ?>
                    <p class="lead"><?= h($member['kisa_biyografi']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="content-section">
        <div class="container">
            <div class="content-grid">
                <article class="prose">
                    <?php if (!empty($member['uzun_biyografi'])): ?>
                        <h2>Biyografi</h2>
                        <?= nl2br(h($member['uzun_biyografi'])) ?>
                    <?php endif; ?>
                </article>
                <aside>
                    <?php if (!empty($member['egitim'])): ?>
                    <div class="info-card">
                        <h4>Eğitim</h4>
                        <div style="font-size:14px;line-height:1.8;color:var(--text-soft);white-space:pre-line"><?= h($member['egitim']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($member['linkedin_url']) || !empty($member['email'])): ?>
                    <div class="info-card">
                        <h4>İletişim</h4>
                        <ul>
                            <?php if (!empty($member['linkedin_url'])): ?>
                            <li><span class="lbl">LinkedIn</span><a href="<?= ha($member['linkedin_url']) ?>" target="_blank" rel="noopener" class="val">Profili Gör →</a></li>
                            <?php endif; ?>
                            <?php if (!empty($member['email'])): ?>
                            <li><span class="lbl">E-posta</span><a href="mailto:<?= ha($member['email']) ?>" class="val"><?= h($member['email']) ?></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/includes/templates/footer.php';
    exit;
}

// Liste görünümü
$members = DB::all("SELECT * FROM ag_yonetim_kurulu WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
$page = [
    'title'       => 'Yönetim Kurulu',
    'description' => 'Aksoy Group Yönetim Kurulu üyeleri — vizyonun arkasındaki liderlik kadrosu.',
];
require_once __DIR__ . '/includes/templates/header.php';
?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="/">Ana Sayfa</a> · <a href="/kurumsal/hakkimizda">Kurumsal</a> · Yönetim Kurulu</div>
        <h1>Vizyonun arkasındaki <em style="color:var(--gold-dark);font-style:italic;font-weight:300">liderlik</em>.</h1>
        <p class="lead">
            Aksoy Group'u şekillendiren strateji, uzun vadeli yatırım vizyonu ve kurumsal yönetim disiplini —
            yönetim kurulu üyelerimizin deneyim ve birikiminin ürünüdür.
        </p>
    </div>
</section>

<section class="content-section">
    <div class="container">
        <?php if ($members): ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:36px">
            <?php foreach ($members as $m): ?>
            <a href="/yonetim-kurulu?slug=<?= ha($m['slug']) ?>" style="text-decoration:none;color:inherit;display:block;text-align:center;padding:32px 24px;background:var(--bg-3);border:1px solid var(--line);transition:all .3s">
                <?php if (!empty($m['fotograf'])): ?>
                <img src="<?= h(uploadUrl($m['fotograf'])) ?>" alt="<?= ha($m['ad_soyad']) ?>"
                     style="width:160px;height:160px;border-radius:50%;object-fit:cover;margin:0 auto 22px;border:1px solid var(--line-2);display:block">
                <?php else: ?>
                <div style="width:160px;height:160px;border-radius:50%;background:var(--bg-2);display:flex;align-items:center;justify-content:center;margin:0 auto 22px;border:1px solid var(--line-2);font-family:'Fraunces',serif;font-weight:300;font-size:64px;color:var(--gold-dark)">
                    <?= h(strtoupper(mb_substr($m['ad_soyad'], 0, 1))) ?>
                </div>
                <?php endif; ?>
                <h3 style="font-family:'Fraunces',serif;font-weight:500;font-size:22px;margin-bottom:6px;color:var(--text)"><?= h($m['ad_soyad']) ?></h3>
                <div style="font-size:13px;color:var(--gold-dark);letter-spacing:.05em;margin-bottom:8px;font-weight:600"><?= h($m['unvan']) ?></div>
                <?php if (!empty($m['pozisyon'])): ?>
                <div style="font-family:'Fraunces',serif;font-style:italic;color:var(--text-mute);font-size:14px;margin-bottom:14px"><?= h($m['pozisyon']) ?></div>
                <?php endif; ?>
                <?php if (!empty($m['kisa_biyografi'])): ?>
                <p style="font-size:14px;color:var(--text-soft);line-height:1.7;margin-top:14px;border-top:1px solid var(--line);padding-top:14px"><?= h(truncate($m['kisa_biyografi'], 160)) ?></p>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:80px 20px">
            <div class="serif" style="font-size:120px;font-weight:200;color:var(--line-2);line-height:1">⋯</div>
            <p style="color:var(--text-soft);margin-top:24px">Yönetim Kurulu üyeleri yakında bu sayfada yayınlanacaktır.</p>
            <a href="/" class="btn outline" style="margin-top:32px">Ana Sayfaya Dön</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/templates/footer.php'; ?>
