<?php
declare(strict_types=1);
define('AG_ADMIN', true);
$adminTitle = 'Kullanıcılar';
$adminBreadcrumb = 'Sistem';
$adminMenu = 'kullanicilar';
require __DIR__ . '/../_layout.php';

Auth::requireRole('superadmin', 'admin');

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);
$me = Auth::userId();

if ($action === 'delete' && $id) {
    CSRF::require();
    if ($id === $me) {
        flash('error', 'Kendi hesabınızı silemezsiniz.');
    } else {
        $target = DB::row("SELECT role FROM ag_users WHERE id = ?", [$id]);
        if ($target && $target['role'] === 'superadmin' && !Auth::isSuperAdmin()) {
            flash('error', 'Süper yöneticiyi sadece başka bir süper yönetici silebilir.');
        } else {
            DB::exec("DELETE FROM ag_users WHERE id = ?", [$id]);
            Audit::log('delete', 'user', $id, null, null, 'danger');
            flash('success', 'Kullanıcı silindi.');
        }
    }
    redirect('/yonetim/modules/kullanicilar.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $role     = in_array($_POST['role'] ?? '', ['superadmin','admin','editor','viewer'], true) ? $_POST['role'] : 'editor';
    $password = $_POST['password'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Yetki kontrolü
    if ($role === 'superadmin' && !Auth::isSuperAdmin()) {
        flash('error', 'Süper yönetici rolü atayamazsınız.');
        redirect('/yonetim/modules/kullanicilar.php');
    }

    if (!$username || !$email || !$fullName) {
        flash('error', 'Tüm alanlar zorunludur.');
    } elseif (!isEmail($email)) {
        flash('error', 'Geçersiz e-posta.');
    } elseif (!$id && !$password) {
        flash('error', 'Yeni kullanıcı için şifre zorunludur.');
    } elseif ($password && !Auth::isStrongPassword($password)) {
        flash('error', 'Şifre en az ' . AG_PASSWORD_MIN_LENGTH . ' karakter, büyük+küçük+rakam içermeli.');
    } else {
        try {
            $data = [
                'username'  => $username,
                'email'     => $email,
                'full_name' => $fullName,
                'role'      => $role,
                'is_active' => $isActive,
            ];
            if ($password) {
                $data['password_hash'] = Auth::hashPassword($password);
                $data['password_changed_at'] = date('Y-m-d H:i:s');
            }
            if ($id > 0) {
                DB::update('ag_users', $data, 'id = :pid', ['pid' => $id]);
                Audit::log('update', 'user', $id, null, ['username' => $username, 'role' => $role], 'warning');
                flash('success', 'Kullanıcı güncellendi.');
            } else {
                $id = DB::insert('ag_users', $data);
                Audit::log('create', 'user', $id, null, ['username' => $username, 'role' => $role], 'warning');
                flash('success', 'Kullanıcı oluşturuldu.');
            }
            redirect('/yonetim/modules/kullanicilar.php');
        } catch (Throwable $e) {
            flash('error', 'Hata: ' . $e->getMessage());
        }
    }
}

$item = null;
if ($action === 'edit' && $id) {
    $item = DB::row("SELECT * FROM ag_users WHERE id = ?", [$id]);
    if (!$item) { flash('error', 'Kullanıcı bulunamadı.'); redirect('/yonetim/modules/kullanicilar.php'); }
}
if ($action === 'new') $item = ['role' => 'editor', 'is_active' => 1];

if (!$item) {
    $kullanicilar = DB::all("SELECT * FROM ag_users ORDER BY role, full_name");
}
?>

<?php if ($item): ?>
    <a href="/yonetim/modules/kullanicilar.php" class="btn ghost btn-sm" style="margin-bottom:16px"><?= icon('arrow-left', 14) ?> Geri</a>
    <form method="post" class="card">
        <?= CSRF::field() ?>
        <div class="card-head">
            <h2><?= $id ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı' ?></h2>
            <div class="actions">
                <a href="/yonetim/modules/kullanicilar.php" class="btn outline btn-sm">İptal</a>
                <button type="submit" class="btn btn-sm"><?= icon('check', 14) ?> Kaydet</button>
            </div>
        </div>
        <div class="card-body">
            <div class="form-row cols-2">
                <div class="field-group">
                    <label>Ad Soyad <span class="req">*</span></label>
                    <input type="text" name="full_name" required value="<?= h($item['full_name'] ?? '') ?>">
                </div>
                <div class="field-group">
                    <label>Rol <span class="req">*</span></label>
                    <select name="role">
                        <?php if (Auth::isSuperAdmin()): ?>
                            <option value="superadmin" <?= ($item['role'] ?? '') === 'superadmin' ? 'selected' : '' ?>>Süper Yönetici</option>
                        <?php endif; ?>
                        <option value="admin" <?= ($item['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Yönetici</option>
                        <option value="editor" <?= ($item['role'] ?? '') === 'editor' ? 'selected' : '' ?>>Editör</option>
                        <option value="viewer" <?= ($item['role'] ?? '') === 'viewer' ? 'selected' : '' ?>>Görüntüleyici</option>
                    </select>
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="field-group">
                    <label>Kullanıcı Adı <span class="req">*</span></label>
                    <input type="text" name="username" required value="<?= h($item['username'] ?? '') ?>">
                </div>
                <div class="field-group">
                    <label>E-posta <span class="req">*</span></label>
                    <input type="email" name="email" required value="<?= h($item['email'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="field-group">
                    <label>Şifre <?= $id ? '' : '<span class="req">*</span>' ?></label>
                    <input type="password" name="password" minlength="<?= AG_PASSWORD_MIN_LENGTH ?>" <?= $id ? '' : 'required' ?>>
                    <span class="help">
                        <?= $id ? 'Şifreyi değiştirmek istemiyorsanız boş bırakın.' : 'En az ' . AG_PASSWORD_MIN_LENGTH . ' karakter, büyük+küçük+rakam içermeli.' ?>
                    </span>
                </div>
            </div>
            <hr class="divider">
            <div class="flex gap" style="align-items:center">
                <label class="toggle"><input type="checkbox" name="is_active" <?= !empty($item['is_active']) ? 'checked' : '' ?>><span></span></label>
                <span>Aktif</span>
            </div>
            <?php if ($id && ($item['last_login_at'] ?? null)): ?>
                <hr class="divider">
                <div class="muted" style="font-size:12px">
                    Son giriş: <?= h(formatDate($item['last_login_at'], 'd M Y H:i')) ?> ·
                    IP: <code><?= h($item['last_login_ip'] ?? '') ?></code>
                </div>
            <?php endif; ?>
        </div>
    </form>
<?php else: ?>
    <div class="card">
        <div class="card-head">
            <h2>Kullanıcılar · <span class="muted" style="font-size:14px">Toplam <?= count($kullanicilar) ?></span></h2>
            <div class="actions"><a href="?action=new" class="btn"><?= icon('plus', 14) ?> Yeni Kullanıcı</a></div>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Kullanıcı</th><th>Rol</th><th>Son Giriş</th><th class="center">Durum</th><th class="right">İşlem</th></tr></thead>
                <tbody>
                <?php foreach ($kullanicilar as $u): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px">
                                <div class="img-thumb" style="background:var(--navy);color:var(--gold);display:flex;align-items:center;justify-content:center;font-family:Fraunces,serif"><?= h(mb_substr($u['full_name'], 0, 1)) ?></div>
                                <div>
                                    <a href="?action=edit&id=<?= (int)$u['id'] ?>" style="font-weight:600;color:var(--text);text-decoration:none">
                                        <?= h($u['full_name']) ?>
                                        <?php if ($u['id'] == $me): ?> <span class="badge gold">Sen</span><?php endif; ?>
                                    </a>
                                    <div class="muted" style="font-size:12px">@<?= h($u['username']) ?> · <?= h($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php $roleColors = ['superadmin'=>'danger','admin'=>'warning','editor'=>'gold','viewer'=>'muted']; ?>
                            <span class="badge <?= $roleColors[$u['role']] ?? 'muted' ?>"><?= h(ucfirst($u['role'])) ?></span>
                        </td>
                        <td class="muted nowrap" style="font-size:12px">
                            <?= $u['last_login_at'] ? h(timeAgo($u['last_login_at'])) : '—' ?>
                        </td>
                        <td class="center"><?= boolBadge((bool)$u['is_active']) ?></td>
                        <td class="right">
                            <div class="row-actions" style="justify-content:flex-end">
                                <a href="?action=edit&id=<?= (int)$u['id'] ?>" class="btn ghost btn-sm"><?= icon('edit', 14) ?></a>
                                <?php if ($u['id'] != $me): ?>
                                <form method="get" style="display:inline" onsubmit="return confirm('<?= h($u['username']) ?> kalıcı olarak silinsin mi?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
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
