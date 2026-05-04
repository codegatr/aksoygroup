-- ════════════════════════════════════════════════════════
-- AKSOY GROUP — v1.0.8 Migration
-- - Yönetim Kurulu admin modülü + public sayfa
-- - Statik sayfalara (Hakkımızda, KVKK, Gizlilik vs.) zengin Türkçe içerik
-- - Header'da Kurumsal dropdown menüsü
-- - 'yonetim-kurulu' slug'ı ag_pages'den gizli yapıldı (özel sayfa kullanılıyor)
-- ════════════════════════════════════════════════════════

-- 1) ag_pages: yonetim-kurulu artık özel sayfa olduğu için menüden gizle
UPDATE `ag_pages` SET `menu_konumu` = 'gizli' WHERE `slug` = 'yonetim-kurulu';

-- 2) HAKKIMIZDA — zengin Türkçe içerik
UPDATE `ag_pages` SET
    `alt_baslik` = 'On sektörde, tek vizyon altında',
    `meta_description` = 'Aksoy Group — demir-çelikten yazılıma, sigortadan e-ticarete on farklı sektörde faaliyet gösteren Konya merkezli hizmetler topluluğu.',
    `icerik` = '<p class="lead">Aksoy Group, on farklı sektörde uzmanlaşmış iştirakleriyle Türkiye''nin köklü hizmetler topluluklarından biridir. Konya merkezli faaliyet gösteren topluluğumuz; üretim, teknoloji, finans ve hizmet sektörlerinde verdiği değerle sürdürülebilir büyümeyi hedefler.</p>

<h2>Köklü Geçmiş, Çağdaş Vizyon</h2>
<p>Aksoy Group; Tekcan Metal''den CODEGA Yazılım''a, SBB Sigorta''dan XNews medyasına kadar uzanan iştirak portföyüyle, geleneksel sektörlerin dijital dönüşümüne öncülük eder. Her bir şirketimiz kendi alanında derin uzmanlığa sahipken, topluluk olarak ortak değerlerimizle hareket ederiz.</p>

<h2>Vizyon</h2>
<blockquote>Endüstriyel üretim derinliğini dijital çağın hızıyla buluşturan, Türkiye''nin en güvenilir ve sürdürülebilir hizmetler topluluğu olmak.</blockquote>

<h2>Misyon</h2>
<p>Müşterilerimize, çalışanlarımıza ve paydaşlarımıza uzun vadeli değer üretmek için faaliyet gösterdiğimiz her sektörde:</p>
<ul>
    <li><strong>Kalite ve güveni</strong> en temel değer olarak kabul ederiz.</li>
    <li><strong>Sürdürülebilirliği</strong> kısa vadeli kâr beklentilerinin önünde tutarız.</li>
    <li><strong>Teknolojik dönüşümü</strong> tüm iştiraklerimize yayarız.</li>
    <li><strong>İnsan kaynağına yatırımı</strong> büyümenin temeli sayarız.</li>
</ul>

<h2>Değerlerimiz</h2>
<h3>Şeffaflık</h3>
<p>Her seviyede; çalışanlarımızla, iş ortaklarımızla ve müşterilerimizle açık iletişim kurarız. Verdiğimiz sözün arkasında dururuz.</p>

<h3>Mükemmellik</h3>
<p>Yaptığımız her işte kalite standardını yükseltmek için sürekli iyileştirme prensibiyle çalışırız.</p>

<h3>Sürdürülebilirlik</h3>
<p>Çevresel, sosyal ve ekonomik sürdürülebilirliği iş süreçlerimizin merkezine koyarız.</p>

<h3>Yenilikçilik</h3>
<p>Geleneksel sektörlerde bile dijital dönüşümü ve Ar-Ge yatırımını öncelikli görürüz.</p>

<h2>Coğrafyamız</h2>
<p>Konya merkezli yapımızla Anadolu''nun üretim gücünü temsil ederken, dijital iştiraklerimiz aracılığıyla Türkiye''nin tüm illerine ve uluslararası pazarlara hizmet veririz.</p>',
    `updated_at` = NOW()
WHERE `slug` = 'hakkimizda';

