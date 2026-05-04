<?php
$footerPages = DB::all("SELECT slug, baslik FROM ag_pages
                       WHERE is_active = 1 AND menu_konumu IN ('footer','her_ikisi')
                       ORDER BY sort_order");
$footerSektorler = DB::all("SELECT slug, ad, roman_no FROM ag_sektorler WHERE is_active = 1 ORDER BY sort_order LIMIT 8");
$socialLinks = DB::all("SELECT platform, url, ikon_class FROM ag_sosyal_medya WHERE is_active = 1 ORDER BY sort_order");
$siteTagline = setting('site_tagline', "Türkiye'nin Çok Sektörlü Hizmetler Topluluğu");
$contactEmail = setting('contact_email', 'info@aksoy.web.tr');
$contactPhone = setting('contact_phone', '+90 (332) 000 00 00');
$contactPhone2 = setting('contact_phone_2', '');
$contactFax = setting('contact_fax', '');
$contactAddr = setting('contact_address', 'Konya, Türkiye');
?>
</main>
<footer class="site-footer">
    <div class="container">

        <!-- 4 Sütun Grid -->
        <div class="footer-grid">

            <!-- Sütun 1: Marka + Tagline + Sosyal -->
            <div class="footer-brand">
                <a href="/" class="logo">AKSOY</a>
                <p class="desc"><?= h($siteTagline) ?>. On sektörde uzmanlaşmış iştirakleriyle Anadolu'nun üretim derinliğini dijital çağın hızıyla buluşturan kurumsal yapı.</p>
                <?php if ($socialLinks): ?>
                <div style="margin-top:24px;display:flex;gap:10px">
                    <?php foreach ($socialLinks as $s): ?>
                        <a href="<?= ha($s['url']) ?>" target="_blank" rel="noopener" title="<?= h(ucfirst($s['platform'])) ?>"
                           style="width:38px;height:38px;border:1px solid rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:11px;font-weight:600;letter-spacing:.05em;transition:all .15s"
                           onmouseover="this.style.background='var(--gold)';this.style.color='var(--navy)';this.style.borderColor='var(--gold)'"
                           onmouseout="this.style.background='transparent';this.style.color='var(--gold)';this.style.borderColor='rgba(255,255,255,.15)'">
                            <?= h(strtoupper(substr($s['platform'], 0, 2))) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sütun 2: Faaliyet Alanları (sektörler) -->
            <div class="footer-col">
                <h5>Faaliyet Alanları</h5>
                <?php foreach ($footerSektorler as $s): ?>
                    <a href="/sektor/<?= ha($s['slug']) ?>"><?= h($s['ad']) ?></a>
                <?php endforeach; ?>
                <a href="/sektorler" style="margin-top:8px;color:var(--gold);font-weight:600">Tümü →</a>
            </div>

            <!-- Sütun 3: Kurumsal -->
            <div class="footer-col">
                <h5>Kurumsal</h5>
                <a href="/hakkimizda">Hakkımızda</a>
                <a href="/yonetim-kurulu">Yönetim Kurulu</a>
                <a href="/sirketler">İştiraklerimiz</a>
                <a href="/surdurulebilirlik">Sürdürülebilirlik</a>
                <a href="/kariyer">Kariyer ve Yaşam</a>
                <a href="/basin">Basın Odası</a>
                <a href="/haberler">Haberler</a>
            </div>

            <!-- Sütun 4: İletişim -->
            <div class="footer-col">
                <h5>İletişim</h5>
                <?php if ($contactAddr): ?>
                <div class="footer-contact-line">
                    <svg class="ico" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span><?= nl2br(h($contactAddr)) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($contactPhone): ?>
                <div class="footer-contact-line">
                    <svg class="ico" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.33 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $contactPhone)) ?>" style="color:rgba(255,255,255,.85)"><?= h($contactPhone) ?></a>
                </div>
                <?php endif; ?>
                <?php if ($contactPhone2): ?>
                <div class="footer-contact-line">
                    <svg class="ico" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.33 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $contactPhone2)) ?>" style="color:rgba(255,255,255,.85)"><?= h($contactPhone2) ?></a>
                </div>
                <?php endif; ?>
                <?php if ($contactFax): ?>
                <div class="footer-contact-line">
                    <svg class="ico" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    <span style="color:rgba(255,255,255,.65)"><?= h($contactFax) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($contactEmail): ?>
                <div class="footer-contact-line">
                    <svg class="ico" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <a href="mailto:<?= h($contactEmail) ?>" style="color:rgba(255,255,255,.85)"><?= h($contactEmail) ?></a>
                </div>
                <?php endif; ?>
                <a href="/iletisim" style="margin-top:14px;color:var(--gold);font-weight:600;display:inline-block">İletişim Formu →</a>
            </div>
        </div>

        <!-- Sertifika ve referanslar -->
        <div class="footer-cert">
            <span class="badge">Türkiye'de Üretildi</span>
            <span class="badge">Konya Merkezli</span>
            <span class="badge">10 Sektör · 9 İştirak</span>
            <span class="badge">KVKK Uyumlu</span>
            <span class="badge">İş Güvenliği · ISO Standardı</span>
        </div>

        <!-- Telif satırı -->
        <div class="footer-bottom">
            <div>© <?= date('Y') ?> AKSOY GROUP — Tüm hakları saklıdır.</div>
            <div class="legal">
                <a href="/kvkk">KVKK</a>
                <span>·</span>
                <a href="/gizlilik">Gizlilik</a>
                <span>·</span>
                <a href="/cerez-politikasi">Çerez</a>
                <span>·</span>
                <a href="/kullanim-kosullari">Kullanım Koşulları</a>
                <span>·</span>
                <span>v<?= h(setting('current_version', AG_VERSION)) ?></span>
                <span>·</span>
                <a href="https://codega.com.tr" target="_blank" rel="noopener">CODEGA</a>
            </div>
        </div>
    </div>
</footer>
</body>
</html>
