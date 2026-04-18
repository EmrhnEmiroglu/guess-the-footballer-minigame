=== Oyuncu Tahmin Oyunu ===
Contributors: sporkulis
Tags: oyun, spor, wordle, tahmin, oyuncu, günlük
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Blurlu görsel ve Wordle mekanikleriyle günlük 5 oyunculu tahmin oyunu.

== Description ==

Her gün herkesin aynı 5 oyuncuyu tahmin ettiği, Wordle tarzı günlük bir tahmin oyunu.
Blurlu görsel giderek netleşir. Tahmin sonuçları renk kodlu olarak gösterilir.

**Temel Özellikler:**

* Günlük 5 oyuncu seti: 2 Kolay + 2 Orta + 1 Zor
* Herkes aynı günlük seti görür (wp_options + transient)
* Admin panelinden günlük set override (manuel atama)
* "İpucu Al" butonu — her basışta 1 hak düşürür
* Maksimum 5 tahmin hakkı (ipucu dahil)
* Yanlış tahminde azalan blur efekti (20px → 0px)
* Wordle tarzı harf renklendirme (yeşil / sarı / gri)
* Türkçe karakter duyarlılığı (ı=i, ü=u, ş=s, ç=c, ğ=g, ö=o)
* Gün sonu özet kartı + gece yarısına geri sayım
* Seri takibi (cookie tabanlı, 1 yıl)
* Cookie tabanlı state (sayfa yenilemeye dayanıklı)
* Shortcode ile herhangi bir sayfaya gömme
* Açık ve koyu tema desteği

== Kurulum ==

1. Plugin klasörünü (`oyuncu-tahmin-oyunu/`) `/wp-content/plugins/` dizinine yükleyin.
2. WordPress admin panelinden "Eklentiler" menüsüne gidin.
3. "Oyuncu Tahmin Oyunu" eklentisini etkinleştirin.
4. Sol menüden "Oyuncular" menüsüne tıklayarak oyuncu ekleyin.
5. "Oyuncular > Günlük Set" sayfasından günlük seti düzenleyebilirsiniz (opsiyonel).
6. Oyunun görünmesini istediğiniz sayfaya `[oyuncu_tahmin]` shortcode'unu ekleyin.

== Oyuncu Ekleme ==

1. Admin panelinden "Oyuncular > Yeni Oyuncu Ekle"ye gidin.
2. Başlık alanına oyuncunun tam adını yazın (bu cevap anahtarıdır).
3. "Oyuncu Görseli ve Bilgileri" meta box'ından görseli seçin.
4. Zorluk seviyesini seçin: Kolay / Orta / Zor.
5. İpucu 1, İpucu 2, İpucu 3 alanlarını doldurun.
6. "Havuzda Göster" kutusunun işaretli olduğundan emin olun.
7. Yayınlayın.

== Admin — Günlük Set Yönetimi ==

"Oyuncular > Günlük Set" sayfasından:
* Bugünün günlük seti görüntülenir.
* Her slot (Kolay 1, Kolay 2, Orta 1, Orta 2, Zor 1) için manuel oyuncu atanabilir.
* Boş bırakılan slotlar otomatik olarak doldurulur (son 7 günde çıkmamış oyuncular önceliklendirilir).

== Shortcode Kullanımı ==

    [oyuncu_tahmin]
    [oyuncu_tahmin tema="koyu"]

| Parametre | Default | Açıklama                 |
|-----------|---------|--------------------------|
| tema      | acik    | `acik` veya `koyu`      |

== Oyun Mekanikleri ==

**Günlük Set:**
* Her gün 5 oyuncu: 2 Kolay + 2 Orta + 1 Zor
* Set gece yarısı sıfırlanır
* Herkes aynı 5 oyuncuyu oynar

**Blur Seviyeleri:**

| Hak Kullanımı | Blur    |
|---------------|---------|
| Başlangıç     | 20px    |
| 1. hak        | 15px    |
| 2. hak        | 10px    |
| 3. hak        | 6px     |
| 4. hak        | 2px     |
| 5. hak (son)  | 0px     |

**İpucu Al Butonu:**
Her basışta sıradaki ipucu gösterilir, 1 hak düşer.

**Wordle Renk Sistemi:**
* Yeşil: Harf doğru, yer doğru
* Sarı: Harf var, yer yanlış
* Gri: Harf yok

**Türkçe Karakter Desteği:**
Karşılaştırma her iki tarafta da normalize edilir:
ı → i, ü → u, ş → s, ç → c, ğ → g, ö → o

== REST API ==

* `GET /wp-json/oyun/v1/durum` — Sayfa yüklenince çağrılır. Cookie'den veya yeni set oluşturarak durumu döndürür.
* `POST /wp-json/oyun/v1/tahmin` — Tahmin gönderir. Nonce gerektirir.
* `POST /wp-json/oyun/v1/ipucu` — İpucu ister. Nonce gerektirir.

== Güvenlik ==

* POST endpoint'leri nonce doğrulaması gerektirir.
* Tüm kullanıcı girdileri sanitize edilir.
* Rate limiting: Aynı IP'den 10 saniyede en fazla 15 istek.
* Cevap anahtarı hiçbir zaman frontend'e açık olarak gönderilmez.

== Sık Sorulan Sorular ==

= Oyuncu görseli nerede depolanır? =
Görseller WordPress Medya Kütüphanesi'nde depolanır. Plugin sadece görsel ID ve URL'yi saklar.

= Her gün aynı set mi görünür? =
Evet, v2.0'da herkes aynı 5 oyuncuyu görür. Set gece yarısı otomatik yenilenir.

= Mobil cihazlarda çalışıyor mu? =
Evet, minimum 320px genişlik desteklenmektedir.

= Kaç oyuncu ekleyebilirim? =
Sınırsız oyuncu ekleyebilirsiniz. Aktif olmayan oyuncular oyunda görünmez.

= Günlük set manuel ayarlanabilir mi? =
Evet, "Oyuncular > Günlük Set" sayfasından her slot için oyuncu atayabilirsiniz.

== Changelog ==

= 2.0.0 =
* Günlük 5 oyuncu seti mimarisine geçildi (2 Kolay + 2 Orta + 1 Zor).
* Herkes aynı günlük seti görür (wp_options + transient).
* Admin "Günlük Set" yönetim sayfası eklendi.
* "İpucu Al" butonu eklendi (hak karşılığında).
* Cookie tabanlı state yönetimine geçildi (localStorage kaldırıldı).
* Seri takibi cookie'ye taşındı (1 yıl geçerli).
* Gün sonu özet kartı ve gece yarısı geri sayımı eklendi.
* REST API yeniden yazıldı: GET /durum, POST /tahmin, POST /ipucu.
* Zorluk seviyesi (Kolay/Orta/Zor) sistemi eklendi.
* Rate limit 15 req/10s'e yükseltildi.

= 1.0.0 =
* İlk yayın.
* Custom Post Type ile oyuncu yönetimi.
* Wordle tarzı tahmin mekanizması.
* Türkçe karakter normalize.
* Cookie tabanlı günlük oyun durumu.
* REST API endpoint'leri.
* Shortcode desteği.
* Açık ve koyu tema.
* Mobil uyumlu tasarım.

== Upgrade Notice ==

= 2.0.0 =
Günlük set mimarisine geçiş. Cookie yapısı değişti — eski localStorage verileri silinecektir.
