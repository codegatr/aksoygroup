# AKSOY GROUP — Hizmetler Topluluğu Platformu

[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3+-777bb4)](https://www.php.net/)
[![MariaDB 10.6+](https://img.shields.io/badge/MariaDB-10.6+-003545)](https://mariadb.org/)
[![License](https://img.shields.io/badge/license-Proprietary-c9a961)](.)

> **Aksoy Group** — Demir-çelikten yazılıma, sigortadan tarıma, lojistikten 3D üretime — **on sektörde** geleceği tasarlayan bir **hizmetler topluluğu**. Bu repo, [aksoy.web.tr](https://aksoy.web.tr) için PHP 8.3 ile sıfırdan yazılmış kurumsal CMS platformudur.

---

## 🏛️ Sektörler

| № | Sektör | Slogan |
|---|---|---|
| I | Demir & Çelik | Endüstriyel Üretimin Temeli |
| II | Yazılım & Teknoloji | Dijital Dönüşümün Mimarı |
| III | Hosting & Bulut | Sınırsız Dijital Altyapı |
| IV | E-Ticaret | Yeni Nesil Ticaret Platformları |
| V | 3D Üretim & Aditif İmalat | Geleceğin Üretim Teknolojisi |
| VI | Sigorta & Finansal Aracılık | Güvenin Güvencesi |
| VII | Dijital Yayıncılık | Bilginin Kraliyet Merkezi |
| VIII | Gıda & İçecek | Topraktan Sofraya Kalite |
| IX | Ziraat Aletleri & Tarım İlaçları | Verimli Tarımın Anahtarı |
| **X** | **Lojistik & Taşımacılık** | **Yükün İpek Yolu** |

## 🛠️ Teknik Yığın

- **PHP 8.3+** · zero-framework, custom architecture
- **MariaDB 10.6+** · UTF-8mb4, InnoDB, prefix `ag_`
- **DirectAdmin / LiteSpeed** · shared hosting uyumlu
- **GitHub Releases** tabanlı otomatik güncelleme sistemi
- **Argon2id** şifre hashleme + opsiyonel TOTP 2FA
- **CSRF** stateless HMAC, session olmadan da doğrulama
- **Audit log** her admin aksiyonu için seviye bazlı (info/warning/danger/critical)

## 🚀 Kurulum

```bash
# 1) Bu repo'yu indir
git clone https://github.com/codegatr/aksoygroup.git public_html

# 2) Tarayıcıda kurulum sihirbazını aç
https://aksoy.web.tr/setup.php

# 3) DB bilgileri → Migrasyon → Admin oluştur
# 4) setup.php dosyasını sil
```

## 📂 Proje Yapısı

```
aksoygroup/
├── includes/           # Çekirdek sınıflar (DB, Auth, CSRF, Mailer, Updater)
├── migrations/         # Sıralı SQL migration dosyaları
│   ├── v1.0.0.sql      # Genesis — 23 tablo, 10 sektör seed
│   └── v1.0.1.sql      # Lojistik + Hizmetler Topluluğu
├── yonetim/            # Admin paneli
│   ├── modules/        # CRUD modülleri (sektör, şirket, haber, vs.)
│   ├── _layout.php     # Master layout
│   └── _helpers.php    # Helpers, ikonlar, paginate
├── assets/             # CSS / JS / fonts
├── uploads/            # Kullanıcı yüklemeleri (.htaccess korumalı)
├── manifest.json       # Versiyon manifest'i
└── setup.php           # 3-adım kurulum sihirbazı
```

## 📦 Sürüm Geçmişi

| Sürüm | Tarih | İçerik |
|---|---|---|
| **v1.0.1** | 2026-05-04 | Lojistik sektörü (X), marka: Hizmetler Topluluğu |
| **v1.0.0** | 2026-05-04 | Genesis Launch — 23 tablo, 9 sektör, dark editorial luxury tema |

## 🤝 Yapımcı

[**CODEGA**](https://codega.com.tr) · Konya, Türkiye

---

© 2026 AKSOY GROUP — Tüm hakları saklıdır.
