-- ════════════════════════════════════════════════════════
-- AKSOY GROUP — v1.0.5 Migration
-- - HOTFIX: CSRF token AJAX field uyumsuzluğu (csrf_token → _csrf header)
-- ════════════════════════════════════════════════════════

INSERT IGNORE INTO `ag_versions` (`version`, `release_name`, `release_notes`, `migration_executed`, `status`)
VALUES ('1.0.5', 'CSRF Hotfix',
    'AJAX CSRF token X-CSRF-Token header üzerinden gönderiliyor. Token kaydetme + diğer AJAX aksiyonları artık çalışıyor.',
    1, 'success');

UPDATE `ag_settings` SET `setting_value` = '1.0.5' WHERE `setting_key` = 'current_version';
