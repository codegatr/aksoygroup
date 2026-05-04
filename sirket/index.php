<?php
/**
 * AKSOY GROUP — Şirket Detay
 * Path: /sirket/{slug}
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) { http_response_code(404); exit; }

$sirket = DB::row("SELECT s.*, sk.ad AS sektor_ad, sk.slug AS sektor_slug, sk.roman_no
                   FROM ag_sirketler s
                   LEFT JOIN ag_sektorler sk ON s.sektor_id = sk.id
                   WHERE s.slug = ? AND s.durum = 'aktif'", [$slug]);

if (!$sirket) {
    http_response_code(404);
    require_once __DIR__ . '/../includes/templates/header.php';
    echo '<section class="section" style="text-align:center;padding-top:200px"><div class="container"><h1 class="serif" style="font-size:64px;font-weight:200;color:var(--gold)">404</h1><p>Şirket bulunamadı.</p><a href="/sirketler" class="btn outline" style="margin-top:32px">Tüm Şirketler</a></div></section>';
    require_once __DIR__ . '/../includes/templates/footer.php';
    exit;
}

// Bu şirketle ilgili haberler
$haberler = DB::all("SELECT h.*, k.ad AS kategori_ad
                     FROM ag_haberler h
                     LEFT JOIN ag_haber_kategori k ON h.kategori_id = k.id
                     WHERE h.sirket_id = ? AND h.is_active = 1
                     ORDER BY COALESCE(h.yayim_tarihi, h.created_at) DESC LIMIT 4", [$sirket['id']]);

$page = [
    'title'       => $sirket['kisa_unvan'] ?? $sirket['unvan'],
    'description' => $sirket['slogan'] ?? ($sirket['unvan'] . ' — Aksoy Group iştiraki'),
    'og_image'    => !empty($sirket['kapak_gorsel']) ? uploadUrl($sirket['kapak_gorsel']) : null,
];
require_once __DIR__ . '/../includes/templates/header.php';
?>

<!-- ════ HERO ════ -->
<section class="page-hero" style="<?= !empty($sirket['kapak_gorsel']) ? "background-image:linear-gradient(180deg, rgba(10,14,26,.85), var(--bg) 100%), url(" . uploadUrl($sirket['kapak_gorsel']) . ");background-size:cover;background-position:center" : "" ?>">
    <?php if (!empty($sirket['roman_no'])): ?>
    <div class="roman-bg"><?= h($sirket['roman_no']) ?></div>
    <?php endif; ?>
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Ana Sayfa</a> · <a href="/sirketler">Şirketler</a>
            <?php if (!empty($sirket['sektor_slug'])): ?>
            · <a href="/sektor/<?= ha($sirket['sektor_slug']) ?>"><?= h($sirket['sektor_ad']) ?></a>
            <?php endif; ?>
            · <?= h($sirket['kisa_unvan'] ?? $sirket['unvan']) ?>
        </div>

        <div style="display:flex;align-items:flex-start;gap:32px;flex-wrap:wrap">
            <?php if (!empty($sirket['logo'])): ?>
            <div style="flex-shrink:0;width:120px;height:120px;border-radius:50%;background:var(--bg-2);border:1px solid var(--line-2);display:flex;align-items:center;justify-content:center;padding:18px">
                <img src="<?= h(uploadUrl($sirket['logo'])) ?>" alt="<?= ha($sirket['kisa_unvan']) ?>" style="max-width:100%;max-height:100%;object-fit:contain">
            </div>
            <?php endif; ?>
            <div style="flex:1;min-width:280px">
                <h1 style="margin-bottom:12px"><?= h($sirket['unvan']) ?></h1>
                <?php if (!empty($sirket['slogan'])): ?>
                <div style="font-family:'Fraunces',serif;font-style:italic;color:var(--gold);font-size:22px;margin-bottom:16px">"<?= h($sirket['slogan']) ?>"</div>
                <?php endif; ?>
                <?php if (!empty($sirket['sektor_ad'])): ?>
                <div style="display:inline-block;padding:6px 16px;border:1px solid var(--gold-dark);border-radius:100px;font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold)">
                    <?= h($sirket['sektor_ad']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ════ İÇERİK + İLETİŞİM PANELİ ════ -->
<section class="content-section">
    <div class="container">
        <div class="content-grid">
            <article class="prose">
                <?php if (!empty($sirket['aciklama'])): ?>
                    <?= $sirket['aciklama'] ?>
                <?php else: ?>
                    <h2>Hakkımızda</h2>
                    <p><?= h($sirket['unvan']) ?>, Aksoy Group bünyesinde <?= h($sirket['sektor_ad'] ?? 'sektörel') ?> alanda faaliyet göstermektedir<?= !empty($sirket['kurulus_yili']) ? ' ve ' . (int)$sirket['kurulus_yili'] . ' yılından bu yana hizmet vermektedir' : '' ?>.</p>
                    <?php if (!empty($sirket['merkez_sehir'])): ?>
                    <p>Merkez ofisimiz <strong><?= h($sirket['merkez_sehir']) ?></strong>'da bulunmaktadır.</p>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($haberler): ?>
                <h2 style="margin-top:64px">Şirketten Haberler</h2>
                <div class="news-grid" style="margin-top:24px">
                    <?php foreach ($haberler as $hb): ?>
                    <a href="/haber/<?= ha($hb['slug']) ?>" class="news-card">
                        <?php if (!empty($hb['kapak_gorsel'])): ?>
                        <div class="cover"><img src="<?= h(uploadUrl($hb['kapak_gorsel'])) ?>" alt="<?= ha($hb['baslik']) ?>"></div>
                        <?php endif; ?>
                        <div class="body">
                            <div class="meta"><?= h(formatDate($hb['yayim_tarihi'] ?? $hb['created_at'])) ?></div>
                            <h4 style="font-size:18px"><?= h($hb['baslik']) ?></h4>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </article>

            <aside>
                <div class="info-card">
                    <h4>Şirket Bilgileri</h4>
                    <ul>
                        <?php if (!empty($sirket['kurulus_yili'])): ?>
                        <li><span class="lbl">Kuruluş</span><span class="val"><?= (int)$sirket['kurulus_yili'] ?></span></li>
                        <?php endif; ?>
                        <?php if (!empty($sirket['merkez_sehir'])): ?>
                        <li><span class="lbl">Merkez</span><span class="val"><?= h($sirket['merkez_sehir']) ?></span></li>
                        <?php endif; ?>
                        <?php if (!empty('')): ?>
                        <li><span class="lbl">Çalışan</span><span class="val"><?= h('') ?></span></li>
                        <?php endif; ?>
                        <?php if (!empty($sirket['vergi_dairesi'])): ?>
                        <li><span class="lbl">Vergi Dairesi</span><span class="val"><?= h($sirket['vergi_dairesi']) ?></span></li>
                        <?php endif; ?>
                        <?php if (!empty($sirket['vergi_no'])): ?>
                        <li><span class="lbl">Vergi No</span><span class="val"><?= h($sirket['vergi_no']) ?></span></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <?php if (!empty($sirket['email']) || !empty($sirket['telefon']) || !empty($sirket['web_url']) || !empty($sirket['merkez_adres'])): ?>
                <div class="info-card">
                    <h4>İletişim</h4>
                    <ul>
                        <?php if (!empty($sirket['email'])): ?>
                        <li><span class="lbl">E-posta</span><a href="mailto:<?= ha($sirket['email']) ?>" class="val"><?= h($sirket['email']) ?></a></li>
                        <?php endif; ?>
                        <?php if (!empty($sirket['telefon'])): ?>
                        <li><span class="lbl">Telefon</span><a href="tel:<?= ha(preg_replace('/[^0-9+]/', '', $sirket['telefon'])) ?>" class="val"><?= h($sirket['telefon']) ?></a></li>
                        <?php endif; ?>
                        <?php if (!empty($sirket['web_url'])): ?>
                        <li><span class="lbl">Web</span><a href="<?= ha($sirket['web_url']) ?>" target="_blank" rel="noopener" class="val"><?= h(parse_url($sirket['web_url'], PHP_URL_HOST) ?: $sirket['web_url']) ?></a></li>
                        <?php endif; ?>
                        <?php if (!empty($sirket['merkez_adres'])): ?>
                        <li style="display:block"><span class="lbl" style="display:block;margin-bottom:6px">Adres</span><span class="val" style="text-align:left;font-size:13px;color:var(--text-soft);line-height:1.6"><?= nl2br(h($sirket['merkez_adres'])) ?></span></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php
                // Sosyal medya
                $sosyal = [];
                foreach (['linkedin_url'=>'LinkedIn','twitter_url'=>'Twitter','instagram_url'=>'Instagram','facebook_url'=>'Facebook','youtube_url'=>'YouTube'] as $col=>$lbl) {
                    if (!empty($sirket[$col])) $sosyal[$col] = ['url'=>$sirket[$col],'lbl'=>$lbl];
                }
                ?>
                <?php if ($sosyal): ?>
                <div class="info-card">
                    <h4>Sosyal Medya</h4>
                    <div style="display:flex;gap:10px;flex-wrap:wrap">
                        <?php foreach ($sosyal as $col => $info): ?>
                        <a href="<?= ha($info['url']) ?>" target="_blank" rel="noopener" title="<?= ha($info['lbl']) ?>"
                           style="width:38px;height:38px;border:1px solid var(--line-2);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--gold);text-transform:uppercase;font-size:11px">
                            <?= h(strtoupper(substr($info['lbl'], 0, 2))) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/templates/footer.php'; ?>
