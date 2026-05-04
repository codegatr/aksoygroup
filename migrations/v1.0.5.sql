-- ════════════════════════════════════════════════════════
-- AKSOY GROUP — v1.0.5 Migration
-- - Token kaydetme: detaylı hata teşhisi + esnek regex
-- - Sistem teşhisi endpoint (api/update.php?action=diagnose)
-- - JS regex: gho_/ghs_/ghr_/github_pat_ varyasyonları + tire desteği
-- - UI: Ayarlar sekmesinde "Sistem Teşhisi" kartı
-- ════════════════════════════════════════════════════════

INSERT IGNORE INTO `ag_versions` (`version`, `release_name`, `release_notes`, `migration_executed`, `status`)
VALUES ('1.0.5', 'Smart Update Hotfix',
    'Token kaydetme detaylı sonuç döner (file/db/where), esnek regex (tire+varyasyonlar), sistem teşhisi endpoint, UI diag butonu.',
    1, 'success');

UPDATE `ag_settings` SET `setting_value` = '1.0.5' WHERE `setting_key` = 'current_version';
