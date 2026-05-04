-- ════════════════════════════════════════════════════════
-- AKSOY GROUP — v1.0.5 Hotfix
-- - saveToken() DB fallback (.gh_token yazılamazsa ag_settings'e)
-- - JS'te buton feedback + hata diagnostiği
-- - Token format validasyonu (ghp_ / github_pat_ / gho_)
-- ════════════════════════════════════════════════════════

INSERT IGNORE INTO `ag_versions` (`version`, `release_name`, `release_notes`, `migration_executed`, `status`)
VALUES ('1.0.5', 'Token Save Hotfix',
    'Token kaydetme sorunu düzeltildi: DB fallback, daha iyi hata raporu, JS feedback.',
    1, 'success');

UPDATE `ag_settings` SET `setting_value` = '1.0.5' WHERE `setting_key` = 'current_version';
