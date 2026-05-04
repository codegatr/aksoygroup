<?php
declare(strict_types=1);
define('AG_ADMIN', true);
$adminTitle = 'Haberler';
$adminBreadcrumb = 'İçerik';
$adminMenu = 'haberler';
require __DIR__ . '/../_layout.php';

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

if ($action === 'delete' && $id) {
    CSRF::require();
    DB::exec("DELETE FROM ag_haberler WHERE id = ?", [$id]);
    Audit::log('delete', 'haber', $id, null, null, 'warning');
    flash('success', 'Haber silindi.');
    redirect('/yonetim/modules/haberler.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $data = [
        'kategori_id' => (int)($_POST['kategori_id'] ?? 0) ?: null,
        'sirket_id'   => (int)($_POST['sirket_id'] ?? 0) ?: null,
        'slug'        => slugify($_POST['slug'] ?? ($_POST['baslik'] ?? '')),
        'baslik'      => trim($_POST['baslik'] ?? ''),
        'ozet'        => trim($_POST['ozet'] ?? '') ?: null,
        'icerik'      => trim($_POST['icerik'] ?? '') ?: null,
        'yazar'       => trim($_POST['yazar'] ?? 'AKSOY GROUP'),
        'etiketler'   => trim($_POST['etiketler'] ?? '') ?: null,
        'meta_title'  => trim($_POST['meta_title'] ?? '') ?: null,
        'meta_description' => trim($_POST['meta_description'] ?? '') ?: null,
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        'yayim_tarihi'=> $_POST['yayim_tarihi'] ?: date('Y-m-d H:i:s'),
    ];
    if (!empty($_FILES['kapak_gorsel']['tmp_name'])) {
        $up = uploadImage($_FILES['kapak_gorsel'], 'haberler');
        if ($up) $data['kapak_gorsel'] = $up;
    }
    if (!$data['baslik'] || !$data['slug']) {
        flash('error', 'Başlık ve slug zorunludur.');
    } else {
        try {
            if ($id > 0) {
                DB::update('ag_haberler', $data, 'id = :pid', ['pid' => $id]);
                Audit::log('update', 'haber', $id);
                flash('success', 'Haber güncellendi.');
            } else {
                $id = DB::insert('ag_haberler', $data);
                Audit::log('create', 'haber', $id);
                flash('success', 'Haber eklendi.');
            }
            redirect('/yonetim/modules/haberler.php');
        } catch (Throwable $e) {
            flash('error', 'Hata: ' . $e->getMessage());
        }
    }
}

$item = null;
if ($action === 'edit' && $id) {
    $item = DB::row("SELECT * FROM ag_haberler WHERE id = ?", [$id]);
    if (!$item) { flash('error', 'Haber bulunamadı.'); redirect('/yonetim/modules/haberler.php'); }
}
if ($action === 'new') {
    $item = ['is_active' => 1, 'yayim_tarihi' => date('Y-m-d H:i'), 'yazar' => 'AKSOY GROUP'];
}

$kategoriler = DB::all("SELECT id, ad FROM ag_haber_kategori WHERE is_active = 1 ORDER BY sort_order");
$sirketler = DB::all("SELECT id, kisa_unvan, unvan FROM ag_sirketler ORDER BY sort_order");

if (!$item) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 20;
    $total = (int)DB::scalar("SELECT COUNT(*) FROM ag_haberler");
    $p = paginate($total, $page, $perPage);
    $haberler = DB::all("SELECT h.*, k.ad AS kategori_ad, k.renk AS kategori_renk, s.kisa_unvan AS sirket_ad
                        FROM ag_haberler h
                        LEFT JOIN ag_haber_kategori k ON k.id = h.kategori_id
                        LEFT JOIN ag_sirketler s ON s.id = h.sirket_id
                        ORDER BY h.yayim_tarihi DESC LIMIT $perPage OFFSET {$p['offset']}");
}
?>

