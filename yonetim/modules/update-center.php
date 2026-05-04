<?php
declare(strict_types=1);
define('AG_ADMIN', true);
$adminTitle = 'Güncelleme Merkezi';
$adminBreadcrumb = 'Sistem';
$adminMenu = 'update';
require __DIR__ . '/../_layout.php';

Auth::requireRole('superadmin');

$updater = new Updater();
$current = $updater->currentVersion();
$tokenSet = !empty($updater->token());
$migrations = [];
try { $migrations = DB::all("SELECT * FROM ag_migrations ORDER BY id DESC LIMIT 50"); } catch (Throwable $e) {}
$versionHistory = DB::all("SELECT * FROM ag_versions ORDER BY id DESC LIMIT 20");
$csrfToken = CSRF::token();
?>

<style>
/* ── Update Center Smart UI ───────────────────────────── */
.upd-wrap { display: flex; flex-direction: column; gap: 24px; }
.upd-tabs {
    display: flex; gap: 4px; border-bottom: 1px solid var(--border);
    overflow-x: auto; background: var(--bg);
    margin: -24px -24px 0; padding: 0 24px;
}
.upd-tab {
    padding: 14px 22px; background: none; border: 0; border-bottom: 2px solid transparent;
    color: var(--text-mute); font-size: 13px; font-weight: 500; cursor: pointer;
    white-space: nowrap; display: flex; align-items: center; gap: 8px;
    transition: all .15s; font-family: inherit;
}
.upd-tab:hover { color: var(--text); }
.upd-tab.on { color: var(--gold-dark); border-bottom-color: var(--gold); font-weight: 600; }
.upd-body { display: none; padding-top: 24px; }
.upd-body.on { display: block; }

