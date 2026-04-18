# 📦 Kurulum Rehberi

## Gereksinimler

- ✅ WordPress 5.8+
- ✅ PHP 7.4+
- ✅ MySQL 5.7+ veya MariaDB 10.3+

## Adım 1: Plugin'i Yükle

### Option A: ZIP (WordPress Admin)
1. Admin Paneli → Eklentiler → Yeni Ekle
2. Dosya Yükle → ZIP seç
3. "Şimdi Kur" tıkla

### Option B: FTP/SSH
1. Plugin dosyalarını yükle:
   ```
   /wp-content/plugins/oyuncu-tahmin-oyunu/
   ```
2. Admin panelde etkinleştir

### Option C: WP-CLI
```bash
wp plugin install oyuncu-tahmin-oyunu --activate
```

## Adım 2: Etkinleştir

**Admin Paneli:**
```
Eklentiler → Oyuncu Tahmin Oyunu → Etkinleştir
```

Sol menüde "Oyuncular" menüsü görünecek.

## Adım 3: Oyuncu Ekle

1. **Admin Paneli:**
   ```
   Oyuncular → Yeni Ekle
   ```

2. **Bilgileri Doldur:**
   - **Başlık** (zorunlu): Oyuncu adı
   - **Görsel** (zorunlu): Medya Kütüphanesi
   - **Zorluk**: Kolay / Orta / Zor
   - **İpuçları**: En fazla 3 tane
   - **Havuzda Göster**: ✓ İşaretle

3. **Yayınla**

### Öneriler
- Her zorluk seviyesinde **en az 10 oyuncu**
- Toplam **20+ oyuncu** ideal
- Görsel **200x200px+** olmalı

## Adım 4: Sayfaya Ekle

1. Sayfayı seç/oluştur
2. Shortcode ekle:
   ```
   [oyuncu_tahmin]
   ```
   veya
   ```
   [oyuncu_tahmin tema="koyu" gorsel_boyut="420"]
   ```

## Adım 5: Günlük Set Yönetimi (İsteğe Bağlı)

1. **Admin Paneli:**
   ```
   Oyuncular → Günlük Set
   ```

2. **"Düzenle" tıkla** ve oyuncu seç
3. **Kaydet**

Boş bırakılanlar otomatik doldurulur.

## Adım 6: Test Et

1. Oyun sayfasını aç
2. Tarayıcı console (F12)
3. Hata yok mu kontrol et
4. Oyunu test et

---

## 🎯 Sorun Giderme

| Sorun | Çözüm |
|-------|-------|
| "Yükleniyor..." sıkışıyor | Plugin aktif mi? REST API çalışıyor mu? |
| Görsel boş | Görsel uploadlandı mı? Medya ayarları kontrol et |
| Rate limit hatası | Cache temizle, 1 dakika bekle |
| Seri sıfırlanıyor | Cookie ayarlarını kontrol et |
| Mobil kaymıyor | Cache temizle, sayfayı yenile |

---

**Kurulum tamamlandı! Oyunları ekleyip oynamaya başlayabilirsiniz.** 🎮