<?php if ($item): ?>
    <a href="/yonetim/modules/haberler.php" class="btn ghost btn-sm" style="margin-bottom:16px"><?= icon('arrow-left', 14) ?> Tüm Haberler</a>

    <form method="post" enctype="multipart/form-data" class="card">
        <?= CSRF::field() ?>
        <div class="card-head">
            <h2><?= $id ? 'Haber Düzenle' : 'Yeni Haber' ?></h2>
            <div class="actions">
                <a href="/yonetim/modules/haberler.php" class="btn outline btn-sm">İptal</a>
                <button type="submit" class="btn btn-sm"><?= icon('check', 14) ?> Kaydet</button>
            </div>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="field-group">
                    <label>Başlık <span class="req">*</span></label>
                    <input type="text" name="baslik" required value="<?= h($item['baslik'] ?? '') ?>" data-auto-slug="[name=slug]">
                </div>
            </div>
            <div class="form-row cols-3">
                <div class="field-group">
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?= h($item['slug'] ?? '') ?>" data-touch-on-input>
                </div>
                <div class="field-group">
                    <label>Kategori</label>
                    <select name="kategori_id">
                        <option value="">— Kategori yok —</option>
                        <?php foreach ($kategoriler as $k): ?>
                            <option value="<?= (int)$k['id'] ?>" <?= ($item['kategori_id'] ?? 0) == $k['id'] ? 'selected' : '' ?>><?= h($k['ad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label>İlgili Şirket</label>
                    <select name="sirket_id">
                        <option value="">— Yok —</option>
                        <?php foreach ($sirketler as $s): ?>
                            <option value="<?= (int)$s['id'] ?>" <?= ($item['sirket_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>><?= h($s['kisa_unvan'] ?: $s['unvan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="field-group">
                    <label>Özet (160 karakter)</label>
                    <textarea name="ozet" rows="2" maxlength="500"><?= h($item['ozet'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="form-row">
                <div class="field-group">
                    <label>İçerik (HTML)</label>
                    <textarea name="icerik" rows="14" style="font-family:ui-monospace,monospace;font-size:13px"><?= h($item['icerik'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="form-row cols-3">
                <div class="field-group">
                    <label>Yazar</label>
                    <input type="text" name="yazar" value="<?= h($item['yazar'] ?? 'AKSOY GROUP') ?>">
                </div>
                <div class="field-group">
                    <label>Yayım Tarihi</label>
                    <input type="datetime-local" name="yayim_tarihi" value="<?= h(date('Y-m-d\TH:i', strtotime($item['yayim_tarihi'] ?? 'now'))) ?>">
                </div>
                <div class="field-group">
                    <label>Etiketler (virgülle)</label>
                    <input type="text" name="etiketler" value="<?= h($item['etiketler'] ?? '') ?>" placeholder="ihracat, yatırım, tekcan">
                </div>
            </div>
            <div class="form-row">
                <div class="field-group">
                    <label>Kapak Görseli</label>
                    <input type="file" name="kapak_gorsel" accept="image/*" data-preview="#cover-preview">
                    <?php if (!empty($item['kapak_gorsel'])): ?>
                        <img src="<?= ha(uploadUrl($item['kapak_gorsel'])) ?>" id="cover-preview" style="max-width:300px;margin-top:10px;border-radius:6px">
                    <?php else: ?>
                        <img src="" id="cover-preview" style="max-width:300px;margin-top:10px;display:none">
                    <?php endif; ?>
                </div>
            </div>

            <hr class="divider">
            <div class="serif" style="font-size:14px;color:var(--text-mute);margin-bottom:12px">SEO</div>
            <div class="form-row cols-2">
                <div class="field-group"><label>Meta Başlık</label><input type="text" name="meta_title" value="<?= h($item['meta_title'] ?? '') ?>"></div>
                <div class="field-group"><label>Meta Açıklama</label><input type="text" name="meta_description" value="<?= h($item['meta_description'] ?? '') ?>"></div>
            </div>

            <hr class="divider">
            <div class="flex gap" style="align-items:center">
                <label class="toggle"><input type="checkbox" name="is_active" <?= !empty($item['is_active']) ? 'checked' : '' ?>><span></span></label>
                <span>Yayında</span>
                <label class="toggle" style="margin-left:24px"><input type="checkbox" name="is_featured" <?= !empty($item['is_featured']) ? 'checked' : '' ?>><span></span></label>
                <span>Öne Çıkan (Manşet)</span>
            </div>
        </div>
    </form>
<?php else: ?>
    <div class="card">
        <div class="card-head">
            <h2>Haberler · <span class="muted" style="font-size:14px">Toplam <?= formatNumber($total) ?></span></h2>
            <div class="actions"><a href="?action=new" class="btn"><?= icon('plus', 14) ?> Yeni Haber</a></div>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr>
                    <th style="width:60px"></th><th>Başlık</th><th>Kategori</th><th>Yayım</th><th class="right num">Görüntülenme</th><th class="center">Durum</th><th class="right">İşlem</th>
                </tr></thead>
                <tbody>
                <?php foreach ($haberler as $h): ?>
                    <tr>
                        <td>
                            <?php if ($h['kapak_gorsel']): ?>
                                <img src="<?= ha(uploadUrl($h['kapak_gorsel'])) ?>" class="img-thumb">
                            <?php else: ?>
                                <div class="img-thumb" style="background:var(--bg-soft);display:flex;align-items:center;justify-content:center;color:var(--text-mute)"><?= icon('image', 18) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?action=edit&id=<?= (int)$h['id'] ?>" style="font-weight:600;color:var(--text);text-decoration:none"><?= h($h['baslik']) ?></a>
                            <?php if ($h['is_featured']): ?> <span class="badge gold">Manşet</span><?php endif; ?>
                            <?php if ($h['ozet']): ?><div class="muted" style="font-size:12px"><?= h(truncate($h['ozet'], 80)) ?></div><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($h['kategori_ad']): ?>
                                <span class="badge" style="background:<?= ha($h['kategori_renk']) ?>20;color:<?= ha($h['kategori_renk']) ?>"><?= h($h['kategori_ad']) ?></span>
                            <?php else: ?><span class="muted">—</span><?php endif; ?>
                        </td>
                        <td class="muted nowrap"><?= h(formatDate($h['yayim_tarihi'], 'd M Y')) ?></td>
                        <td class="right num"><?= formatNumber((int)$h['goruntulenme']) ?></td>
                        <td class="center"><?= boolBadge((bool)$h['is_active'], 'Yayında', 'Taslak') ?></td>
                        <td class="right">
                            <div class="row-actions" style="justify-content:flex-end">
                                <a href="?action=edit&id=<?= (int)$h['id'] ?>" class="btn ghost btn-sm"><?= icon('edit', 14) ?></a>
                                <form method="get" style="display:inline" onsubmit="return confirm('Bu haber silinsin mi?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                                    <?= CSRF::field() ?>
                                    <button class="btn ghost btn-sm" style="color:var(--danger)"><?= icon('trash', 14) ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$haberler): ?>
                    <tr><td colspan="7"><div class="empty"><div class="serif">Henüz haber yok</div><p>Yukarıdaki <b>Yeni Haber</b> butonuyla başlayabilirsiniz.</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= paginatorHtml($p ?? [], '/yonetim/modules/haberler.php') ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../_footer.php'; ?>
