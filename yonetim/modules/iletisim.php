<?php
declare(strict_types=1);
define('AG_ADMIN', true);
$adminTitle = 'İletişim Mesajları';
$adminBreadcrumb = 'İletişim';
$adminMenu = 'iletisim';
require __DIR__ . '/../_layout.php';

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

if ($action === 'mark-read' && $id) {
    CSRF::require();
    DB::exec("UPDATE ag_iletisim_mesajlari SET okundu = 1 WHERE id = ?", [$id]);
    redirect('/yonetim/modules/iletisim.php?action=view&id=' . $id);
}
if ($action === 'delete' && $id) {
    CSRF::require();
    DB::exec("DELETE FROM ag_iletisim_mesajlari WHERE id = ?", [$id]);
    Audit::log('delete', 'mesaj', $id);
    flash('success', 'Mesaj silindi.');
    redirect('/yonetim/modules/iletisim.php');
}

if ($action === 'view' && $id) {
    $msg = DB::row("SELECT * FROM ag_iletisim_mesajlari WHERE id = ?", [$id]);
    if (!$msg) { flash('error', 'Mesaj bulunamadı.'); redirect('/yonetim/modules/iletisim.php'); }
    if (!$msg['okundu']) {
        DB::exec("UPDATE ag_iletisim_mesajlari SET okundu = 1 WHERE id = ?", [$id]);
        $msg['okundu'] = 1;
    }
} else {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 25;
    $filterUnread = ($_GET['filter'] ?? '') === 'unread';
    $where = $filterUnread ? "WHERE okundu = 0" : "";
    $total = (int)DB::scalar("SELECT COUNT(*) FROM ag_iletisim_mesajlari $where");
    $p = paginate($total, $page, $perPage);
    $mesajlar = DB::all("SELECT * FROM ag_iletisim_mesajlari $where ORDER BY created_at DESC LIMIT $perPage OFFSET {$p['offset']}");
}
?>

<?php if ($action === 'view' && isset($msg)): ?>
    <a href="/yonetim/modules/iletisim.php" class="btn ghost btn-sm" style="margin-bottom:16px"><?= icon('arrow-left', 14) ?> Tüm Mesajlar</a>
    <div class="card">
        <div class="card-head">
            <h2><?= h($msg['konu']) ?></h2>
            <div class="actions">
                <a href="mailto:<?= ha($msg['email']) ?>?subject=Re: <?= ha($msg['konu']) ?>" class="btn"><?= icon('mail', 14) ?> Cevapla</a>
                <form method="get" style="display:inline" onsubmit="return confirm('Mesaj silinsin mi?')">
                    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$msg['id'] ?>">
                    <?= CSRF::field() ?>
                    <button class="btn outline btn-sm" style="color:var(--danger)"><?= icon('trash', 14) ?> Sil</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:20px; padding:20px; background:var(--bg-soft); border-radius:6px; margin-bottom:24px">
                <div>
                    <div class="muted" style="font-size:11px;letter-spacing:.15em;text-transform:uppercase">Gönderen</div>
                    <div style="font-weight:600;margin-top:4px"><?= h($msg['ad_soyad']) ?></div>
                </div>
                <div>
                    <div class="muted" style="font-size:11px;letter-spacing:.15em;text-transform:uppercase">E-posta</div>
                    <div style="margin-top:4px"><a href="mailto:<?= ha($msg['email']) ?>" style="color:var(--gold-dark);text-decoration:none"><?= h($msg['email']) ?></a></div>
                </div>
                <?php if ($msg['telefon']): ?>
                <div>
                    <div class="muted" style="font-size:11px;letter-spacing:.15em;text-transform:uppercase">Telefon</div>
                    <div style="margin-top:4px"><?= h($msg['telefon']) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($msg['firma']): ?>
                <div>
                    <div class="muted" style="font-size:11px;letter-spacing:.15em;text-transform:uppercase">Firma</div>
                    <div style="margin-top:4px"><?= h($msg['firma']) ?></div>
                </div>
                <?php endif; ?>
                <div>
                    <div class="muted" style="font-size:11px;letter-spacing:.15em;text-transform:uppercase">Tarih</div>
                    <div style="margin-top:4px"><?= h(formatDate($msg['created_at'], 'd M Y H:i')) ?></div>
                </div>
                <div>
                    <div class="muted" style="font-size:11px;letter-spacing:.15em;text-transform:uppercase">IP</div>
                    <div style="margin-top:4px;font-family:ui-monospace,monospace;font-size:13px"><?= h($msg['ip_adresi'] ?? '—') ?></div>
                </div>
            </div>
            <div style="font-size:15px;line-height:1.7;white-space:pre-wrap"><?= h($msg['mesaj']) ?></div>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-head">
            <h2>Mesajlar · <span class="muted" style="font-size:14px">Toplam <?= formatNumber($total) ?></span></h2>
            <div class="actions">
                <a href="?" class="btn outline btn-sm <?= !$filterUnread ? 'active' : '' ?>">Tümü</a>
                <a href="?filter=unread" class="btn outline btn-sm <?= $filterUnread ? 'active' : '' ?>">Okunmamış</a>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th></th><th>Gönderen</th><th>Konu</th><th>Tarih</th><th class="right">İşlem</th></tr></thead>
                <tbody>
                <?php foreach ($mesajlar as $m): ?>
                    <tr style="<?= !$m['okundu'] ? 'background:rgba(201,169,97,.05)' : '' ?>">
                        <td><?= !$m['okundu'] ? '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--gold)" title="Yeni"></span>' : '' ?></td>
                        <td>
                            <a href="?action=view&id=<?= (int)$m['id'] ?>" style="font-weight:<?= $m['okundu'] ? '500' : '700' ?>;color:var(--text);text-decoration:none">
                                <?= h($m['ad_soyad']) ?>
                            </a>
                            <div class="muted" style="font-size:12px"><?= h($m['email']) ?></div>
                        </td>
                        <td>
                            <a href="?action=view&id=<?= (int)$m['id'] ?>" style="color:var(--text);text-decoration:none">
                                <?= h($m['konu']) ?>
                            </a>
                            <div class="muted" style="font-size:12px"><?= h(truncate($m['mesaj'], 80)) ?></div>
                        </td>
                        <td class="muted nowrap" style="font-size:13px"><?= h(timeAgo($m['created_at'])) ?></td>
                        <td class="right">
                            <div class="row-actions" style="justify-content:flex-end">
                                <a href="?action=view&id=<?= (int)$m['id'] ?>" class="btn ghost btn-sm"><?= icon('eye', 14) ?></a>
                                <form method="get" style="display:inline" onsubmit="return confirm('Mesaj silinsin mi?')">
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                    <?= CSRF::field() ?>
                                    <button class="btn ghost btn-sm" style="color:var(--danger)"><?= icon('trash', 14) ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$mesajlar): ?>
                    <tr><td colspan="5"><div class="empty"><div class="serif">Mesaj yok</div><p>İletişim formundan gelen mesajlar burada listelenir.</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= paginatorHtml($p, '/yonetim/modules/iletisim.php' . ($filterUnread ? '?filter=unread' : '')) ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../_footer.php'; ?>
