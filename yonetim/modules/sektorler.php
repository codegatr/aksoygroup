<?php
declare(strict_types=1);
define('AG_ADMIN', true);
$adminTitle = 'Sektörler';
$adminBreadcrumb = 'İçerik';
$adminMenu = 'sektorler';

require __DIR__ . '/../_layout.php';

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

// ── DELETE ──────────────────────────────
if ($action === 'delete' && $id) {
    CSRF::require();
    $sirketCount = (int)DB::scalar("SELECT COUNT(*) FROM ag_sirketler WHERE sektor_id = ?", [$id]);
    if ($sirketCount > 0) {
        flash('error', "Bu sektöre bağlı $sirketCount şirket var. Önce şirketleri silin veya başka sektöre taşıyın.");
    } else {
        DB::exec("DELETE FROM ag_sektorler WHERE id = ?", [$id]);
        Audit::log('delete', 'sektor', $id, null, null, 'warning');
        flash('success', 'Sektör silindi.');
    }
    redirect('/yonetim/modules/sektorler.php');
}

// ── SAVE (NEW / EDIT) ───────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $data = [
        'slug'             => slugify($_POST['slug'] ?? ($_POST['ad'] ?? '')),
        'roman_no'         => trim($_POST['roman_no'] ?? ''),
        'ad'               => trim($_POST['ad'] ?? ''),
        'alt_baslik'       => trim($_POST['alt_baslik'] ?? '') ?: null,
        'kisa_aciklama'    => trim($_POST['kisa_aciklama'] ?? '') ?: null,
        'uzun_aciklama'    => trim($_POST['uzun_aciklama'] ?? '') ?: null,
        'vurgu_renk'       => trim($_POST['vurgu_renk'] ?? '#C9A961'),
        'vizyon'           => trim($_POST['vizyon'] ?? '') ?: null,
        'misyon'           => trim($_POST['misyon'] ?? '') ?: null,
        'kurulus_yili'     => $_POST['kurulus_yili'] ? (int)$_POST['kurulus_yili'] : null,
        'calisan_sayisi'   => trim($_POST['calisan_sayisi'] ?? '') ?: null,
        'meta_title'       => trim($_POST['meta_title'] ?? '') ?: null,
        'meta_description' => trim($_POST['meta_description'] ?? '') ?: null,
        'sort_order'       => (int)($_POST['sort_order'] ?? 0),
        'is_active'        => isset($_POST['is_active']) ? 1 : 0,
        'is_featured'      => isset($_POST['is_featured']) ? 1 : 0,
    ];

    // Görsel upload
    if (!empty($_FILES['kapak_gorsel']['tmp_name'])) {
        $up = uploadImage($_FILES['kapak_gorsel'], 'sektorler');
        if ($up) $data['kapak_gorsel'] = $up;
    }
    if (!empty($_POST['ikon_svg'])) {
        $data['ikon_svg'] = trim($_POST['ikon_svg']);
    }

    if (!$data['ad'] || !$data['slug']) {
        flash('error', 'Sektör adı ve slug zorunludur.');
    } else {
        try {
            if ($id > 0) {
                DB::update('ag_sektorler', $data, 'id = :pid', ['pid' => $id]);
                Audit::log('update', 'sektor', $id, null, ['ad' => $data['ad']]);
                flash('success', 'Sektör güncellendi.');
            } else {
                $id = DB::insert('ag_sektorler', $data);
                Audit::log('create', 'sektor', $id, null, ['ad' => $data['ad']]);
                flash('success', 'Sektör eklendi.');
            }
            redirect('/yonetim/modules/sektorler.php');
        } catch (Throwable $e) {
            flash('error', 'Kayıt hatası: ' . $e->getMessage());
        }
    }
}

// ── EDIT FORM ───────────────────────────
$item = null;
if ($action === 'edit' && $id) {
    $item = DB::row("SELECT * FROM ag_sektorler WHERE id = ?", [$id]);
    if (!$item) { flash('error', 'Sektör bulunamadı.'); redirect('/yonetim/modules/sektorler.php'); }
}
if ($action === 'new') {
    $item = ['vurgu_renk' => '#C9A961', 'is_active' => 1, 'sort_order' => 100];
}

