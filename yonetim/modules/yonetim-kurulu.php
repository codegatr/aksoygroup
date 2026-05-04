<?php
declare(strict_types=1);
define('AG_ADMIN', true);
$adminTitle = 'Yönetim Kurulu';
$adminBreadcrumb = 'İçerik';
$adminMenu = 'yonetim-kurulu';
require __DIR__ . '/../_layout.php';

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

// ── DELETE ──────────────────────────────
if ($action === 'delete' && $id) {
    CSRF::require();
    $row = DB::row("SELECT fotograf FROM ag_yonetim_kurulu WHERE id = ?", [$id]);
    DB::exec("DELETE FROM ag_yonetim_kurulu WHERE id = ?", [$id]);
    if (!empty($row['fotograf']) && file_exists(AG_UPLOADS . '/' . $row['fotograf'])) {
        @unlink(AG_UPLOADS . '/' . $row['fotograf']);
    }
    Audit::log('delete', 'yonetim_kurulu', $id, null, null, 'warning');
    flash('success', 'Üye silindi.');
    redirect('/yonetim/modules/yonetim-kurulu.php');
}

// ── SAVE (NEW / EDIT) ───────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $editingId = (int)($_POST['id'] ?? 0);
    $adSoyad = trim($_POST['ad_soyad'] ?? '');
    $unvan   = trim($_POST['unvan'] ?? '');

    if (mb_strlen($adSoyad) < 2) { flash('error', 'Ad Soyad zorunlu.'); redirect("?action=edit&id=$editingId"); }
    if (mb_strlen($unvan) < 2) { flash('error', 'Ünvan zorunlu.'); redirect("?action=edit&id=$editingId"); }

    $data = [
        'ad_soyad'         => $adSoyad,
        'unvan'            => $unvan,
        'pozisyon'         => trim($_POST['pozisyon'] ?? '') ?: null,
        'slug'             => slugify($_POST['slug'] ?? '') ?: slugify($adSoyad),
        'kisa_biyografi'   => trim($_POST['kisa_biyografi'] ?? '') ?: null,
        'uzun_biyografi'   => trim($_POST['uzun_biyografi'] ?? '') ?: null,
        'egitim'           => trim($_POST['egitim'] ?? '') ?: null,
        'linkedin_url'     => trim($_POST['linkedin_url'] ?? '') ?: null,
        'email'            => trim($_POST['email'] ?? '') ?: null,
        'sort_order'       => (int)($_POST['sort_order'] ?? 0),
        'is_active'        => isset($_POST['is_active']) ? 1 : 0,
    ];

    // Slug unique check
    $clash = DB::row("SELECT id FROM ag_yonetim_kurulu WHERE slug = ? AND id != ?", [$data['slug'], $editingId]);
    if ($clash) { flash('error', "Slug çakışması: {$data['slug']} zaten var."); redirect("?action=edit&id=$editingId"); }

    // Foto upload
    if (!empty($_FILES['fotograf']['tmp_name'])) {
        $fname = uploadImage($_FILES['fotograf'], 'yonetim');
        if ($fname) $data['fotograf'] = $fname;
    }

    try {
        if ($editingId) {
            DB::update('ag_yonetim_kurulu', $data, 'id = ?', [$editingId]);
            Audit::log('update', 'yonetim_kurulu', $editingId, null, $data);
            flash('success', $adSoyad . ' güncellendi.');
        } else {
            $newId = DB::insert('ag_yonetim_kurulu', $data);
            Audit::log('create', 'yonetim_kurulu', $newId, null, $data);
            flash('success', $adSoyad . ' eklendi.');
        }
    } catch (Throwable $e) {
        flash('error', 'Kayıt hatası: ' . $e->getMessage());
        redirect("?action=edit&id=$editingId");
    }

    redirect('/yonetim/modules/yonetim-kurulu.php');
}

// ── DATA ────────────────────────────────
$current = $id ? DB::row("SELECT * FROM ag_yonetim_kurulu WHERE id = ?", [$id]) : null;
$rows = DB::all("SELECT * FROM ag_yonetim_kurulu ORDER BY sort_order ASC, id ASC");
?>