-- 3) SÜRDÜRÜLEBİLİRLİK
UPDATE `ag_pages` SET
    `alt_baslik` = 'Bugünden geleceğe sorumlu üretim',
    `meta_description` = 'Aksoy Group sürdürülebilirlik politikası — çevresel, sosyal ve ekonomik etki yönetimi.',
    `icerik` = '<p class="lead">Sürdürülebilirlik bizim için bir pazarlama söylemi değil, iş yapış biçimimizin temelidir. Her iştirakimiz, faaliyet gösterdiği sektörde çevresel ve sosyal sorumluluk standartlarını gözetir.</p>

<h2>Çevresel Sorumluluk</h2>
<p>Demir-çelik ve hurda işleme tesislerimizden e-ticaret operasyonlarımıza kadar tüm faaliyet alanlarımızda karbon ayak izimizi düzenli olarak ölçer ve azaltma hedeflerimizi şeffaf şekilde paylaşırız. Geri dönüşüm ve döngüsel ekonomi prensiplerini iş modellerimize entegre ettik.</p>

<h2>Sosyal Etki</h2>
<p>Topluluğumuzda 500+ çalışan, her gün ailelerine ekmek götürmenin ötesinde; kariyerlerini geliştirebilecekleri, kendilerini güvende hissedebilecekleri bir iş ortamında görev yapar. Çalışan eğitimine, iş güvenliğine ve fırsat eşitliğine yatırımımız sürekli büyümektedir.</p>

<h2>Yönetişim</h2>
<p>Şeffaf yönetişim; etik kurallarımız, iç denetim mekanizmalarımız ve bağımsız yönetim kurulu üyelerimizle desteklenir. Tedarikçi seçiminden rekabet uyumuna kadar tüm süreçlerimizde uluslararası kurumsal yönetim standartlarını referans alırız.</p>',
    `updated_at` = NOW()
WHERE `slug` = 'surdurulebilirlik';

-- 4) KARİYER
UPDATE `ag_pages` SET
    `alt_baslik` = 'Geleceği birlikte inşa edelim',
    `meta_description` = 'Aksoy Group kariyer fırsatları — on sektörde, beş yüzü aşkın çalışana ev sahipliği yapan topluluğumuzda yerinizi alın.',
    `icerik` = '<p class="lead">On farklı sektörde faaliyet gösteren Aksoy Group ailesi, alanında uzmanlaşmak isteyen yetenekli profesyonelleri her zaman bekler. Topluluğumuzda mühendislikten yazılıma, finanstan operasyona kadar geniş bir yelpazede kariyer fırsatları bulabilirsiniz.</p>

<h2>Neden Aksoy Group?</h2>
<ul>
    <li><strong>Çoklu sektör deneyimi:</strong> Tek bir grup içinde demir-çelikten yazılıma kadar farklı dinamiklerde tecrübe kazanabilirsiniz.</li>
    <li><strong>Sürekli eğitim:</strong> Yıl boyu teknik ve liderlik eğitim programlarımıza erişim.</li>
    <li><strong>Uzun vadeli kariyer:</strong> Aile ortamında, ortalama çalışan kıdemi 7 yılın üzerinde.</li>
    <li><strong>Anadolu''nun üretim merkezinde:</strong> Konya merkezli, yaşam kalitesi yüksek bir konumda kariyer.</li>
</ul>

<h2>Açık Pozisyonlar</h2>
<p>Aktif iş ilanlarımız ve başvuru formu için kariyer modülümüz yakında devreye alınacaktır. Bu süreçte CV''nizi doğrudan iletişim formu üzerinden iletebilirsiniz.</p>',
    `updated_at` = NOW()
WHERE `slug` = 'kariyer';

-- 5) BASIN ODASI
UPDATE `ag_pages` SET
    `alt_baslik` = 'Topluluğumuzdan haberler',
    `meta_description` = 'Aksoy Group basın odası — kurumsal duyurular, basın bültenleri ve medya kaynakları.',
    `icerik` = '<p class="lead">Aksoy Group ve iştiraklerine ilişkin basın bültenleri, kurumsal duyurular ve medya kaynaklarına buradan ulaşabilirsiniz.</p>

