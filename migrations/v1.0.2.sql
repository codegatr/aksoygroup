-- ════════════════════════════════════════════════════════
-- AKSOY GROUP — v1.0.2 Migration
-- - ag_migrations tablosu (idempotent SQL tracking)
-- - github_token ayar girdisi (boş varsayılan, .gh_token tercih edilir)
-- - Updater v2 ERP-pattern desteği
-- Tamamen idempotent — birden fazla kez çalıştırılabilir.
-- ════════════════════════════════════════════════════════

-- 1) ag_migrations tablosu
CREATE TABLE IF NOT EXISTS `ag_migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `filename` VARCHAR(255) NOT NULL,
    `checksum` VARCHAR(64) DEFAULT NULL,
    `status` ENUM('ok', 'error', 'skipped') NOT NULL DEFAULT 'ok',
    `notes` TEXT DEFAULT NULL,
    `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_filename` (`filename`),
    KEY `idx_applied_at` (`applied_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Geçmiş migrationları işaretle (yeniden çalıştırılmasın)
INSERT IGNORE INTO `ag_migrations` (`filename`, `status`, `notes`)
VALUES
    ('v1.0.0.sql', 'ok', 'Genesis seed — kurulum sihirbazından uygulandı'),
    ('v1.0.1.sql', 'ok', 'Lojistik & Hizmetler Topluluğu güncellemesi');

-- 3) github_token ayar girdisi (boş, .gh_token dosyası tercih edilir)
INSERT IGNORE INTO `ag_settings`
    (`setting_key`, `setting_value`, `setting_group`, `setting_type`, `label`, `description`, `sort_order`, `is_public`)
VALUES
    ('github_token', '', 'sistem', 'text', 'GitHub Token',
     'Update Center > Ayarlar sekmesinden girilir. Boşsa includes/.gh_token dosyası kullanılır.',
     20, 0);

-- 4) Versiyon kaydı
INSERT IGNORE INTO `ag_versions` (`version`, `release_name`, `release_notes`, `migration_executed`, `status`)
VALUES ('1.0.2', 'ERP-style Updater',
    'Güncelleme motoru ERP pattern: GitHub tree SHA diff, Smart/Force Sync, Rollback (10 yedek), 5-sekmeli UI.', 1, 'success');

UPDATE `ag_settings` SET `setting_value` = '1.0.2' WHERE `setting_key` = 'current_version';
