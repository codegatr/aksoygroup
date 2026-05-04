<?php
declare(strict_types=1);
define('AG_ADMIN', true);
$adminTitle = 'Güncelleme Merkezi';
$adminBreadcrumb = 'Sistem';
$adminMenu = 'update';
require __DIR__ . '/../_layout.php';

Auth::requireRole('superadmin');

$action = $_GET['action'] ?? '';
$result = null;
$error = null;

if ($action === 'check' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    try {
        $latest = Updater::fetchLatest();
        $_SESSION['ag_update_check'] = $latest;
        flash('success', 'Güncelleme bilgisi alındı.');
    } catch (Throwable $e) {
        flash('error', 'Hata: ' . $e->getMessage());
    }
    redirect('/yonetim/modules/update-center.php');
}

if ($action === 'install' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $version = trim($_POST['version'] ?? '');
    if (!$version) {
        flash('error', 'Sürüm belirtilmedi.');
        redirect('/yonetim/modules/update-center.php');
    }
    try {
        $r = Updater::install($version);
        Audit::log('update_install', 'system', null, null, ['version' => $version], 'critical');
        flash('success', "v$version başarıyla kuruldu.");
    } catch (Throwable $e) {
        Audit::log('update_failed', 'system', null, null, ['version' => $version, 'error' => $e->getMessage()], 'critical');
        flash('error', 'Kurulum hatası: ' . $e->getMessage());
    }
    redirect('/yonetim/modules/update-center.php');
}

$current = setting('current_version', AG_VERSION);
$latest = $_SESSION['ag_update_check'] ?? null;
$history = DB::all("SELECT * FROM ag_versions ORDER BY installed_at DESC LIMIT 20");
?>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px" class="update-grid">

    <!-- Mevcut sürüm -->
    <div class="card">
        <div class="card-head"><h2><?= icon('package', 18) ?> &nbsp;Mevcut Sürüm</h2></div>
        <div class="card-body">
            <div style="text-align:center; padding:24px 0">
                <div class="serif" style="font-size:64px; font-weight:200; color:var(--gold-dark); line-height:1">
                    v<?= h($current) ?>
                </div>
                <div class="muted" style="margin-top:8px; font-size:13px">
                    Genesis Release · Aksoy Group Platform
                </div>
            </div>
            <hr class="divider">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:12px">
                <div>
                    <div class="muted">GitHub</div>
                    <div><code><?= h(AG_GITHUB_OWNER) ?>/<?= h(AG_GITHUB_REPO) ?></code></div>
                </div>
                <div>
                    <div class="muted">Branş</div>
                    <div><code><?= h(AG_GITHUB_BRANCH) ?></code></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Güncelleme kontrol -->
    <div class="card">
        <div class="card-head">
            <h2><?= icon('refresh', 18) ?> &nbsp;Güncellemeleri Kontrol Et</h2>
            <form method="post" action="?action=check"><?= CSRF::field() ?>
                <button type="submit" class="btn btn-sm"><?= icon('refresh', 14) ?> Kontrol</button>
            </form>
        </div>
        <div class="card-body">
            <?php if (!$latest): ?>
                <div class="empty">
                    <div class="serif">Henüz kontrol edilmedi</div>
                    <p>GitHub'dan en son sürümü çekmek için <b>Kontrol</b> butonuna basın.</p>
                </div>
            <?php elseif (version_compare($latest['version'], $current, '<=')): ?>
                <div class="alert success">
                    <?= icon('check', 16) ?> &nbsp;Sisteminiz güncel. En son sürüm: <b>v<?= h($latest['version']) ?></b>
                </div>
            <?php else: ?>
                <div class="alert warning">
                    <?= icon('rocket', 16) ?> &nbsp;<b>v<?= h($latest['version']) ?></b> sürümü mevcut.
                </div>
                <div style="margin-bottom:16px">
                    <div class="muted" style="font-size:11px;letter-spacing:.15em;text-transform:uppercase">Sürüm Adı</div>
                    <div class="serif" style="font-size:18px;margin-top:4px"><?= h($latest['name'] ?? 'v' . $latest['version']) ?></div>
                </div>
                <?php if (!empty($latest['body'])): ?>
                    <div style="background:var(--bg-soft);padding:14px;border-radius:6px;font-size:13px;white-space:pre-wrap;max-height:240px;overflow:auto"><?= h(truncate($latest['body'], 1000)) ?></div>
                <?php endif; ?>
                <form method="post" action="?action=install" style="margin-top:20px" onsubmit="return confirm('v<?= h($latest['version']) ?> sürümünü kurmak istediğinizden emin misiniz? Bu işlem geri alınamaz.')">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="version" value="<?= h($latest['version']) ?>">
                    <button type="submit" class="btn navy"><?= icon('download', 14) ?> v<?= h($latest['version']) ?> Sürümünü Kur</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>@media (max-width: 900px) { .update-grid { grid-template-columns: 1fr !important; } }</style>

<!-- Güncelleme geçmişi -->
<div class="card mt">
    <div class="card-head">
        <h2><?= icon('database', 18) ?> &nbsp;Güncelleme Geçmişi</h2>
    </div>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Sürüm</th><th>Ad</th><th>Tarih</th><th>Süre</th><th class="center">Durum</th></tr></thead>
            <tbody>
            <?php foreach ($history as $v): ?>
                <tr>
                    <td><span class="serif" style="font-size:16px;font-weight:500">v<?= h($v['version']) ?></span></td>
                    <td><?= h($v['release_name'] ?? '—') ?></td>
                    <td class="muted nowrap"><?= h(formatDate($v['installed_at'], 'd M Y H:i')) ?></td>
                    <td class="num"><?= $v['duration_ms'] ? formatNumber((int)$v['duration_ms']) . ' ms' : '—' ?></td>
                    <td class="center">
                        <?php if ($v['status'] === 'success'): ?>
                            <span class="badge success">Başarılı</span>
                        <?php elseif ($v['status'] === 'failed'): ?>
                            <span class="badge danger">Başarısız</span>
                        <?php else: ?>
                            <span class="badge muted"><?= h($v['status']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$history): ?>
                <tr><td colspan="5"><div class="empty"><div class="serif">Henüz güncelleme yapılmamış</div></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../_footer.php'; ?>
