-- ============================================================
-- AKSOY GROUP — aksoy.web.tr
-- Migration: v1.0.0 (Initial Schema)
-- Database prefix: ag_
-- Engine: InnoDB / utf8mb4_unicode_ci
-- PHP: 8.3+ / MariaDB 10.6+
-- ============================================================
-- Bu migration idempotent'tir; tekrar çalıştırılabilir.
-- INFORMATION_SCHEMA kontrolleri ile ALTER TABLE'lar korunur.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";

-- ============================================================
-- 1) AG_SETTINGS — Site Ayarları (key/value)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` LONGTEXT DEFAULT NULL,
    `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `setting_type` ENUM('text','textarea','html','image','number','boolean','json','color') NOT NULL DEFAULT 'text',
    `label` VARCHAR(150) DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_public` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_setting_key` (`setting_key`),
    KEY `idx_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2) AG_USERS — Admin Kullanıcıları
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `role` ENUM('superadmin','admin','editor','viewer') NOT NULL DEFAULT 'editor',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `totp_secret` VARCHAR(128) DEFAULT NULL,
    `totp_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `password_changed_at` TIMESTAMP NULL DEFAULT NULL,
    `last_login_at` TIMESTAMP NULL DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `failed_attempts` INT NOT NULL DEFAULT 0,
    `locked_until` TIMESTAMP NULL DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_username` (`username`),
    UNIQUE KEY `uniq_email` (`email`),
    KEY `idx_role` (`role`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3) AG_SEKTORLER — Ana Sektörler (10 sektör)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_sektorler` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(100) NOT NULL,
    `roman_no` VARCHAR(10) NOT NULL,
    `ad` VARCHAR(150) NOT NULL,
    `alt_baslik` VARCHAR(255) DEFAULT NULL,
    `kisa_aciklama` TEXT DEFAULT NULL,
    `uzun_aciklama` LONGTEXT DEFAULT NULL,
    `kapak_gorsel` VARCHAR(255) DEFAULT NULL,
    `ikon_svg` LONGTEXT DEFAULT NULL,
    `vurgu_renk` VARCHAR(20) DEFAULT '#C9A961',
    `vizyon` TEXT DEFAULT NULL,
    `misyon` TEXT DEFAULT NULL,
    `kurulus_yili` SMALLINT DEFAULT NULL,
    `calisan_sayisi` VARCHAR(50) DEFAULT NULL,
    `meta_title` VARCHAR(255) DEFAULT NULL,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `meta_keywords` VARCHAR(500) DEFAULT NULL,
    `og_image` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_slug` (`slug`),
    KEY `idx_active_sort` (`is_active`, `sort_order`),
    KEY `idx_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4) AG_SIRKETLER — Grup İştirakleri
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_sirketler` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sektor_id` INT UNSIGNED NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `unvan` VARCHAR(200) NOT NULL,
    `kisa_unvan` VARCHAR(100) DEFAULT NULL,
    `slogan` VARCHAR(255) DEFAULT NULL,
    `aciklama` LONGTEXT DEFAULT NULL,
    `logo` VARCHAR(255) DEFAULT NULL,
    `logo_dark` VARCHAR(255) DEFAULT NULL,
    `kapak_gorsel` VARCHAR(255) DEFAULT NULL,
    `kurulus_yili` SMallINT DEFAULT NULL,
    `merkez_sehir` VARCHAR(100) DEFAULT 'Konya',
    `merkez_adres` TEXT DEFAULT NULL,
    `telefon` VARCHAR(30) DEFAULT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `web_url` VARCHAR(255) DEFAULT NULL,
    `linkedin_url` VARCHAR(255) DEFAULT NULL,
    `instagram_url` VARCHAR(255) DEFAULT NULL,
    `facebook_url` VARCHAR(255) DEFAULT NULL,
    `twitter_url` VARCHAR(255) DEFAULT NULL,
    `youtube_url` VARCHAR(255) DEFAULT NULL,
    `vergi_dairesi` VARCHAR(100) DEFAULT NULL,
    `vergi_no` VARCHAR(20) DEFAULT NULL,
    `mersis_no` VARCHAR(30) DEFAULT NULL,
    `faaliyet_alani` TEXT DEFAULT NULL,
    `meta_title` VARCHAR(255) DEFAULT NULL,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `durum` ENUM('aktif','pasif','gelistiriliyor') NOT NULL DEFAULT 'aktif',
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_slug` (`slug`),
    KEY `idx_sektor` (`sektor_id`),
    KEY `idx_durum_sort` (`durum`, `sort_order`),
    CONSTRAINT `fk_sirket_sektor` FOREIGN KEY (`sektor_id`) REFERENCES `ag_sektorler`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5) AG_PAGES — Statik Sayfalar (Hakkımızda, Kariyer, KVKK vb.)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_pages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(150) NOT NULL,
    `baslik` VARCHAR(200) NOT NULL,
    `alt_baslik` VARCHAR(255) DEFAULT NULL,
    `icerik` LONGTEXT DEFAULT NULL,
    `kapak_gorsel` VARCHAR(255) DEFAULT NULL,
    `template` VARCHAR(50) NOT NULL DEFAULT 'default',
    `meta_title` VARCHAR(255) DEFAULT NULL,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `meta_keywords` VARCHAR(500) DEFAULT NULL,
    `og_image` VARCHAR(255) DEFAULT NULL,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `menu_konumu` ENUM('header','footer','her_ikisi','gizli') NOT NULL DEFAULT 'gizli',
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_slug` (`slug`),
    KEY `idx_active_menu` (`is_active`, `menu_konumu`),
    KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6) AG_HABER_KATEGORI — Haber Kategorileri
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_haber_kategori` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(100) NOT NULL,
    `ad` VARCHAR(100) NOT NULL,
    `renk` VARCHAR(20) DEFAULT '#C9A961',
    `aciklama` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7) AG_HABERLER — Haberler & Basın
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_haberler` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kategori_id` INT UNSIGNED DEFAULT NULL,
    `sirket_id` INT UNSIGNED DEFAULT NULL,
    `slug` VARCHAR(200) NOT NULL,
    `baslik` VARCHAR(255) NOT NULL,
    `ozet` VARCHAR(500) DEFAULT NULL,
    `icerik` LONGTEXT DEFAULT NULL,
    `kapak_gorsel` VARCHAR(255) DEFAULT NULL,
    `yazar` VARCHAR(100) DEFAULT 'AKSOY GROUP',
    `etiketler` VARCHAR(500) DEFAULT NULL,
    `goruntulenme` INT UNSIGNED NOT NULL DEFAULT 0,
    `meta_title` VARCHAR(255) DEFAULT NULL,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `yayim_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_slug` (`slug`),
    KEY `idx_kategori` (`kategori_id`),
    KEY `idx_sirket` (`sirket_id`),
    KEY `idx_active_yayim` (`is_active`, `yayim_tarihi`),
    KEY `idx_featured` (`is_featured`),
    CONSTRAINT `fk_haber_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `ag_haber_kategori`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_haber_sirket` FOREIGN KEY (`sirket_id`) REFERENCES `ag_sirketler`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8) AG_YONETIM_KURULU — Yönetim Kurulu
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_yonetim_kurulu` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ad_soyad` VARCHAR(150) NOT NULL,
    `unvan` VARCHAR(150) NOT NULL,
    `pozisyon` VARCHAR(100) DEFAULT NULL,
    `slug` VARCHAR(150) NOT NULL,
    `kisa_biyografi` TEXT DEFAULT NULL,
    `uzun_biyografi` LONGTEXT DEFAULT NULL,
    `fotograf` VARCHAR(255) DEFAULT NULL,
    `egitim` TEXT DEFAULT NULL,
    `linkedin_url` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_slug` (`slug`),
    KEY `idx_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9) AG_KARIYER — İş İlanları
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_kariyer` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sirket_id` INT UNSIGNED DEFAULT NULL,
    `slug` VARCHAR(200) NOT NULL,
    `pozisyon` VARCHAR(200) NOT NULL,
    `departman` VARCHAR(100) DEFAULT NULL,
    `lokasyon` VARCHAR(100) DEFAULT 'Konya',
    `calisma_sekli` ENUM('tam_zamanli','yari_zamanli','staj','sozlesmeli','uzaktan') NOT NULL DEFAULT 'tam_zamanli',
    `deneyim_seviyesi` ENUM('stajyer','baslangic','orta','kidemli','yonetici') NOT NULL DEFAULT 'orta',
    `aciklama` LONGTEXT DEFAULT NULL,
    `aranan_nitelikler` LONGTEXT DEFAULT NULL,
    `gorev_tanimi` LONGTEXT DEFAULT NULL,
    `son_basvuru_tarihi` DATE DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_slug` (`slug`),
    KEY `idx_sirket` (`sirket_id`),
    KEY `idx_active` (`is_active`),
    CONSTRAINT `fk_kariyer_sirket` FOREIGN KEY (`sirket_id`) REFERENCES `ag_sirketler`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10) AG_KARIYER_BASVURU — İş Başvuruları
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_kariyer_basvuru` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kariyer_id` INT UNSIGNED DEFAULT NULL,
    `ad_soyad` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `telefon` VARCHAR(30) NOT NULL,
    `cv_dosya` VARCHAR(255) DEFAULT NULL,
    `on_yazi` TEXT DEFAULT NULL,
    `linkedin_url` VARCHAR(255) DEFAULT NULL,
    `kvkk_onay` TINYINT(1) NOT NULL DEFAULT 0,
    `ip_adresi` VARCHAR(45) DEFAULT NULL,
    `durum` ENUM('yeni','inceleniyor','gorusme','reddedildi','kabul') NOT NULL DEFAULT 'yeni',
    `notlar` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kariyer` (`kariyer_id`),
    KEY `idx_durum` (`durum`),
    KEY `idx_email` (`email`),
    CONSTRAINT `fk_basvuru_kariyer` FOREIGN KEY (`kariyer_id`) REFERENCES `ag_kariyer`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11) AG_ILETISIM_MESAJLARI — İletişim Formu
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_iletisim_mesajlari` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ad_soyad` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `telefon` VARCHAR(30) DEFAULT NULL,
    `firma` VARCHAR(150) DEFAULT NULL,
    `konu` VARCHAR(200) NOT NULL,
    `mesaj` TEXT NOT NULL,
    `ilgili_sektor_id` INT UNSIGNED DEFAULT NULL,
    `ilgili_sirket_id` INT UNSIGNED DEFAULT NULL,
    `ip_adresi` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `kvkk_onay` TINYINT(1) NOT NULL DEFAULT 0,
    `okundu` TINYINT(1) NOT NULL DEFAULT 0,
    `cevaplandi` TINYINT(1) NOT NULL DEFAULT 0,
    `notlar` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_okundu` (`okundu`),
    KEY `idx_email` (`email`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12) AG_BASIN_BULTENI — Basın Bültenleri (PDF download)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_basin_bulteni` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(200) NOT NULL,
    `baslik` VARCHAR(255) NOT NULL,
    `aciklama` TEXT DEFAULT NULL,
    `pdf_dosya` VARCHAR(255) DEFAULT NULL,
    `kapak_gorsel` VARCHAR(255) DEFAULT NULL,
    `tarih` DATE NOT NULL,
    `indirme_sayisi` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_slug` (`slug`),
    KEY `idx_active_tarih` (`is_active`, `tarih`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13) AG_GALERI — Galeri Görselleri
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_galeri` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kategori` VARCHAR(50) DEFAULT 'genel',
    `baslik` VARCHAR(200) DEFAULT NULL,
    `aciklama` TEXT DEFAULT NULL,
    `dosya` VARCHAR(255) NOT NULL,
    `dosya_tipi` ENUM('image','video','youtube') NOT NULL DEFAULT 'image',
    `youtube_id` VARCHAR(50) DEFAULT NULL,
    `sirket_id` INT UNSIGNED DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kategori` (`kategori`),
    KEY `idx_sirket` (`sirket_id`),
    KEY `idx_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14) AG_RAKAMLAR — Sayılarla Aksoy
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_rakamlar` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `etiket` VARCHAR(150) NOT NULL,
    `deger` VARCHAR(50) NOT NULL,
    `birim` VARCHAR(20) DEFAULT NULL,
    `prefix` VARCHAR(10) DEFAULT NULL,
    `aciklama` VARCHAR(255) DEFAULT NULL,
    `ikon` VARCHAR(50) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 15) AG_ZAMAN_CIZGISI — Tarihçe / Milestones
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_zaman_cizgisi` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `yil` SMALLINT NOT NULL,
    `ay` TINYINT DEFAULT NULL,
    `baslik` VARCHAR(200) NOT NULL,
    `aciklama` TEXT DEFAULT NULL,
    `gorsel` VARCHAR(255) DEFAULT NULL,
    `sirket_id` INT UNSIGNED DEFAULT NULL,
    `is_milestone` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_yil` (`yil`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 16) AG_REFERANSLAR — İş Ortakları / Müşteriler
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_referanslar` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ad` VARCHAR(150) NOT NULL,
    `logo` VARCHAR(255) DEFAULT NULL,
    `web_url` VARCHAR(255) DEFAULT NULL,
    `sirket_id` INT UNSIGNED DEFAULT NULL,
    `kategori` VARCHAR(50) DEFAULT 'musteri',
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_active_sort` (`is_active`, `sort_order`),
    KEY `idx_sirket` (`sirket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 17) AG_SERTIFIKALAR — Belgeler / Sertifikalar
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_sertifikalar` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ad` VARCHAR(200) NOT NULL,
    `aciklama` TEXT DEFAULT NULL,
    `gorsel` VARCHAR(255) DEFAULT NULL,
    `pdf_dosya` VARCHAR(255) DEFAULT NULL,
    `veren_kurum` VARCHAR(150) DEFAULT NULL,
    `tarih` DATE DEFAULT NULL,
    `gecerlilik` DATE DEFAULT NULL,
    `sirket_id` INT UNSIGNED DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_active_sort` (`is_active`, `sort_order`),
    KEY `idx_sirket` (`sirket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 18) AG_SOSYAL_MEDYA — Sosyal Medya Linkleri
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_sosyal_medya` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `platform` VARCHAR(50) NOT NULL,
    `url` VARCHAR(255) NOT NULL,
    `ikon_class` VARCHAR(100) DEFAULT NULL,
    `takipci_sayisi` VARCHAR(20) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 19) AG_NEWSLETTER — Bülten Aboneleri
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_newsletter` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(150) NOT NULL,
    `ad` VARCHAR(100) DEFAULT NULL,
    `confirmation_token` VARCHAR(64) DEFAULT NULL,
    `is_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `ip_adresi` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `confirmed_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 20) AG_AUDIT_LOG — Güvenlik / Aktivite Logu
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_audit_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity` VARCHAR(50) DEFAULT NULL,
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `old_value` LONGTEXT DEFAULT NULL,
    `new_value` LONGTEXT DEFAULT NULL,
    `ip_adresi` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `severity` ENUM('info','warning','danger','critical') NOT NULL DEFAULT 'info',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_entity` (`entity`, `entity_id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 21) AG_LOGIN_ATTEMPTS — Login Deneme Takibi (Rate Limit)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_login_attempts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(100) DEFAULT NULL,
    `ip_adresi` VARCHAR(45) NOT NULL,
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ip_created` (`ip_adresi`, `created_at`),
    KEY `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 22) AG_SEO_REDIRECTS — 301 Yönlendirmeler
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_seo_redirects` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kaynak_url` VARCHAR(500) NOT NULL,
    `hedef_url` VARCHAR(500) NOT NULL,
    `tip` ENUM('301','302','307') NOT NULL DEFAULT '301',
    `hit_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_kaynak` (`kaynak_url`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 23) AG_VERSIONS — Güncelleme Sistemi (GitHub Releases)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ag_versions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `version` VARCHAR(20) NOT NULL,
    `release_name` VARCHAR(150) DEFAULT NULL,
    `release_notes` LONGTEXT DEFAULT NULL,
    `migration_executed` TINYINT(1) NOT NULL DEFAULT 0,
    `migration_log` LONGTEXT DEFAULT NULL,
    `installed_by` INT UNSIGNED DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'success',
    `duration_ms` INT UNSIGNED DEFAULT NULL,
    `installed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_version` (`version`),
    KEY `idx_installed_at` (`installed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA (varsayılan veriler)
-- ============================================================

-- Varsayılan sektörler
INSERT IGNORE INTO `ag_sektorler` (`slug`, `roman_no`, `ad`, `alt_baslik`, `vurgu_renk`, `sort_order`, `is_active`, `is_featured`) VALUES
('demir-celik', 'I', 'Demir & Çelik', 'Endüstriyel Üretimin Temeli', '#8B0000', 10, 1, 1),
('yazilim-teknoloji', 'II', 'Yazılım & Teknoloji', 'Dijital Dönüşümün Mimarı', '#0066FF', 20, 1, 1),
('hosting-bulut', 'III', 'Hosting & Bulut', 'Sınırsız Dijital Altyapı', '#00B894', 30, 1, 0),
('e-ticaret', 'IV', 'E-Ticaret', 'Yeni Nesil Ticaret Platformları', '#FF6B35', 40, 1, 1),
('3d-uretim', 'V', '3D Üretim & Aditif İmalat', 'Geleceğin Üretim Teknolojisi', '#6C5CE7', 50, 1, 0),
('sigorta-finans', 'VI', 'Sigorta & Finansal Aracılık', 'Güvenin Güvencesi', '#003366', 60, 1, 0),
('dijital-yayincilik', 'VII', 'Dijital Yayıncılık', 'Bilginin Kraliyet Merkezi', '#C9A961', 70, 1, 0),
('gida-icecek', 'VIII', 'Gıda & İçecek', 'Topraktan Sofraya Kalite', '#27AE60', 80, 1, 0),
('ziraat-aletleri', 'IX', 'Ziraat Aletleri & Tarım İlaçları', 'Verimli Tarımın Anahtarı', '#7C5E2C', 90, 1, 0),
('lojistik', 'X', 'Lojistik & Taşımacılık', 'Yükün İpek Yolu', '#34495E', 100, 1, 0);

-- Varsayılan şirketler
INSERT IGNORE INTO `ag_sirketler` (`sektor_id`, `slug`, `unvan`, `kisa_unvan`, `slogan`, `merkez_sehir`, `web_url`, `kurulus_yili`, `sort_order`, `durum`, `is_featured`) VALUES
(1, 'tekcan-metal', 'Tekcan Metal Sanayi ve Ticaret A.Ş.', 'Tekcan Metal', 'Çelikten ötesi', 'Konya', 'https://tekcanmetal.com', 2010, 10, 'aktif', 1),
(2, 'codega', 'CODEGA Yazılım ve Bilişim Teknolojileri', 'CODEGA', 'Yazılımı yeniden tanımlamak', 'Konya', 'https://codega.com.tr', 2018, 10, 'aktif', 1),
(3, 'codega-hosting', 'CODEGA Hosting & Bulut Hizmetleri', 'CODEGA Hosting', 'Sınırsız altyapı, sıfır endişe', 'Konya', 'https://codega.com.tr/hosting', 2020, 10, 'aktif', 0),
(4, 'akilli-ticaret', 'Akıllı Ticaret E-Ticaret Çözümleri', 'Akıllı Ticaret', 'Akıllıca satış, hızlı büyüme', 'Konya', NULL, 2021, 10, 'aktif', 1),
(5, 'minya-3d', 'Minya 3D Aditif İmalat', 'Minya 3D', 'Hayal ettiğiniz her şey, üç boyutta', 'Konya', 'https://minya3d.com', 2022, 10, 'aktif', 0),
(6, 'sbb-sigorta', 'SBB Sigorta Aracılık Hizmetleri', 'SBB Sigorta', 'Geleceğinizi güvence altına alın', 'Konya', 'https://sbbsigorta.com', 2023, 10, 'aktif', 0),
(7, 'xnews', 'XNews Dijital Haber Platformu', 'XNews', 'Bilginin kraliyet merkezi', 'Konya', 'https://xnews.com.tr', 2024, 10, 'aktif', 0),
(8, 'aksoy-gida', 'Aksoy Gıda ve İçecek', 'Aksoy Gıda', 'Topraktan sofraya, doğal kalite', 'Konya', NULL, 2025, 10, 'gelistiriliyor', 0),
(9, 'aksoy-ziraat', 'Aksoy Ziraat Aletleri ve Tarım İlaçları', 'Aksoy Ziraat', 'Verimli tarımın güvenilir ortağı', 'Konya', NULL, 2025, 10, 'gelistiriliyor', 0);

-- Varsayılan haber kategorileri
INSERT IGNORE INTO `ag_haber_kategori` (`slug`, `ad`, `renk`, `sort_order`) VALUES
('kurumsal', 'Kurumsal', '#0A0E1A', 10),
('basin', 'Basın', '#C9A961', 20),
('yatirim', 'Yatırım', '#27AE60', 30),
('etkinlik', 'Etkinlik', '#6C5CE7', 40),
('odul', 'Ödül & Başarı', '#8B0000', 50),
('surdurulebilirlik', 'Sürdürülebilirlik', '#00B894', 60);

-- Varsayılan rakamlar
INSERT IGNORE INTO `ag_rakamlar` (`etiket`, `deger`, `birim`, `prefix`, `aciklama`, `sort_order`) VALUES
('Yıllık Deneyim', '15', '+', NULL, 'Çeyrek asra yaklaşan tecrübemiz', 10),
('Grup Şirketi', '9', NULL, NULL, 'Farklı sektörlerde aktif iştirak', 20),
('Çalışan', '250', '+', NULL, 'Aksoy ailesinin gücü', 30),
('Müşteri', '12.000', '+', NULL, 'Türkiye genelinde aktif müşteri', 40),
('Şehir', '81', NULL, NULL, 'Türkiye geneline yayılmış hizmet ağı', 50),
('Ülke İhracat', '14', NULL, NULL, 'Üç kıtada Aksoy varlığı', 60);

-- Varsayılan zaman çizgisi
INSERT IGNORE INTO `ag_zaman_cizgisi` (`yil`, `baslik`, `aciklama`, `is_milestone`, `sort_order`) VALUES
(2010, 'Tekcan Metal Kuruluşu', 'Aksoy Group’un endüstriyel yolculuğu Konya’da Tekcan Metal ile başladı.', 1, 10),
(2018, 'CODEGA Yazılım', 'Dijital dönüşüm vizyonu CODEGA markasıyla hayata geçirildi.', 1, 20),
(2020, 'CODEGA Hosting', 'Bulut altyapı hizmetleri portföye eklendi.', 0, 30),
(2021, 'Akıllı Ticaret', 'E-ticaret çözümleri sektörü genişletildi.', 0, 40),
(2022, 'Minya 3D', '3D üretim ve aditif imalat alanına giriş yapıldı.', 0, 50),
(2023, 'SBB Sigorta', 'Finansal aracılık hizmetleri başlatıldı.', 0, 60),
(2024, 'XNews Yayıncılık', 'Dijital haber platformu XNews kuruldu.', 0, 70),
(2025, 'Gıda & Ziraat Yatırımları', 'İki yeni sektörel yatırımla portföy 9 sektöre ulaştı.', 1, 80),
(2026, 'Lojistik Sektörüne Açılış', 'Lojistik & taşımacılık sektörünün ilave edilmesiyle topluluk 10 sektöre ulaştı.', 1, 90);

-- Varsayılan iletişim ayarları
INSERT IGNORE INTO `ag_settings` (`setting_key`, `setting_value`, `setting_group`, `setting_type`, `label`, `sort_order`, `is_public`) VALUES
('site_title', 'AKSOY GROUP', 'general', 'text', 'Site Adı', 10, 1),
('site_tagline', 'Türkiye’nin Çok Sektörlü Hizmetler Topluluğu', 'general', 'text', 'Slogan', 20, 1),
('site_description', 'Demir-çelikten yazılıma, sigortadan tarıma, lojistikten 3D üretime — on sektörde geleceği tasarlayan bir hizmetler topluluğu.', 'general', 'textarea', 'Açıklama', 30, 1),
('site_logo', '/assets/img/logo.svg', 'general', 'image', 'Logo', 40, 1),
('site_logo_dark', '/assets/img/logo-dark.svg', 'general', 'image', 'Dark Logo', 50, 1),
('site_favicon', '/assets/img/favicon.ico', 'general', 'image', 'Favicon', 60, 1),
('contact_email', 'info@aksoy.web.tr', 'iletisim', 'text', 'E-posta', 10, 1),
('contact_phone', '+90 332 000 00 00', 'iletisim', 'text', 'Telefon', 20, 1),
('contact_address', 'Konya, Türkiye', 'iletisim', 'textarea', 'Adres', 30, 1),
('contact_map_lat', '37.8746', 'iletisim', 'text', 'Harita Lat', 40, 1),
('contact_map_lng', '32.4932', 'iletisim', 'text', 'Harita Lng', 50, 1),
('seo_meta_keywords', 'aksoy group, aksoy hizmetler topluluğu, konya hizmetler grubu, tekcan metal, codega, minya 3d, sbb sigorta, xnews, lojistik', 'seo', 'textarea', 'Meta Keywords', 10, 0),
('smtp_host', '', 'mail', 'text', 'SMTP Host', 10, 0),
('smtp_port', '465', 'mail', 'number', 'SMTP Port', 20, 0),
('smtp_user', '', 'mail', 'text', 'SMTP User', 30, 0),
('smtp_pass', '', 'mail', 'text', 'SMTP Password', 40, 0),
('smtp_secure', 'ssl', 'mail', 'text', 'SMTP Secure (ssl/tls)', 50, 0),
('smtp_from_email', 'noreply@aksoy.web.tr', 'mail', 'text', 'Gönderen E-posta', 60, 0),
('smtp_from_name', 'AKSOY GROUP', 'mail', 'text', 'Gönderen Ad', 70, 0),
('github_repo', 'codegatr/aksoygroup', 'sistem', 'text', 'GitHub Repo (owner/repo)', 10, 0),
('github_token', '', 'sistem', 'text', 'GitHub PAT', 20, 0),
('current_version', '1.0.0', 'sistem', 'text', 'Mevcut Sürüm', 30, 0),
('maintenance_mode', '0', 'sistem', 'boolean', 'Bakım Modu', 40, 0),
('analytics_code', '', 'seo', 'textarea', 'Google Analytics', 20, 0);

-- Varsayılan sosyal medya
INSERT IGNORE INTO `ag_sosyal_medya` (`platform`, `url`, `ikon_class`, `sort_order`, `is_active`) VALUES
('linkedin', 'https://linkedin.com/company/aksoy-group', 'fab fa-linkedin-in', 10, 1),
('instagram', 'https://instagram.com/aksoygroup', 'fab fa-instagram', 20, 1),
('twitter', 'https://twitter.com/aksoygroup', 'fab fa-x-twitter', 30, 1),
('youtube', 'https://youtube.com/@aksoygroup', 'fab fa-youtube', 40, 1),
('facebook', 'https://facebook.com/aksoygroup', 'fab fa-facebook-f', 50, 0);

-- Varsayılan sayfalar
INSERT IGNORE INTO `ag_pages` (`slug`, `baslik`, `template`, `menu_konumu`, `sort_order`, `is_active`, `is_system`) VALUES
('hakkimizda', 'Hakkımızda', 'hakkimizda', 'header', 10, 1, 1),
('yonetim-kurulu', 'Yönetim Kurulu', 'yonetim_kurulu', 'header', 20, 1, 1),
('surdurulebilirlik', 'Sürdürülebilirlik', 'default', 'header', 30, 1, 1),
('kariyer', 'Kariyer', 'kariyer', 'header', 40, 1, 1),
('basin', 'Basın Odası', 'basin', 'header', 50, 1, 1),
('iletisim', 'İletişim', 'iletisim', 'header', 60, 1, 1),
('kvkk', 'KVKK Aydınlatma Metni', 'default', 'footer', 10, 1, 1),
('cerez-politikasi', 'Çerez Politikası', 'default', 'footer', 20, 1, 1),
('gizlilik', 'Gizlilik Politikası', 'default', 'footer', 30, 1, 1),
('kullanim-kosullari', 'Kullanım Koşulları', 'default', 'footer', 40, 1, 1);

-- İlk versiyon kaydı
INSERT IGNORE INTO `ag_versions` (`version`, `release_name`, `release_notes`, `migration_executed`) VALUES
('1.0.0', 'Genesis — Aksoy Group Launch', 'İlk yayın: 23 tablo, 10 sektör, 9 iştirak, dark editorial luxury tema, GitHub Releases güncelleme sistemi.', 1);

-- ============================================================
-- BİTİŞ — v1.0.0 Migration
-- ============================================================
