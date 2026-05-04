<?php
declare(strict_types=1);
define('AG_ADMIN', true);
$adminTitle = 'Sayfalar';
$adminBreadcrumb = 'İçerik';
$adminMenu = 'pages';
require __DIR__ . '/../_layout.php';

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

if ($action === 'delete' && $id) {
    CSRF::require();
    $sayfa = DB::row("SELECT is_system FROM ag_pages WHERE id = ?", [$id]);
    if ($sayfa && $sayfa['is_system']) {
        flash('error', 'Sistem sayfaları silinemez.');
    } else {
        DB::exec("DELETE FROM ag_pages WHERE id = ?", [$id]);
        Audit::log('delete', 'page', $id, null, null, 'warning');
        flash('success', 'Sayfa silindi.');
    }
    redirect('/yonetim/modules/pages.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $data = [
        'slug'         => slugify($_POST['slug'] ?? ($_POST['baslik'] ?? '')),
        'baslik'       => trim($_POST['baslik'] ?? ''),
        'alt_baslik'   => trim($_POST['alt_baslik'] ?? '') ?: null,
        'icerik'       => trim($_POST['icerik'] ?? '') ?: null,
        'template'     => trim($_POST['template'] ?? 'default'),
        'menu_konumu'  => in_array($_POST['menu_konumu'] ?? '', ['header','footer','her_ikisi','gizli'], true) ? $_POST['menu_konumu'] : 'gizli',
        'sort_order'   => (int)($_POST['sort_order'] ?? 0),
        'meta_title'   => trim($_POST['meta_title'] ?? '') ?: null,
        'meta_description' => trim($_POST['meta_description'] ?? '') ?: null,
        'is_active'    => isset($_POST['is_active']) ? 1 : 0,
    ];
    if (!empty($_FILES['kapak_gorsel']['tmp_name'])) {
        $up = uploadImage($_FILES['kapak_gorsel'], 'sektorler');
        if ($up) $data['kapak_gorsel'] = $up;
    }
    if (!$data['baslik'] || !$data['slug']) {
        flash('error', 'Başlık ve slug zorunludur.');
    } else {
        try {
            if ($id > 0) {
                DB::update('ag_pages', $data, 'id = :pid', ['pid' => $id]);
                Audit::log('update', 'page', $id);
                flash('success', 'Sayfa güncellendi.');
            } else {
                $id = DB::insert('ag_pages', $data);
                Audit::log('create', 'page', $id);
                flash('success', 'Sayfa eklendi.');
            }
            redirect('/yonetim/modules/pages.php');
        } catch (Throwable $e) { flash('error', 'Hata: ' . $e->getMessage()); }
    }
}

$item = null;
if ($action === 'edit' && $id) {
    $item = DB::row("SELECT * FROM ag_pages WHERE id = ?", [$id]);
    if (!$item) { flash('error', 'Sayfa bulunamadı.'); redirect('/yonetim/modules/pages.php'); }
}
if ($action === 'new') $item = ['is_active' => 1, 'menu_konumu' => 'gizli', 'template' => 'default'];

if (!$item) {
    $sayfalar = DB::all("SELECT * FROM ag_pages ORDER BY menu_konumu, sort_order, baslik");
}
?>