/* Repository status header */
.upd-rs {
    background: var(--bg-soft); border: 1px solid var(--border);
    padding: 28px 32px; border-radius: 8px;
}
.upd-rs-h { font-size: 11px; letter-spacing: .2em; text-transform: uppercase; color: var(--gold-dark); margin-bottom: 18px; font-weight: 600; }
.upd-ver { display: flex; align-items: center; gap: 24px; margin-bottom: 24px; flex-wrap: wrap; }
.upd-vbox { padding: 14px 22px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; min-width: 140px; }
.upd-vbox .upd-vlbl { font-size: 10px; letter-spacing: .25em; text-transform: uppercase; color: var(--text-mute); margin-bottom: 6px; }
.upd-vbox .upd-vval { font-family: var(--serif); font-weight: 300; font-size: 28px; color: var(--gold-dark); line-height: 1; letter-spacing: -.01em; }
.upd-arrow { font-size: 20px; color: var(--gold); font-weight: 300; }
.upd-vbadge .upd-badge { padding: 6px 14px; border-radius: 100px; font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
.upd-b-blue { background: rgba(15,20,36,.08); color: var(--gold-dark); }
.upd-b-green { background: rgba(31,122,77,.12); color: var(--success); border: 1px solid rgba(31,122,77,.25); }
.upd-b-warn { background: rgba(232,165,37,.15); color: #c08400; border: 1px solid rgba(232,165,37,.3); }
.upd-b-red { background: rgba(184,42,42,.12); color: var(--danger); border: 1px solid rgba(184,42,42,.3); }

/* Stats grid */
.upd-stats {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;
    margin-bottom: 24px;
}
.upd-stat {
    background: var(--bg); border: 1px solid var(--border); padding: 16px;
    border-radius: 8px; text-align: center;
}
.upd-stat-v { font-family: var(--serif); font-weight: 300; font-size: 32px; line-height: 1; }
.upd-stat-l { font-size: 10px; letter-spacing: .2em; text-transform: uppercase; color: var(--text-mute); margin-top: 6px; }

/* Action buttons */
.upd-act { display: flex; gap: 10px; flex-wrap: wrap; }
.upd-act .btn:disabled { opacity: .5; cursor: not-allowed; }

/* File grid */
.upd-fmsg { color: var(--text-mute); font-size: 13px; margin-bottom: 16px; }
.upd-fgrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 8px; max-height: 600px; overflow-y: auto; padding: 4px; }
.upd-fitem {
    display: flex; align-items: center; gap: 10px; padding: 10px 14px;
    background: var(--bg-soft); border: 1px solid var(--border); border-radius: 6px;
    font-size: 13px; transition: all .15s;
}
.upd-fitem:hover { background: var(--bg); border-color: var(--gold-dark); }
.upd-fitem.f-ok { color: var(--text-mute); }
.upd-fitem.f-diff { background: rgba(232,165,37,.06); border-color: rgba(232,165,37,.25); }
.upd-fitem.f-missing { background: rgba(31,122,77,.06); border-color: rgba(31,122,77,.25); }
.upd-fitem .fn { flex: 1; font-family: ui-monospace, monospace; font-size: 11.5px; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.upd-fitem button { font-size: 10px; padding: 4px 10px; }

/* Live console (overlay) */
.upd-overlay {
    display: none; position: fixed; inset: 0; background: rgba(15,20,36,.85);
    backdrop-filter: blur(6px); z-index: 1000; align-items: center; justify-content: center; padding: 24px;
}
.upd-overlay.on { display: flex; }
.upd-ovbox {
    background: #0F1424; border: 1px solid #2A2F40; border-radius: 12px;
    width: 100%; max-width: 720px; padding: 36px 32px 28px; color: #F5F1E8;
    position: relative; overflow: hidden;
    box-shadow: 0 24px 80px rgba(0,0,0,.4);
}
.upd-ovbox::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, #C9A961 0%, #C9A961 var(--p, 0%), #2A2F40 var(--p, 0%));
    transition: --p .6s ease;
}
.upd-ovh { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; }
.upd-ovh-icon { width: 44px; height: 44px; border-radius: 50%; background: rgba(201,169,97,.15); display: flex; align-items: center; justify-content: center; font-size: 24px; color: #C9A961; }
.upd-ovh-t { font-family: var(--serif); font-weight: 300; font-size: 22px; line-height: 1.2; }
.upd-ovh-s { font-size: 12px; color: #8c8a82; margin-top: 2px; }

/* Steps */
.upd-steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 22px; position: relative; }
.upd-steps::before {
    content: ''; position: absolute; top: 19px; left: 8%; right: 8%; height: 1px;
    background: #2A2F40; z-index: 0;
}
.upd-step { display: flex; flex-direction: column; align-items: center; gap: 8px; position: relative; z-index: 1; }
.upd-step-circle { width: 38px; height: 38px; border-radius: 50%; background: #1F2434; border: 1px solid #2A2F40; display: flex; align-items: center; justify-content: center; transition: all .3s; font-size: 14px; color: #6b7280; }
.upd-step-label { font-size: 10px; letter-spacing: .1em; color: #6b7280; text-align: center; line-height: 1.4; }
.upd-step.active .upd-step-circle { background: rgba(201,169,97,.18); border-color: #C9A961; color: #C9A961; box-shadow: 0 0 0 4px rgba(201,169,97,.1); }
.upd-step.active .upd-step-circle .spin { animation: spin 1s linear infinite; }
.upd-step.active .upd-step-label { color: #C9A961; }
.upd-step.done .upd-step-circle { background: rgba(31,122,77,.2); border-color: var(--success); color: var(--success); }
.upd-step.done .upd-step-label { color: var(--success); }
.upd-step.error .upd-step-circle { background: rgba(184,42,42,.2); border-color: var(--danger); color: var(--danger); }
@keyframes spin { from { transform: rotate(0); } to { transform: rotate(360deg); } }

.upd-detail {
    padding: 12px 14px; background: rgba(201,169,97,.08); border: 1px solid rgba(201,169,97,.2);
    border-radius: 8px; font-size: 13px; color: #F5F1E8; margin-bottom: 16px; min-height: 42px;
    display: flex; align-items: center; gap: 10px;
}
.upd-detail.success { background: rgba(31,122,77,.12); border-color: rgba(31,122,77,.3); }
.upd-detail.error { background: rgba(184,42,42,.12); border-color: rgba(184,42,42,.3); }

.upd-log {
    background: #0A0E1A; border: 1px solid #1F2434; border-radius: 8px;
    padding: 12px 14px; font-family: ui-monospace, monospace; font-size: 11.5px;
    max-height: 220px; overflow-y: auto; line-height: 1.7;
}
.upd-log .ol-info { color: #b3aea2; }
.upd-log .ol-ok   { color: #5fbe85; }
.upd-log .ol-err  { color: #e9a8a8; }
.upd-log .ol-warn { color: #f0c068; }

.upd-ov-actions { margin-top: 18px; display: flex; gap: 10px; justify-content: flex-end; }

/* Toast */
.upd-toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 1100;
    background: var(--text); color: var(--bg); padding: 12px 20px; border-radius: 6px;
    font-size: 13px; box-shadow: 0 8px 24px rgba(0,0,0,.2); animation: slideUp .3s;
}
.upd-toast.success { background: var(--success); color: white; }
.upd-toast.error { background: var(--danger); color: white; }
@keyframes slideUp { from { transform: translateY(40px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

@media (max-width: 768px) {
    .upd-stats { grid-template-columns: repeat(2, 1fr); }
    .upd-vbox { min-width: 100px; padding: 12px 14px; }
    .upd-vbox .upd-vval { font-size: 22px; }
    .upd-fgrid { grid-template-columns: 1fr; }
}
</style>

<div class="upd-wrap">
    <!-- Sekme menüsü -->
    <div class="upd-tabs">
        <button class="upd-tab on" data-tab="overview"><?= icon('home', 14) ?> Genel Durum</button>
        <button class="upd-tab" data-tab="files"><?= icon('package', 14) ?> Dosyalar</button>
        <button class="upd-tab" data-tab="commits"><?= icon('activity', 14) ?> Commits</button>
        <button class="upd-tab" data-tab="migrations"><?= icon('database', 14) ?> Migration</button>
        <button class="upd-tab" data-tab="backups"><?= icon('shield', 14) ?> Yedekler</button>
        <button class="upd-tab" data-tab="settings"><?= icon('settings', 14) ?> Ayarlar</button>
    </div>

    <!-- ════ OVERVIEW ════ -->
    <div class="upd-body on" data-pane="overview">
        <div class="upd-rs">
            <div class="upd-rs-h">REPOSITORY STATUS · <?= h(AG_GITHUB_OWNER) ?>/<?= h(AG_GITHUB_REPO) ?> · <?= h(AG_GITHUB_BRANCH) ?></div>
            <div class="upd-ver">
                <div class="upd-vbox">
                    <div class="upd-vlbl">LOCAL</div>
                    <div class="upd-vval" id="upd-vlocal"><?= h($current) ?></div>
                </div>
                <span class="upd-arrow">→</span>
                <div class="upd-vbox">
                    <div class="upd-vlbl">GITHUB</div>
                    <div class="upd-vval" id="upd-vremote">…</div>
                </div>
                <div class="upd-vbadge" id="upd-vbadge"><span class="upd-badge upd-b-blue">Henüz kontrol edilmedi</span></div>
            </div>

            <div class="upd-stats">
                <div class="upd-stat"><div class="upd-stat-v" id="upd-sok" style="color:var(--success)">—</div><div class="upd-stat-l">Up to Date</div></div>
                <div class="upd-stat"><div class="upd-stat-v" id="upd-sdiff" style="color:var(--warning)">—</div><div class="upd-stat-l">Changed</div></div>
                <div class="upd-stat"><div class="upd-stat-v" id="upd-smiss" style="color:var(--gold-dark)">—</div><div class="upd-stat-l">Missing</div></div>
                <div class="upd-stat"><div class="upd-stat-v" id="upd-stot" style="color:var(--text-mute)">—</div><div class="upd-stat-l">Total</div></div>
            </div>

            <div class="upd-act">
                <button id="upd-btn-check" class="btn outline" onclick="updCheck()">
                    <?= icon('refresh', 14) ?> Check Status
                </button>
                <button id="upd-btn-sync" class="btn navy" onclick="updSync()" <?= !$tokenSet ? 'disabled title="Token gerekli"' : '' ?>>
                    <?= icon('rocket', 14) ?> Smart Update
                </button>
                <button id="upd-btn-force" class="btn outline" onclick="updForce()" <?= !$tokenSet ? 'disabled title="Token gerekli"' : '' ?>
                        style="color:var(--warning);border-color:var(--warning)">
                    <?= icon('rocket', 14) ?> Force Update
                </button>
                <?php if (!$tokenSet): ?>
                    <span style="margin-left:auto;display:inline-flex;align-items:center;gap:8px;color:var(--warning);font-size:12px">
                        <?= icon('shield', 14) ?> Token tanımlı değil — <a href="#" onclick="updGo('settings');return false" style="color:var(--gold-dark);text-decoration:underline">Ayarlar</a>'a gidin
                    </span>
                <?php endif; ?>
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
                                <?= ($v['status'] ?? 'success') === 'success'
                                    ? '<span class="badge success">Başarılı</span>'
                                    : '<span class="badge danger">' . h($v['status']) . '</span>' ?>
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
    </div>

    <!-- ════ FILES ════ -->
    <div class="upd-body" data-pane="files">
        <div class="card">
            <div class="card-head">
                <h2><?= icon('package', 18) ?> &nbsp;Dosya Senkron Durumu</h2>
                <button class="btn outline btn-sm" onclick="updCheck()"><?= icon('refresh', 14) ?> Yenile</button>
            </div>
            <div class="card-body">
                <p class="upd-fmsg" id="upd-fmsg">Önce <b>Genel Durum → Check Status</b> butonunu çalıştırın — dosya listesi buraya yüklenecek.</p>
                <div class="upd-fgrid" id="upd-fgrid"></div>
            </div>
        </div>
    </div>

    <!-- ════ COMMITS ════ -->
    <div class="upd-body" data-pane="commits">
        <div class="card">
            <div class="card-head">
                <h2><?= icon('activity', 18) ?> &nbsp;Son 20 Commit</h2>
                <button class="btn outline btn-sm" onclick="updLoadCommits()"><?= icon('refresh', 14) ?> Yenile</button>
            </div>
            <div class="card-body">
                <div id="upd-commits-list">
                    <p class="muted">Yükleniyor…</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ════ MIGRATIONS ════ -->
    <div class="upd-body" data-pane="migrations">
        <div class="card">
            <div class="card-head">
                <h2><?= icon('database', 18) ?> &nbsp;DB Migration Geçmişi</h2>
                <button class="btn outline btn-sm" onclick="updMigrate()" <?= !$tokenSet ? 'disabled' : '' ?>>
                    <?= icon('rocket', 14) ?> Migration'ları Çalıştır
                </button>
            </div>
            <div class="table-wrap">
                <table class="data" id="upd-mig-table">
                    <thead><tr><th>Dosya</th><th>Checksum</th><th>Tarih</th><th class="center">Durum</th></tr></thead>
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
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$migrations): ?>
                        <tr><td colspan="4"><div class="empty"><div class="serif">Henüz migration kaydı yok</div></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ════ BACKUPS ════ -->
    <div class="upd-body" data-pane="backups">
        <div class="card">
            <div class="card-head">
                <h2><?= icon('shield', 18) ?> &nbsp;Yedek Dosyaları (max <?= Updater::MAX_BACKUPS ?>)</h2>
                <button class="btn outline btn-sm" onclick="updBackup()"><?= icon('download', 14) ?> Şimdi Yedek Al</button>
            </div>
            <div class="table-wrap">
                <table class="data" id="upd-bk-table">
                    <thead><tr><th>Dosya</th><th class="right num">Boyut</th><th>Tarih</th><th class="right">İşlem</th></tr></thead>
                    <tbody><tr><td colspan="4" class="muted center" style="padding:24px">Yükleniyor…</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ════ SETTINGS ════ -->
    <div class="upd-body" data-pane="settings">
        <div class="card">
            <div class="card-head"><h2><?= icon('settings', 18) ?> &nbsp;GitHub Token Ayarı</h2></div>
            <div class="card-body">
                <p class="muted" style="font-size:13px;margin-bottom:14px;line-height:1.6">
                    GitHub Personal Access Token (PAT) ile uzak depodan dosya senkronlama, release indirme ve API erişimi yapılır.
                    Token sadece sunucudaki <code>includes/.gh_token</code> dosyasında saklanır.
                </p>
                <div class="field-group">
                    <label>Token <?php if ($tokenSet): ?><span class="badge success" style="margin-left:8px">Tanımlı</span><?php endif; ?></label>
                    <div style="display:flex;gap:10px">
                        <input type="password" id="upd-token" placeholder="<?= $tokenSet ? '••••••••••' : 'ghp_… veya github_pat_…' ?>" autocomplete="off" style="flex:1">
                        <button class="btn navy" onclick="updSaveToken()"><?= icon('shield', 14) ?> Kaydet</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt">
            <div class="card-head"><h2><?= icon('upload', 18) ?> &nbsp;Manuel ZIP Yükleme</h2></div>
            <div class="card-body">
                <p class="muted" style="font-size:13px;margin-bottom:14px">manifest.json içeren bir güncelleme ZIP'i yükleyin. Sistem manifest'i okuyup sürüm karşılaştırması yapacak ve dosyaları aktaracak.</p>
                <form id="upd-zip-form" onsubmit="updUploadZip(event);return false">
                    <div style="display:flex;gap:12px;align-items:center">
                        <input type="file" name="zip" accept=".zip" required style="flex:1;padding:10px;border:1px solid var(--border);border-radius:6px">
                        <button class="btn navy"><?= icon('upload', 14) ?> Yükle ve Uygula</button>
                    </div>
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Live update overlay -->
<div class="upd-overlay" id="upd-overlay">
    <div class="upd-ovbox" id="upd-ovbox">
        <div class="upd-ovh">
            <div class="upd-ovh-icon" id="upd-ovh-icon"><?= icon('rocket', 22) ?></div>
            <div>
                <div class="upd-ovh-t" id="upd-ovh-t">Akıllı Güncelleme</div>
                <div class="upd-ovh-s" id="upd-ovh-s">GitHub ile senkronize ediliyor</div>
            </div>
        </div>
        <div class="upd-steps" id="upd-steps">
            <div class="upd-step" data-step="1"><div class="upd-step-circle">1</div><div class="upd-step-label">GitHub Bağlantısı</div></div>
            <div class="upd-step" data-step="2"><div class="upd-step-circle">2</div><div class="upd-step-label">Dosyalar İndiriliyor</div></div>
            <div class="upd-step" data-step="3"><div class="upd-step-circle">3</div><div class="upd-step-label">Yedek + Açma</div></div>
            <div class="upd-step" data-step="4"><div class="upd-step-circle">4</div><div class="upd-step-label">Migration + Tamamla</div></div>
        </div>
        <div class="upd-detail" id="upd-detail">Hazırlanıyor…</div>
        <div class="upd-log" id="upd-log"></div>
        <div class="upd-ov-actions">
            <button class="btn ghost btn-sm" onclick="updOvClose()" id="upd-ov-close" style="display:none">Kapat</button>
        </div>
    </div>
</div>

<script>
const AG_CSRF = <?= json_encode($csrfToken) ?>;
const AG_API = '/api/update.php';
const $u = id => document.getElementById(id);

// ── Tab switch ───────────────────────────────────
document.querySelectorAll('.upd-tab').forEach(t => {
    t.addEventListener('click', () => updGo(t.dataset.tab, t));
});
function updGo(name, btn) {
    document.querySelectorAll('.upd-tab').forEach(x => x.classList.remove('on'));
    document.querySelectorAll('.upd-body').forEach(x => x.classList.remove('on'));
    if (!btn) btn = document.querySelector(`.upd-tab[data-tab="${name}"]`);
    btn.classList.add('on');
    document.querySelector(`[data-pane="${name}"]`).classList.add('on');
    if (name === 'commits' && !window._commitsLoaded) updLoadCommits();
    if (name === 'backups') updLoadBackups();
}

// ── Toast ────────────────────────────────────────
function updToast(msg, kind) {
    const t = document.createElement('div');
    t.className = 'upd-toast ' + (kind || '');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

// ── AJAX helpers ─────────────────────────────────
async function updFetch(action, opts) {
    opts = opts || {};
    opts.credentials = 'same-origin';
    opts.headers = opts.headers || {};
    opts.headers['X-CSRF-Token'] = AG_CSRF;
    opts.headers['X-Requested-With'] = 'XMLHttpRequest';
    opts.headers['Accept'] = 'application/json';
    const r = await fetch(AG_API + '?action=' + action, opts);
    const t = await r.text();
    try { return JSON.parse(t); }
    catch (e) {
        if (r.status === 401 || r.status === 403) throw new Error('Yetki hatası — oturumu yenileyin (HTTP ' + r.status + ')');
        if (r.status === 419) throw new Error('Oturum süresi doldu — sayfayı yenileyin');
        throw new Error('Geçersiz JSON yanıt (HTTP ' + r.status + '): ' + t.substring(0, 200));
    }
}

// ── Overlay (live console) ───────────────────────
function updOvOpen(title, sub, icon) {
    $u('upd-ovh-t').textContent = title;
    $u('upd-ovh-s').textContent = sub || '';
    if (icon) $u('upd-ovh-icon').innerHTML = icon;
    $u('upd-log').innerHTML = '';
    $u('upd-detail').textContent = 'Hazırlanıyor…';
    $u('upd-detail').className = 'upd-detail';
    $u('upd-ov-close').style.display = 'none';
    document.querySelectorAll('.upd-step').forEach(s => s.className = 'upd-step');
    $u('upd-overlay').classList.add('on');
    $u('upd-ovbox').style.setProperty('--p', '0%');
}
function updOvClose() {
    $u('upd-overlay').classList.remove('on');
}
function updStep(n, label) {
    document.querySelectorAll('.upd-step').forEach(s => {
        const sn = parseInt(s.dataset.step);
        if (sn < n) { s.className = 'upd-step done'; s.querySelector('.upd-step-circle').innerHTML = '✓'; }
        else if (sn === n) { s.className = 'upd-step active'; s.querySelector('.upd-step-circle').innerHTML = '<span class="spin">◐</span>'; }
        else { s.className = 'upd-step'; s.querySelector('.upd-step-circle').innerHTML = sn; }
    });
    $u('upd-detail').textContent = label || '';
    $u('upd-ovbox').style.setProperty('--p', (n / 4 * 100) + '%');
}
function updStepAllDone(label) {
    document.querySelectorAll('.upd-step').forEach(s => { s.className = 'upd-step done'; s.querySelector('.upd-step-circle').innerHTML = '✓'; });
    $u('upd-detail').className = 'upd-detail success';
    $u('upd-detail').textContent = label || 'Tamamlandı.';
    $u('upd-ovbox').style.setProperty('--p', '100%');
    $u('upd-ov-close').style.display = 'inline-flex';
}
function updStepError(n, label) {
    const s = document.querySelector(`.upd-step[data-step="${n}"]`);
    if (s) { s.className = 'upd-step error'; s.querySelector('.upd-step-circle').innerHTML = '✗'; }
    $u('upd-detail').className = 'upd-detail error';
    $u('upd-detail').textContent = label || 'Hata oluştu.';
    $u('upd-ov-close').style.display = 'inline-flex';
}
function updLog(text, kind) {
    const d = document.createElement('div');
    d.className = 'ol-' + (kind || 'info');
    d.textContent = text;
    $u('upd-log').appendChild(d);
    $u('upd-log').scrollTop = 99999;
}
const sleep = ms => new Promise(r => setTimeout(r, ms));

// ── CHECK STATUS ─────────────────────────────────
async function updCheck() {
    $u('upd-btn-check').disabled = true;
    $u('upd-vbadge').innerHTML = '<span class="upd-badge upd-b-blue"><span class="spin" style="display:inline-block">◐</span> Kontrol ediliyor…</span>';
    try {
        const d = await updFetch('status');
        if (!d.ok) {
            $u('upd-vbadge').innerHTML = '<span class="upd-badge upd-b-red">✗ ' + d.error + '</span>';
            return;
        }
        $u('upd-vremote').textContent = d.remote_ver;
        $u('upd-sok').textContent = d.stats.ok;
        $u('upd-sdiff').textContent = d.stats.diff;
        $u('upd-smiss').textContent = d.stats.missing;
        $u('upd-stot').textContent = d.total;
        if (d.needs_update) {
            const cnt = d.stats.diff + d.stats.missing;
            $u('upd-vbadge').innerHTML = '<span class="upd-badge upd-b-warn">⚠ ' + cnt + ' dosya güncel değil</span>';
        } else {
            $u('upd-vbadge').innerHTML = '<span class="upd-badge upd-b-green">✓ Tüm dosyalar güncel</span>';
        }
        updRenderFiles(d.files);
    } catch (e) {
        $u('upd-vbadge').innerHTML = '<span class="upd-badge upd-b-red">✗ ' + e.message + '</span>';
    } finally {
        $u('upd-btn-check').disabled = false;
    }
}

function updRenderFiles(files) {
    const keys = Object.keys(files || {});
    if (!keys.length) { $u('upd-fgrid').innerHTML = ''; $u('upd-fmsg').textContent = 'Dosya bulunamadı.'; return; }
    $u('upd-fmsg').style.display = 'none';
    const I = { ok: '✓', diff: '◐', missing: '○' };
    $u('upd-fgrid').innerHTML = keys.map(f => {
        const s = files[f];
        const btn = (s.status !== 'ok')
            ? `<button class="btn ghost" onclick="updOne('${f}', this)">Çek</button>` : '';
        return `<div class="upd-fitem f-${s.status}" title="${f}">
            <span style="width:18px;text-align:center">${I[s.status]}</span>
            <span class="fn">${f}</span>${btn}
        </div>`;
    }).join('');
}

async function updOne(file, btn) {
    btn.disabled = true; btn.textContent = '…';
    const fd = new FormData(); fd.append('file', file);
    try {
        const d = await updFetch('update_file', { method: 'POST', body: fd });
        if (d.ok) { btn.textContent = '✓'; btn.style.background = 'var(--success)'; btn.style.color = '#fff'; updToast(file + ' güncellendi', 'success'); }
        else { btn.textContent = '✗'; updToast(d.error, 'error'); }
    } catch (e) { btn.textContent = '✗'; updToast(e.message, 'error'); }
}

// ── SMART UPDATE ─────────────────────────────────
async function updSync() {
    if (!confirm('Akıllı Güncelleme:\nSadece değişen dosyalar GitHub\'dan indirilecek. Önce otomatik yedek alınır. Migration\'lar otomatik çalıştırılır.\n\nDevam edilsin mi?')) return;
    updOvOpen('Akıllı Güncelleme', 'GitHub ile senkronize ediliyor');
    $u('upd-btn-sync').disabled = true;

    const fetchPromise = updFetch('sync', { method: 'POST' }).catch(e => ({ ok: false, error: e.message }));

    updStep(1, 'GitHub API bağlantısı kuruluyor…');
    updLog('🔗 GitHub bağlantısı kuruluyor…');
    await sleep(500);

    updStep(2, 'Repo dosya ağacı çekiliyor…');
    updLog('📥 Repo tree alınıyor, diff hesaplanıyor…');
    await sleep(800);

    updStep(3, 'Yedek alınıyor + dosyalar indiriliyor…');
    updLog('💾 Otomatik yedek + smart sync…');
    const d = await fetchPromise;

    if (!d.ok) {
        updStepError(3, d.error || 'Bilinmeyen hata');
        updLog('✗ ' + d.error, 'err');
        $u('upd-btn-sync').disabled = false;
        return;
    }

    updStep(4, 'Migration ve sürüm kaydı…');
    (d.log || []).forEach(l => {
        const k = l.startsWith('✓') ? 'ok' : (l.startsWith('⚠') || l.startsWith('✗') || l.startsWith('!')) ? 'err' : 'info';
        updLog(l, k);
    });
    await sleep(400);

    if (d.updated > 0) {
        updStepAllDone('✓ ' + d.updated + ' dosya güncellendi → v' + (d.version || '?'));
        $u('upd-vlocal').textContent = d.version || '?';
        updToast(d.updated + ' dosya güncellendi', 'success');
        setTimeout(() => { updCheck(); }, 1000);
    } else {
        updStepAllDone('✓ Tüm dosyalar zaten güncel');
        updToast('Sistem güncel', 'success');
    }
    $u('upd-btn-sync').disabled = false;
}

// ── FORCE UPDATE ─────────────────────────────────
async function updForce() {
    if (!confirm('FORCE UPDATE:\nTÜM dosyalar GitHub\'dan zorla indirilecek. Bu işlem agresiftir, mevcut yerel değişiklikler kaybolur.\nÖnce otomatik yedek alınır.\n\nDevam edilsin mi?')) return;
    updOvOpen('Force Update', 'Tüm repo zorla yenileniyor', '<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>');
    $u('upd-btn-force').disabled = true;

    const fetchPromise = updFetch('force', { method: 'POST' }).catch(e => ({ ok: false, error: e.message }));

    updStep(1, 'GitHub bağlantısı…'); updLog('🔗 GitHub API…'); await sleep(400);
    updStep(2, 'Tüm repo dosyaları indiriliyor…'); updLog('🔥 Force sync başladı…'); await sleep(700);
    updStep(3, 'Yedek + yazma…');
    const d = await fetchPromise;

    if (!d.ok) {
        updStepError(3, d.error);
        updLog('✗ ' + d.error, 'err');
        $u('upd-btn-force').disabled = false;
        return;
    }
    updStep(4, 'Migration…');
    (d.log || []).forEach(l => updLog(l, l.startsWith('✓') ? 'ok' : (l.startsWith('⚠') || l.startsWith('✗')) ? 'err' : 'info'));
    await sleep(300);

    updStepAllDone('✓ Force Update tamamlandı (' + d.updated + ' dosya)');
    $u('upd-vlocal').textContent = d.version || '?';
    updToast('Force Update tamamlandı', 'success');
    $u('upd-btn-force').disabled = false;
    setTimeout(() => updCheck(), 1000);
}

// ── COMMITS ──────────────────────────────────────
async function updLoadCommits() {
    $u('upd-commits-list').innerHTML = '<p class="muted">Yükleniyor…</p>';
    try {
        const d = await updFetch('commits');
        if (!d.ok) { $u('upd-commits-list').innerHTML = '<p style="color:var(--danger)">✗ ' + d.error + '</p>'; return; }
        if (!d.commits.length) { $u('upd-commits-list').innerHTML = '<p class="muted">Commit yok.</p>'; return; }
        $u('upd-commits-list').innerHTML = '<div style="display:flex;flex-direction:column;gap:8px">'
            + d.commits.map(c => `
                <div style="padding:14px 16px;background:var(--bg-soft);border:1px solid var(--border);border-radius:6px">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:6px">
                        <code style="background:var(--bg);padding:2px 8px;border-radius:4px;font-size:11px;color:var(--gold-dark)">${c.sha}</code>
                        <a href="${c.url}" target="_blank" rel="noopener" style="font-size:11px;color:var(--text-mute)">GitHub →</a>
                    </div>
                    <div style="font-size:13.5px;line-height:1.5;color:var(--text);margin-bottom:6px;white-space:pre-wrap">${c.message.split('\n')[0]}</div>
                    <div style="font-size:11px;color:var(--text-mute)">${c.author} · ${new Date(c.date).toLocaleString('tr-TR')}</div>
                </div>
            `).join('')
            + '</div>';
        window._commitsLoaded = true;
    } catch (e) {
        $u('upd-commits-list').innerHTML = '<p style="color:var(--danger)">✗ ' + e.message + '</p>';
    }
}

// ── MIGRATE ──────────────────────────────────────
async function updMigrate() {
    if (!confirm('Tüm migrations/v*.sql dosyaları çalıştırılacak (idempotent — daha önce uygulananlar atlanır). Devam?')) return;
    updOvOpen('DB Migration', 'Migration dosyaları çalıştırılıyor', '<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/></svg>');
    updStep(2, 'SQL dosyaları taranıyor…');
    await sleep(300);
    updStep(3, 'Statement\'lar çalıştırılıyor…');

    const d = await updFetch('migrate', { method: 'POST' });
    if (!d.ok) { updStepError(3, d.error); return; }
    updStep(4, 'Tamamlandı, tablo yenileniyor…');
    (d.log || []).forEach(l => updLog(l, l.startsWith('✓') ? 'ok' : l.startsWith('↷') ? 'info' : 'warn'));
    await sleep(400);
    updStepAllDone('✓ Migration tamamlandı');
    updToast('Migration tamamlandı', 'success');
    setTimeout(() => location.reload(), 1500);
}

// ── BACKUPS ──────────────────────────────────────
async function updLoadBackups() {
    const d = await updFetch('backups');
    const tbody = document.querySelector('#upd-bk-table tbody');
    if (!d.ok || !d.backups.length) { tbody.innerHTML = '<tr><td colspan="4" class="muted center" style="padding:24px">Henüz yedek yok</td></tr>'; return; }
    tbody.innerHTML = d.backups.map(b => `
        <tr>
            <td><code style="font-size:12px">${b.name}</code></td>
            <td class="right num">${(b.size/1024).toFixed(1)} KB</td>
            <td class="muted nowrap">${new Date(b.time*1000).toLocaleString('tr-TR')}</td>
            <td class="right">
                <button class="btn ghost btn-sm" style="color:var(--warning)" onclick="updRollback('${b.name}')">↺ Geri Dön</button>
            </td>
        </tr>
    `).join('');
}
async function updBackup() {
    const d = await updFetch('backup', { method: 'POST' });
    if (d.ok) { updToast('Yedek alındı: ' + d.name, 'success'); updLoadBackups(); }
    else updToast(d.error, 'error');
}
async function updRollback(name) {
    if (!confirm('Bu yedeğe geri dönülecek:\n' + name + '\n\nÖnce mevcut hal otomatik yedeklenir. Emin misiniz?')) return;
    updOvOpen('Rollback', 'Yedekten geri dönülüyor');
    updStep(2, 'Mevcut hal yedekleniyor…'); await sleep(400);
    updStep(3, 'Yedek dosyaları yazılıyor…');
    const fd = new FormData(); fd.append('backup', name);
    const d = await updFetch('rollback', { method: 'POST', body: fd });
    if (!d.ok) { updStepError(3, d.error); return; }
    updStep(4, 'Tamamlandı'); (d.log || []).forEach(l => updLog(l, 'info'));
    updStepAllDone('✓ Rollback tamamlandı');
    setTimeout(() => location.reload(), 2000);
}

// ── TOKEN ────────────────────────────────────────
async function updSaveToken() {
    const tok = $u('upd-token').value.trim();
    if (!tok) { updToast('Token boş olamaz', 'error'); return; }
    const fd = new FormData(); fd.append('token', tok);
    const d = await updFetch('save_token', { method: 'POST', body: fd });
    if (d.ok) { updToast('Token kaydedildi', 'success'); setTimeout(() => location.reload(), 1000); }
    else updToast(d.error, 'error');
}

// ── ZIP UPLOAD ───────────────────────────────────
async function updUploadZip(ev) {
    if (!confirm('ZIP yüklenip uygulanacak. Otomatik yedek alınır. Devam?')) return;
    const fd = new FormData(ev.target);
    updOvOpen('ZIP Yükleme', 'Manuel paket uygulanıyor', '<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>');
    updStep(1, 'ZIP yükleniyor…'); await sleep(300);
    updStep(2, 'manifest.json okunuyor…');
    const d = await updFetch('upload_zip', { method: 'POST', body: fd });
    if (!d.ok) { updStepError(2, d.error); return; }
    updStep(3, 'Dosyalar açılıyor…'); await sleep(300);
    updStep(4, 'Migration…');
    (d.log || []).forEach(l => updLog(l, l.startsWith('✓') ? 'ok' : 'info'));
    updStepAllDone('✓ ZIP uygulandı: v' + (d.version || '?'));
    setTimeout(() => location.reload(), 2000);
}

// ── İLK YÜKLENİŞ: otomatik kontrol ────────────────
<?php if ($tokenSet): ?>
window.addEventListener('DOMContentLoaded', () => setTimeout(updCheck, 500));
<?php endif; ?>
</script>

<?php require __DIR__ . '/../_footer.php'; ?>
