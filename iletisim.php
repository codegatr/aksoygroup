<?php
/**
 * AKSOY GROUP — İletişim
 * Path: /iletisim
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$page = [
    'title'       => 'İletişim',
    'description' => 'Aksoy Group ile iletişime geçin — yatırım, ortaklık, işbirliği ya da müşteri talepleri için.',
];

$sentSuccess = !empty($_SESSION['_iletisim_sent']);
unset($_SESSION['_iletisim_sent']);

require_once __DIR__ . '/includes/templates/header.php';
?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="/">Ana Sayfa</a> · İletişim</div>
        <h1>Konuşalım.</h1>
        <p class="lead">
            Yatırım, ortaklık, kariyer ya da basın talepleri için aşağıdaki kanallardan ulaşabilir
            veya kısa mesaj formunu doldurabilirsiniz.
        </p>
    </div>
</section>

<section class="content-section">
    <div class="container">
        <div class="content-grid" style="grid-template-columns:1.1fr 1fr">

            <!-- ── İletişim Bilgileri ── -->
            <div>
                <div class="info-card">
                    <h4>Doğrudan İletişim</h4>
                    <ul>
                        <?php if ($e = setting('contact_email')): ?>
                        <li><span class="lbl">E-posta</span><a href="mailto:<?= ha($e) ?>" class="val"><?= h($e) ?></a></li>
                        <?php endif; ?>
                        <?php if ($p = setting('contact_phone')): ?>
                        <li><span class="lbl">Telefon</span><a href="tel:<?= ha(preg_replace('/[^0-9+]/', '', $p)) ?>" class="val"><?= h($p) ?></a></li>
                        <?php endif; ?>
                        <?php if ($p2 = setting('contact_phone_2')): ?>
                        <li><span class="lbl">Telefon 2</span><a href="tel:<?= ha(preg_replace('/[^0-9+]/', '', $p2)) ?>" class="val"><?= h($p2) ?></a></li>
                        <?php endif; ?>
                        <?php if ($f = setting('contact_fax')): ?>
                        <li><span class="lbl">Faks</span><span class="val"><?= h($f) ?></span></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <?php if ($addr = setting('contact_address')): ?>
                <div class="info-card">
                    <h4>Merkez Ofis</h4>
                    <p style="color:var(--text-soft);font-size:14px;line-height:1.7"><?= nl2br(h($addr)) ?></p>
                    <?php if ($map = setting('contact_map_url')): ?>
                    <a href="<?= ha($map) ?>" target="_blank" rel="noopener" class="btn outline" style="margin-top:18px;width:100%;justify-content:center;padding:10px;font-size:11px">
                        Haritada Görüntüle
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="info-card">
                    <h4>Hangi Konuda Yazıyorsunuz?</h4>
                    <ul>
                        <li style="display:block;border-bottom:1px solid var(--line);padding:10px 0">
                            <strong style="color:var(--gold);font-size:13px">Yatırım & Ortaklık</strong>
                            <div style="font-size:12px;color:var(--text-mute);margin-top:4px">Stratejik fırsatlar ve birleşmeler için.</div>
                        </li>
                        <li style="display:block;border-bottom:1px solid var(--line);padding:10px 0">
                            <strong style="color:var(--gold);font-size:13px">Tedarik & İş Geliştirme</strong>
                            <div style="font-size:12px;color:var(--text-mute);margin-top:4px">B2B işbirlikleri ve tedarik teklifleri.</div>
                        </li>
                        <li style="display:block;padding:10px 0">
                            <strong style="color:var(--gold);font-size:13px">Basın & Medya</strong>
                            <div style="font-size:12px;color:var(--text-mute);margin-top:4px">Röportaj talepleri ve basın bültenleri.</div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- ── Form ── -->
            <div>
                <div class="info-card" style="padding:36px 32px">
                    <h4 style="margin-bottom:8px">Bize Yazın</h4>
                    <p style="color:var(--text-soft);font-size:14px;line-height:1.7;margin-bottom:24px">
                        Mesajınız ilgili departmana yönlendirilecek ve 2 iş günü içinde dönüş yapılacaktır.
                    </p>

                    <?php if ($sentSuccess): ?>
                        <div style="background:rgba(31,122,77,.15);border:1px solid var(--emerald);padding:18px;border-radius:6px;color:var(--bone);text-align:center">
                            ✓ Mesajınız iletildi. En kısa sürede dönüş yapılacaktır.
                        </div>
                    <?php endif; ?>

                    <form action="/api/iletisim.php" method="post" class="contact-form">
                        <?= CSRF::field() ?>

                        <div class="field">
                            <label>Ad Soyad *</label>
                            <input type="text" name="ad_soyad" required maxlength="120" autocomplete="name">
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                            <div class="field">
                                <label>E-posta *</label>
                                <input type="email" name="email" required maxlength="150" autocomplete="email">
                            </div>
                            <div class="field">
                                <label>Telefon</label>
                                <input type="tel" name="telefon" maxlength="30" autocomplete="tel">
                            </div>
                        </div>

                        <div class="field">
                            <label>Konu *</label>
                            <select name="konu" required style="width:100%;padding:14px 16px;border:1px solid var(--line-2);background:var(--bg-2);color:var(--text);border-radius:4px;font-family:inherit;font-size:15px">
                                <option value="">— Konu seçiniz —</option>
                                <option value="yatirim">Yatırım & Ortaklık</option>
                                <option value="tedarik">Tedarik & İş Geliştirme</option>
                                <option value="basin">Basın & Medya</option>
                                <option value="kariyer">Kariyer</option>
                                <option value="genel">Genel Bilgi</option>
                            </select>
                        </div>

                        <div class="field">
                            <label>Mesajınız *</label>
                            <textarea name="mesaj" required minlength="10" maxlength="4000"
                                      placeholder="Konunuzu kısaca açıklayın…"></textarea>
                        </div>

                        <!-- Honeypot (anti-bot) -->
                        <input type="text" name="website" tabindex="-1" autocomplete="off"
                               style="position:absolute;left:-9999px" aria-hidden="true">

                        <div class="field" style="font-size:11px;color:var(--text-mute);line-height:1.6">
                            Mesajınızı göndererek <a href="/kvkk" style="color:var(--gold)">KVKK Aydınlatma Metni</a>'ni
                            ve <a href="/gizlilik" style="color:var(--gold)">Gizlilik Politikası</a>'nı kabul etmiş olursunuz.
                        </div>

                        <button type="submit" class="btn primary" style="width:100%;justify-content:center">
                            Mesajı Gönder
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/templates/footer.php'; ?>
