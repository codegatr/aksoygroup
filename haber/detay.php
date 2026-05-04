<?php
/**
 * AKSOY GROUP — Haber Detay
 * Path: /haber/{slug}
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) { http_response_code(404); exit; }

$hb = DB::row("SELECT h.*, k.ad AS kategori_ad, k.slug AS kategori_slug,
                      s.kisa_unvan AS sirket_ad, s.slug AS sirket_slug
               FROM ag_haberler h
               LEFT JOIN ag_haber_kategori k ON h.kategori_id = k.id
               LEFT JOIN ag_sirketler s ON h.sirket_id = s.id
               WHERE h.slug = ? AND h.is_active = 1", [$slug]);

if (!$hb) {
    http_response_code(404);
    require_once __DIR__ . '/../includes/templates/header.php';
    echo '<section class="section" style="text-align:center;padding-top:200px"><div class="container"><h1 class="serif" style="font-size:64px;font-weight:200;color:var(--gold)">404</h1><p>Haber bulunamadı.</p><a href="/haberler" class="btn outline" style="margin-top:32px">Haberlere Dön</a></div></section>';
    require_once __DIR__ . '/../includes/templates/footer.php';
    exit;
}

// Görüntülenme sayacı (silent)
try { DB::exec("UPDATE ag_haberler SET goruntulenme = goruntulenme + 1 WHERE id = ?", [$hb['id']]); } catch (Throwable $e) {}

// İlgili haberler — aynı kategori, kendisi hariç
$ilgili = [];
if ($hb['kategori_id']) {
    $ilgili = DB::all("SELECT h.*, k.ad AS kategori_ad
                       FROM ag_haberler h
                       LEFT JOIN ag_haber_kategori k ON h.kategori_id = k.id
                       WHERE h.kategori_id = ? AND h.id != ? AND h.is_active = 1
                       ORDER BY COALESCE(h.yayim_tarihi, h.created_at) DESC LIMIT 3",
                       [$hb['kategori_id'], $hb['id']]);
}

$page = [
    'title'       => $hb['baslik'],
    'description' => $hb['ozet'] ?? truncate(strip_tags($hb['icerik'] ?? ''), 160),
    'og_image'    => !empty($hb['kapak_gorsel']) ? uploadUrl($hb['kapak_gorsel']) : null,
];
require_once __DIR__ . '/../includes/templates/header.php';
?>

<!-- ════ HERO ════ -->
<section class="page-hero" style="<?= !empty($hb['kapak_gorsel']) ? "background-image:linear-gradient(180deg, rgba(10,14,26,.75) 0%, var(--bg) 100%), url(" . uploadUrl($hb['kapak_gorsel']) . ");background-size:cover;background-position:center" : "" ?>">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Ana Sayfa</a> · <a href="/haberler">Haberler</a>
            <?php if (!empty($hb['kategori_ad'])): ?>
            · <a href="/haberler?kategori=<?= ha($hb['kategori_slug']) ?>"><?= h($hb['kategori_ad']) ?></a>
            <?php endif; ?>
        </div>
        <h1 style="max-width:920px"><?= h($hb['baslik']) ?></h1>
        <?php if (!empty($hb['ozet'])): ?>
        <p class="lead"><?= h($hb['ozet']) ?></p>
        <?php endif; ?>
        <div style="display:flex;gap:24px;flex-wrap:wrap;margin-top:32px;font-size:13px;color:var(--text-mute);letter-spacing:.05em">
            <span><?= icon_calendar() ?> &nbsp;<?= h(formatDate($hb['yayim_tarihi'] ?? $hb['created_at'], 'd F Y')) ?></span>
            <?php if (!empty($hb['yazar'])): ?><span>✎ <?= h($hb['yazar']) ?></span><?php endif; ?>
            <?php if (!empty($hb['sirket_ad'])): ?>
            <span>● <a href="/sirket/<?= ha($hb['sirket_slug']) ?>" style="color:var(--gold)"><?= h($hb['sirket_ad']) ?></a></span>
            <?php endif; ?>
            <?php if (!empty($hb['goruntulenme'])): ?><span><?= formatNumber((int)$hb['goruntulenme']) ?> görüntüleme</span><?php endif; ?>
        </div>
    </div>
</section>

<!-- ════ İÇERİK ════ -->
<section class="content-section">
    <div class="container" style="max-width:840px">
        <article class="prose">
            <?= $hb['icerik'] ?? '' ?>
        </article>

        <!-- Paylaş -->
        <div style="margin-top:64px;padding:32px;background:var(--bg-2);border:1px solid var(--line);text-align:center">
            <div class="pretitle" style="color:var(--gold);font-size:11px;letter-spacing:.3em;text-transform:uppercase;margin-bottom:18px">Paylaş</div>
            <?php
            $shareUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'aksoy.web.tr') . '/haber/' . $hb['slug'];
            $shareText = $hb['baslik'];
            ?>
            <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
                <a href="https://twitter.com/intent/tweet?text=<?= rawurlencode($shareText) ?>&url=<?= rawurlencode($shareUrl) ?>" target="_blank" rel="noopener" class="btn outline" style="padding:10px 22px;font-size:11px">Twitter</a>
                <a href="https://www.linkedin.com/shareArticle?url=<?= rawurlencode($shareUrl) ?>&title=<?= rawurlencode($shareText) ?>" target="_blank" rel="noopener" class="btn outline" style="padding:10px 22px;font-size:11px">LinkedIn</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($shareUrl) ?>" target="_blank" rel="noopener" class="btn outline" style="padding:10px 22px;font-size:11px">Facebook</a>
                <a href="https://wa.me/?text=<?= rawurlencode($shareText . ' ' . $shareUrl) ?>" target="_blank" rel="noopener" class="btn outline" style="padding:10px 22px;font-size:11px">WhatsApp</a>
            </div>
        </div>
    </div>
</section>

<!-- ════ İLGİLİ HABERLER ════ -->
<?php if ($ilgili): ?>
<section class="section" style="background:var(--bg-2)">
    <div class="container">
        <div class="section-head">
            <span class="pretitle">Sizi de İlgilendirebilir</span>
            <h2>Benzer <em style="color:var(--gold)">haberler</em></h2>
        </div>
        <div class="news-grid">
            <?php foreach ($ilgili as $i): ?>
            <a href="/haber/<?= ha($i['slug']) ?>" class="news-card">
                <?php if (!empty($i['kapak_gorsel'])): ?>
                <div class="cover"><img src="<?= h(uploadUrl($i['kapak_gorsel'])) ?>" alt="<?= ha($i['baslik']) ?>"></div>
                <?php endif; ?>
                <div class="body">
                    <div class="meta"><?= h($i['kategori_ad'] ?? '—') ?> · <?= h(formatDate($i['yayim_tarihi'] ?? $i['created_at'])) ?></div>
                    <h4><?= h($i['baslik']) ?></h4>
                    <p class="ozet"><?= h(truncate($i['ozet'] ?? '', 100)) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
function icon_calendar() {
    return '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline;vertical-align:-2px"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>';
}
require_once __DIR__ . '/../includes/templates/footer.php';
?>
