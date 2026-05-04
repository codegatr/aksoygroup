-- ════════════════════════════════════════════════════════
-- AKSOY GROUP — v1.0.10 Migration
-- HOLDING-GRADE REFINEMENT (Alarko-killer)
-- - 5 frame Hero Slider (manifesto, vizyon, sürdürülebilirlik, yönetim, kariyer)
-- - Manifesto bloğu (büyük, dramatik) — "Yarattığı farkla büyüyen, öncü ve saygın"
-- - Sayılarla Aksoy stats block (10 sektör · 9+ iştirak · 500+ çalışan · X+ yıl)
-- - Header megamenu (Kurumsal + Faaliyet Alanları)
-- - Arama overlay + TR/EN toggle
-- - Zenginleştirilmiş footer (4 sütun + iletişim + sertifika rozetleri)
-- ════════════════════════════════════════════════════════

-- Yeni iletişim ayarları (footer için)
INSERT IGNORE INTO `ag_settings` (`setting_key`, `setting_value`, `setting_group`, `setting_type`, `label`, `description`, `sort_order`, `is_public`) VALUES
('contact_phone_2', '', 'iletisim', 'text', 'İkinci Telefon', 'Footer\'da gösterilen ikinci telefon numarası', 30, 1),
('contact_fax', '', 'iletisim', 'text', 'Faks', 'Footer\'da gösterilen faks numarası', 40, 1),
('toplam_calisan', '500', 'genel', 'number', 'Toplam Çalışan Sayısı', 'Sayılarla Aksoy bloğunda gösterilir', 50, 1),
('grup_kurulus_yili', '1992', 'genel', 'number', 'Grup Kuruluş Yılı', 'Tarihçe başlangıç yılı (deneyim hesabı için)', 60, 1);

INSERT IGNORE INTO `ag_versions` (`version`, `release_name`, `release_notes`, `migration_executed`, `status`)
VALUES ('1.0.10', 'Holding-Grade Refinement',
    '5 slide hero slider, manifesto bloğu, sayılarla Aksoy stats block, megamenu (Kurumsal + Faaliyet Alanları), arama overlay, TR/EN toggle, zenginleştirilmiş 4 sütunlu footer.',
    1, 'success');

UPDATE `ag_settings` SET `setting_value` = '1.0.10' WHERE `setting_key` = 'current_version';
