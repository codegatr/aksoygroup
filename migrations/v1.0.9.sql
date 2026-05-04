-- ════════════════════════════════════════════════════════
-- AKSOY GROUP — v1.0.9 Migration
-- - Frontend: Corporate Türk Holding tema (Beyaz + Navy + Burgundy + Gold ikincil)
-- - Cream/luxury yerine kurumsal/enstitüsel görünüm (Koç/Sabancı/Tata referansı)
-- - HTML değişikliği yok, sadece site.css revize + index.php section ritmi
-- ════════════════════════════════════════════════════════

INSERT IGNORE INTO `ag_versions` (`version`, `release_name`, `release_notes`, `migration_executed`, `status`)
VALUES ('1.0.9', 'Corporate Refinement',
    'Cream/light luxury kaldırıldı; Türk holding kurumsal estetiği: saf beyaz arka plan, corporate navy ana renk, burgundy klasik vurgu, gold ikincil. Hero grid pattern (A watermark yerine), sektör kartları geometric grid, Tarihçe dark navy section.',
    1, 'success');

UPDATE `ag_settings` SET `setting_value` = '1.0.9' WHERE `setting_key` = 'current_version';
