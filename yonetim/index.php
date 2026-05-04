<?php
declare(strict_types=1);
define('AG_ADMIN', true);
$adminTitle = 'Genel Bakış';
$adminBreadcrumb = 'Pano';
$adminMenu = 'dashboard';
require __DIR__ . '/_layout.php';

// İstatistikler
$stats = [
    'sektor'    => (int)DB::scalar("SELECT COUNT(*) FROM ag_sektorler WHERE is_active = 1"),
    'sirket'    => (int)DB::scalar("SELECT COUNT(*) FROM ag_sirketler WHERE durum = 'aktif'"),
    'haber'     => (int)DB::scalar("SELECT COUNT(*) FROM ag_haberler WHERE is_active = 1"),
    'mesaj_yeni'=> (int)DB::scalar("SELECT COUNT(*) FROM ag_iletisim_mesajlari WHERE okundu = 0"),
    'mesaj_top' => (int)DB::scalar("SELECT COUNT(*) FROM ag_iletisim_mesajlari"),
    'kullanici' => (int)DB::scalar("SELECT COUNT(*) FROM ag_users WHERE is_active = 1"),
    'sayfa'     => (int)DB::scalar("SELECT COUNT(*) FROM ag_pages WHERE is_active = 1"),
    'basvuru'   => (int)DB::scalar("SELECT COUNT(*) FROM ag_kariyer_basvuru WHERE durum = 'yeni'"),
];

// Son aktiviteler
$recentMessages = DB::all("SELECT id, ad_soyad, email, konu, okundu, created_at FROM ag_iletisim_mesajlari ORDER BY created_at DESC LIMIT 5");
$recentActivity = DB::all("SELECT al.*, u.full_name FROM ag_audit_log al LEFT JOIN ag_users u ON u.id = al.user_id ORDER BY al.created_at DESC LIMIT 8");
$lastUpdate = DB::row("SELECT version, installed_at, release_name FROM ag_versions ORDER BY installed_at DESC LIMIT 1");
?>

<!-- İstatistikler -->
<div class="stats">
    <div class="stat">
        <div class="label">Aktif Sektör</div>
        <div class="value"><?= formatNumber($stats['sektor']) ?></div>
        <div class="delta">9 hedef</div>
    </div>
    <div class="stat navy">
        <div class="label">Aktif İştirak</div>
        <div class="value"><?= formatNumber($stats['sirket']) ?></div>
        <div class="delta muted">grup şirketi</div>
    </div>
    <div class="stat emerald">
        <div class="label">Yayın Haber</div>
        <div class="value"><?= formatNumber($stats['haber']) ?></div>
        <div class="delta muted">basın & duyuru</div>
    </div>
    <div class="stat crimson">
        <div class="label">Okunmamış Mesaj</div>
        <div class="value"><?= formatNumber($stats['mesaj_yeni']) ?></div>
        <div class="delta <?= $stats['mesaj_yeni'] > 0 ? 'down' : '' ?>">
            <?= $stats['mesaj_yeni'] > 0 ? 'cevap bekliyor' : 'tüm mesajlar okundu' ?>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;" class="dash-grid">
    <!-- Son mesajlar -->
    <div class="card">
        <div class="card-head">
            <h2><?= icon('mail', 18) ?> &nbsp;Son Mesajlar</h2>
            <a href="/yonetim/modules/iletisim.php" class="btn outline btn-sm">Tümü →</a>
        </div>
        <div class="card-body tight">
            <?php if (!$recentMessages): ?>
                <div class="empty">
                    <div class="serif">Henüz mesaj yok</div>
                    <p>İletişim formundan gelen mesajlar burada görünecek.</p>
                </div>
            <?php else: ?>
                <table class="data">
                    <tbody>
                    <?php foreach ($recentMessages as $m): ?>
                        <tr>
                            <td>
                                <b><?= h($m['ad_soyad']) ?></b>
                                <div class="muted" style="font-size:12px"><?= h(truncate($m['konu'], 50)) ?></div>
                            </td>
                            <td class="nowrap muted" style="font-size:12px"><?= h(timeAgo($m['created_at'])) ?></td>
                            <td class="right">
                                <?= $m['okundu'] ? '<span class="badge muted">Okundu</span>' : '<span class="badge gold">Yeni</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Aktivite logu -->
    <div class="card">
        <div class="card-head">
            <h2><?= icon('activity', 18) ?> &nbsp;Son Aktiviteler</h2>
            <a href="/yonetim/modules/audit-log.php" class="btn outline btn-sm">Tümü →</a>
        </div>
        <div class="card-body tight">
            <?php if (!$recentActivity): ?>
                <div class="empty"><div class="serif">Henüz kayıt yok</div></div>
            <?php else: ?>
                <table class="data">
                    <tbody>
                    <?php foreach ($recentActivity as $a): ?>
                        <tr>
                            <td>
                                <b><?= h($a['action']) ?></b>
                                <?php if ($a['entity']): ?><span class="badge muted" style="margin-left:6px"><?= h($a['entity']) ?></span><?php endif; ?>
                                <div class="muted" style="font-size:12px"><?= h($a['full_name'] ?? 'sistem') ?></div>
                            </td>
                            <td class="right nowrap muted" style="font-size:12px"><?= h(timeAgo($a['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>@media (max-width: 1100px) { .dash-grid { grid-template-columns: 1fr !important; } }</style>

<!-- Sistem bilgileri -->
<div class="card mt">
    <div class="card-head"><h2><?= icon('database', 18) ?> &nbsp;Sistem Durumu</h2></div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:20px;">
            <div>
                <div class="muted" style="font-size:11px;letter-spacing:.15em;text-transform:uppercase">Mevcut Sürüm</div>
                <div class="serif" style="font-size:24px;margin-top:4px">v<?= h(setting('current_version', AG_VERSION)) ?></div>
            </div>
            <div>
                <div class="muted" style="font-size:11px;letter-spacing:.15em;text-transform:uppercase">PHP</div>
                <div class="serif" style="font-size:24px;margin-top:4px"><?= h(PHP_VERSION) ?></div>
            </div>
            <div>
                <div class="muted" style="font-size:11px;letter-spacing:.15em;text-transform:uppercase">Veritabanı</div>
                <div class="serif" style="font-size:24px;margin-top:4px"><?= h(DB::scalar("SELECT VERSION()")) ?></div>
            </div>
            <div>
                <div class="muted" style="font-size:11px;letter-spacing:.15em;text-transform:uppercase">Son Güncelleme</div>
                <div class="serif" style="font-size:24px;margin-top:4px"><?= $lastUpdate ? h(formatDate($lastUpdate['installed_at'], 'd M Y')) : '—' ?></div>
            </div>
        </div>
        <hr class="divider">
        <div class="flex gap" style="flex-wrap:wrap">
            <a href="/yonetim/modules/sektorler.php" class="btn outline btn-sm"><?= icon('plus', 14) ?> Sektör Ekle</a>
            <a href="/yonetim/modules/sirketler.php" class="btn outline btn-sm"><?= icon('plus', 14) ?> Şirket Ekle</a>
            <a href="/yonetim/modules/haberler.php" class="btn outline btn-sm"><?= icon('plus', 14) ?> Haber Yayınla</a>
            <a href="/yonetim/modules/update-center.php" class="btn navy btn-sm"><?= icon('rocket', 14) ?> Güncellemeleri Kontrol Et</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
