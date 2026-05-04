<?php
declare(strict_types=1);
define('AG_ADMIN', true);
$adminTitle = 'Güncelleme Merkezi';
$adminBreadcrumb = 'Sistem';
$adminMenu = 'update';
require __DIR__ . '/../_layout.php';

Auth::requireRole('superadmin');
@set_time_limit(0);
@ini_set('memory_limit', '512M');

$updater = new Updater();
$tab = $_GET['tab'] ?? 'overview';
$action = $_POST['action'] ?? '';

// ═══ AKSİYONLAR ═══
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $resp = ['success' => false, 'message' => '', 'log' => []];

    try {
        switch ($action) {
            case 'save_token':
                $tok = trim($_POST['token'] ?? '');
                if (!$tok) { flash('error', 'Token boş olamaz.'); }
                elseif ($updater->saveToken($tok)) { flash('success', 'GitHub token kaydedildi.'); Audit::log('update_token_set', 'system'); }
                else { flash('error', 'Token kaydedilemedi.'); }
                redirect('?tab=settings');

            case 'check_remote':
                $rel = $updater->fetchLatest();
                $_SESSION['ag_update_check'] = $rel;
                flash('success', "GitHub: v{$rel['version']} bulundu.");
                Audit::log('update_check', 'system', null, null, ['version' => $rel['version']]);
                redirect('?tab=overview');

            case 'install_release':
                $version = trim($_POST['version'] ?? '');
                if (!$version) throw new RuntimeException('Sürüm belirtilmedi.');
                $r = $updater->install($version);
                if ($r['success']) {
                    Audit::log('update_install', 'system', null, null, ['version' => $version], 'critical');
                    flash('success', "v{$version} kuruldu.");
                } else {
                    Audit::log('update_failed', 'system', null, null, ['version' => $version], 'critical');
                    flash('error', $r['message']);
                }
                $_SESSION['ag_update_log'] = $r['log'] ?? [];
                redirect('?tab=overview');

            case 'smart_sync':
                $r = $updater->smartSync();
                Audit::log('update_smart_sync', 'system', null, null, [], 'warning');
                flash($r['success'] ? 'success' : 'error', $r['message']);
                $_SESSION['ag_update_log'] = $r['log'] ?? [];
                redirect('?tab=files');

            case 'force_sync':
                $r = $updater->forceSync();
                Audit::log('update_force_sync', 'system', null, null, [], 'critical');
                flash($r['success'] ? 'success' : 'error', $r['message']);
                $_SESSION['ag_update_log'] = $r['log'] ?? [];
                redirect('?tab=files');

            case 'sync_one':
                $path = (string)($_POST['path'] ?? '');
                $r = $updater->syncFile($path);
                flash($r['ok'] ? 'success' : 'error', $r['msg']);
                redirect('?tab=files');

            case 'create_backup':
                $name = $updater->createBackup('manual');
                if ($name) { Audit::log('backup_create', 'system', null, null, ['file' => $name]); flash('success', "Yedek alındı: $name"); }
                else { flash('error', 'Yedek alınamadı.'); }
                redirect('?tab=backups');

            case 'rollback':
                $bk = (string)($_POST['backup'] ?? '');
                $r = $updater->rollback($bk);
                Audit::log('rollback', 'system', null, null, ['backup' => $bk], 'critical');
                flash($r['success'] ? 'success' : 'error', $r['message']);
                $_SESSION['ag_update_log'] = $r['log'] ?? [];
                redirect('?tab=backups');

            case 'upload_zip':
                if (empty($_FILES['zip']['tmp_name'])) throw new RuntimeException('ZIP dosyası seçilmedi.');
                $r = $updater->updateFromZip($_FILES['zip']['tmp_name']);
                Audit::log('update_zip_upload', 'system', null, null, ['file' => $_FILES['zip']['name']], 'critical');
                flash($r['success'] ? 'success' : 'error', $r['message']);
                $_SESSION['ag_update_log'] = $r['log'] ?? [];
                redirect('?tab=overview');

            case 'run_migrations':
                $log = [];
                $updater->runMigrationsFromDir(AG_ROOT . '/migrations', $log);
                flash('info', 'Migration çalıştırıldı.');
                $_SESSION['ag_update_log'] = $log;
                redirect('?tab=migrations');

            case 'mark_skipped':
                $filename = trim($_POST['filename'] ?? '');
                if ($filename) {
                    DB::exec("UPDATE ag_migrations SET status='skipped' WHERE filename = ?", [$filename]);
                    Audit::log('migration_skip', 'system', null, null, ['filename' => $filename], 'warning');
                    flash('warning', "$filename atlandı.");
                }
                redirect('?tab=migrations');
        }
    } catch (Throwable $e) {
        flash('error', 'Hata: ' . $e->getMessage());
        redirect('?tab=' . $tab);
    }
}

