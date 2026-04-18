# 🎯 Oyuncu Tahmin Oyunu — Günlük Futbolcu Wordle Oyunu

![License: GPL v2](https://img.shields.io/badge/License-GPL_v2-blue.svg)
![WordPress Compatibility](https://img.shields.io/badge/WordPress-5.8+-21759b.svg?logo=wordpress)
![PHP Compatibility](https://img.shields.io/badge/PHP-7.4+-777bb4.svg?logo=php)

> **Oyuncu Tahmin Oyunu**, Wordle tarzı günlük tahmin mekanizmasıyla futbolcuları tahmin ettiren, rekabetçi ve eğlenceli bir WordPress eklentisidir.

Her gün tüm oyuncular için aynı **5 futbolcuyu** tahmin etmeye çalışırsınız:
* 🟢 **2 Kolay** — Dünyaca ünlü oyuncular
* 🟠 **2 Orta** — İyi bilinen oyuncular
* 🔴 **1 Zor** — Daha az tanınan oyuncular

Blurlu görsel her tahminde giderek netleşir, Wordle tarzı harf renklendirmesi ile yönlendirme yapılır, ipuçları alınabilir ve başarılı tahminlerle kullanıcı serileri (streak) takip edilir.

---

## 🎮 Temel Özellikler

### 🖥️ Frontend (Oyuncu Deneyimi)
* **Günlük Set:** Her gün yenilenen 5 oyuncu (2 Kolay + 2 Orta + 1 Zor)
* **Blur Mekanizması:** Görsel bulanıklığı her tahminde azalır (20px → 0px)
* **Wordle Dinamikleri:** İsabetli harf ve konum renklendirmeleri
* **Oyun İçi Yardım:** 5 tahmin hakkı ve oyuncu başına 3 ipucu
* **Seri (Streak) Takibi:** 1 yıl geçerli kullanıcı başarı serisi
* **Arayüz:** Gün sonu özet kartı, açık/koyu tema seçenekleri
* **Erişilebilirlik:** Mobil responsive (320px+) ve tam Türkçe karakter desteği

### ⚙️ Admin Paneli
* **Kapsamlı Yönetim (CRUD):** Kolayca oyuncu ekleme, düzenleme ve silme
* **Gelişmiş Filtreler:** Zorluk seviyesi ve yayın durumuna göre listeleme
* **Oyun Kontrolü:** İpucu yönetimi ve gelecek 8 gün için günlük set belirleme
* **Rehber:** Admin paneli içine entegre kullanım kılavuzu

### 🔒 Güvenlik
* **Input Validation:** sanitize_text_field, absint, esc_url_raw kullanımı
* **Output Escaping:** esc_attr, esc_html, esc_url, wp_json_encode kullanımı
* **CSRF Protection:** wp_verify_nonce ve X-WP-Nonce header doğrulamaları
* **Rate Limiting:** IP bazlı koruma (10 saniyede maksimum 15 istek)
* **Cevap Gizliliği:** Doğru cevaplar frontend'e hiçbir zaman açık gönderilmez
* **SQL Injection Önlemi:** Sadece Prepared Queries ve WP Meta Query kullanımı

---

## 🚀 Hızlı Başlangıç

### 1. Kurulum
Eklenti klasörünü WordPress eklentiler dizinine kopyalayın:
```bash
cp -r oyuncu-tahmin-oyunu /wp-content/plugins/
```

### 2. Etkinleştirme
WordPress Admin paneline giriş yapın ve **Eklentiler → Oyuncu Tahmin Oyunu → Etkinleştir** adımlarını izleyin.

### 3. Oyuncu Ekleme
**Oyuncular → Yeni Ekle** menüsünden futbolcu bilgilerini girip yayınlayın.
> 💡 Oyunun sorunsuz çalışması için her zorluk seviyesinde en az 5-10 oyuncu eklemeniz önerilir.

### 4. Sayfada Gösterme
Oyunu herhangi bir sayfada veya yazıda göstermek için aşağıdaki kısa kodları (shortcode) kullanabilirsiniz:
```
[oyuncu_tahmin]
```
veya tema ve boyut özelleştirmesi için:
```
[oyuncu_tahmin tema="koyu" gorsel_boyut="420"]
```

---

## 📊 Oyun Mekanikleri

### 🗓️ Günlük Set
* Her gün 5 oyuncu (2 Kolay + 2 Orta + 1 Zor) sorulur
* Tüm kullanıcılar aynı seti oynar ve rekabet eder
* Setler gece yarısı otomatik olarak yenilenir

### 👁️ Blur (Bulanıklık) Seviyeleri
Kullanıcı tahmin yaptıkça oyuncu görseli netleşir:

| Kullanılan Hak | Blur Seviyesi |
|---|---|
| 0 | 20px |
| 1 | 15px |
| 2 | 10px |
| 3 | 6px |
| 4 | 2px |
| 5 | 0px (Tam Net) |

### 🎨 Wordle Renkleri
* 🟩 **Yeşil:** Harf doğru ve yeri doğru
* 🟨 **Sarı:** Harf isimde var ancak yeri yanlış
* ⬜ **Gri:** Harf isimde bulunmuyor

### 🔥 Seri (Streak) Takibi
* **Başarı:** Günün 5 oyuncusu doğru tahmin edilirse seri +1 artar
* **Sıfırlama:** Set içerisinden 1 oyuncu bile kaçırılırsa seri sıfırlanır

---

## 🔧 Teknoloji Stack

| Katman | Teknoloji |
|---|---|
| **Backend** | PHP 7.4+, WordPress 5.8+, REST API |
| **Frontend** | Vanilla JavaScript, CSS3, Responsive Design |
| **Database** | Custom Post Type, Meta Fields, Transient Cache |
| **Güvenlik** | Nonce, Rate Limiting, Input Sanitization |

---

## 📁 Dosya Yapısı

```
oyuncu-tahmin-oyunu/
├── oyuncu-tahmin-oyunu.php    # Ana eklenti dosyası (1,462 satır)
├── assets/
│   ├── oyun.js                # Frontend mantığı (1,181 satır)
│   ├── oyun.css               # Stil dosyası (869 satır)
│   └── logo.png
├── README.md                  # Proje tanıtım dosyası
├── INSTALLATION.md            # Detaylı kurulum rehberi
├── ARCHITECTURE.md            # Teknik mimari detayları
├── CONTRIBUTING.md            # Katkı rehberi
├── PROJECT_SUMMARY.md         # Proje özeti
└── LICENSE                    # Lisans bilgileri
```

### 📊 Kod İstatistikleri
* **PHP:** 1,462 satır
* **JavaScript:** 1,181 satır
* **CSS:** 869 satır
* **Toplam:** 3,512 satır kod
* **Boyut:** ~550 KB

---

## 🧪 Test Ortamları

Aşağıdaki yapılandırma ve cihazlarda sorunsuz çalışacak şekilde test edilmiştir:

* ✅ **WordPress:** 5.8 - 6.5
* ✅ **PHP:** 7.4 - 8.2
* ✅ **Tarayıcılar:** Chrome, Firefox, Safari, Edge
* ✅ **Platformlar:** iOS, Android ve Masaüstü
* ✅ **Özellikler:** Türkçe karakter uyumluluğu, Rate limiting, REST API

---

## 🐛 Sorun Giderme

| Karşılaşılan Sorun | Olası Çözüm |
|---|---|
| Eklenti yüklenmeme | Sunucunuzdaki PHP (7.4+) ve WordPress (5.8+) sürümlerini kontrol edin |
| Görsel boş çıkma | Oyuncunun öne çıkan görseli (medya) eklendi mi? Dosya izinlerini kontrol edin |
| Rate limit hatası | Tarayıcı önbelleğini temizleyin ve 1 dakika bekleyin |
| Seri kaybolma | Tarayıcınızın çerezlere (cookies) izin verdiğinden emin olun |
| Mobil ekranda sorun | Tarayıcı önbelleğini temizleyip sayfayı sert yenileme (Ctrl+F5) yapın |

---

## 📖 Dokümantasyon ve Destek

Detaylı teknik bilgi ve kurulum için diğer Markdown dosyalarımızı inceleyebilirsiniz:

* 📄 **[INSTALLATION.md](INSTALLATION.md)** — Adım adım kurulum rehberi
* 🏗️ **[ARCHITECTURE.md](ARCHITECTURE.md)** — Sistemin teknik mimarisi
* 🤝 **[CONTRIBUTING.md](CONTRIBUTING.md)** — Projeye katkıda bulunma adımları
* 📝 **[PROJECT_SUMMARY.md](PROJECT_SUMMARY.md)** — Projenin genel özeti

### İletişim
Hatalar, sorular veya öneriler için GitHub üzerinden etkileşime geçebilirsiniz:

* 🐛 **[Issues](https://github.com/EmrhnEmiroglu/guess-the-footballer-minigame/issues)** — Hata raporları
* 💡 **[Discussions](https://github.com/EmrhnEmiroglu/guess-the-footballer-minigame/discussions)** — Soru & öneriler

---

## 👨‍💻 Geliştirici

**Emirhan Emiroğlu**

* 🔗 GitHub: [@EmrhnEmiroglu](https://github.com/EmrhnEmiroglu)
* 📧 İletişim: Proje reposu üzerinde issue veya discussion açarak ulaşabilirsiniz

---

## 📝 Lisans

Bu proje **GPL v2 lisansı** altında lisanslanmıştır. Daha fazla bilgi için [LICENSE](LICENSE) dosyasına göz atabilirsiniz.

---

## ⭐ Projeyi Beğendiyseniz

Projeyi beğendiyseniz lütfen bir yıldız ⭐ verin!

---

**Made with ❤️ for football fans.**

Eğlenceli oyunlar! ⚽🎮
