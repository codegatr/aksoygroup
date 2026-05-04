-- ════════════════════════════════════════════════════════
-- AKSOY GROUP — v1.0.12 Migration
-- Göltaş-Style Hero (sade, sinematik)
-- - Hero slogan tek satıra düşürüldü (lead + 2 buton kaldırıldı)
-- - Sağ dikey badge (writing-mode:vertical-rl, gold border, glassmorph)
-- - Üst-orta logo decoration ("— AKSOY GROUP —")
-- - Sayılı pagination (01-02-03-04-05) alt-orta — Göltaş'taki gibi
-- - Kenar oklar (52x52, glassmorph) eski sağ-alt kontrolün yerini aldı
-- - Şema değişikliği YOK
-- ════════════════════════════════════════════════════════

INSERT IGNORE INTO `ag_versions` (`version`, `release_name`, `release_notes`, `migration_executed`, `status`)
VALUES ('1.0.12', 'Göltaş-Style Hero',
    'Hero slider Göltaş Çimento tarzı sinematik minimalizme dönüştürüldü. Tek satır slogan, sağ dikey badge, üst logo decoration, alt-orta sayılı pagination (01-05), kenar oklar.',
    1, 'success');

UPDATE `ag_settings` SET `setting_value` = '1.0.12' WHERE `setting_key` = 'current_version';
