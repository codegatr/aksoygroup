<?php
declare(strict_types=1);
define('AG_ADMIN', true);
$adminTitle = 'Site Ayarları';
$adminBreadcrumb = 'Sistem';
$adminMenu = 'ayarlar';
require __DIR__ . '/../_layout.php';

Auth::requireRole('superadmin', 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $changes = 0;
    foreach ($_POST['settings'] ?? [] as $key => $value) {
        $key = (string)$key;
        $value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
        $current = DB::scalar("SELECT setting_value FROM ag_settings WHERE setting_key = ?", [$key]);
        if ($current !== $value) {
            DB::exec("UPDATE ag_settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
            $changes++;
        }
    }
    if ($changes > 0) {
        Audit::log('update', 'settings', null, null, ['count' => $changes]);
        flash('success', "$changes ayar güncellendi.");
    } else {
        flash('info', 'Hiçbir değişiklik yok.');
    }
    redirect('/yonetim/modules/ayarlar.php' . (isset($_GET['group']) ? '?group=' . urlencode($_GET['group']) : ''));
}

$activeGroup = $_GET['group'] ?? 'general';
$groups = DB::all("SELECT setting_group, COUNT(*) AS cnt FROM ag_settings GROUP BY setting_group ORDER BY setting_group");
$settings = DB::all("SELECT * FROM ag_settings WHERE setting_group = ? ORDER BY sort_order, setting_key", [$activeGroup]);

$groupLabels = [
    'general'  => 'Genel',
    'iletisim' => 'İletişim',
    'seo'      => 'SEO',
    'mail'     => 'E-posta / SMTP',
    'sistem'   => 'Sistem',
];
?>

<div style="display:grid; grid-template-columns:240px 1fr; gap:24px" class="settings-grid">
    <!-- Grup menüsü -->
    <div class="card" style="position:sticky; top:90px; align-self:start; margin-bottom:0">
        <div class="card-body" style="padding:8px 0">
            <?php foreach ($groups as $g): ?>
                <a href="?group=<?= ha($g['setting_group']) ?>"
                   class="nav-link"
                   style="color:var(--text);<?= $activeGroup === $g['setting_group'] ? 'background:var(--bg-soft);border-left:2px solid var(--gold);font-weight:600' : '' ?>">
                    <?= h($groupLabels[$g['setting_group']] ?? ucfirst($g['setting_group'])) ?>
                    <span class="badge muted" style="margin-left:auto"><?= (int)$g['cnt'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Form -->
    <form method="post" class="card">
        <?= CSRF::field() ?>
        <div class="card-head">
            <h2><?= h($groupLabels[$activeGroup] ?? ucfirst($activeGroup)) ?> Ayarları</h2>
            <button type="submit" class="btn btn-sm"><?= icon('check', 14) ?> Kaydet</button>
        </div>
        <div class="card-body">
            <?php if (!$settings): ?>
                <div class="empty"><div class="serif">Bu grupta ayar yok</div></div>
            <?php else: ?>
                <?php foreach ($settings as $s): ?>
                    <div class="field-group" style="margin-bottom:20px">
                        <label>
                            <?= h($s['label'] ?: $s['setting_key']) ?>
                            <code style="color:var(--text-mute);font-size:11px;font-weight:400;margin-left:6px"><?= h($s['setting_key']) ?></code>
                        </label>
                        <?php
                        $name = 'settings[' . $s['setting_key'] . ']';
                        $val = $s['setting_value'] ?? '';
                        switch ($s['setting_type']) {
                            case 'textarea':
                                echo '<textarea name="' . ha($name) . '" rows="3">' . h($val) . '</textarea>';
                                break;
                            case 'html':
                                echo '<textarea name="' . ha($name) . '" rows="6" style="font-family:ui-monospace,monospace;font-size:13px">' . h($val) . '</textarea>';
                                break;
                            case 'number':
                                echo '<input type="number" name="' . ha($name) . '" value="' . h($val) . '">';
                                break;
                            case 'boolean':
                                echo '<label class="toggle"><input type="hidden" name="' . ha($name) . '" value="0">';
                                echo '<input type="checkbox" name="' . ha($name) . '" value="1" ' . ($val ? 'checked' : '') . '><span></span></label>';
                                break;
                            case 'email':
                                echo '<input type="email" name="' . ha($name) . '" value="' . h($val) . '">';
                                break;
                            case 'url':
                                echo '<input type="url" name="' . ha($name) . '" value="' . h($val) . '">';
                                break;
                            case 'color':
                                echo '<input type="color" name="' . ha($name) . '" value="' . h($val ?: '#C9A961') . '">';
                                break;
                            case 'select':
                                $opts = $s['options'] ? json_decode($s['options'], true) : [];
                                echo '<select name="' . ha($name) . '">';
                                foreach ($opts as $k => $lbl) {
                                    echo '<option value="' . ha((string)$k) . '"' . ($val == $k ? ' selected' : '') . '>' . h((string)$lbl) . '</option>';
                                }
                                echo '</select>';
                                break;
                            default:
                                echo '<input type="text" name="' . ha($name) . '" value="' . h($val) . '">';
                        }
                        if ($s['description']): ?>
                            <span class="help"><?= h($s['description']) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </form>
</div>

<style>@media (max-width: 900px) { .settings-grid { grid-template-columns: 1fr !important; } }</style>

<?php require __DIR__ . '/../_footer.php'; ?>
