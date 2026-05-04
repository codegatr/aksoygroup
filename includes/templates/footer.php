<?php
$footerPages = DB::all("SELECT slug, baslik FROM ag_pages
                       WHERE is_active = 1 AND menu_konumu IN ('footer','her_ikisi')
                       ORDER BY sort_order");
$footerSektorler = DB::all("SELECT slug, ad, roman_no FROM ag_sektorler WHERE is_active = 1 ORDER BY sort_order LIMIT 6");
$socialLinks = DB::all("SELECT platform, url, ikon_class FROM ag_sosyal_medya WHERE is_active = 1 ORDER BY sort_order");
$siteTagline = setting('site_tagline', 'Türkiye’nin Çok Sektörlü Hizmetler Topluluğu');
?>
</main>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="/" class="logo">AKSOY</a>
                <p class="desc"><?= h($siteTagline) ?>. <?= h(setting('site_description', '')) ?></p>
                <?php if ($socialLinks): ?>
                    <div style="margin-top:24px; display:flex; gap:14px">
                        <?php foreach ($socialLinks as $s): ?>
                            <a href="<?= ha($s['url']) ?>" target="_blank" rel="noopener" title="<?= h(ucfirst($s['platform'])) ?>"
                               style="width:38px;height:38px;border:1px solid var(--line-2);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--gold)">
                                <?= h(strtoupper(substr($s['platform'], 0, 2))) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="footer-col">
                <h5>Sektörler</h5>
                <?php foreach ($footerSektorler as $s): ?>
                    <a href="/sektor/<?= ha($s['slug']) ?>"><?= h($s['ad']) ?></a>
                <?php endforeach; ?>
                <a href="/sektorler" style="margin-top:12px;color:var(--gold)">Tümü →</a>
            </div>
            <div class="footer-col">
                <h5>Kurumsal</h5>
                <a href="/sirketler">İştiraklerimiz</a>
                <a href="/haberler">Haberler & Basın</a>
                <?php foreach ($footerPages as $p): ?>
                    <a href="/<?= ha($p['slug']) ?>"><?= h($p['baslik']) ?></a>
                <?php endforeach; ?>
            </div>
            <div class="footer-col">
                <h5>İletişim</h5>
                <?php $email = setting('contact_email'); ?>
                <?php if ($email): ?><a href="mailto:<?= ha($email) ?>"><?= h($email) ?></a><?php endif; ?>
                <?php $phone = setting('contact_phone'); ?>
                <?php if ($phone): ?><a href="tel:<?= ha(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= h($phone) ?></a><?php endif; ?>
                <?php $addr = setting('contact_address'); ?>
                <?php if ($addr): ?><span style="display:block;color:var(--text-soft);font-size:14px;padding:6px 0;line-height:1.6"><?= nl2br(h($addr)) ?></span><?php endif; ?>
                <a href="/iletisim" style="margin-top:12px;color:var(--gold)">İletişim Formu →</a>
            </div>
        </div>
        <div class="footer-bottom">
            <div>© <?= date('Y') ?> AKSOY GROUP — Tüm hakları saklıdır.</div>
            <div class="legal">
                <span>v<?= h(setting('current_version', AG_VERSION)) ?></span>
                <span>·</span>
                <a href="https://codega.com.tr" target="_blank" rel="noopener">CODEGA tarafından geliştirildi</a>
            </div>
        </div>
    </div>
</footer>
<script>
(function(){
    // Header scroll efekti
    const h = document.getElementById('siteHeader');
    const onScroll = () => h.classList.toggle('scrolled', window.scrollY > 30);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
})();
</script>
</body>
</html>
