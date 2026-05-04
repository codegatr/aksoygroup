<?php
declare(strict_types=1);
define('AG_ADMIN', true);
$adminTitle = 'Şirketler';
$adminBreadcrumb = 'İçerik';
$adminMenu = 'sirketler';
require __DIR__ . '/../_layout.php';

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

if ($action === 'delete' && $id) {
    CSRF::require();
    DB::exec("DELETE FROM ag_sirketler WHERE id = ?", [$id]);
    Audit::log('delete', 'sirket', $id, null, null, 'warning');
    flash('success', 'Şirket silindi.');
    redirect('/yonetim/modules/sirketler.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $data = [
        'sektor_id'      => (int)($_POST['sektor_id'] ?? 0),
        'slug'           => slugify($_POST['slug'] ?? ($_POST['unvan'] ?? '')),
        'unvan'          => trim($_POST['unvan'] ?? ''),
        'kisa_unvan'     => trim($_POST['kisa_unvan'] ?? '') ?: null,
        'slogan'         => trim($_POST['slogan'] ?? '') ?: null,
        'aciklama'       => trim($_POST['aciklama'] ?? '') ?: null,
        'kurulus_yili'   => $_POST['kurulus_yili'] ? (int)$_POST['kurulus_yili'] : null,
        'merkez_sehir'   => trim($_POST['merkez_sehir'] ?? 'Konya'),
        'merkez_adres'   => trim($_POST['merkez_adres'] ?? '') ?: null,
        'telefon'        => trim($_POST['telefon'] ?? '') ?: null,
        'email'          => trim($_POST['email'] ?? '') ?: null,
        'web_url'        => trim($_POST['web_url'] ?? '') ?: null,
        'linkedin_url'   => trim($_POST['linkedin_url'] ?? '') ?: null,
        'instagram_url'  => trim($_POST['instagram_url'] ?? '') ?: null,
        'facebook_url'   => trim($_POST['facebook_url'] ?? '') ?: null,
        'twitter_url'    => trim($_POST['twitter_url'] ?? '') ?: null,
        'youtube_url'    => trim($_POST['youtube_url'] ?? '') ?: null,
        'vergi_dairesi'  => trim($_POST['vergi_dairesi'] ?? '') ?: null,
        'vergi_no'       => trim($_POST['vergi_no'] ?? '') ?: null,
        'mersis_no'      => trim($_POST['mersis_no'] ?? '') ?: null,
        'faaliyet_alani' => trim($_POST['faaliyet_alani'] ?? '') ?: null,
        'meta_title'     => trim($_POST['meta_title'] ?? '') ?: null,
        'meta_description' => trim($_POST['meta_description'] ?? '') ?: null,
        'sort_order'     => (int)($_POST['sort_order'] ?? 0),
        'durum'          => in_array($_POST['durum'] ?? '', ['aktif','pasif','gelistiriliyor'], true) ? $_POST['durum'] : 'aktif',
        'is_featured'    => isset($_POST['is_featured']) ? 1 : 0,
    ];

    if (!empty($_FILES['logo']['tmp_name'])) {
        $up = uploadImage($_FILES['logo'], 'sirketler');
        if ($up) $data['logo'] = $up;
    }
    if (!empty($_FILES['kapak_gorsel']['tmp_name'])) {
        $up = uploadImage($_FILES['kapak_gorsel'], 'sirketler');
        if ($up) $data['kapak_gorsel'] = $up;
    }

    if (!$data['unvan'] || !$data['slug'] || !$data['sektor_id']) {
        flash('error', 'Ünvan, slug ve sektör zorunludur.');
    } else {
        try {
            if ($id > 0) {
                DB::update('ag_sirketler', $data, 'id = :pid', ['pid' => $id]);
                Audit::log('update', 'sirket', $id, null, ['unvan' => $data['unvan']]);
                flash('success', 'Şirket güncellendi.');
            } else {
                $id = DB::insert('ag_sirketler', $data);
                Audit::log('create', 'sirket', $id, null, ['unvan' => $data['unvan']]);
                flash('success', 'Şirket eklendi.');
            }
            redirect('/yonetim/modules/sirketler.php');
        } catch (Throwable $e) {
            flash('error', 'Hata: ' . $e->getMessage());
        }
    }
}

$item = null;
if ($action === 'edit' && $id) {
    $item = DB::row("SELECT * FROM ag_sirketler WHERE id = ?", [$id]);
    if (!$item) { flash('error', 'Şirket bulunamadı.'); redirect('/yonetim/modules/sirketler.php'); }
}
if ($action === 'new') {
    $item = ['durum' => 'aktif', 'merkez_sehir' => 'Konya', 'sort_order' => 100];
}