// ── LIST ────────────────────────────────
if (!$item) {
    $sektorler = DB::all("SELECT s.*, (SELECT COUNT(*) FROM ag_sirketler WHERE sektor_id = s.id) AS sirket_sayisi
                         FROM ag_sektorler s ORDER BY s.sort_order ASC, s.id ASC");
}
?>

<?php if ($item): ?>
    <!-- ═══════════ FORM ═══════════ -->
    <a href="/yonetim/modules/sektorler.php" class="btn ghost btn-sm" style="margin-bottom:16px"><?= icon('arrow-left', 14) ?> Tüm Sektörler</a>

    <form method="post" enctype="multipart/form-data" class="card">
        <?= CSRF::field() ?>
        <div class="card-head">
            <h2><?= $id ? 'Sektör Düzenle' : 'Yeni Sektör' ?></h2>
            <div class="actions">
                <a href="/yonetim/modules/sektorler.php" class="btn outline btn-sm">İptal</a>
                <button type="submit" class="btn btn-sm"><?= icon('check', 14) ?> Kaydet</button>
            </div>
        </div>

        <div class="card-body">
            <div class="form-row cols-3">
                <div class="field-group">
                    <label>Roma Rakamı</label>
                    <input type="text" name="roman_no" value="<?= h($item['roman_no'] ?? '') ?>" placeholder="I, II, III..." maxlength="10">
                </div>
                <div class="field-group" style="grid-column:span 2">
                    <label>Sektör Adı <span class="req">*</span></label>
                    <input type="text" name="ad" required value="<?= h($item['ad'] ?? '') ?>" data-auto-slug="[name=slug]">
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="field-group">
                    <label>Slug (URL) <span class="req">*</span></label>
                    <input type="text" name="slug" required value="<?= h($item['slug'] ?? '') ?>" data-touch-on-input>
                    <span class="help">URL'de görünecek. Örn: <code>demir-celik</code></span>
                </div>
                <div class="field-group">
                    <label>Vurgu Rengi</label>
                    <input type="color" name="vurgu_renk" value="<?= h($item['vurgu_renk'] ?? '#C9A961') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="field-group">
                    <label>Alt Başlık</label>
                    <input type="text" name="alt_baslik" value="<?= h($item['alt_baslik'] ?? '') ?>" placeholder="Endüstriyel Üretimin Temeli">
                </div>
            </div>

            <div class="form-row">
                <div class="field-group">
                    <label>Kısa Açıklama</label>
                    <textarea name="kisa_aciklama" rows="2"><?= h($item['kisa_aciklama'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="field-group">
                    <label>Uzun Açıklama (HTML kullanılabilir)</label>
                    <textarea name="uzun_aciklama" rows="6"><?= h($item['uzun_aciklama'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="field-group">
                    <label>Vizyon</label>
                    <textarea name="vizyon" rows="3"><?= h($item['vizyon'] ?? '') ?></textarea>
                </div>
                <div class="field-group">
                    <label>Misyon</label>
                    <textarea name="misyon" rows="3"><?= h($item['misyon'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-row cols-3">
                <div class="field-group">
                    <label>Kuruluş Yılı</label>
                    <input type="number" name="kurulus_yili" min="1900" max="2100" value="<?= h((string)($item['kurulus_yili'] ?? '')) ?>">
                </div>
                <div class="field-group">
                    <label>Çalışan Sayısı</label>
                    <input type="text" name="calisan_sayisi" value="<?= h($item['calisan_sayisi'] ?? '') ?>" placeholder="50+">
                </div>
                <div class="field-group">
                    <label>Sıralama</label>
                    <input type="number" name="sort_order" value="<?= (int)($item['sort_order'] ?? 100) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="field-group">
                    <label>Kapak Görseli</label>
                    <input type="file" name="kapak_gorsel" accept="image/*" data-preview="#cover-preview">
                    <?php if (!empty($item['kapak_gorsel'])): ?>
                        <img src="<?= ha(uploadUrl($item['kapak_gorsel'])) ?>" id="cover-preview" style="max-width:200px;margin-top:10px;border-radius:6px">
                    <?php else: ?>
                        <img src="" id="cover-preview" style="max-width:200px;margin-top:10px;border-radius:6px;display:none">
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="field-group">
                    <label>İkon SVG (inline kod)</label>
                    <textarea name="ikon_svg" rows="3" style="font-family:ui-monospace,monospace;font-size:12px"><?= h($item['ikon_svg'] ?? '') ?></textarea>
                    <span class="help">İsteğe bağlı. Frontend'de sektör ikonu olarak kullanılır.</span>
                </div>
            </div>

            <hr class="divider">
            <div class="serif" style="font-size:14px;color:var(--text-mute);margin-bottom:12px">SEO Ayarları</div>

            <div class="form-row">
                <div class="field-group">
                    <label>Meta Başlık</label>
                    <input type="text" name="meta_title" value="<?= h($item['meta_title'] ?? '') ?>" maxlength="255">
                </div>
            </div>
            <div class="form-row">
                <div class="field-group">
                    <label>Meta Açıklama</label>
                    <textarea name="meta_description" rows="2" maxlength="500"><?= h($item['meta_description'] ?? '') ?></textarea>
                </div>
            </div>

            <hr class="divider">
            <div class="flex gap" style="align-items:center">
                <label class="toggle">
                    <input type="checkbox" name="is_active" <?= !empty($item['is_active']) ? 'checked' : '' ?>>
                    <span></span>
                </label>
                <span>Aktif</span>

                <label class="toggle" style="margin-left:24px">
                    <input type="checkbox" name="is_featured" <?= !empty($item['is_featured']) ? 'checked' : '' ?>>
                    <span></span>
                </label>
                <span>Öne Çıkan (Ana sayfada vitrinde)</span>
            </div>
        </div>
    </form>

<?php else: ?>
    <!-- ═══════════ LIST ═══════════ -->
    <div class="card">
        <div class="card-head">
            <h2>Sektörler · <span class="muted" style="font-size:14px">Toplam <?= count($sektorler) ?></span></h2>
            <div class="actions">
                <a href="?action=new" class="btn"><?= icon('plus', 14) ?> Yeni Sektör</a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data">
                <thead>
                <tr>
                    <th style="width:60px">№</th>
                    <th>Sektör</th>
                    <th>Renk</th>
                    <th class="right">Şirket</th>
                    <th class="center">Durum</th>
                    <th class="right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($sektorler as $s): ?>
                    <tr>
                        <td><span class="serif" style="font-size:18px;color:var(--gold-dark)"><?= h($s['roman_no']) ?></span></td>
                        <td>
                            <a href="?action=edit&id=<?= (int)$s['id'] ?>" style="font-weight:600;color:var(--text);text-decoration:none">
                                <?= h($s['ad']) ?>
                            </a>
                            <?php if ($s['alt_baslik']): ?>
                                <div class="muted" style="font-size:12px"><?= h($s['alt_baslik']) ?></div>
                            <?php endif; ?>
                            <div class="muted" style="font-size:11px;margin-top:2px">/sektor/<?= h($s['slug']) ?></div>
                        </td>
                        <td><span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:<?= ha($s['vurgu_renk']) ?>;vertical-align:middle"></span></td>
                        <td class="right num"><?= (int)$s['sirket_sayisi'] ?></td>
                        <td class="center">
                            <?= boolBadge((bool)$s['is_active']) ?>
                            <?php if ($s['is_featured']): ?> <span class="badge gold">Öne Çıkan</span><?php endif; ?>
                        </td>
                        <td class="right">
                            <div class="row-actions" style="justify-content:flex-end">
                                <a href="?action=edit&id=<?= (int)$s['id'] ?>" class="btn ghost btn-sm" title="Düzenle"><?= icon('edit', 14) ?></a>
                                <form method="get" action="?action=delete" onsubmit="return confirm('<?= h($s['ad']) ?> silinsin mi?')">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <button type="submit" class="btn ghost btn-sm" style="color:var(--danger)" title="Sil"><?= icon('trash', 14) ?></button>
                                </form>
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
