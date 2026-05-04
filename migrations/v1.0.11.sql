-- ════════════════════════════════════════════════════════
-- AKSOY GROUP — v1.0.11 Migration
-- One-Page Corporate (Göltaş referansı)
-- - Anasayfa 13 section, hepsi anchor id'li (#hakkimizda, #yonetim-kurulu vb.)
-- - Header menü anchor link'lere yönlendiriyor
-- - Smooth scroll, active section detection
-- - Yeni section'lar: Hakkımızda özeti, V/M/D, Yön. Kurulu önizleme,
--   Sürdürülebilirlik 3 kolon, Kariyer banner, İletişim form inline
-- ════════════════════════════════════════════════════════

INSERT IGNORE INTO `ag_versions` (`version`, `release_name`, `release_notes`, `migration_executed`, `status`)
VALUES ('1.0.11', 'One-Page Corporate',
    'Anasayfa Göltaş tarzı tek-sayfa yapısına dönüştürüldü. 13 anchor section: #anasayfa, #manifesto, #sayilarla, #hakkimizda, #vizyon-misyon, #sektorler, #istirakler, #yonetim-kurulu, #surdurulebilirlik, #haberler, #tarihce, #kariyer, #iletisim. Header menü anchor link\\\"lere yönlendirir. Smooth scroll + active section detection. İleride section\\\"lar ayrı sayfalara taşınabilir.',
    1, 'success');

UPDATE `ag_settings` SET `setting_value` = '1.0.11' WHERE `setting_key` = 'current_version';