if (!$item) {
    $sirketler = DB::all("SELECT s.*, sk.ad AS sektor_ad, sk.roman_no FROM ag_sirketler s
                         LEFT JOIN ag_sektorler sk ON sk.id = s.sektor_id
                         ORDER BY sk.sort_order, s.sort_order, s.id");
}
$sektorOptions = DB::all("SELECT id, ad, roman_no FROM ag_sektorler WHERE is_active = 1 ORDER BY sort_order");
?>

<?php if ($item): ?>
    <a href="/yonetim/modules/sirketler.php" class="btn ghost btn-sm" style="margin-bottom:16px"><?= icon('arrow-left', 14) ?> Tüm Şirketler</a>

    <form method="post" enctype="multipart/form-data" class="card">
        <?= CSRF::field() ?>
        <div class="card-head">
            <h2><?= $id ? 'Şirket Düzenle' : 'Yeni Şirket' ?></h2>
            <div class="actions">
                <a href="/yonetim/modules/sirketler.php" class="btn outline btn-sm">İptal</a>
                <button type="submit" class="btn btn-sm"><?= icon('check', 14) ?> Kaydet</button>
            </div>
        </div>

        <div class="card-body">
            <div class="form-row cols-2">
                <div class="field-group">
                    <label>Sektör <span class="req">*</span></label>
                    <select name="sektor_id" required>
                        <option value="">Seçiniz</option>
                        <?php foreach ($sektorOptions as $s): ?>
                            <option value="<?= (int)$s['id'] ?>" <?= ($item['sektor_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>>
                                <?= h($s['roman_no']) ?> · <?= h($s['ad']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label>Durum</label>
                    <select name="durum">
                        <option value="aktif" <?= ($item['durum'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="pasif" <?= ($item['durum'] ?? '') === 'pasif' ? 'selected' : '' ?>>Pasif</option>
                        <option value="gelistiriliyor" <?= ($item['durum'] ?? '') === 'gelistiriliyor' ? 'selected' : '' ?>>Geliştiriliyor</option>
                    </select>
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="field-group">
                    <label>Tam Ünvan <span class="req">*</span></label>
                    <input type="text" name="unvan" required value="<?= h($item['unvan'] ?? '') ?>" data-auto-slug="[name=slug]" placeholder="Tekcan Metal Sanayi ve Ticaret A.Ş.">
                </div>
                <div class="field-group">
                    <label>Kısa Ünvan</label>
                    <input type="text" name="kisa_unvan" value="<?= h($item['kisa_unvan'] ?? '') ?>" placeholder="Tekcan Metal">
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="field-group">
                    <label>Slug (URL)</label>
                    <input type="text" name="slug" value="<?= h($item['slug'] ?? '') ?>" data-touch-on-input>
                </div>
                <div class="field-group">
                    <label>Slogan</label>
                    <input type="text" name="slogan" value="<?= h($item['slogan'] ?? '') ?>" placeholder="Çelikten ötesi">
                </div>
            </div>

            <div class="form-row">
                <div class="field-group">
                    <label>Açıklama (HTML kullanılabilir)</label>
                    <textarea name="aciklama" rows="6"><?= h($item['aciklama'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="field-group">
                    <label>Logo</label>
                    <input type="file" name="logo" accept="image/*" data-preview="#logo-preview">
                    <?php if (!empty($item['logo'])): ?>
                        <img src="<?= ha(uploadUrl($item['logo'])) ?>" id="logo-preview" style="max-width:160px;margin-top:10px;background:white;padding:10px;border-radius:6px;border:1px solid var(--border)">
                    <?php else: ?>
                        <img src="" id="logo-preview" style="max-width:160px;margin-top:10px;display:none">
                    <?php endif; ?>
                </div>
                <div class="field-group">
                    <label>Kapak Görseli</label>
                    <input type="file" name="kapak_gorsel" accept="image/*" data-preview="#cover-preview">
                    <?php if (!empty($item['kapak_gorsel'])): ?>
                        <img src="<?= ha(uploadUrl($item['kapak_gorsel'])) ?>" id="cover-preview" style="max-width:200px;margin-top:10px;border-radius:6px">
                    <?php else: ?>
                        <img src="" id="cover-preview" style="max-width:200px;margin-top:10px;display:none">
                    <?php endif; ?>
                </div>
            </div>

            <hr class="divider">
            <div class="serif" style="font-size:14px;color:var(--text-mute);margin-bottom:12px">İletişim Bilgileri</div>

            <div class="form-row cols-3">
                <div class="field-group">
                    <label>Kuruluş Yılı</label>
                    <input type="number" name="kurulus_yili" value="<?= h((string)($item['kurulus_yili'] ?? '')) ?>">
                </div>
                <div class="field-group">
                    <label>Merkez Şehir</label>
                    <input type="text" name="merkez_sehir" value="<?= h($item['merkez_sehir'] ?? 'Konya') ?>">
                </div>
                <div class="field-group">
                    <label>Telefon</label>
                    <input type="text" name="telefon" value="<?= h($item['telefon'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="field-group">
                    <label>E-posta</label>
                    <input type="email" name="email" value="<?= h($item['email'] ?? '') ?>">
                </div>
                <div class="field-group">
                    <label>Web Sitesi</label>
                    <input type="url" name="web_url" value="<?= h($item['web_url'] ?? '') ?>" placeholder="https://...">
                </div>
            </div>

            <div class="form-row">
                <div class="field-group">
                    <label>Merkez Adres</label>
                    <textarea name="merkez_adres" rows="2"><?= h($item['merkez_adres'] ?? '') ?></textarea>
                </div>
            </div>

            <hr class="divider">
            <div class="serif" style="font-size:14px;color:var(--text-mute);margin-bottom:12px">Sosyal Medya</div>

            <div class="form-row cols-2">
                <div class="field-group"><label>LinkedIn</label><input type="url" name="linkedin_url" value="<?= h($item['linkedin_url'] ?? '') ?>"></div>
                <div class="field-group"><label>Instagram</label><input type="url" name="instagram_url" value="<?= h($item['instagram_url'] ?? '') ?>"></div>
            </div>
            <div class="form-row cols-3">
                <div class="field-group"><label>Facebook</label><input type="url" name="facebook_url" value="<?= h($item['facebook_url'] ?? '') ?>"></div>
                <div class="field-group"><label>Twitter / X</label><input type="url" name="twitter_url" value="<?= h($item['twitter_url'] ?? '') ?>"></div>
                <div class="field-group"><label>YouTube</label><input type="url" name="youtube_url" value="<?= h($item['youtube_url'] ?? '') ?>"></div>
            </div>

            <hr class="divider">
            <div class="serif" style="font-size:14px;color:var(--text-mute);margin-bottom:12px">Yasal Bilgiler</div>

            <div class="form-row cols-3">
                <div class="field-group"><label>Vergi Dairesi</label><input type="text" name="vergi_dairesi" value="<?= h($item['vergi_dairesi'] ?? '') ?>"></div>
                <div class="field-group"><label>Vergi No</label><input type="text" name="vergi_no" value="<?= h($item['vergi_no'] ?? '') ?>"></div>
                <div class="field-group"><label>MERSİS No</label><input type="text" name="mersis_no" value="<?= h($item['mersis_no'] ?? '') ?>"></div>
            </div>

            <div class="form-row">
                <div class="field-group">
                    <label>Faaliyet Alanı</label>
                    <textarea name="faaliyet_alani" rows="2"><?= h($item['faaliyet_alani'] ?? '') ?></textarea>
                </div>
            </div>

            <hr class="divider">
            <div class="form-row cols-2">
                <div class="field-group">
                    <label>Sıralama</label>
                    <input type="number" name="sort_order" value="<?= (int)($item['sort_order'] ?? 100) ?>">
                </div>
                <div class="field-group" style="display:flex;align-items:center;gap:12px;padding-top:24px">
                    <label class="toggle">
                        <input type="checkbox" name="is_featured" <?= !empty($item['is_featured']) ? 'checked' : '' ?>>
                        <span></span>
                    </label>
                    <span>Öne Çıkan</span>
                </div>
            </div>
        </div>
    </form>

<?php else: ?>
    <div class="card">
        <div class="card-head">
            <h2>Şirketler · <span class="muted" style="font-size:14px">Toplam <?= count($sirketler) ?></span></h2>
            <div class="actions"><a href="?action=new" class="btn"><?= icon('plus', 14) ?> Yeni Şirket</a></div>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr>
                    <th>Şirket</th><th>Sektör</th><th>Şehir</th><th>Web</th><th class="center">Durum</th><th class="right">İşlem</th>
                </tr></thead>
                <tbody>
                <?php foreach ($sirketler as $s): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px">
                                <?php if ($s['logo']): ?>
                                    <img src="<?= ha(uploadUrl($s['logo'])) ?>" class="img-thumb" style="background:white">
                                <?php else: ?>
                                    <div class="img-thumb" style="background:var(--navy);color:var(--gold);display:flex;align-items:center;justify-content:center;font-family:Fraunces,serif"><?= h(mb_substr($s['unvan'], 0, 1)) ?></div>
                                <?php endif; ?>
                                <div>
                                    <a href="?action=edit&id=<?= (int)$s['id'] ?>" style="font-weight:600;color:var(--text);text-decoration:none">
                                        <?= h($s['kisa_unvan'] ?: $s['unvan']) ?>
                                    </a>
                                    <?php if ($s['slogan']): ?><div class="muted" style="font-size:12px"><?= h($s['slogan']) ?></div><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge muted"><?= h($s['roman_no'] ?? '') ?></span> <?= h($s['sektor_ad'] ?? '—') ?></td>
                        <td><?= h($s['merkez_sehir'] ?? '—') ?></td>
                        <td>
                            <?php if ($s['web_url']): ?>
                                <a href="<?= ha($s['web_url']) ?>" target="_blank" class="muted" style="font-size:12px">
                                    <?= h(parse_url($s['web_url'], PHP_URL_HOST) ?: '') ?> ↗
                                </a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="center"><?= durumBadge($s['durum']) ?></td>
                        <td class="right">
                            <div class="row-actions" style="justify-content:flex-end">
                                <a href="?action=edit&id=<?= (int)$s['id'] ?>" class="btn ghost btn-sm"><?= icon('edit', 14) ?></a>
                                <form method="get" style="display:inline" onsubmit="return confirm('<?= h($s['unvan']) ?> silinsin mi?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <?= CSRF::field() ?>
                                    <button class="btn ghost btn-sm" style="color:var(--danger)"><?= icon('trash', 14) ?></button>
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