// ═══ VERİ ═══
$current = $updater->currentVersion();
$token = $updater->token();
$tokenSet = !empty($token);
$cachedRelease = $_SESSION['ag_update_check'] ?? null;
$lastLog = $_SESSION['ag_update_log'] ?? [];
unset($_SESSION['ag_update_log']);

$migrations = [];
try { $migrations = DB::all("SELECT * FROM ag_migrations ORDER BY id DESC LIMIT 50"); } catch (Throwable $e) {}
$versionHistory = DB::all("SELECT * FROM ag_versions ORDER BY id DESC LIMIT 20");
$backups = $updater->listBackups();

$diff = null;
if ($tab === 'files' && $tokenSet) {
    try { $diff = $updater->compareFiles(); } catch (Throwable $e) { $diffError = $e->getMessage(); }
}
?>

<!-- Sekme menüsü -->
<div class="card" style="padding:0">
    <div style="display:flex;border-bottom:1px solid var(--border);overflow-x:auto">
        <?php
        $tabs = [
            'overview'   => ['Genel Durum', 'home'],
            'files'      => ['Dosya Senkronu', 'package'],
            'migrations' => ['Migration', 'database'],
            'backups'    => ['Yedekler', 'shield'],
            'settings'   => ['Ayarlar', 'settings'],
        ];
        foreach ($tabs as $k => [$label, $ico]):
            $active = $tab === $k;
        ?>
            <a href="?tab=<?= $k ?>" style="
                padding:14px 22px; text-decoration:none; color:<?= $active ? 'var(--gold-dark)' : 'var(--text-mute)' ?>;
                font-size:13px; font-weight:<?= $active ? '600' : '500' ?>;
                border-bottom:2px solid <?= $active ? 'var(--gold)' : 'transparent' ?>;
                white-space:nowrap; display:flex;align-items:center;gap:8px">
                <?= icon($ico, 16) ?> <?= h($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Son işlem logu -->
<?php if ($lastLog): ?>
    <div class="card mt">
        <div class="card-head"><h2><?= icon('activity', 18) ?> &nbsp;Son İşlem Logu</h2></div>
        <div class="card-body">
            <pre style="font-family:ui-monospace,monospace;font-size:12.5px;line-height:1.7;max-height:300px;overflow:auto;padding:14px;background:#0F1424;color:#F5F1E8;border-radius:6px;white-space:pre-wrap;word-break:break-word"><?php
                foreach ($lastLog as $line) echo h($line) . "\n";
            ?></pre>
        </div>
    </div>
<?php endif; ?>

<?php if ($tab === 'overview'): ?>
    <!-- ════════ GENEL DURUM ════════ -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:24px" class="dual">
        <!-- Mevcut sürüm -->
        <div class="card">
            <div class="card-head"><h2><?= icon('package', 18) ?> &nbsp;Mevcut Sürüm</h2></div>
            <div class="card-body" style="text-align:center;padding:32px 24px">
                <div class="serif" style="font-size:64px;font-weight:200;color:var(--gold-dark);line-height:1">v<?= h($current) ?></div>
                <div class="muted" style="margin-top:8px;font-size:13px">Aksoy Group · Hizmetler Topluluğu Platformu</div>
                <hr class="divider">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:12px;text-align:left">
                    <div><div class="muted">Repo</div><div><code><?= h(AG_GITHUB_OWNER) ?>/<?= h(AG_GITHUB_REPO) ?></code></div></div>
                    <div><div class="muted">Branş</div><div><code><?= h(AG_GITHUB_BRANCH) ?></code></div></div>
                    <div><div class="muted">PHP</div><div><?= h(PHP_VERSION) ?></div></div>
                    <div><div class="muted">DB</div><div><?= h((string)DB::scalar("SELECT VERSION()")) ?></div></div>
                </div>
            </div>
        </div>

        <!-- Uzak sürüm -->
        <div class="card">
            <div class="card-head">
                <h2><?= icon('refresh', 18) ?> &nbsp;Uzak Sürüm</h2>
                <form method="post"><?= CSRF::field() ?><input type="hidden" name="action" value="check_remote">
                    <button class="btn btn-sm" <?= !$tokenSet ? 'disabled title="Önce GitHub token girin"' : '' ?>>
                        <?= icon('refresh', 14) ?> Kontrol Et
                    </button>
                </form>
            </div>
            <div class="card-body">
                <?php if (!$tokenSet): ?>
                    <div class="alert warning"><?= icon('shield', 16) ?> &nbsp;GitHub token tanımlı değil. <a href="?tab=settings">Ayarlar</a> sekmesinden ekleyin.</div>
                <?php elseif (!$cachedRelease): ?>
                    <div class="empty"><div class="serif">Henüz kontrol edilmedi</div><p>Üstteki <b>Kontrol Et</b> butonuna basın.</p></div>
                <?php else:
                    $up = version_compare($cachedRelease['version'], $current, '>');
                ?>
                    <div style="text-align:center;padding:8px 0">
                        <div class="serif" style="font-size:48px;font-weight:300;color:<?= $up ? 'var(--success)' : 'var(--text-mute)' ?>;line-height:1">v<?= h($cachedRelease['version']) ?></div>
                        <div class="muted" style="margin-top:6px;font-size:13px"><?= h($cachedRelease['name']) ?></div>
                        <?php if ($cachedRelease['published']): ?>
                            <div class="muted" style="font-size:11px;margin-top:4px"><?= h(formatDate($cachedRelease['published'], 'd M Y H:i')) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($cachedRelease['body']): ?>
                        <div style="background:var(--bg-soft);padding:14px;border-radius:6px;font-size:12.5px;white-space:pre-wrap;max-height:200px;overflow:auto;margin:14px 0"><?= h(truncate($cachedRelease['body'], 2000)) ?></div>
                    <?php endif; ?>
                    <?php if ($up): ?>
                        <form method="post" onsubmit="return confirm('v<?= h($cachedRelease['version']) ?> kurulacak. Otomatik yedek alınır. Emin misiniz?')">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="action" value="install_release">
                            <input type="hidden" name="version" value="<?= h($cachedRelease['version']) ?>">
                            <button class="btn navy" style="width:100%"><?= icon('download', 14) ?> v<?= h($cachedRelease['version']) ?> Sürümünü Kur</button>
                        </form>
                    <?php else: ?>
                        <div class="alert success"><?= icon('check', 16) ?> &nbsp;Sisteminiz güncel.</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ZIP yükleme -->
    <div class="card mt">
        <div class="card-head"><h2><?= icon('upload', 18) ?> &nbsp;Manuel ZIP Yükleme</h2></div>
        <div class="card-body">
            <p class="muted" style="font-size:13px;margin-bottom:14px">manifest.json içeren bir güncelleme ZIP'i yükleyin. Sistem manifest'i okuyup sürüm karşılaştırması yapacak ve dosyaları aktaracak.</p>
            <form method="post" enctype="multipart/form-data" onsubmit="return confirm('ZIP yüklenip uygulanacak. Otomatik yedek alınır. Emin misiniz?')">
                <?= CSRF::field() ?>
                <input type="hidden" name="action" value="upload_zip">
                <div style="display:flex;gap:12px;align-items:center">
                    <input type="file" name="zip" accept=".zip" required style="flex:1;padding:10px;border:1px solid var(--border);border-radius:6px">
                    <button class="btn navy"><?= icon('upload', 14) ?> Yükle ve Uygula</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Versiyon geçmişi -->
    <div class="card mt">
        <div class="card-head"><h2><?= icon('database', 18) ?> &nbsp;Sürüm Geçmişi</h2></div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Sürüm</th><th>Ad</th><th>Tarih</th><th class="center">Durum</th></tr></thead>
                <tbody>
                <?php foreach ($versionHistory as $v): ?>
                    <tr>
                        <td><span class="serif" style="font-size:16px;font-weight:500">v<?= h($v['version']) ?></span></td>
                        <td><?= h($v['release_name'] ?? '—') ?></td>
                        <td class="muted nowrap" style="font-size:12px"><?= h(formatDate($v['installed_at'], 'd M Y H:i')) ?></td>
                        <td class="center">
                            <?php if (($v['status'] ?? 'success') === 'success'): ?>
                                <span class="badge success">Başarılı</span>
                            <?php else: ?>
                                <span class="badge danger"><?= h($v['status'] ?? 'unknown') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$versionHistory): ?>
                    <tr><td colspan="4"><div class="empty"><div class="serif">Henüz güncelleme yok</div></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <style>@media(max-width:1024px){.dual{grid-template-columns:1fr !important}}</style>