<?php if ($item): ?>
    <a href="/yonetim/modules/pages.php" class="btn ghost btn-sm" style="margin-bottom:16px"><?= icon('arrow-left', 14) ?> Tüm Sayfalar</a>
    <form method="post" enctype="multipart/form-data" class="card">
        <?= CSRF::field() ?>
        <div class="card-head">
            <h2><?= $id ? 'Sayfa Düzenle' : 'Yeni Sayfa' ?></h2>
            <div class="actions">
                <a href="/yonetim/modules/pages.php" class="btn outline btn-sm">İptal</a>
                <button type="submit" class="btn btn-sm"><?= icon('check', 14) ?> Kaydet</button>
            </div>
        </div>
        <div class="card-body">
            <div class="form-row cols-2">
                <div class="field-group">
                    <label>Başlık <span class="req">*</span></label>
                    <input type="text" name="baslik" required value="<?= h($item['baslik'] ?? '') ?>" data-auto-slug="[name=slug]">
                </div>
                <div class="field-group">
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?= h($item['slug'] ?? '') ?>" data-touch-on-input <?= !empty($item['is_system']) ? 'readonly' : '' ?>>
                    <span class="help">Sayfa URL'i: <code>/<?= h($item['slug'] ?? 'slug') ?></code></span>
                </div>
            </div>
            <div class="form-row">
                <div class="field-group">
                    <label>Alt Başlık</label>
                    <input type="text" name="alt_baslik" value="<?= h($item['alt_baslik'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="field-group">
                    <label>İçerik (HTML)</label>
                    <textarea name="icerik" rows="18" style="font-family:ui-monospace,monospace;font-size:13px"><?= h($item['icerik'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="form-row cols-3">
                <div class="field-group">
                    <label>Şablon</label>
                    <select name="template">
                        <option value="default" <?= ($item['template'] ?? '') === 'default' ? 'selected' : '' ?>>Varsayılan</option>
                        <option value="hakkimizda" <?= ($item['template'] ?? '') === 'hakkimizda' ? 'selected' : '' ?>>Hakkımızda</option>
                        <option value="kariyer" <?= ($item['template'] ?? '') === 'kariyer' ? 'selected' : '' ?>>Kariyer</option>
                        <option value="iletisim" <?= ($item['template'] ?? '') === 'iletisim' ? 'selected' : '' ?>>İletişim</option>
                        <option value="basin" <?= ($item['template'] ?? '') === 'basin' ? 'selected' : '' ?>>Basın</option>
                        <option value="yonetim_kurulu" <?= ($item['template'] ?? '') === 'yonetim_kurulu' ? 'selected' : '' ?>>Yönetim Kurulu</option>
                    </select>
                </div>
                <div class="field-group">
                    <label>Menü Konumu</label>
                    <select name="menu_konumu">
                        <option value="header" <?= ($item['menu_konumu'] ?? '') === 'header' ? 'selected' : '' ?>>Üst Menü</option>
                        <option value="footer" <?= ($item['menu_konumu'] ?? '') === 'footer' ? 'selected' : '' ?>>Alt Menü</option>
                        <option value="her_ikisi" <?= ($item['menu_konumu'] ?? '') === 'her_ikisi' ? 'selected' : '' ?>>Her İkisi</option>
                        <option value="gizli" <?= ($item['menu_konumu'] ?? '') === 'gizli' ? 'selected' : '' ?>>Gizli</option>
                    </select>
                </div>
                <div class="field-group">
                    <label>Sıralama</label>
                    <input type="number" name="sort_order" value="<?= (int)($item['sort_order'] ?? 0) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="field-group">
                    <label>Kapak Görseli</label>
                    <input type="file" name="kapak_gorsel" accept="image/*" data-preview="#cover-preview">
                    <?php if (!empty($item['kapak_gorsel'])): ?>
                        <img src="<?= ha(uploadUrl($item['kapak_gorsel'])) ?>" id="cover-preview" style="max-width:240px;margin-top:10px;border-radius:6px">
                    <?php else: ?>
                        <img id="cover-preview" style="display:none;max-width:240px;margin-top:10px;border-radius:6px">
                    <?php endif; ?>
                </div>
            </div>
            <hr class="divider">
            <div class="form-row cols-2">
                <div class="field-group"><label>Meta Başlık</label><input type="text" name="meta_title" value="<?= h($item['meta_title'] ?? '') ?>"></div>
                <div class="field-group"><label>Meta Açıklama</label><input type="text" name="meta_description" value="<?= h($item['meta_description'] ?? '') ?>"></div>
            </div>
            <hr class="divider">
            <div class="flex gap" style="align-items:center">
                <label class="toggle"><input type="checkbox" name="is_active" <?= !empty($item['is_active']) ? 'checked' : '' ?>><span></span></label>
                <span>Yayında</span>
            </div>
        </div>
    </form>
<?php else: ?>
    <div class="card">
        <div class="card-head">
            <h2>Sayfalar · <span class="muted" style="font-size:14px">Toplam <?= count($sayfalar) ?></span></h2>
            <div class="actions"><a href="?action=new" class="btn"><?= icon('plus', 14) ?> Yeni Sayfa</a></div>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Başlık</th><th>Slug</th><th>Şablon</th><th>Menü</th><th class="center">Durum</th><th class="right">İşlem</th></tr></thead>
                <tbody>
                <?php foreach ($sayfalar as $s): ?>
                    <tr>
                        <td>
                            <a href="?action=edit&id=<?= (int)$s['id'] ?>" style="font-weight:600;color:var(--text);text-decoration:none"><?= h($s['baslik']) ?></a>
                            <?php if ($s['is_system']): ?> <span class="badge muted">Sistem</span><?php endif; ?>
                        </td>
                        <td><code style="font-size:12px">/<?= h($s['slug']) ?></code></td>
                        <td><span class="badge muted"><?= h($s['template']) ?></span></td>
                        <td><span class="badge muted"><?= h($s['menu_konumu']) ?></span></td>
                        <td class="center"><?= boolBadge((bool)$s['is_active']) ?></td>
                        <td class="right">
                            <div class="row-actions" style="justify-content:flex-end">
                                <a href="<?= ha('/' . $s['slug']) ?>" target="_blank" class="btn ghost btn-sm" title="Önizle"><?= icon('eye', 14) ?></a>
                                <a href="?action=edit&id=<?= (int)$s['id'] ?>" class="btn ghost btn-sm"><?= icon('edit', 14) ?></a>
                                <?php if (!$s['is_system']): ?>
                                <form method="get" style="display:inline" onsubmit="return confirm('Sayfa silinsin mi?')">
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <?= CSRF::field() ?>
                                    <button class="btn ghost btn-sm" style="color:var(--danger)"><?= icon('trash', 14) ?></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../_footer.php'; ?>
