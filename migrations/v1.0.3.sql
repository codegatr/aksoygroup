-- ════════════════════════════════════════════════════════
-- AKSOY GROUP — v1.0.3 Migration
-- - Public frontend launch (sayfa kodları, schema değişikliği yok)
-- ════════════════════════════════════════════════════════

INSERT IGNORE INTO `ag_versions` (`version`, `release_name`, `release_notes`, `migration_executed`, `status`)
VALUES ('1.0.3', 'Public Frontend Launch',
    'Hero, sektör grid, vitrine, haberler, zaman çizgisi; sektör/şirket/sayfa/haber detay; iletişim formu + handler.',
    1, 'success');

UPDATE `ag_settings` SET `setting_value` = '1.0.3' WHERE `setting_key` = 'current_version';