<?php if ($action === 'list'): ?>
    <!-- ════════ LİSTE ════════ -->
    <div class="card mt">
        <div class="card-head">
            <h2><?= icon('users', 18) ?> &nbsp;Yönetim Kurulu Üyeleri (<?= count($rows) ?>)</h2>
            <a href="?action=new" class="btn navy btn-sm"><?= icon('plus', 14) ?> Yeni Üye</a>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th style="width:80px">Foto</th><th>Ad Soyad / Ünvan</th><th>Pozisyon</th><th class="center">Sıra</th><th class="center">Durum</th><th class="right">İşlem</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <?php if (!empty($r['fotograf'])): ?>
                                <img src="<?= h(uploadUrl($r['fotograf'])) ?>" alt="<?= ha($r['ad_soyad']) ?>"
                                     style="width:54px;height:54px;border-radius:50%;object-fit:cover;border:1px solid var(--border)">
                            <?php else: ?>
                                <div style="width:54px;height:54px;border-radius:50%;background:var(--bg-soft);display:flex;align-items:center;justify-content:center;font-family:var(--serif);font-size:18px;color:var(--gold-dark);border:1px solid var(--border)">
                                    <?= h(strtoupper(mb_substr($r['ad_soyad'], 0, 1))) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight:500"><?= h($r['ad_soyad']) ?></div>
                            <div class="muted" style="font-size:12px;margin-top:2px"><?= h($r['unvan']) ?></div>
                        </td>
                        <td><?= h($r['pozisyon'] ?? '—') ?></td>
                        <td class="center num"><?= (int)$r['sort_order'] ?></td>
                        <td class="center"><?= (int)$r['is_active'] ? '<span class="badge success">Aktif</span>' : '<span class="badge muted">Pasif</span>' ?></td>
                        <td class="right">
                            <a href="?action=edit&id=<?= $r['id'] ?>" class="btn ghost btn-sm"><?= icon('edit', 12) ?> Düzenle</a>
                            <form method="post" action="?action=delete&id=<?= $r['id'] ?>" style="display:inline" onsubmit="return confirm('<?= ha($r['ad_soyad']) ?> silinecek. Emin misiniz?')">
                                <?= CSRF::field() ?>
                                <button class="btn ghost btn-sm" style="color:var(--danger)"><?= icon('trash', 12) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="6"><div class="empty"><div class="serif">Henüz yönetim kurulu üyesi eklenmemiş</div><p>İlk üyeyi eklemek için yukarıdaki butonu kullanın.</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($action === 'new' || $action === 'edit'): ?>
    <!-- ════════ FORM ════════ -->
    <div class="card mt">
        <div class="card-head">
            <h2><?= icon($action === 'new' ? 'plus' : 'edit', 18) ?> &nbsp;<?= $action === 'new' ? 'Yeni Üye Ekle' : 'Üye Düzenle: ' . h($current['ad_soyad'] ?? '') ?></h2>
            <a href="/yonetim/modules/yonetim-kurulu.php" class="btn ghost btn-sm">← Listeye Dön</a>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <?= CSRF::field() ?>
                <input type="hidden" name="id" value="<?= $current['id'] ?? '' ?>">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                    <div class="field-group">
                        <label>Ad Soyad *</label>
                        <input type="text" name="ad_soyad" required maxlength="150"
                               value="<?= ha($current['ad_soyad'] ?? '') ?>" placeholder="ör. Mehmet Aksoy">
                    </div>
                    <div class="field-group">
                        <label>Slug (URL) <span class="muted" style="font-weight:400">— boş bırakırsan otomatik</span></label>
                        <input type="text" name="slug" maxlength="150"
                               value="<?= ha($current['slug'] ?? '') ?>" placeholder="mehmet-aksoy">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                    <div class="field-group">
                        <label>Ünvan *</label>
                        <input type="text" name="unvan" required maxlength="150"
                               value="<?= ha($current['unvan'] ?? '') ?>" placeholder="Yönetim Kurulu Başkanı">
                    </div>
                    <div class="field-group">
                        <label>Pozisyon <span class="muted" style="font-weight:400">— opsiyonel ek başlık</span></label>
                        <input type="text" name="pozisyon" maxlength="100"
                               value="<?= ha($current['pozisyon'] ?? '') ?>" placeholder="Kurucu Ortak">
                    </div>
                </div>

                <div class="field-group">
                    <label>Kısa Biyografi <span class="muted" style="font-weight:400">— grid kartında görünür (~200 kar)</span></label>
                    <textarea name="kisa_biyografi" rows="3" maxlength="500"><?= h($current['kisa_biyografi'] ?? '') ?></textarea>
                </div>

                <div class="field-group">
                    <label>Uzun Biyografi <span class="muted" style="font-weight:400">— detay sayfasında görünür</span></label>
                    <textarea name="uzun_biyografi" rows="8"><?= h($current['uzun_biyografi'] ?? '') ?></textarea>
                </div>

                <div class="field-group">
                    <label>Eğitim</label>
                    <textarea name="egitim" rows="3" placeholder="ör. ODTÜ Endüstri Mühendisliği — 1995&#10;Harvard MBA — 2001"><?= h($current['egitim'] ?? '') ?></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                    <div class="field-group">
                        <label>LinkedIn URL</label>
                        <input type="url" name="linkedin_url" maxlength="255"
                               value="<?= ha($current['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/...">
                    </div>
                    <div class="field-group">
                        <label>E-posta</label>
                        <input type="email" name="email" maxlength="150"
                               value="<?= ha($current['email'] ?? '') ?>" placeholder="ornek@aksoy.web.tr">
                    </div>
                </div>

                <div class="field-group">
                    <label>Fotoğraf</label>
                    <?php if (!empty($current['fotograf'])): ?>
                        <div style="margin-bottom:12px">
                            <img src="<?= h(uploadUrl($current['fotograf'])) ?>" alt=""
                                 style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:1px solid var(--border)">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="fotograf" accept="image/*">
                    <span class="help">JPEG/PNG, kare format önerilir. Mevcut foto varsa yeni yüklerseniz değişir.</span>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                    <div class="field-group">
                        <label>Sıra Numarası</label>
                        <input type="number" name="sort_order" min="0" max="999"
                               value="<?= (int)($current['sort_order'] ?? 0) ?>">
                        <span class="help">Düşük sayı önce gelir. 0 = ilk sıra.</span>
                    </div>
                    <div class="field-group">
                        <label style="display:flex;align-items:center;gap:8px;margin-top:32px">
                            <input type="checkbox" name="is_active" value="1" <?= ($current['is_active'] ?? 1) ? 'checked' : '' ?>>
                            Aktif (sitede gösterilsin)
                        </label>
                    </div>
                </div>

                <div style="display:flex;gap:10px;margin-top:24px">
                    <button type="submit" class="btn navy"><?= icon('check', 14) ?> Kaydet</button>
                    <a href="/yonetim/modules/yonetim-kurulu.php" class="btn ghost">İptal</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../_footer.php'; ?>