<h2>Basın İletişim</h2>
<p>Medya talepleriniz, röportaj başvurularınız ve kurumsal bilgi istekleri için iletişim sayfamız üzerinden bizimle iletişime geçebilirsiniz. Basın bültenleri arşivimiz yakında bu sayfada yayında olacaktır.</p>

<h2>Logo ve Marka Varlıkları</h2>
<p>Aksoy Group ve iştiraklerine ait logo dosyaları, kurumsal kimlik kılavuzu ve marka varlıkları için iletişim formundan başvurmanız yeterli. Talepleriniz 24 saat içinde değerlendirilir.</p>',
    `updated_at` = NOW()
WHERE `slug` = 'basin';

-- 6) KVKK
UPDATE `ag_pages` SET
    `alt_baslik` = '6698 sayılı Kanun kapsamında bilgilendirme',
    `meta_description` = 'Aksoy Group KVKK aydınlatma metni — kişisel verilerinizin işlenme amacı, kapsamı ve haklarınız.',
    `icerik` = '<p class="lead">6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) kapsamında, veri sorumlusu sıfatıyla Aksoy Group ve iştirakleri tarafından işlenen kişisel verilerinize ilişkin aydınlatma metnidir.</p>

<h2>1. Veri Sorumlusunun Kimliği</h2>
<p>Aksoy Group ve bağlı iştirakler (Tekcan Metal, CODEGA Yazılım, SBB Sigorta, XNews, Akıllı Ticaret, Minya 3D ve diğer iştirakler) "veri sorumlusu" sıfatıyla hareket etmektedir.</p>

<h2>2. Kişisel Verilerin İşlenme Amaçları</h2>
<ul>
    <li>Hizmetlerimizin sunulması ve sözleşme yükümlülüklerinin yerine getirilmesi</li>
    <li>Yasal yükümlülüklerin yerine getirilmesi (vergi, SGK, ticari mevzuat)</li>
    <li>Müşteri ilişkileri yönetimi ve memnuniyet ölçümü</li>
    <li>İletişim formu ve başvuruların değerlendirilmesi</li>
    <li>İş güvenliğinin sağlanması</li>
</ul>

<h2>3. Aktarılabileceği Taraflar</h2>
<p>Kişisel verileriniz; yasal yükümlülükler kapsamında yetkili kamu kurumlarına, hizmet aldığımız tedarikçilere (örn. e-fatura, kargo, ödeme altyapısı sağlayıcıları) ve yurt içi grup şirketlerine aktarılabilir.</p>

<h2>4. Toplama Yöntemi ve Hukuki Sebep</h2>
<p>Kişisel verileriniz; web sitemiz üzerinden iletişim formları, çağrı merkezi, e-posta ve fiziksel ortamda doldurulan formlar aracılığıyla toplanır. Hukuki sebep olarak; sözleşmenin ifası, yasal yükümlülük ve açık rıza esaslarından biri uygulanır.</p>

<h2>5. KVKK Kapsamındaki Haklarınız</h2>
<p>KVKK''nın 11. maddesi uyarınca; kişisel verilerinizin işlenip işlenmediğini öğrenme, işlenmişse bilgi talep etme, düzeltme, silme veya yok edilmesini isteme, aktarıldığı üçüncü kişileri öğrenme ve zarara uğramanız halinde tazminat talep etme haklarına sahipsiniz.</p>

<h2>6. Başvuru</h2>
<p>Haklarınızı kullanmak için <a href="/iletisim">iletişim sayfamız</a> üzerinden veya e-posta yoluyla başvurabilirsiniz. Başvurularınız azami 30 gün içinde sonuçlandırılır.</p>

<p style="margin-top:48px;padding-top:24px;border-top:1px solid var(--line);font-size:14px;color:var(--text-mute)">Bu metin bilgilendirme amaçlıdır. Hukuki danışmanlık niteliği taşımaz. Güncellemeler için bu sayfayı periyodik olarak ziyaret edebilirsiniz.</p>',
    `updated_at` = NOW()
WHERE `slug` = 'kvkk';

