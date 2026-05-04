<?php
declare(strict_types=1);
define('AG_ADMIN', true);
$adminTitle = 'Aktivite Logu';
$adminBreadcrumb = 'Sistem';
$adminMenu = 'audit-log';
require __DIR__ . '/../_layout.php';

Auth::requireRole('superadmin', 'admin');

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$filterAction = trim($_GET['action_filter'] ?? '');
$filterEntity = trim($_GET['entity'] ?? '');
$filterSeverity = trim($_GET['severity'] ?? '');
$filterUser = (int)($_GET['user_id'] ?? 0);

$where = []; $params = [];
if ($filterAction) { $where[] = 'al.action = ?'; $params[] = $filterAction; }
if ($filterEntity) { $where[] = 'al.entity = ?'; $params[] = $filterEntity; }
if ($filterSeverity) { $where[] = 'al.severity = ?'; $params[] = $filterSeverity; }
if ($filterUser) { $where[] = 'al.user_id = ?'; $params[] = $filterUser; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int)DB::scalar("SELECT COUNT(*) FROM ag_audit_log al $whereSql", $params);
$p = paginate($total, $page, $perPage);
$logs = DB::all("SELECT al.*, u.full_name, u.username FROM ag_audit_log al
                 LEFT JOIN ag_users u ON u.id = al.user_id
                 $whereSql ORDER BY al.created_at DESC LIMIT $perPage OFFSET {$p['offset']}", $params);

$users = DB::all("SELECT id, full_name, username FROM ag_users ORDER BY full_name");
$actions = DB::all("SELECT DISTINCT action FROM ag_audit_log WHERE action IS NOT NULL ORDER BY action");
$entities = DB::all("SELECT DISTINCT entity FROM ag_audit_log WHERE entity IS NOT NULL ORDER BY entity");

$severityColors = ['info'=>'muted','warning'=>'warning','danger'=>'danger','critical'=>'danger'];
?>

<form method="get" class="card">
    <div class="filters">
        <select name="user_id">
            <option value="">Tüm kullanıcılar</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= (int)$u['id'] ?>" <?= $filterUser == $u['id'] ? 'selected' : '' ?>><?= h($u['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="action_filter">
            <option value="">Tüm işlemler</option>
            <?php foreach ($actions as $a): ?>
                <option value="<?= ha($a['action']) ?>" <?= $filterAction === $a['action'] ? 'selected' : '' ?>><?= h($a['action']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="entity">
            <option value="">Tüm varlıklar</option>
            <?php foreach ($entities as $e): ?>
                <option value="<?= ha($e['entity']) ?>" <?= $filterEntity === $e['entity'] ? 'selected' : '' ?>><?= h($e['entity']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="severity">
            <option value="">Tüm seviyeler</option>
            <option value="info" <?= $filterSeverity === 'info' ? 'selected' : '' ?>>Bilgi</option>
            <option value="warning" <?= $filterSeverity === 'warning' ? 'selected' : '' ?>>Uyarı</option>
            <option value="danger" <?= $filterSeverity === 'danger' ? 'selected' : '' ?>>Tehlikeli</option>
            <option value="critical" <?= $filterSeverity === 'critical' ? 'selected' : '' ?>>Kritik</option>
        </select>
        <button type="submit" class="btn btn-sm"><?= icon('search', 14) ?> Filtrele</button>
        <a href="/yonetim/modules/audit-log.php" class="btn ghost btn-sm">Temizle</a>
    </div>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Tarih</th><th>Kullanıcı</th><th>İşlem</th><th>Varlık</th><th>IP</th><th class="center">Seviye</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td class="nowrap muted" style="font-size:13px"><?= h(formatDate($l['created_at'], 'd M Y H:i:s')) ?></td>
                    <td>
                        <?php if ($l['full_name']): ?>
                            <b><?= h($l['full_name']) ?></b>
                            <div class="muted" style="font-size:11px">@<?= h($l['username']) ?></div>
                        <?php else: ?>
                            <span class="muted">— sistem —</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge muted"><?= h($l['action']) ?></span></td>
                    <td>
                        <?php if ($l['entity']): ?>
                            <code style="font-size:12px"><?= h($l['entity']) ?><?= $l['entity_id'] ? '#' . (int)$l['entity_id'] : '' ?></code>
                        <?php endif; ?>
                    </td>
                    <td><code style="font-size:12px;color:var(--text-mute)"><?= h($l['ip_address'] ?? '—') ?></code></td>
                    <td class="center">
                        <span class="badge <?= $severityColors[$l['severity']] ?? 'muted' ?>"><?= h($l['severity']) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?>
                <tr><td colspan="6"><div class="empty"><div class="serif">Kayıt yok</div></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= paginatorHtml($p, '/yonetim/modules/audit-log.php?' . http_build_query($_GET)) ?>
</form>

<?php require __DIR__ . '/../_footer.php'; ?>
