-- ════════════════════════════════════════════════════════
-- AKSOY GROUP — v1.0.7 Migration
-- - Public frontend tema değişimi (dark luxury → light editorial)
-- - HTML değişikliği yok, sadece site.css revize + index.php section ritmi
-- - Şema değişikliği yok
-- ════════════════════════════════════════════════════════

INSERT IGNORE INTO `ag_versions` (`version`, `release_name`, `release_notes`, `migration_executed`, `status`)
VALUES ('1.0.7', 'Light Editorial Theme',
    'Dark luxury tema light editorial luxurye çevrildi: Bone cream arka plan + Navy charcoal metin + Champagne Gold vurgu. Görsel ritim için dark CTA section korundu. Hermès/Tata holding aesthetic.',
    1, 'success');

UPDATE `ag_settings` SET `setting_value` = '1.0.7' WHERE `setting_key` = 'current_version';