-- 7) ÇEREZ POLİTİKASI
UPDATE `ag_pages` SET
    `alt_baslik` = 'Çerez kullanımı ve tercihleriniz',
    `meta_description` = 'Aksoy Group çerez politikası — kullanılan çerezler, amaçları ve tarayıcı ayarlarıyla yönetimi.',
    `icerik` = '<p class="lead">aksoy.web.tr ve iştiraklerimize ait alan adları üzerinde kullanıcı deneyimini iyileştirmek, site performansını ölçmek ve yasal yükümlülükleri yerine getirmek için çerezler kullanılmaktadır.</p>

<h2>Çerez Nedir?</h2>
<p>Çerezler; ziyaret ettiğiniz web siteleri tarafından tarayıcınıza gönderilen ve sonraki ziyaretlerinizde sunucuya geri iletilen küçük metin dosyalarıdır. Sitemizi çerezleri kabul etmeden de kullanabilirsiniz; ancak bazı işlevler kısıtlanabilir.</p>

<h2>Kullandığımız Çerez Türleri</h2>
<h3>Zorunlu Çerezler</h3>
<p>Site''nin temel işlevlerinin çalışması için gereklidir (oturum yönetimi, CSRF koruması). Devre dışı bırakılamaz.</p>

<h3>Performans / Analitik Çerezler</h3>
<p>Ziyaretçi sayısı, sayfa görüntüleme süreleri ve gezinme davranışı gibi anonim verileri toplar. Site içeriğini iyileştirmek için kullanılır.</p>

<h3>İşlevsellik Çerezleri</h3>
<p>Tercihlerinizi (dil, görüntüleme ayarları) hatırlamak için kullanılır.</p>

<h2>Çerez Tercihlerinizi Yönetme</h2>
<p>Tarayıcınızın ayarlarından çerezleri silebilir, gelecekte saklanmasını engelleyebilirsiniz. Detaylı bilgi için tarayıcınızın yardım sayfasına bakabilirsiniz: Chrome, Firefox, Safari, Edge.</p>',
    `updated_at` = NOW()
WHERE `slug` = 'cerez-politikasi';

-- 8) GİZLİLİK POLİTİKASI
UPDATE `ag_pages` SET
    `alt_baslik` = 'Verilerinizin korunmasına dair taahhüdümüz',
    `meta_description` = 'Aksoy Group gizlilik politikası — kişisel verilerinizin korunma standartları ve taahhütlerimiz.',
    `icerik` = '<p class="lead">Aksoy Group olarak ziyaretçilerimizin, müşterilerimizin ve çalışanlarımızın gizlilik haklarına en üst düzeyde saygı duyarız. Bu politika, veri toplama ve işleme uygulamalarımızı şeffaf şekilde açıklamaktadır.</p>

<h2>Topladığımız Bilgiler</h2>
<ul>
    <li><strong>Sizin sağladığınız bilgiler:</strong> İletişim formu, başvuru ve kayıt formları aracılığıyla aktif olarak sağladığınız ad, soyad, e-posta, telefon, mesaj içeriği gibi bilgiler.</li>
    <li><strong>Otomatik toplanan bilgiler:</strong> IP adresi, tarayıcı tipi, ziyaret zamanları, ziyaret edilen sayfalar gibi teknik veriler.</li>
</ul>

<h2>Bilgilerin Kullanımı</h2>
<p>Topladığımız bilgileri yalnızca; size hizmet sunmak, taleplerinize yanıt vermek, yasal yükümlülükleri yerine getirmek ve hizmetlerimizi iyileştirmek amacıyla kullanırız.</p>

<h2>Bilgi Paylaşımı</h2>
<p>Kişisel bilgilerinizi açık rızanız olmaksızın üçüncü taraflara <strong>satmayız, kiralamaz</strong> veya pazarlama amacıyla paylaşmayız. İstisnalar yalnızca yasal zorunluluklar (mahkeme kararı, kamu otoritesi talebi) ve hizmet sunmak için zorunlu tedarikçi paylaşımlarıdır.</p>

