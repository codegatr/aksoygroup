-- ════════════════════════════════════════════════════════
-- AKSOY GROUP — v1.0.1 Migration
-- - Lojistik & Taşımacılık sektörü ilave edildi (X. sıra)
-- - Marka konumlandırması: "Holding" → "Hizmetler Topluluğu"
-- - GitHub repo: codegatr/aksoyholding → codegatr/aksoygroup
-- - ag_versions tablosuna status ve duration_ms kolonları
-- Tamamen idempotent — birden fazla kez çalıştırılabilir.
-- MariaDB 10.6+ gerekir (manifest.json'da zaten zorunlu).
-- ════════════════════════════════════════════════════════

-- 0) ag_versions: eksik kolonları idempotent ekle
ALTER TABLE `ag_versions` ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) NOT NULL DEFAULT 'success' AFTER `installed_by`;
ALTER TABLE `ag_versions` ADD COLUMN IF NOT EXISTS `duration_ms` INT UNSIGNED DEFAULT NULL AFTER `status`;

-- 1) Lojistik sektörü ekle (yoksa)
INSERT IGNORE INTO `ag_sektorler`
    (`slug`, `roman_no`, `ad`, `alt_baslik`, `vurgu_renk`, `sort_order`, `is_active`, `is_featured`)
VALUES
    ('lojistik', 'X', 'Lojistik & Taşımacılık', 'Yükün İpek Yolu', '#34495E', 100, 1, 0);

-- 2) Marka mesajı: Holding → Hizmetler Topluluğu
UPDATE `ag_settings` SET `setting_value` =
    'Demir-çelikten yazılıma, sigortadan tarıma, lojistikten 3D üretime — on sektörde geleceği tasarlayan bir hizmetler topluluğu.'
WHERE `setting_key` = 'site_description';

UPDATE `ag_settings` SET `setting_value` =
    'Türkiye’nin Çok Sektörlü Hizmetler Topluluğu'
WHERE `setting_key` = 'site_tagline';

UPDATE `ag_settings` SET `setting_value` =
    'aksoy group, aksoy hizmetler topluluğu, konya hizmetler grubu, tekcan metal, codega, minya 3d, sbb sigorta, xnews, lojistik'
WHERE `setting_key` = 'seo_meta_keywords';

-- 3) GitHub repo bilgisini güncelle
UPDATE `ag_settings` SET `setting_value` =
    'codegatr/aksoygroup'
WHERE `setting_key` = 'github_repo';

-- 4) Zaman çizgisine 2026 milestone ekle (yoksa)
INSERT IGNORE INTO `ag_zaman_cizgisi`
    (`yil`, `baslik`, `aciklama`, `is_active`, `sort_order`)
VALUES
    (2026, 'Lojistik Sektörüne Açılış', 'Lojistik & taşımacılık sektörünün ilave edilmesiyle topluluk 10 sektöre ulaştı.', 1, 90);

-- 5) Versiyon kaydı
INSERT IGNORE INTO `ag_versions` (`version`, `release_name`, `release_notes`, `migration_executed`, `status`)
VALUES ('1.0.1', 'Lojistik & Hizmetler Topluluğu', 'Lojistik sektörü (X) ilave edildi; marka konumu Hizmetler Topluluğu olarak güncellendi.', 1, 'success');

UPDATE `ag_settings` SET `setting_value` = '1.0.1' WHERE `setting_key` = 'current_version';
