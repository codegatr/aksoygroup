-- ════════════════════════════════════════════════════════
-- AKSOY GROUP — v1.0.6 Migration
-- Düzeltmeler:
-- - CSRF token: hem _csrf hem csrf_token kabul edilir
-- - Updater::getOwner/getRepo/getBranch dinamik (DB > config)
-- - Bootstrap fallback sabitler (eski config.php'ler için)
-- - 419 hatasında frontend otomatik refresh teklifi
-- - Token regex genişletildi (zaten v1.0.5'te yapıldı)
-- - Repo bilgisi DB'den okunduğu için yanlış config.php'ye karşı bağışıklık
-- ════════════════════════════════════════════════════════

-- 1) ag_settings.github_repo'nun doğruluğundan emin ol
UPDATE `ag_settings` SET `setting_value` = 'codegatr/aksoygroup'
 WHERE `setting_key` = 'github_repo' AND `setting_value` = 'codegatr/aksoyholding';

-- Yoksa ekle
INSERT IGNORE INTO `ag_settings`
    (`setting_key`, `setting_value`, `setting_group`, `setting_type`, `label`, `description`, `sort_order`, `is_public`)
VALUES
    ('github_repo', 'codegatr/aksoygroup', 'sistem', 'text', 'GitHub Repo (owner/repo)',
     'Updater bu değeri config.php''den önce okur. Yanlış config.php düzeltmek için kullanışlı.',
     10, 0);

-- 2) Versiyon kaydı
INSERT IGNORE INTO `ag_versions` (`version`, `release_name`, `release_notes`, `migration_executed`, `status`)
VALUES ('1.0.6', 'Repo + CSRF Hotfix',
    'CSRF token uyumsuzluğu (_csrf/csrf_token) düzeltildi, Updater repo bilgisi DB-öncelikli okuyor (yanlış config.php''ye karşı bağışıklık), 419 hatasında otomatik refresh teklifi.',
    1, 'success');

UPDATE `ag_settings` SET `setting_value` = '1.0.6' WHERE `setting_key` = 'current_version';
