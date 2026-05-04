<?php
/**
 * AKSOY GROUP — Audit Log
 * @package AksoyHolding\Core
 */

declare(strict_types=1);

final class Audit
{
    public static function log(
        string $action,
        ?string $entity = null,
        ?int $entityId = null,
        mixed $oldValue = null,
        mixed $newValue = null,
        string $severity = 'info'
    ): void {
        try {
            DB::insert('ag_audit_log', [
                'user_id'    => $_SESSION['user_id'] ?? null,
                'action'     => substr($action, 0, 100),
                'entity'     => $entity ? substr($entity, 0, 50) : null,
                'entity_id'  => $entityId,
                'old_value'  => $oldValue !== null ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : null,
                'new_value'  => $newValue !== null ? json_encode($newValue, JSON_UNESCAPED_UNICODE) : null,
                'ip_adresi'  => clientIp(),
                'user_agent' => userAgent(),
                'severity'   => in_array($severity, ['info','warning','danger','critical'], true) ? $severity : 'info',
            ]);
        } catch (Throwable $e) {
            error_log('[Audit] ' . $e->getMessage());
        }
    }
}
