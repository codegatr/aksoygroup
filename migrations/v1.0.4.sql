-- ════════════════════════════════════════════════════════
-- AKSOY GROUP — v1.0.4 Migration
-- - Smart Update Center (ERP-style AJAX UI)
-- - Şema değişikliği yok
-- ════════════════════════════════════════════════════════

INSERT IGNORE INTO `ag_versions` (`version`, `release_name`, `release_notes`, `migration_executed`, `status`)
VALUES ('1.0.4', 'Smart Update Center',
    'AJAX-based update UI: LOCAL→GITHUB header, 4-stat dashboard, 3 buton (Check/Smart/Force), live console overlay, Commits sekmesi, tek dosya update.',
    1, 'success');

UPDATE `ag_settings` SET `setting_value` = '1.0.4' WHERE `setting_key` = 'current_version';