<h2>Veri Güvenliği</h2>
<p>Kişisel verileriniz; SSL/TLS şifreleme, güçlü erişim kontrolleri, düzenli güvenlik denetimleri ve yedeklemeler ile korunur. Sızıntı veya yetkisiz erişim durumunda KVKK''nın öngördüğü 72 saatlik bildirim yükümlülüğüne uyarız.</p>

<h2>Veri Saklama Süresi</h2>
<p>Kişisel verileriniz; işleme amacının gerektirdiği süre boyunca veya yasal saklama yükümlülüklerinin sona ermesine kadar saklanır. Süre sonunda veriler silinir, yok edilir veya anonim hale getirilir.</p>',
    `updated_at` = NOW()
WHERE `slug` = 'gizlilik';

-- 9) KULLANIM KOŞULLARI
UPDATE `ag_pages` SET
    `alt_baslik` = 'Web sitesi kullanım kuralları',
    `meta_description` = 'aksoy.web.tr kullanım koşulları — web sitemizi ziyaret eden kullanıcıların hak ve yükümlülükleri.',
    `icerik` = '<p class="lead">aksoy.web.tr alan adı altında yayınlanan bu web sitesini ("Site") ziyaret etmek ve içeriklerinden faydalanmak için aşağıdaki kullanım koşullarını kabul etmiş sayılırsınız.</p>

<h2>1. Genel Şartlar</h2>
<p>Bu Site Aksoy Group tarafından bilgilendirme amaçlı olarak işletilmektedir. Sitedeki bilgilerin doğruluğunu sağlamak için makul tüm özen gösterilmiş olsa da; içerik tamamen bilgilendirme niteliğindedir, hukuki, finansal veya teknik bir taahhüt oluşturmaz.</p>

<h2>2. Fikri Mülkiyet Hakları</h2>
<p>Site''de yer alan tüm metinler, görseller, logolar, marka unsurları ve yazılım bileşenleri Aksoy Group ve iştiraklerinin fikri mülkiyetidir. İzinsiz kopyalanamaz, çoğaltılamaz, dağıtılamaz veya ticari amaçla kullanılamaz.</p>

<h2>3. Kullanıcı Sorumluluğu</h2>
<p>Site''yi ziyaret eden kullanıcılar; içeriği yalnızca kişisel ve ticari olmayan amaçlarla kullanmayı, otomatik tarama (bot, scraper) yapmamayı, güvenlik açıkları aramamayı taahhüt eder.</p>

<h2>4. Sorumluluğun Sınırı</h2>
<p>Site''nin teknik nedenlerle erişilemez olması, hatalı bilgi içermesi veya üçüncü taraf bağlantılarına yönlendirme yapması durumunda Aksoy Group herhangi bir sorumluluk kabul etmez.</p>

<h2>5. Değişiklikler</h2>
<p>Aksoy Group bu kullanım koşullarını önceden bildirim yapmaksızın değiştirme hakkını saklı tutar. Değişikliklerden sonra Site''nin kullanılmaya devam edilmesi, güncel koşulların kabul edildiği anlamına gelir.</p>

<h2>6. Uygulanacak Hukuk</h2>
<p>Bu kullanım koşullarından doğabilecek uyuşmazlıklarda Türkiye Cumhuriyeti yasaları geçerlidir ve Konya Mahkemeleri ile İcra Daireleri yetkilidir.</p>',
    `updated_at` = NOW()
WHERE `slug` = 'kullanim-kosullari';

-- 10) Versiyon kaydı
INSERT IGNORE INTO `ag_versions` (`version`, `release_name`, `release_notes`, `migration_executed`, `status`)
VALUES ('1.0.8', 'Yönetim Kurulu + Kurumsal İçerik',
    'Yönetim Kurulu admin modülü + public sayfa, Kurumsal dropdown menüsü, statik sayfalara (Hakkımızda, KVKK, Gizlilik, Kullanım Koşulları, Sürdürülebilirlik, Kariyer, Basın, Çerez) zengin Türkçe içerik seed.',
    1, 'success');

UPDATE `ag_settings` SET `setting_value` = '1.0.8' WHERE `setting_key` = 'current_version';
