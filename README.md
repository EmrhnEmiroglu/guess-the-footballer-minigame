# 🎯 Oyuncu Tahmin Oyunu — Günlük Futbolcu Wordle Oyunu

[![License: GPL v2](https://img.shields.io/badge/License-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress Compatibility](https://img.shields.io/badge/WordPress-5.8+-green.svg)](https://wordpress.org/)
[![PHP Compatibility](https://img.shields.io/badge/PHP-7.4+-blue.svg)](https://www.php.net/)
[![Version](https://img.shields.io/badge/Version-2.0.0-orange.svg)](#)

## 📌 Özet

**Oyuncu Tahmin Oyunu**, Wordle tarzı günlük tahmin mekanizmasıyla futbolcuları tahmin ettiren **WordPress eklentisidir**. 

Her gün aynı 5 oyuncuyu tahmin etmeye çalışırsınız:
- 🟢 **2 Kolay** — Dünyaca ünlü oyuncular
- 🟠 **2 Orta** — İyi bilinen oyuncular  
- 🔴 **1 Zor** — Daha az tanınan oyuncular

Blurlu görsel giderek netleşir, Wordle tarzı harf renklendirmesi yapılır, ipuço almanız mümkündür ve seri takibi yapılır.

---

## 🎮 Temel Özellikler

### Frontend (Oyuncu Deneyimi)
✅ Günlük 5 oyuncu seti (2 kolay + 2 orta + 1 zor)
✅ Blurlu görsel sistemi (20px → 0px)
✅ Wordle tarzı tahmin mekanizması
✅ 5 tahmin hakkı + 3 ipuço/oyuncu
✅ Seri takibi (1 yıl geçerli)
✅ Gün sonu özet kartı
✅ Açık & koyu tema
✅ Mobil responsive (320px+)
✅ Türkçe karakter desteği

### Admin Paneli
✅ Oyuncu yönetimi (CRUD)
✅ Zorluk & durum filtreleri
✅ İpuço yönetimi
✅ Günlük set override (gelecek 8 gün)
✅ Admin kılavuzu sayfası

### Güvenlik
✅ Rate limiting (15 req/10s)
✅ Nonce doğrulaması (CSRF)
✅ Input sanitization
✅ Output escaping
✅ Cevap gizli tutma

---

## 🚀 Hızlı Başlangıç

### 1. Yükle
```bash
# Bu klasörü /wp-content/plugins/ ye kopyala
cp -r oyuncu-tahmin-oyunu /wp-content/plugins/
```

### 2. Etkinleştir
```
Admin → Eklentiler → Oyuncu Tahmin Oyunu → Etkinleştir
```

### 3. Oyuncu Ekle
```
Oyuncular → Yeni Ekle → Bilgi gir → Yayınla
(Her zorluk seviyesinde en az 5-10 oyuncu)
```

### 4. Sayfaya Ekle
```
[oyuncu_tahmin]
[oyuncu_tahmin tema="koyu" gorsel_boyut="420"]
```

---

## 📖 Dokümantasyon

- **[INSTALLATION.md](INSTALLATION.md)** — Detaylı kurulum rehberi
- **[ARCHITECTURE.md](ARCHITECTURE.md)** — Teknik mimari detayları
- **[CONTRIBUTING.md](CONTRIBUTING.md)** — Katkı rehberi
- **[PROJECT_SUMMARY.md](PROJECT_SUMMARY.md)** — Proje özeti

---

## 🔧 Teknoloji Stack

| Katman | Teknoloji |
|--------|-----------|
| **Backend** | PHP 7.4+, WordPress 5.8+, REST API |
| **Frontend** | Vanilla JavaScript, CSS3, Responsive |
| **Database** | Custom Post Type, Meta fields, Transient cache |
| **Güvenlik** | Nonce, Rate Limiting, Input Sanitization |

---

## 📊 Oyun Mekanikleri

### Günlük Set
- Her gün 5 oyuncu (2K + 2O + 1Z)
- Herkes aynı seti oynar
- Gece yarısı otomatik yenilenir

### Blur Seviyeleri
| Hak | Blur |
|-----|------|
| 0   | 20px |
| 1   | 15px |
| 2   | 10px |
| 3   | 6px  |
| 4   | 2px  |
| 5   | 0px  |

### Wordle Renkleri
- 🟩 **Yeşil**: Harf doğru, yer doğru
- 🟨 **Sarı**: Harf var, yer yanlış
- ⬜ **Gri**: Harf yok

### Seri Takibi
- 5 oyuncuyu doğru tahmin → Seri +1
- 1 oyuncu kaçırılsa → Seri sıfırlanır

---

## 📁 Dosya Yapısı

```
oyuncu-tahmin-oyunu/
├── oyuncu-tahmin-oyunu.php    (1,462 satır)
├── assets/
│   ├── oyun.js                (1,181 satır)
│   ├── oyun.css               (869 satır)
│   └── logo.png
├── README.md                  ← Şu dosya
├── INSTALLATION.md
├── ARCHITECTURE.md
├── CONTRIBUTING.md
├── PROJECT_SUMMARY.md
├── LICENSE
└── .gitignore
```

---

## 🔒 Güvenlik Özellikleri

✅ **Input Validation**: sanitize_text_field, absint, esc_url_raw
✅ **Output Escaping**: esc_attr, esc_html, esc_url, wp_json_encode
✅ **CSRF Protection**: wp_verify_nonce, X-WP-Nonce header
✅ **Rate Limiting**: 15 istek / 10 saniye (IP bazlı)
✅ **Cevap Gizliliği**: Frontend'e hiçbir zaman açıklanmaz
✅ **SQL Injection Prevention**: Prepared queries, meta queries

---

## 📊 Kod İstatistikleri

- **PHP**: 1,462 satır
- **JavaScript**: 1,181 satır
- **CSS**: 869 satır
- **Toplam**: 3,512 satır
- **Boyut**: ~550 KB

---

## 🧪 Test Edilmiş

✅ WordPress 5.8 - 6.5
✅ PHP 7.4 - 8.2
✅ Chrome, Firefox, Safari, Edge
✅ iOS & Android
✅ Türkçe karakterler
✅ Rate limiting
✅ REST API

---

## 🐛 Sorun Giderme

| Sorun | Çözüm |
|-------|-------|
| Plugin yüklemiyor | PHP 7.4+, WP 5.8+ kontrol et |
| Görsel boş | Medya yüklendi mi? Yetki kontrol et |
| Rate limit hatası | Cache temizle, bir dakika bekle |
| Seri kayboldu | Cookies'i kontrol et |
| Mobil kaymıyor | Cache temizle, sayfayı yenile |

---

## 📞 Destek

- 🐛 [Issues](https://github.com/EmrhnEmiroglu/guess-the-footballer-minigame/issues) — Hata raporu
- 💡 [Discussions](https://github.com/EmrhnEmiroglu/guess-the-footballer-minigame/discussions) — Soru & Önerileri
- 🤝 [Contributing](CONTRIBUTING.md) — Katkı rehberi

---

## 👨‍💻 Geliştirici

**Emirhan Emiroğlu**

- 🔗 GitHub: [@EmrhnEmiroglu](https://github.com/EmrhnEmiroglu)
- 📧 İletişim: Proje üzerinde issue veya discussion açabilirsiniz

---

## 📝 Lisans

**GPLv2 or later** — [LICENSE](LICENSE)

---

## ⭐ Bu Projeyi Beğendiyseniz

Projeyi beğendiyseniz **bir yıldız** ⭐ vermek bizi çok mutlu eder!

---

## 🎯 Sonraki Adımlar

1. [INSTALLATION.md](INSTALLATION.md) okuyun
2. WordPress'te kurun
3. Oyuncu ekleyin
4. Oyunu test edin
5. Feedback gönderin

---

**Made with ❤️ for football fans.**

Eğlenceli oyunlar! ⚽🎮
