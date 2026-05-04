<?php
/**
 * AKSOY GROUP — Admin Helpers
 * Modüllerin ortak ihtiyaçları: ikonlar, badge, paginate, dropdown options, vs.
 */

declare(strict_types=1);
if (!defined('AG_ADMIN')) { die('Erişim engellendi.'); }

/** SVG ikon (Lucide ikon seti karışımı, native SVG). */
function icon(string $name, int $size = 18): string
{
    static $icons = [
        'home'        => '<path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1z"/>',
        'briefcase'   => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>',
        'building'    => '<path d="M3 21V8a1 1 0 0 1 1-1h6V4a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v17M9 11h.01M9 15h.01M9 19h.01M14 7h.01M14 11h.01M14 15h.01M14 19h.01"/>',
        'newspaper'   => '<path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zM18 14h-8M15 18h-5M10 6h8v4h-8z"/>',
        'file-text'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
        'image'       => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',
        'users'       => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'mail'        => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6 12 13 2 6"/>',
        'settings'    => '<path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'shield'      => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'logout'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'plus'        => '<path d="M12 5v14M5 12h14"/>',
        'edit'        => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        'trash'       => '<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/>',
        'eye'         => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'check'       => '<path d="M20 6L9 17l-5-5"/>',
        'x'           => '<path d="M18 6L6 18M6 6l12 12"/>',
        'arrow-left'  => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
        'arrow-right' => '<path d="M5 12h14M12 5l7 7-7 7"/>',
        'download'    => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
        'upload'      => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>',
        'refresh'     => '<path d="M3 12a9 9 0 0 1 15-6.7l3 2.7M21 12a9 9 0 0 1-15 6.7L3 16M21 3v6h-6M3 21v-6h6"/>',
        'menu'        => '<path d="M3 12h18M3 6h18M3 18h18"/>',
        'search'      => '<circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>',
        'bell'        => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/>',
        'package'     => '<path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16zM3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/>',
        'globe'       => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'activity'    => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
        'star'        => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',
        'database'    => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>',
        'rocket'      => '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09zM12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2zM9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>',
    ];
    $path = $icons[$name] ?? '<circle cx="12" cy="12" r="10"/>';
    return "<svg width=\"$size\" height=\"$size\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\">$path</svg>";
}

/** Sayfalama oluştur. */
function paginate(int $total, int $page, int $perPage): array
{
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    return [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'offset' => ($page - 1) * $perPage,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages,
    ];
}

/** Pagination HTML. */
function paginatorHtml(array $p, string $baseUrl): string
{
    if ($p['total_pages'] <= 1) return '';
    $sep = str_contains($baseUrl, '?') ? '&' : '?';
    $start = ($p['page'] - 1) * $p['per_page'] + 1;
    $end = min($p['page'] * $p['per_page'], $p['total']);
    ob_start(); ?>
    <div class="pager">
        <div class="info">Toplam <b><?= formatNumber($p['total']) ?></b> kayıt — <?= $start ?>–<?= $end ?> arası gösteriliyor</div>
        <nav>
            <a href="<?= $p['has_prev'] ? ha($baseUrl . $sep . 'page=' . ($p['page'] - 1)) : '#' ?>" class="<?= $p['has_prev'] ? '' : 'disabled' ?>">‹</a>
            <?php
            $start = max(1, $p['page'] - 2);
            $endP = min($p['total_pages'], $p['page'] + 2);
            if ($start > 1) echo '<a href="' . ha($baseUrl . $sep . 'page=1') . '">1</a>';
            if ($start > 2) echo '<span class="disabled">…</span>';
            for ($i = $start; $i <= $endP; $i++) {
                if ($i === $p['page']) echo "<span class=\"current\">$i</span>";
                else echo '<a href="' . ha($baseUrl . $sep . 'page=' . $i) . '">' . $i . '</a>';
            }
            if ($endP < $p['total_pages'] - 1) echo '<span class="disabled">…</span>';
            if ($endP < $p['total_pages']) echo '<a href="' . ha($baseUrl . $sep . 'page=' . $p['total_pages']) . '">' . $p['total_pages'] . '</a>';
            ?>
            <a href="<?= $p['has_next'] ? ha($baseUrl . $sep . 'page=' . ($p['page'] + 1)) : '#' ?>" class="<?= $p['has_next'] ? '' : 'disabled' ?>">›</a>
        </nav>
    </div>
    <?php return (string)ob_get_clean();
}

/** Boolean badge. */
function boolBadge(bool $value, string $okText = 'Aktif', string $noText = 'Pasif'): string
{
    return $value
        ? '<span class="badge success">' . h($okText) . '</span>'
        : '<span class="badge muted">' . h($noText) . '</span>';
}

/** Durum badge'i. */
function durumBadge(string $durum): string
{
    return match($durum) {
        'aktif'         => '<span class="badge success">Aktif</span>',
        'pasif'         => '<span class="badge muted">Pasif</span>',
        'gelistiriliyor' => '<span class="badge warning">Geliştiriliyor</span>',
        default         => '<span class="badge muted">' . h($durum) . '</span>',
    };
}