<?php elseif ($tab === 'files'): ?>
    <!-- ════════ DOSYA SENKRONU ════════ -->
    <div class="card mt">
        <div class="card-head">
            <h2><?= icon('package', 18) ?> &nbsp;GitHub'dan Dosya Senkronu</h2>
            <div class="actions">
                <?php if ($diff): ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('Sadece değişen dosyalar GitHub\'dan çekilecek. Otomatik yedek alınır. Devam?')">
                        <?= CSRF::field() ?><input type="hidden" name="action" value="smart_sync">
                        <button class="btn navy"><?= icon('refresh', 14) ?> Akıllı Senkron</button>
                    </form>
                    <form method="post" style="display:inline" onsubmit="return confirm('TÜM dosyalar GitHub\'dan zorla yenilenecek. Bu işlem agresiftir. Otomatik yedek alınır. Emin misiniz?')">
                        <?= CSRF::field() ?><input type="hidden" name="action" value="force_sync">
                        <button class="btn outline" style="color:var(--danger);border-color:var(--danger)"><?= icon('rocket', 14) ?> Force Sync</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (!$tokenSet): ?>
                <div class="alert warning">GitHub token gerekli. <a href="?tab=settings">Ayarlar</a>'dan ekleyin.</div>
            <?php elseif (isset($diffError)): ?>
                <div class="alert error">⚠ <?= h($diffError) ?></div>
            <?php elseif ($diff): ?>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px">
                    <div style="padding:14px;background:var(--bg-soft);border-radius:6px;text-align:center">
                        <div class="muted" style="font-size:11px;letter-spacing:.15em;text-transform:uppercase">Toplam</div>
                        <div class="serif" style="font-size:28px"><?= count($diff['new']) + count($diff['changed']) + count($diff['unchanged']) ?></div>
                    </div>
                    <div style="padding:14px;background:#ecf6f0;border-radius:6px;text-align:center">
                        <div class="muted" style="font-size:11px;letter-spacing:.15em;text-transform:uppercase">Yeni</div>
                        <div class="serif" style="font-size:28px;color:var(--success)"><?= count($diff['new']) ?></div>
                    </div>
                    <div style="padding:14px;background:#fcf2e1;border-radius:6px;text-align:center">
                        <div class="muted" style="font-size:11px;letter-spacing:.15em;text-transform:uppercase">Değişmiş</div>
                        <div class="serif" style="font-size:28px;color:var(--warning)"><?= count($diff['changed']) ?></div>
                    </div>
                    <div style="padding:14px;background:var(--bg-soft);border-radius:6px;text-align:center">
                        <div class="muted" style="font-size:11px;letter-spacing:.15em;text-transform:uppercase">Aynı</div>
                        <div class="serif" style="font-size:28px;color:var(--text-mute)"><?= count($diff['unchanged']) ?></div>
                    </div>
                </div>

                <?php $allChanges = array_merge(
                    array_map(fn($f) => $f + ['_status' => 'new'], $diff['new']),
                    array_map(fn($f) => $f + ['_status' => 'changed'], $diff['changed'])
                ); ?>
                <?php if ($allChanges): ?>
                    <table class="data">
                        <thead><tr><th>Dosya</th><th class="right">Boyut</th><th class="center">Durum</th><th class="right">İşlem</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($allChanges, 0, 100) as $f): ?>
                            <tr>
                                <td><code style="font-size:12px"><?= h($f['path']) ?></code></td>
                                <td class="right num muted"><?= formatNumber((int)($f['size'] ?? 0)) ?> B</td>
                                <td class="center">
                                    <?= $f['_status'] === 'new'
                                        ? '<span class="badge success">Yeni</span>'
                                        : '<span class="badge warning">Değişmiş</span>' ?>
                                </td>
                                <td class="right">
                                    <form method="post" style="display:inline">
                                        <?= CSRF::field() ?>
                                        <input type="hidden" name="action" value="sync_one">
                                        <input type="hidden" name="path" value="<?= h($f['path']) ?>">
                                        <button class="btn ghost btn-sm"><?= icon('download', 12) ?> Çek</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($allChanges) > 100): ?>
                            <tr><td colspan="4" class="center muted">… ve <?= count($allChanges) - 100 ?> dosya daha</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert success"><?= icon('check', 16) ?> &nbsp;Tüm dosyalar GitHub ile senkron.</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($tab === 'migrations'): ?>
    <!-- ════════ MIGRATION ════════ -->
    <div class="card mt">
        <div class="card-head">
            <h2><?= icon('database', 18) ?> &nbsp;DB Migration Geçmişi</h2>
            <form method="post" onsubmit="return confirm('Tüm migrations/v*.sql dosyaları çalıştırılacak (idempotent). Devam?')">
                <?= CSRF::field() ?><input type="hidden" name="action" value="run_migrations">
                <button class="btn outline btn-sm"><?= icon('rocket', 14) ?> Migration'ları Çalıştır</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Dosya</th><th>Checksum</th><th>Tarih</th><th class="center">Durum</th><th class="right">İşlem</th></tr></thead>
                <tbody>
                <?php foreach ($migrations as $m): ?>
                    <tr>
                        <td><code style="font-size:12px"><?= h($m['filename']) ?></code>
                            <?php if ($m['notes']): ?><div class="muted" style="font-size:11px;margin-top:4px"><?= h(truncate($m['notes'], 100)) ?></div><?php endif; ?>
                        </td>
                        <td><code style="font-size:11px;color:var(--text-mute)"><?= h(substr($m['checksum'] ?? '', 0, 12)) ?></code></td>
                        <td class="muted nowrap" style="font-size:12px"><?= h(formatDate($m['applied_at'], 'd M Y H:i')) ?></td>
                        <td class="center">
                            <?= match($m['status']) {
                                'ok'      => '<span class="badge success">Uygulandı</span>',
                                'error'   => '<span class="badge danger">Hata</span>',
                                'skipped' => '<span class="badge muted">Atlandı</span>',
                                default   => '<span class="badge muted">' . h($m['status']) . '</span>',
                            } ?>
                        </td>
                        <td class="right">
                            <?php if ($m['status'] === 'error'): ?>
                                <form method="post" style="display:inline" onsubmit="return confirm('<?= h($m['filename']) ?> kalıcı olarak atlansın mı?')">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="action" value="mark_skipped">
                                    <input type="hidden" name="filename" value="<?= h($m['filename']) ?>">
                                    <button class="btn ghost btn-sm" style="color:var(--warning)">Atla</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$migrations): ?>
                    <tr><td colspan="5"><div class="empty"><div class="serif">Henüz migration kaydı yok</div></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($tab === 'backups'): ?>
    <!-- ════════ YEDEKLER ════════ -->
    <div class="card mt">
        <div class="card-head">
            <h2><?= icon('shield', 18) ?> &nbsp;Yedek Dosyaları (max <?= Updater::MAX_BACKUPS ?>)</h2>
            <form method="post"><?= CSRF::field() ?><input type="hidden" name="action" value="create_backup">
                <button class="btn outline btn-sm"><?= icon('download', 14) ?> Şimdi Yedek Al</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Dosya</th><th class="right num">Boyut</th><th>Tarih</th><th class="right">İşlem</th></tr></thead>
                <tbody>
                <?php foreach ($backups as $b): ?>
                    <tr>
                        <td><code style="font-size:12px"><?= h($b['name']) ?></code></td>
                        <td class="right num"><?= formatNumber((int)round($b['size']/1024)) ?> KB</td>
                        <td class="muted nowrap"><?= h(formatDate(date('Y-m-d H:i:s', $b['time']), 'd M Y H:i')) ?></td>
                        <td class="right">
                            <form method="post" style="display:inline" onsubmit="return confirm('Bu yedeğe geri dönülecek. Mevcut dosyalar üzerine yazılır (önce otomatik yedek alınır). Emin misiniz?')">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="action" value="rollback">
                                <input type="hidden" name="backup" value="<?= h($b['name']) ?>">
                                <button class="btn ghost btn-sm" style="color:var(--warning)"><?= icon('refresh', 12) ?> Geri Dön</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$backups): ?>
                    <tr><td colspan="4"><div class="empty"><div class="serif">Henüz yedek yok</div><p>Güncelleme yaptığınızda otomatik yedek alınır.</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($tab === 'settings'): ?>
    <!-- ════════ AYARLAR ════════ -->
    <div class="card mt">
        <div class="card-head"><h2><?= icon('settings', 18) ?> &nbsp;GitHub Token Ayarı</h2></div>
        <div class="card-body">
            <p class="muted" style="font-size:13px;margin-bottom:14px;line-height:1.6">
                GitHub Personal Access Token (PAT) ile uzak depodan dosya senkronlama, release indirme ve API erişimi yapılır.
                <br>Token oluşturma: <a href="https://github.com/settings/tokens?type=beta" target="_blank" style="color:var(--gold-dark)">github.com/settings/tokens</a> →
                <b>Fine-grained</b> → repo: <code><?= h(AG_GITHUB_OWNER) ?>/<?= h(AG_GITHUB_REPO) ?></code> → Permissions: <b>Contents (Read)</b>
            </p>
            <form method="post">
                <?= CSRF::field() ?>
                <input type="hidden" name="action" value="save_token">
                <div class="field-group">
                    <label>Token <?php if ($tokenSet): ?><span class="badge success" style="margin-left:8px">Tanımlı</span><?php endif; ?></label>
                    <input type="password" name="token" placeholder="<?= $tokenSet ? '••••••••••••••••••••' . h(substr($token, -4)) : 'ghp_… veya github_pat_…' ?>" autocomplete="off">
                    <span class="help">Token sadece sunucudaki <code>includes/.gh_token</code> dosyasında saklanır.</span>
                </div>
                <button class="btn"><?= icon('shield', 14) ?> Token'ı Kaydet</button>
            </form>
        </div>
    </div>

    <div class="card mt">
        <div class="card-head"><h2><?= icon('database', 18) ?> &nbsp;Sistem Bilgileri</h2></div>
        <div class="card-body">
            <table class="data">
                <tbody>
                    <tr><td>Mevcut Sürüm</td><td><b>v<?= h($current) ?></b></td></tr>
                    <tr><td>Repo</td><td><code><?= h(AG_GITHUB_OWNER) ?>/<?= h(AG_GITHUB_REPO) ?></code></td></tr>
                    <tr><td>Branş</td><td><code><?= h(AG_GITHUB_BRANCH) ?></code></td></tr>
                    <tr><td>Token Durumu</td><td><?= $tokenSet ? '<span class="badge success">Tanımlı</span>' : '<span class="badge warning">Tanımsız</span>' ?></td></tr>
                    <tr><td>PHP Sürümü</td><td><?= h(PHP_VERSION) ?></td></tr>
                    <tr><td>cURL</td><td><?= function_exists('curl_init') ? '<span class="badge success">Var</span>' : '<span class="badge danger">Yok</span>' ?></td></tr>
                    <tr><td>ZipArchive</td><td><?= class_exists('ZipArchive') ? '<span class="badge success">Var</span>' : '<span class="badge danger">Yok</span>' ?></td></tr>
                    <tr><td>Yedek Klasörü</td><td><code><?= h(str_replace(AG_ROOT, '', Updater::BACKUP_DIR)) ?></code> <?= is_writable(dirname(Updater::BACKUP_DIR)) ? '<span class="badge success">Yazılabilir</span>' : '<span class="badge danger">Yazılamaz</span>' ?></td></tr>
                    <tr><td>Yedek Sayısı</td><td><?= count($backups) ?> / <?= Updater::MAX_BACKUPS ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../_footer.php'; ?>
