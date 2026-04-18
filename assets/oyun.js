(function () {
	'use strict';

	// =========================================================================
	// 1. STATE
	// =========================================================================

	const state = {
		set:         [],   // [id1,id2,id3,id4,id5]
		setMeta:     [],   // [{zorluk:'kolay', etiket:'Kolay'}, ...]
		aktifIndex:  0,    // 0-4
		oyuncular:   [],   // [{id,durum,hak_kullanilan,ipucu_acilan[],tahminler[],ipucu_sayisi}]
		gunBitti:    false,
		seri:        0,
		nonce:       '',
		yukluyor:    false,
		maxHak:      5,
	};

	// =========================================================================
	// 2. COOKIE
	// =========================================================================

	function oto_bugun_tarih() {
		const d = new Date();
		const y = d.getFullYear();
		const m = String(d.getMonth() + 1).padStart(2, '0');
		const g = String(d.getDate()).padStart(2, '0');
		return String(y) + m + g;
	}

	function oto_gece_yarisi_expires() {
		const now = new Date();
		const yarin = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0, 0);
		return yarin.toUTCString();
	}

	function oto_cookie_oku() {
		const ad = 'oyun_durum_' + oto_bugun_tarih();
		const diz = document.cookie.split(';');
		for (let i = 0; i < diz.length; i++) {
			const parca = diz[i].trim();
			if (parca.startsWith(ad + '=')) {
				try {
					return JSON.parse(decodeURIComponent(parca.slice(ad.length + 1)));
				} catch (e) {
					return null;
				}
			}
		}
		return null;
	}

	function oto_cookie_yaz(data) {
		const ad = 'oyun_durum_' + oto_bugun_tarih();
		const val = encodeURIComponent(JSON.stringify(data));
		document.cookie = ad + '=' + val + '; expires=' + oto_gece_yarisi_expires() + '; path=/; SameSite=Lax';
	}

	function oto_seri_cookie_oku() {
		const diz = document.cookie.split(';');
		for (let i = 0; i < diz.length; i++) {
			const parca = diz[i].trim();
			if (parca.startsWith('oto_seri=')) {
				const val = parseInt(parca.slice(9), 10);
				return isNaN(val) ? 0 : val;
			}
		}
		return 0;
	}

	function oto_seri_cookie_yaz(n) {
		const yilSonra = new Date();
		yilSonra.setFullYear(yilSonra.getFullYear() + 1);
		document.cookie = 'oto_seri=' + n + '; expires=' + yilSonra.toUTCString() + '; path=/; SameSite=Lax';
	}

	function oto_state_kaydet() {
		oto_cookie_yaz({
			set: state.set,
			aktif_index: state.aktifIndex,
			oyuncular: state.oyuncular,
			gun_bitti: state.gunBitti,
		});
	}

	// =========================================================================
	// 3. BLUR + HAK
	// =========================================================================

	const BLUR_SEVIYELERI = [20, 15, 10, 6, 2, 0];

	// Görsel yükleme — tüm edge case'leri karşılar:
	// - onload mobilde cache'den gelmeyebilir → complete kontrolü
	// - onload hiç tetiklenmezse → 8 saniyelik timeout fallback
	// - onerror → yukleniyorEl gizlenir, gorsel yine de gösterilmeye çalışılır
	function oto_gorsel_yukle(gorselEl, yukleniyorEl, url) {
		if (!gorselEl || !url) return;

		var timeout = null;

		function goster() {
			if (timeout) clearTimeout(timeout);
			if (yukleniyorEl) yukleniyorEl.style.display = 'none';
			gorselEl.style.display = '';
		}

		function hata() {
			if (timeout) clearTimeout(timeout);
			if (yukleniyorEl) {
				yukleniyorEl.style.display = 'none';
			}
			// Görsel bozuk da olsa container'ı gizleme — oyun devam etmeli
			gorselEl.style.display = '';
		}

		gorselEl.style.display = 'none';
		if (yukleniyorEl) {
			yukleniyorEl.style.display = '';
			yukleniyorEl.textContent = 'Yükleniyor...';
		}

		// Event'leri sıfırla
		gorselEl.onload = goster;
		gorselEl.onerror = hata;

		// src değişmeden önce complete kontrolü (aynı URL yeniden set edilirse onload gelmez)
		if (gorselEl.src === url && gorselEl.complete && gorselEl.naturalWidth > 0) {
			goster();
			return;
		}

		gorselEl.src = url;

		// src set sonrası anında complete olduysa (cache)
		if (gorselEl.complete) {
			if (gorselEl.naturalWidth > 0) {
				goster();
			} else {
				hata();
			}
			return;
		}

		// Mobil fallback: 8 saniye içinde onload gelmezse zorla göster
		timeout = setTimeout(function () {
			if (gorselEl.style.display === 'none') {
				goster();
			}
		}, 8000);
	}

	function oto_blur_guncelle(hakKullanilan) {
		const deger = BLUR_SEVIYELERI[hakKullanilan] !== undefined ? BLUR_SEVIYELERI[hakKullanilan] : 0;
		const gorsel = document.getElementById('oto-gorsel');
		if (gorsel) {
			gorsel.style.filter = 'blur(' + deger + 'px)';
		}
		document.documentElement.style.setProperty('--oto-blur-px', deger + 'px');
	}

	// HATA 4 DÜZELTMESİ: Hak metni de güncelleniyor.
	function oto_hak_noktalarini_guncelle(kalanHak) {
		const noktalar = document.querySelectorAll('.oto-hak-nokta');
		const kullanilanSayisi = state.maxHak - kalanHak;
		noktalar.forEach(function (nokta, i) {
			if (i < kullanilanSayisi) {
				nokta.classList.add('bos');
			} else {
				nokta.classList.remove('bos');
			}
		});
		const hakText = document.getElementById('oto-hak-text');
		if (hakText) {
			hakText.textContent = kalanHak + ' hak';
		}
	}

	// =========================================================================
	// 4. SERI
	// =========================================================================

	// HATA 6 DÜZELTMESİ: Streak tek oyuncuya göre değil, günün tüm 5 oyuncusuna göre hesaplanır.
	// Bu fonksiyon artık sadece gün sonu çağrılır.
	function oto_seri_gun_sonu_guncelle() {
		const hepsKazandi = state.oyuncular.every(function (o) {
			return o.durum === 'kazandi';
		});
		state.seri = hepsKazandi ? (state.seri + 1) : 0;
		oto_seri_cookie_yaz(state.seri);
		oto_seri_goster();
	}

	function oto_seri_goster() {
		const el = document.querySelector('.oto-seri-rozet');
		if (!el) return;

		if (state.seri > 0) {
			el.textContent = state.seri + ' seri';
			el.classList.add('gorunur');
		} else {
			el.textContent = '';
			el.classList.remove('gorunur');
		}
	}

	// =========================================================================
	// 5. ILERLEME
	// =========================================================================

	function oto_ilerleme_guncelle() {
		const metinEl = document.getElementById('oto-ilerleme-metin');
		const rozetEl = document.getElementById('oto-zorluk-rozet');
		const ilerlemeKutu = document.getElementById('oto-ilerleme');
		if (ilerlemeKutu) ilerlemeKutu.style.display = 'flex';
		
		if (!metinEl) return;

		const no = state.aktifIndex + 1;
		metinEl.textContent = 'Oyuncu ' + no + '/5';

		const slot = state.setMeta && state.setMeta[state.aktifIndex] ? state.setMeta[state.aktifIndex] : null;
		if (rozetEl && slot) {
			const z = slot.zorluk || 'kolay';
			rozetEl.textContent = slot.etiket || (z.charAt(0).toUpperCase() + z.slice(1));
			rozetEl.className = 'oto-zorluk-rozet ' + z;
		}
	}

	// =========================================================================
	// 6. RENDER
	// =========================================================================

	function oto_oyuncu_normalize_et(oyuncu, id) {
		const o = oyuncu && typeof oyuncu === 'object' ? oyuncu : {};
		const sonuc = {
			id: typeof id === 'number' && id ? id : (parseInt(o.id, 10) || 0),
			durum: typeof o.durum === 'string' ? o.durum : 'bekliyor',
			hak_kullanilan: parseInt(o.hak_kullanilan, 10) || 0,
			ipucu_acilan: Array.isArray(o.ipucu_acilan) ? o.ipucu_acilan : [],
			tahminler: Array.isArray(o.tahminler) ? o.tahminler : [],
			cevap_uzunluk: parseInt(o.cevap_uzunluk, 10) || 0,
			// HATA 5 DÜZELTMESİ: Her oyuncunun bağımsız ipucu sayısı
			ipucu_sayisi: parseInt(o.ipucu_sayisi, 10) || 0,
		};

		// Geriye dönük: camelCase cookie
		if (o.hakKullanilan !== undefined && !sonuc.hak_kullanilan) {
			sonuc.hak_kullanilan = parseInt(o.hakKullanilan, 10) || 0;
		}
		if (Array.isArray(o.ipucuAcilan) && sonuc.ipucu_acilan.length === 0) {
			sonuc.ipucu_acilan = o.ipucuAcilan;
		}

		if (o.gorsel_url) sonuc.gorsel_url = o.gorsel_url;
		if (o.zorluk) sonuc.zorluk = o.zorluk;
		if (o.oyuncu_adi) sonuc.oyuncu_adi = o.oyuncu_adi;
		if (o.oyuncu_adi_ham) sonuc.oyuncu_adi_ham = o.oyuncu_adi_ham;
		if (o.tahmin_sonuclari) sonuc.tahmin_sonuclari = o.tahmin_sonuclari;
		if (o.cevap_uzunluk) sonuc.cevap_uzunluk = o.cevap_uzunluk;

		return sonuc;
	}

	function oto_aktif_oyuncuyu_render_et(gorselUrl) {
		const aktifOyuncu = state.oyuncular[state.aktifIndex];
		if (!aktifOyuncu) return;

		// İlerleme barını geri göster (sonuç kartından gelince gizlenmiş olabilir)
		const ilerlemeKutuRender = document.getElementById('oto-ilerleme');
		if (ilerlemeKutuRender) ilerlemeKutuRender.style.display = 'flex';

		const hakKullanilan = aktifOyuncu.hak_kullanilan || 0;

		oto_blur_guncelle(hakKullanilan);
		oto_hak_noktalarini_guncelle(state.maxHak - hakKullanilan);

		// İpuçları
		const ipucuListesi = document.getElementById('oto-ipuclari');
		if (ipucuListesi) {
			ipucuListesi.innerHTML = '';
			if (aktifOyuncu.ipucu_acilan && aktifOyuncu.ipucu_acilan.length > 0) {
				aktifOyuncu.ipucu_acilan.forEach(function (metin) {
					const span = document.createElement('span');
					span.className = 'oto-ipucu-item gorunur';
					span.textContent = metin;
					ipucuListesi.appendChild(span);
				});
			}
		}

		// Tahmin geçmişi — renk bilgisi tahmin_sonuclari'ndan alınır, animasyon yok.
		const gecmisEl = document.getElementById('oto-tahminler');
		if (gecmisEl) {
			gecmisEl.innerHTML = '';
			if (aktifOyuncu.tahminler && aktifOyuncu.tahminler.length > 0) {
				aktifOyuncu.tahminler.forEach(function (tahmin, i) {
					const sonuclar = (aktifOyuncu.tahmin_sonuclari && aktifOyuncu.tahmin_sonuclari[i])
						? aktifOyuncu.tahmin_sonuclari[i]
						: [];
					gecmisEl.appendChild(oto_tahmin_satiri_olustur(tahmin, sonuclar, false));
				});
			}
		}

		// Görsel
		const gorselEl = document.getElementById('oto-gorsel');
		const yukleniyorEl = document.getElementById('oto-gorsel-yukleniyor');
		oto_gorsel_yukle(gorselEl, yukleniyorEl, gorselUrl);

		// Sayac HTML'den silindi.

		// Aktif tahmin satırı — Oyuncu ismini boşluklu (Harry Kane → [H][A][R][R][Y] GAP [K][A][N][E]) göster
		oto_isim_kutularini_olustur(aktifOyuncu);

		// İpucu butonu — HATA 5: her oyuncunun bağımsız ipucu_sayisi kullanılır
		const ipucuBtn = document.getElementById('oto-ipucu-btn');
		if (ipucuBtn) {
			const oyuncuIpucuSayisi = aktifOyuncu.ipucu_sayisi || 0;
			const acilmis = aktifOyuncu.ipucu_acilan ? aktifOyuncu.ipucu_acilan.length : 0;
			if (aktifOyuncu.durum === 'bekliyor' || aktifOyuncu.durum === 'devam') {
				ipucuBtn.disabled = false;
				if (oyuncuIpucuSayisi === 0 || acilmis >= oyuncuIpucuSayisi) {
					ipucuBtn.style.display = 'none';
				} else {
					ipucuBtn.style.display = '';
				}
			} else {
				ipucuBtn.style.display = 'none';
			}
		}

		// Giriş alanı
		const girisAlani = document.getElementById('oto-giris-alani');
		const inputEl = document.getElementById('oto-tahmin-input');
		const tahminBtn = document.getElementById('oto-tahmin-btn');
		if (aktifOyuncu.durum === 'kazandi' || aktifOyuncu.durum === 'kaybetti') {
			if (girisAlani) girisAlani.classList.add('gizli');
		} else {
			if (girisAlani) girisAlani.classList.remove('gizli');
			if (inputEl) { inputEl.disabled = false; inputEl.value = ''; }
			if (tahminBtn) tahminBtn.disabled = false;
			if (inputEl) inputEl.focus();
		}

		oto_ilerleme_guncelle();
	}

	// =========================================================================
	// 7. MESAJ
	// =========================================================================

	function oto_mesaj_goster(metin, tip) {
		const el = document.getElementById('oto-mesaj');
		if (!el) return;
		el.textContent = metin;
		el.className = 'oto-mesaj' + (tip ? ' ' + tip : '');
	}

	function oto_mesaj_temizle() {
		const el = document.getElementById('oto-mesaj');
		if (!el) return;
		el.textContent = '';
		el.className = 'oto-mesaj';
	}

	function oto_input_shake() {
		const input = document.getElementById('oto-tahmin-input');
		if (!input) return;
		input.classList.remove('oto-shake');
		void input.offsetWidth;
		input.classList.add('oto-shake');
		input.addEventListener('animationend', function onEnd() {
			input.classList.remove('oto-shake');
			input.removeEventListener('animationend', onEnd);
		});
	}

	// =========================================================================
	// 8. SONUC KARTI (OYUNCU)
	// =========================================================================

	function oto_sonuc_karti_goster(kazandi, oyuncuAdi) {
		const girisAlani = document.getElementById('oto-giris-alani');
		if (girisAlani) girisAlani.classList.add('gizli');

		const ipucuBtn = document.getElementById('oto-ipucu-btn');
		if (ipucuBtn) ipucuBtn.style.display = 'none';

		// İlerleme barını ve aktif tahmin satırını gizle
		const ilerlemeKutu = document.getElementById('oto-ilerleme');
		if (ilerlemeKutu) ilerlemeKutu.style.display = 'none';
		const aktifSatir = document.getElementById('oto-aktif-tahmin-satiri');
		if (aktifSatir) aktifSatir.style.display = 'none';

		const eskiKart = document.querySelector('.oto-sonuc-kart');
		if (eskiKart) eskiKart.remove();

		const kart = document.createElement('div');
		kart.className = 'oto-sonuc-kart ' + (kazandi ? 'kazandi' : 'kaybetti');

		const baslik = document.createElement('div');
		baslik.className = 'oto-sonuc-baslik';
		baslik.textContent = kazandi ? 'Tebrikler!' : 'Bulamadın!';
		kart.appendChild(baslik);

		const alt = document.createElement('div');
		alt.className = 'oto-sonuc-alt';
		alt.textContent = kazandi ? 'Doğru buldunuz.' : 'Oyuncu bulunamadı.';
		kart.appendChild(alt);

		const oyuncuEl = document.createElement('div');
		oyuncuEl.className = 'oto-sonuc-oyuncu';
		oyuncuEl.textContent = oyuncuAdi || '';
		kart.appendChild(oyuncuEl);

		const btnRow = document.createElement('div');
		btnRow.className = 'oto-sonuc-btn-row';

		const sonrakiBtn = document.createElement('button');
		sonrakiBtn.className = 'oto-sonraki-btn';
		sonrakiBtn.type = 'button';
		sonrakiBtn.textContent = 'Sonraki Oyuncu →';
		sonrakiBtn.addEventListener('click', function () {
			kart.remove();
			oto_sonraki_oyuncuya_gec();
		});
		btnRow.appendChild(sonrakiBtn);

		kart.appendChild(btnRow);

		const konteyner = document.getElementById('oyuncu-tahmin-oyunu');
		if (konteyner) konteyner.appendChild(kart);
	}

	// =========================================================================
	// 9. SONRAKI OYUNCU (BÖLÜM 20 HATA 1)
	// =========================================================================

	function oto_sonraki_oyuncuya_gec() {
		state.aktifIndex++;
		oto_state_kaydet();

		if (state.aktifIndex >= 5) {
			oto_gun_sonu_ozetini_goster();
			return;
		}

		oto_yeni_oyuncu_yukle();
	}

	function oto_yeni_oyuncu_yukle() {
		const gorselEl = document.getElementById('oto-gorsel');
		const gecmisEl = document.getElementById('oto-tahminler');
		const ipucuListesi = document.getElementById('oto-ipuclari');
		const inputEl = document.getElementById('oto-tahmin-input');
		const tahminBtn = document.getElementById('oto-tahmin-btn');
		const ipucuBtn = document.getElementById('oto-ipucu-btn');
		const girisAlani = document.getElementById('oto-giris-alani');
		const yukleniyorEl = document.getElementById('oto-gorsel-yukleniyor');
		
		const oyuncu = state.oyuncular[state.aktifIndex];

		// Yeni oyuncuda blur sıfırla, geçiş animasyonunu kapat
		document.documentElement.style.setProperty('--oto-blur-px', '20px');
		if (gorselEl) {
			gorselEl.style.transition = 'none';
			gorselEl.style.filter = 'blur(20px)';
			void gorselEl.offsetWidth;
			gorselEl.style.transition = '';
		}

		// Cookie'de URL varsa hemen yüklemeye başla (API cevabını bekleme)
		if (gorselEl && oyuncu && oyuncu.gorsel_url) {
			oto_gorsel_yukle(gorselEl, yukleniyorEl, oyuncu.gorsel_url);
		} else if (gorselEl) {
			gorselEl.style.display = 'none';
			if (yukleniyorEl) { yukleniyorEl.style.display = ''; yukleniyorEl.textContent = 'Yükleniyor...'; }
		}
		
		if (gecmisEl) gecmisEl.innerHTML = '';
		if (ipucuListesi) ipucuListesi.innerHTML = '';

		// Aktif tahmin satırını göster ve sıfırla
		const aktifSatirTemizle = document.getElementById('oto-aktif-tahmin-satiri');
		if (aktifSatirTemizle) {
			aktifSatirTemizle.innerHTML = '';
			aktifSatirTemizle.style.display = 'flex';
		}

		oto_hak_noktalarini_guncelle(state.maxHak);
		
		if (inputEl) {
			inputEl.value = '';
			inputEl.disabled = false;
		}
		
		if (tahminBtn) tahminBtn.disabled = false;
		if (girisAlani) girisAlani.classList.remove('gizli');

		oto_mesaj_temizle();
		oto_ilerleme_guncelle();

		// GET /durum ile aktif oyuncunun gorsel + ipucu sayısını al
		fetch(oyunConfig.restUrl + 'durum?_t=' + Date.now(), {
			method: 'GET',
			headers: { 'X-WP-Nonce': state.nonce },
			credentials: 'same-origin',
		})
		.then(function (res) { return res.json(); })
		.then(function (data) {
			if (data.hata) {
				oto_mesaj_goster(data.hata, 'hata');
				return;
			}

			if (data.nonce) state.nonce = data.nonce;
			state.setMeta = data.set_meta || state.setMeta;

			// HATA 5: ipucu_sayisi oyuncunun kendi state'ine yazılır (durum endpointinden geldi)
			const aktifOyuncuYeni = state.oyuncular[state.aktifIndex];
			if (aktifOyuncuYeni && data.ipucu_sayisi !== undefined) {
				aktifOyuncuYeni.ipucu_sayisi = parseInt(data.ipucu_sayisi, 10) || 0;
			}

			// Sunucudan gelen üst düzey cevap_uzunluk ve oyuncu_adi_ham bilgilerini state'e yaz
			if (aktifOyuncuYeni) {
				if (data.cevap_uzunluk) aktifOyuncuYeni.cevap_uzunluk = parseInt(data.cevap_uzunluk, 10) || 0;
				if (data.oyuncu_adi_ham) aktifOyuncuYeni.oyuncu_adi_ham = data.oyuncu_adi_ham;
				// İsim kutucuklarını oluştur
				oto_isim_kutularini_olustur(aktifOyuncuYeni);
			}

			// API'den gelen URL, cookie'dekinden farklıysa (veya cookie yoksa) yeniden yükle
			if (gorselEl && data.gorsel_url && gorselEl.src !== data.gorsel_url) {
				oto_gorsel_yukle(gorselEl, yukleniyorEl, data.gorsel_url);
			} else if (gorselEl && !data.gorsel_url && yukleniyorEl) {
				yukleniyorEl.style.display = 'none';
				gorselEl.style.display = '';
			}

			// Sayac silindi

			if (ipucuBtn) {
				// HATA 5: oyuncunun kendi ipucu_sayisi kullanılır
				const aktifO = state.oyuncular[state.aktifIndex];
				const oIpucuSayisi = aktifO ? (aktifO.ipucu_sayisi || 0) : 0;
				const oAcilmis = aktifO && aktifO.ipucu_acilan ? aktifO.ipucu_acilan.length : 0;
				ipucuBtn.disabled = false;
				ipucuBtn.style.display = (oIpucuSayisi > 0 && oAcilmis < oIpucuSayisi) ? '' : 'none';
			}

			if (inputEl) inputEl.focus();
		})
		.catch(function () {
			oto_mesaj_goster('Bağlantı hatası.', 'hata');
		});
	}

	// =========================================================================
	// 10. GUN SONU
	// =========================================================================

	function oto_gun_sonu_ozetini_goster() {
		state.gunBitti = true;
		oto_state_kaydet();
		// HATA 6: Streak günün sonunda tüm oyunculara göre hesaplanır
		oto_seri_gun_sonu_guncelle();

		const eskiOzet = document.querySelector('.oto-gun-sonu-kart');
		if (eskiOzet) eskiOzet.remove();

		// Skor
		const kazananlar = state.oyuncular.filter(function (o) { return o.durum === 'kazandi'; }).length;
		const toplamIpucu = state.oyuncular.reduce(function (toplam, o) {
			return toplam + (o.ipucu_acilan ? o.ipucu_acilan.length : 0);
		}, 0);

		const kart = document.createElement('div');
		kart.className = 'oto-gun-sonu-kart';

		const baslik = document.createElement('div');
		baslik.className = 'oto-gun-sonu-baslik';
		baslik.textContent = 'Bugünlük bitti!';
		kart.appendChild(baslik);

		const skor = document.createElement('div');
		skor.className = 'oto-gun-sonu-skor';
		skor.textContent = kazananlar + '/5 oyuncu doğru';
		kart.appendChild(skor);

		const ipucuBilgi = document.createElement('div');
		ipucuBilgi.className = 'oto-gun-sonu-ipucu';
		ipucuBilgi.textContent = 'Kullanılan ipucu: ' + toplamIpucu;
		kart.appendChild(ipucuBilgi);

		const cta = document.createElement('div');
		cta.className = 'oto-gun-sonu-cta';
		cta.textContent = 'Yarın tekrar gel, seriyi devam ettir!';
		kart.appendChild(cta);

		// "Tekrar Oyna" Butonu
		const tekrarBtn = document.createElement('button');
		tekrarBtn.className = 'oto-sonraki-btn'; 
		tekrarBtn.type = 'button';
		tekrarBtn.textContent = 'Tekrar Oyna';
		tekrarBtn.style.marginTop = '12px';
		tekrarBtn.style.marginBottom = '12px';
		tekrarBtn.style.width = '100%';
		tekrarBtn.addEventListener('click', function() {
			document.cookie = 'oyun_durum_' + oto_bugun_tarih() + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
			window.location.reload();
		});
		kart.appendChild(tekrarBtn);

		const etiket = document.createElement('div');
		etiket.className = 'oto-gun-sonu-etiket';
		etiket.textContent = 'Yeni oyuncular için kalan süre:';
		kart.appendChild(etiket);

		const geriSayimEl = document.createElement('div');
		geriSayimEl.id = 'oto-geri-sayim';
		geriSayimEl.className = 'oto-geri-sayim';
		kart.appendChild(geriSayimEl);

		const ilerlemeKutu = document.getElementById('oto-ilerleme');
		if (ilerlemeKutu) ilerlemeKutu.style.display = 'none';

		const konteyner = document.getElementById('oyuncu-tahmin-oyunu');
		if (konteyner) {
			const gorselWrapper = document.getElementById('oto-gorsel-wrapper');
			if (gorselWrapper) gorselWrapper.style.display = 'none';

			// Aktif tahmin kutusunu gizle
			const _akt = document.getElementById('oto-aktif-tahmin-satiri');
			if (_akt) _akt.style.display = 'none';

			const ipucuListesi = document.getElementById('oto-ipuclari');
			if (ipucuListesi) ipucuListesi.style.display = 'none';

			const tahminGecmisi = document.getElementById('oto-tahminler');
			if (tahminGecmisi) tahminGecmisi.style.display = 'none';

			const girisAlani = document.getElementById('oto-giris-alani');
			if (girisAlani) girisAlani.classList.add('gizli');

			const ipucuBtn = document.getElementById('oto-ipucu-btn');
			if (ipucuBtn) ipucuBtn.style.display = 'none';

			// Gün sonu ekranında hak noktaları + seri rozeti gizle
			const hakGrubu = document.querySelector('.oto-hak-grubu');
			if (hakGrubu) hakGrubu.style.display = 'none';
			const seriRozet = document.querySelector('.oto-seri-rozet');
			if (seriRozet) seriRozet.style.display = 'none';

			konteyner.appendChild(kart);
		}

		oto_geri_sayim_baslat();
	}

	function oto_geri_sayim_baslat() {
		function hesapla() {
			const simdi = new Date();
			const yarin = new Date(simdi.getFullYear(), simdi.getMonth(), simdi.getDate() + 1, 0, 0, 0);
			const fark = Math.max(0, Math.floor((yarin - simdi) / 1000));
			const saat = Math.floor(fark / 3600);
			const dakika = Math.floor((fark % 3600) / 60);
			const saniye = fark % 60;
			return String(saat).padStart(2, '0') + ':' +
				String(dakika).padStart(2, '0') + ':' +
				String(saniye).padStart(2, '0');
		}

		const el = document.getElementById('oto-geri-sayim');
		if (!el) return;

		el.textContent = hesapla();
		const interval = setInterval(function () {
			el.textContent = hesapla();
		}, 1000);

		const yarin = new Date();
		yarin.setDate(yarin.getDate() + 1);
		yarin.setHours(0, 0, 0, 0);
		const kalan = yarin - Date.now();
		setTimeout(function () {
			clearInterval(interval);
		}, kalan + 1000);
	}

	// =========================================================================
	// 11. TAHMIN / IPCU
	// =========================================================================

	// Responsive kutu boyutu — offsetWidth tabanlı, pencere genişliğini dikkate alır.
	function oto_kutucuk_boyutu_hesapla(karakterSayisi) {
		if (!karakterSayisi || karakterSayisi < 1) return { size: 28, fontSize: 12 };
		let genislik = 320;
		const grid = document.getElementById('oto-tahminler');
		if (grid && grid.offsetWidth > 0) {
			genislik = grid.offsetWidth;
		} else {
			// fallback için üst kapsayıcıya bak
			const wrap = document.getElementById('oyuncu-tahmin-oyunu');
			if (wrap && wrap.offsetWidth > 0) genislik = Math.min(wrap.offsetWidth - 28, 400);
		}
		const gap = 4;
		const maxBoyut = Math.floor((genislik - (gap * (karakterSayisi - 1))) / karakterSayisi);
		const tabloBoyutu = karakterSayisi <= 6  ? 40
		                  : karakterSayisi <= 9  ? 34
		                  : karakterSayisi <= 12 ? 28 : 22;
		const size = Math.max(16, Math.min(maxBoyut, tabloBoyutu));
		const fontSize = Math.max(9, Math.round(size * 0.4));
		return { size, fontSize };
	}

	// Eski sabit px fonksiyonu — artık kutucuk_boyutu_hesapla kullanılıyor, sadece fallback için bırakıldı.
	function oto_harf_kutu_boyutu_hesapla(normalizeIsim) {
		return oto_kutucuk_boyutu_hesapla(normalizeIsim.length);
	}

	// İSİM KUTULARI: "Harry Kane" → [H][A][R][R][Y] GAP [K][A][N][E]
	// oyuncu.oyuncu_adi_ham: boşluklu orijinal isim (normalize edilmemiş)
	function oto_isim_kutularini_olustur(oyuncu) {
		const el = document.getElementById('oto-aktif-tahmin-satiri');
		if (!el) return;
		el.innerHTML = '';

		// Ham ismi al; eğer yoksa sadece uzunluğa göre tek kelime olarak göster
		const hamIsim = oyuncu.oyuncu_adi_ham || '';
		const harfSayisi = oyuncu.cevap_uzunluk || 5;

		el.style.display = 'flex';
		el.style.flexWrap = 'wrap';
		el.style.justifyContent = 'center';

		// Boşluksuz harf sayısına göre boyut hesapla
		const boyut = oto_kutucuk_boyutu_hesapla(harfSayisi);

		if (hamIsim) {
			// Her kelimeyi ayrı grup olarak oluştur
			const kelimeler = hamIsim.split(' ');
			kelimeler.forEach(function(kelime, ki) {
				// Kelime grubu
				const grup = document.createElement('span');
				grup.className = 'oto-isim-kelime-grubu';
				grup.style.display = 'inline-flex';
				grup.style.gap = '3px';

				Array.from(kelime.toUpperCase()).forEach(function(harf, hi) {
					const kutu = document.createElement('span');
					kutu.className = 'oto-isim-kutu';
					kutu.dataset.kelime = ki;
					kutu.dataset.harf = hi;
					kutu.style.width = boyut.size + 'px';
					kutu.style.height = boyut.size + 'px';
					kutu.style.fontSize = boyut.fontSize + 'px';
					kutu.style.lineHeight = boyut.size + 'px';
					grup.appendChild(kutu);
				});

				el.appendChild(grup);

				// Kelimeler arası boşluk (son kelimeden sonra yok)
				if (ki < kelimeler.length - 1) {
					const bosluk = document.createElement('span');
					bosluk.style.width = boyut.size * 0.5 + 'px';
					bosluk.style.display = 'inline-block';
					el.appendChild(bosluk);
				}
			});
		} else {
			// Ham isim yoksa tek satır boş kutular
			const grup = document.createElement('span');
			grup.className = 'oto-isim-kelime-grubu';
			grup.style.display = 'inline-flex';
			grup.style.gap = '3px';
			for (let i = 0; i < harfSayisi; i++) {
				const kutu = document.createElement('span');
				kutu.className = 'oto-isim-kutu';
				kutu.dataset.kelime = 0;
				kutu.dataset.harf = i;
				kutu.style.width = boyut.size + 'px';
				kutu.style.height = boyut.size + 'px';
				kutu.style.fontSize = boyut.fontSize + 'px';
				kutu.style.lineHeight = boyut.size + 'px';
				grup.appendChild(kutu);
			}
			el.appendChild(grup);
		}
	}

	// Input'a yazıldıkça isim kutularını güncelle
	function oto_isim_kutularini_guncelle(deger) {
		const el = document.getElementById('oto-aktif-tahmin-satiri');
		if (!el) return;
		// Boşlukları sil → ham yazılan harfler
		const harfler = deger.replace(/\s+/g, '').toUpperCase();
		const kutular = el.querySelectorAll('.oto-isim-kutu');
		kutular.forEach(function(kutu, i) {
			if (i < harfler.length) {
				kutu.textContent = harfler[i];
				kutu.classList.add('dolu');
			} else {
				kutu.textContent = '';
				kutu.classList.remove('dolu');
			}
		});
	}

	function oto_tahmin_satiri_olustur(tahmin, sonuclar, animasyonlu) {
		const satirEl = document.createElement('div');
		satirEl.className = 'oto-tahmin-satiri';

		// Boşlukları atla: "Harry Kane" → H,A,R,R,Y,K,A,N,E
		const harfler = Array.from(tahmin.toUpperCase()).filter(function (h) { return h !== ' '; });
		const boyut = oto_kutucuk_boyutu_hesapla(harfler.length);

		harfler.forEach(function (harf, i) {
			const span = document.createElement('span');
			span.className = 'oto-harf ' + (sonuclar[i] || 'gri');
			span.textContent = harf;
			span.style.width = boyut.size + 'px';
			span.style.height = boyut.size + 'px';
			span.style.fontSize = boyut.fontSize + 'px';
			span.style.lineHeight = boyut.size + 'px';
			if (animasyonlu !== false) {
				span.style.animationDelay = (i * 0.08) + 's';
				span.classList.add('animasyon');
			}
			satirEl.appendChild(span);
		});
		return satirEl;
	}

	function oto_tahmin_gonder() {
		if (state.yukluyor) return;

		const aktifOyuncu = state.oyuncular[state.aktifIndex];
		if (!aktifOyuncu) return;
		if (aktifOyuncu.durum === 'kazandi' || aktifOyuncu.durum === 'kaybetti') return;

		// --- İSTEMCI TARAFI HAK KONTROLÜ ---
		const kalanHakOnce = state.maxHak - (aktifOyuncu.hak_kullanilan || 0);
		if (kalanHakOnce <= 0) {
			aktifOyuncu.durum = 'kaybetti';
			oto_state_kaydet();
			oto_blur_guncelle(state.maxHak);
			if (state.aktifIndex >= 4) {
				oto_gun_sonu_ozetini_goster();
			} else {
				oto_sonuc_karti_goster(false, aktifOyuncu.oyuncu_adi || '');
			}
			return;
		}

		const input = document.getElementById('oto-tahmin-input');
		if (!input) return;

		const girilenDeger = input.value.trim();
		if (!girilenDeger) {
			oto_mesaj_goster('Lütfen bir oyuncu adı girin.', 'hata');
			oto_input_shake();
			return;
		}

		state.yukluyor = true;
		const btn = document.getElementById('oto-tahmin-btn');
		if (btn) btn.disabled = true;
		input.disabled = true;

		fetch(oyunConfig.restUrl + 'tahmin', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': state.nonce,
			},
			credentials: 'same-origin',
			body: JSON.stringify({
				tahmin: girilenDeger,
				oyuncu_id: aktifOyuncu.id,
			}),
		})
		.then(function (res) {
			return res.json().then(function (data) {
				return { ok: res.ok, status: res.status, data: data };
			});
		})
		.then(function (result) {
			state.yukluyor = false;

			if (!result.ok) {
				const hataMetin = result.data && result.data.hata ? result.data.hata : ('Sunucu hatası (' + result.status + ').');
				oto_mesaj_goster(hataMetin, 'hata');
				input.disabled = false;
				if (btn) btn.disabled = false;
				return;
			}

			const yanit = result.data;

			const gecmisEl = document.getElementById('oto-tahminler');
			if (gecmisEl) {
				gecmisEl.appendChild(oto_tahmin_satiri_olustur(girilenDeger, yanit.sonuclar || []));
			}

			aktifOyuncu.hak_kullanilan = (aktifOyuncu.hak_kullanilan || 0) + 1;
			aktifOyuncu.tahminler = aktifOyuncu.tahminler || [];
			aktifOyuncu.tahminler.push(girilenDeger);

			// Sunucudan gelen kalan_hak varsa senkronize et
			if (yanit.kalan_hak !== undefined) {
				aktifOyuncu.hak_kullanilan = state.maxHak - yanit.kalan_hak;
			}

			const kalanHak = state.maxHak - aktifOyuncu.hak_kullanilan;
			oto_hak_noktalarini_guncelle(kalanHak);
			oto_blur_guncelle(aktifOyuncu.hak_kullanilan);

			// Aktif satırı temizle
			const aktifSatirSync = document.getElementById('oto-aktif-tahmin-satiri');
			if (aktifSatirSync) {
				aktifSatirSync.querySelectorAll('.oto-harf').forEach(function(k) {
					k.textContent = '';
				});
			}

			if (yanit.oyuncu_bitti) {
				const kazandi = !!yanit.kazandi;
				aktifOyuncu.durum = kazandi ? 'kazandi' : 'kaybetti';
				oto_state_kaydet();
				oto_mesaj_temizle();
				oto_blur_guncelle(state.maxHak);

				if (state.aktifIndex >= 4) {
					oto_gun_sonu_ozetini_goster();
				} else {
					oto_sonuc_karti_goster(kazandi, yanit.oyuncu_adi || '');
				}

				return;
			}

			aktifOyuncu.durum = 'devam';
			oto_state_kaydet();
			oto_mesaj_temizle();
			input.value = '';
			input.disabled = false;
			if (btn) btn.disabled = false;
			input.focus();
		})
		.catch(function () {
			state.yukluyor = false;
			oto_mesaj_goster('Bağlantı hatası. Lütfen tekrar deneyin.', 'hata');
			input.disabled = false;
			if (btn) btn.disabled = false;
		});
	}

	function oto_ipucu_iste() {
		if (state.yukluyor) return;

		const aktifOyuncu = state.oyuncular[state.aktifIndex];
		if (!aktifOyuncu) return;

		// HATA 5 DÜZELTMESİ: Her oyuncunun bağımsız ipucu_sayisi kullanılır.
		const oyuncuIpucuSayisi = aktifOyuncu.ipucu_sayisi || 0;
		const acilmis = aktifOyuncu.ipucu_acilan ? aktifOyuncu.ipucu_acilan.length : 0;
		if (acilmis >= oyuncuIpucuSayisi) return;

		state.yukluyor = true;
		const ipucuBtn = document.getElementById('oto-ipucu-btn');
		if (ipucuBtn) ipucuBtn.disabled = true;

		fetch(oyunConfig.restUrl + 'ipucu', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': state.nonce,
			},
			credentials: 'same-origin',
			body: JSON.stringify({
				oyuncu_id: aktifOyuncu.id,
				acilan_sayisi: acilmis,
			}),
		})
		.then(function (res) { return res.json(); })
		.then(function (data) {
			state.yukluyor = false;

			if (data.hata) {
				if (ipucuBtn) ipucuBtn.disabled = false;
				return;
			}

			if (!aktifOyuncu.ipucu_acilan) aktifOyuncu.ipucu_acilan = [];
			aktifOyuncu.ipucu_acilan.push(data.ipucu);

			const ipucuListesi = document.getElementById('oto-ipuclari');
			if (ipucuListesi) {
				const span = document.createElement('span');
				span.className = 'oto-ipucu-item gorunur';
				span.textContent = data.ipucu;
				ipucuListesi.appendChild(span);
			}

			aktifOyuncu.hak_kullanilan = (aktifOyuncu.hak_kullanilan || 0) + 1;
			const kalanHak = state.maxHak - aktifOyuncu.hak_kullanilan;
			oto_hak_noktalarini_guncelle(kalanHak);
			oto_blur_guncelle(aktifOyuncu.hak_kullanilan);

			oto_state_kaydet();

			if (!data.sonraki_var || aktifOyuncu.ipucu_acilan.length >= (aktifOyuncu.ipucu_sayisi || 0)) {
				if (ipucuBtn) ipucuBtn.style.display = 'none';
			} else if (ipucuBtn) {
				ipucuBtn.disabled = false;
			}

			if (kalanHak <= 0) {
				aktifOyuncu.durum = 'kaybetti';
				oto_state_kaydet();
				oto_blur_guncelle(state.maxHak);

				if (state.aktifIndex >= 4) {
					oto_gun_sonu_ozetini_goster();
				} else {
					oto_sonuc_karti_goster(false, '');
				}
			}
		})
		.catch(function () {
			state.yukluyor = false;
			if (ipucuBtn) ipucuBtn.disabled = false;
		});
	}

	// =========================================================================
	// 12. INIT
	// =========================================================================

	function oto_init() {
		const konteyner = document.getElementById('oyuncu-tahmin-oyunu');
		if (!konteyner) return;
		if (typeof oyunConfig === 'undefined') return;

		state.maxHak = parseInt(oyunConfig.maxHak, 10) || 5;
		state.nonce = oyunConfig.nonce;

		state.seri = oto_seri_cookie_oku();
		oto_seri_goster();

		const tahminBtn = document.getElementById('oto-tahmin-btn');
		if (tahminBtn) tahminBtn.addEventListener('click', oto_tahmin_gonder);

		const inputEl = document.getElementById('oto-tahmin-input');
		if (inputEl) {
			inputEl.addEventListener('keydown', function (e) {
				if (e.key === 'Enter') oto_tahmin_gonder();
			});
		}

		const ipucuBtn = document.getElementById('oto-ipucu-btn');
		if (ipucuBtn) ipucuBtn.addEventListener('click', oto_ipucu_iste);

		fetch(oyunConfig.restUrl + 'durum?_t=' + Date.now(), {
			method: 'GET',
			headers: { 'X-WP-Nonce': state.nonce },
			credentials: 'same-origin',
		})
		.then(function (res) { return res.json(); })
		.then(function (data) {
			if (data.hata) {
				oto_mesaj_goster(data.hata, 'hata');
				return;
			}

			if (data.nonce) state.nonce = data.nonce;

			state.set = Array.isArray(data.set) ? data.set : [];
			state.aktifIndex = parseInt(data.aktif_index, 10) || 0;
			state.gunBitti = !!data.gun_bitti;

			// Oyuncuları normalize et ve set ile senkronize et
			const rawOyuncular = Array.isArray(data.oyuncular) ? data.oyuncular : [];
			const yeniOyuncular = [];
			state.set.forEach(function (id, i) {
				const mevcut = rawOyuncular[i] || {};
				yeniOyuncular[i] = oto_oyuncu_normalize_et(mevcut, parseInt(id, 10) || 0);
				if (!yeniOyuncular[i].durum) yeniOyuncular[i].durum = (i === state.aktifIndex ? 'devam' : 'bekliyor');
			});
			state.oyuncular = yeniOyuncular;

			// HATA 2 DÜZELTMESİ: setMeta oyuncuların gerçek zorluk bilgisinden türetilir.
			// Her oyuncunun zorluk bilgisi PHP tarafından oyuncular dizisinde gönderilir.
			const ZORLUK_ETIKET = { kolay: 'Kolay', orta: 'Orta', zor: 'Zor' };
			state.setMeta = state.oyuncular.map(function (o) {
				const z = o.zorluk || (Array.isArray(data.set_meta) && data.set_meta[state.oyuncular.indexOf(o)]
					? data.set_meta[state.oyuncular.indexOf(o)].zorluk
					: 'kolay');
				return { zorluk: z, etiket: ZORLUK_ETIKET[z] || z };
			});

			// HATA 5: aktif oyuncunun ipucu_sayisi init'te PHP'den gelen değerle güncellenir
			const aktifOyuncuInit = state.oyuncular[state.aktifIndex];
			if (aktifOyuncuInit && !aktifOyuncuInit.ipucu_sayisi && data.ipucu_sayisi !== undefined) {
				aktifOyuncuInit.ipucu_sayisi = parseInt(data.ipucu_sayisi, 10) || 0;
			}

			oto_state_kaydet();

			if (state.gunBitti) {
				oto_gun_sonu_ozetini_goster();
				return;
			}

			oto_aktif_oyuncuyu_render_et(data.gorsel_url || '');

			const aktifOyuncuReload = state.oyuncular[state.aktifIndex];
			if (aktifOyuncuReload) {
				if (aktifOyuncuReload.durum === 'kazandi' || aktifOyuncuReload.durum === 'kaybetti') {
					oto_sonuc_karti_goster(aktifOyuncuReload.durum === 'kazandi', aktifOyuncuReload.oyuncu_adi || '');
				} else {
					// Geçmişi yeniden oluştur (sayfa yenilendiğinde)
					const gecmisEl = document.getElementById('oto-tahminler');
					if (gecmisEl && aktifOyuncuReload.tahminler && aktifOyuncuReload.tahmin_sonuclari) {
						gecmisEl.innerHTML = '';
						aktifOyuncuReload.tahminler.forEach(function (t, i) {
							if (aktifOyuncuReload.tahmin_sonuclari[i]) {
								gecmisEl.appendChild(oto_tahmin_satiri_olustur(t, aktifOyuncuReload.tahmin_sonuclari[i], false));
							}
						});
					}
					// Kutucukların anında güncellenmesi için bir kerelik resize tetikle
					window.dispatchEvent(new Event('resize'));
				}
			}
		})
		.catch(function () {
			oto_mesaj_goster('Oyun yüklenemedi. Lütfen sayfayı yenileyin.', 'hata');
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', oto_init);
	} else {
		oto_init();
	}

	// Pencere değiştiğinde yeniden hesaplamak (Debounced)
	let otoResizeTimer;
	window.addEventListener('resize', function() {
		clearTimeout(otoResizeTimer);
		otoResizeTimer = setTimeout(function() {
			const satirlar = document.querySelectorAll('.oto-tahmin-satiri, #oto-aktif-tahmin-satiri');
			satirlar.forEach(function(satir) {
				const harfler = satir.querySelectorAll('.oto-harf');
				if (harfler.length > 0) {
					const boyut = oto_kutucuk_boyutu_hesapla(harfler.length);
					harfler.forEach(function(span) {
						span.style.width = boyut.size + 'px';
						span.style.height = boyut.size + 'px';
						span.style.fontSize = boyut.fontSize + 'px';
						span.style.lineHeight = boyut.size + 'px';
					});
				}
			});
		}, 100);
	});

	// Input'a yazıldıkça isim kutularını doldur
	const otoTahminInput = document.getElementById('oto-tahmin-input');
	if (otoTahminInput) {
		otoTahminInput.addEventListener('input', function(e) {
			oto_isim_kutularini_guncelle(e.target.value);
		});
	}

})();

