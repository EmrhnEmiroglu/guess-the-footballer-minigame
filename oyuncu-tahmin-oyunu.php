<?php
/**
 * Plugin Name: Oyuncu Tahmin Oyunu
 * Plugin URI:  https://sporkulis.com
 * Description: Blurlu görsel ve harf tahmin mekanikleriyle günlük oyuncu tahmin oyunu
 * Version:     2.0.0
 * Author:      Sporkulis
 * Text Domain: oyuncu-tahmin-oyunu
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OTO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OTO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OTO_VERSION', '2.0.0' );

// Günlük set yapısı: 2 kolay + 2 orta + 1 zor
define( 'OTO_SET_YAPISI', array(
	array( 'zorluk' => 'kolay', 'etiket' => 'Kolay' ),
	array( 'zorluk' => 'kolay', 'etiket' => 'Kolay' ),
	array( 'zorluk' => 'orta',  'etiket' => 'Orta' ),
	array( 'zorluk' => 'orta',  'etiket' => 'Orta' ),
	array( 'zorluk' => 'zor',   'etiket' => 'Zor' ),
) );

// =============================================================================
// 1. CUSTOM POST TYPE KAYDI
// =============================================================================

add_action( 'init', 'oto_register_cpt' );

function oto_register_cpt() {
	$labels = array(
		'name'          => 'Oyuncular',
		'singular_name' => 'Oyuncu',
		'add_new'       => 'Yeni Ekle',
		'add_new_item'  => 'Yeni Oyuncu Ekle',
		'edit_item'     => 'yu Düzenle',
		'new_item'      => 'Yeni Oyuncu',
		'view_item'     => 'Oyuncuyu Görüntüle',
		'search_items'  => 'Oyuncu Ara',
		'not_found'     => 'Oyuncu bulunamadı',
		'menu_name'     => 'Oyuncular',
	);

	register_post_type( 'oyun_oyuncusu', array(
		'labels'       => $labels,
		'public'       => false,
		'show_ui'      => true,
		'show_in_rest' => false,
		'supports'     => array( 'title' ),
		'menu_icon'    => 'dashicons-groups',
		'capabilities' => array(
			'edit_post'          => 'edit_posts',
			'edit_posts'         => 'edit_posts',
			'edit_others_posts'  => 'edit_others_posts',
			'publish_posts'      => 'publish_posts',
			'read_post'          => 'read',
			'read_private_posts' => 'read_private_posts',
			'delete_post'        => 'delete_posts',
		),
	) );
}

// =============================================================================
// 2. ADMIN LİSTE SÜTUNLARI
// =============================================================================

add_filter( 'manage_oyun_oyuncusu_posts_columns', 'oto_admin_columns' );

function oto_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $value ) {
		$new[ $key ] = $value;
		if ( 'title' === $key ) {
			$new['kucuk_gorsel']    = 'Görsel';
			$new['zorluk']          = 'Zorluk';
			$new['durum']           = 'Durum';
			$new['gosterim_sayisi'] = 'Gösterim';
		}
	}
	return $new;
}

add_action( 'manage_oyun_oyuncusu_posts_custom_column', 'oto_admin_column_content', 10, 2 );

function oto_admin_column_content( $column, $post_id ) {
	if ( 'kucuk_gorsel' === $column ) {
		$id = absint( get_post_meta( $post_id, '_oyuncu_gorsel_id', true ) );
		echo $id ? wp_get_attachment_image( $id, array( 50, 50 ) ) : '<span style="color:#999">—</span>';
	}

	if ( 'zorluk' === $column ) {
		$z        = get_post_meta( $post_id, '_oyuncu_zorluk', true );
		$renkler  = array( 'kolay' => '#4CAF50', 'orta' => '#FF9800', 'zor' => '#F44336' );
		$etiketler = array( 'kolay' => 'Kolay', 'orta' => 'Orta', 'zor' => 'Zor' );
		if ( $z && isset( $renkler[ $z ] ) ) {
			$r = esc_attr( $renkler[ $z ] );
			$e = esc_html( $etiketler[ $z ] );
			echo "<span style='background:{$r};color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;'>{$e}</span>";
		} else {
			echo '<span style="color:#999">—</span>';
		}
	}

	if ( 'durum' === $column ) {
		$aktif = get_post_meta( $post_id, '_oyuncu_aktif', true );
		echo ( '0' === $aktif )
			? '<span style="color:#999">⛔ Pasif</span>'
			: '<span style="color:#0a8a0a">✅ Aktif</span>';
	}

	if ( 'gosterim_sayisi' === $column ) {
		echo esc_html( absint( get_post_meta( $post_id, '_oyuncu_gosterim_sayisi', true ) ) );
	}
}

// =============================================================================
// 2b. ADMIN FİLTRE + SIRALAMA
// =============================================================================

// Zorluk ve Durum sütunlarını sıralanabilir yap
add_filter( 'manage_edit-oyun_oyuncusu_sortable_columns', 'oto_sortable_columns' );

function oto_sortable_columns( $columns ) {
	$columns['zorluk'] = 'zorluk';
	$columns['durum']  = 'durum';
	return $columns;
}

// Filtre formu — liste üstüne
add_action( 'restrict_manage_posts', 'oto_admin_filtre_formu' );

function oto_admin_filtre_formu( $post_type ) {
	if ( 'oyun_oyuncusu' !== $post_type ) return;

	$secili_zorluk = isset( $_GET['oto_zorluk'] ) ? sanitize_text_field( wp_unslash( $_GET['oto_zorluk'] ) ) : '';
	$secili_durum  = isset( $_GET['oto_durum'] )  ? sanitize_text_field( wp_unslash( $_GET['oto_durum'] ) )  : '';

	?>
	<select name="oto_zorluk">
		<option value="">— Tüm Zorluklar —</option>
		<option value="kolay" <?php selected( $secili_zorluk, 'kolay' ); ?>>Kolay</option>
		<option value="orta"  <?php selected( $secili_zorluk, 'orta' );  ?>>Orta</option>
		<option value="zor"   <?php selected( $secili_zorluk, 'zor' );   ?>>Zor</option>
	</select>
	<select name="oto_durum">
		<option value="">— Tüm Durumlar —</option>
		<option value="aktif"  <?php selected( $secili_durum, 'aktif' );  ?>>Aktif</option>
		<option value="pasif"  <?php selected( $secili_durum, 'pasif' );  ?>>Pasif</option>
	</select>
	<?php
}

// Filtreyi ve sıralamayı sorguya uygula
add_action( 'pre_get_posts', 'oto_admin_filtre_uygula' );

function oto_admin_filtre_uygula( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) return;
	if ( 'oyun_oyuncusu' !== $query->get( 'post_type' ) ) return;

	$meta_query = array( 'relation' => 'AND' );

	// Zorluk filtresi
	$zorluk = isset( $_GET['oto_zorluk'] ) ? sanitize_text_field( wp_unslash( $_GET['oto_zorluk'] ) ) : '';
	if ( in_array( $zorluk, array( 'kolay', 'orta', 'zor' ), true ) ) {
		$meta_query[] = array( 'key' => '_oyuncu_zorluk', 'value' => $zorluk, 'compare' => '=' );
	}

	// Durum filtresi
	$durum = isset( $_GET['oto_durum'] ) ? sanitize_text_field( wp_unslash( $_GET['oto_durum'] ) ) : '';
	if ( 'aktif' === $durum ) {
		$meta_query[] = array(
			'relation' => 'OR',
			array( 'key' => '_oyuncu_aktif', 'compare' => 'NOT EXISTS' ),
			array( 'key' => '_oyuncu_aktif', 'value' => '1', 'compare' => '=' ),
		);
	} elseif ( 'pasif' === $durum ) {
		$meta_query[] = array( 'key' => '_oyuncu_aktif', 'value' => '0', 'compare' => '=' );
	}

	if ( count( $meta_query ) > 1 ) {
		$query->set( 'meta_query', $meta_query );
	}

	// Sıralama
	$orderby = $query->get( 'orderby' );
	if ( 'zorluk' === $orderby ) {
		$query->set( 'meta_key', '_oyuncu_zorluk' );
		$query->set( 'orderby', 'meta_value' );
	} elseif ( 'durum' === $orderby ) {
		$query->set( 'meta_key', '_oyuncu_aktif' );
		$query->set( 'orderby', 'meta_value' );
	}
}

// =============================================================================
// 3. META BOX
// =============================================================================

add_action( 'add_meta_boxes', 'oto_add_meta_box' );

function oto_add_meta_box() {
	add_meta_box(
		'oto_oyuncu_bilgileri',
		'Oyuncu Görseli ve Bilgileri',
		'oto_meta_box_render',
		'oyun_oyuncusu',
		'normal',
		'high'
	);
}

function oto_meta_box_render( $post ) {
	wp_nonce_field( 'oto_meta_box_save', 'oto_meta_box_nonce' );

	$gorsel_id  = absint( get_post_meta( $post->ID, '_oyuncu_gorsel_id', true ) );
	$gorsel_url = esc_url( get_post_meta( $post->ID, '_oyuncu_gorsel_url', true ) );
	$zorluk     = sanitize_text_field( get_post_meta( $post->ID, '_oyuncu_zorluk', true ) ) ?: 'kolay';
	$ipucu_1    = sanitize_text_field( get_post_meta( $post->ID, '_oyuncu_ipucu_1', true ) );
	$ipucu_2    = sanitize_text_field( get_post_meta( $post->ID, '_oyuncu_ipucu_2', true ) );
	$ipucu_3    = sanitize_text_field( get_post_meta( $post->ID, '_oyuncu_ipucu_3', true ) );
	$aktif_val  = get_post_meta( $post->ID, '_oyuncu_aktif', true );
	// KARAR: meta hiç yoksa (yeni oyuncu) varsayılan aktif kabul edilir
	$aktif_checked = ( '0' !== $aktif_val );
	?>
	<table class="form-table">
		<tr>
			<th><label>Görsel Seç</label></th>
			<td>
				<input type="hidden" id="oto_gorsel_id" name="oto_gorsel_id" value="<?php echo esc_attr( $gorsel_id ); ?>" />
				<input type="hidden" id="oto_gorsel_url" name="oto_gorsel_url" value="<?php echo esc_attr( $gorsel_url ); ?>" />
				<button type="button" class="button" id="oto_gorsel_sec_btn">Medya Kütüphanesinden Seç</button>
				<button type="button" class="button" id="oto_gorsel_kaldir_btn" style="<?php echo $gorsel_id ? '' : 'display:none;'; ?>">Görseli Kaldır</button>
				<div id="oto_gorsel_onizleme" style="margin-top:10px;">
					<?php if ( $gorsel_url ) : ?>
						<img src="<?php echo esc_url( $gorsel_url ); ?>" style="max-width:200px;max-height:200px;" />
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<tr>
			<th><label>Zorluk</label></th>
			<td>
				<?php foreach ( array( 'kolay' => 'Kolay', 'orta' => 'Orta', 'zor' => 'Zor' ) as $val => $etiket ) : ?>
					<label style="margin-right:16px;">
						<input type="radio" name="oto_zorluk" value="<?php echo esc_attr( $val ); ?>" <?php checked( $val, $zorluk ); ?> />
						<?php echo esc_html( $etiket ); ?>
					</label>
				<?php endforeach; ?>
			</td>
		</tr>
		<tr>
			<th><label for="oto_ipucu_1">İpucu 1</label></th>
			<td><input type="text" id="oto_ipucu_1" name="oto_ipucu_1" value="<?php echo esc_attr( $ipucu_1 ); ?>" class="regular-text" placeholder="Örn: Arjantinli" /></td>
		</tr>
		<tr>
			<th><label for="oto_ipucu_2">İpucu 2</label></th>
			<td><input type="text" id="oto_ipucu_2" name="oto_ipucu_2" value="<?php echo esc_attr( $ipucu_2 ); ?>" class="regular-text" placeholder="Örn: Forvet" /></td>
		</tr>
		<tr>
			<th><label for="oto_ipucu_3">İpucu 3</label></th>
			<td><input type="text" id="oto_ipucu_3" name="oto_ipucu_3" value="<?php echo esc_attr( $ipucu_3 ); ?>" class="regular-text" placeholder="Örn: Dünya Kupası şampiyonu" /></td>
		</tr>
		<tr>
			<th><label>Havuzda Göster</label></th>
			<td>
				<label>
					<input type="checkbox" name="oto_aktif" value="1" <?php checked( $aktif_checked ); ?> />
					Bu oyuncuyu günlük set havuzuna dahil et
				</label>
			</td>
		</tr>
	</table>

	<script>
	(function() {
		var frame;
		document.getElementById('oto_gorsel_sec_btn').addEventListener('click', function(e) {
			e.preventDefault();
			if (frame) { frame.open(); return; }
			frame = wp.media({
				title: 'Oyuncu Görseli Seç',
				button: { text: 'Bu görseli seç' },
				multiple: false,
				library: { type: 'image' }
			});
			frame.on('select', function() {
				var a = frame.state().get('selection').first().toJSON();
				document.getElementById('oto_gorsel_id').value = a.id;
				document.getElementById('oto_gorsel_url').value = a.url;
				document.getElementById('oto_gorsel_onizleme').innerHTML = '<img src="' + a.url + '" style="max-width:200px;max-height:200px;" />';
				document.getElementById('oto_gorsel_kaldir_btn').style.display = '';
			});
			frame.open();
		});
		document.getElementById('oto_gorsel_kaldir_btn').addEventListener('click', function(e) {
			e.preventDefault();
			document.getElementById('oto_gorsel_id').value = '';
			document.getElementById('oto_gorsel_url').value = '';
			document.getElementById('oto_gorsel_onizleme').innerHTML = '';
			this.style.display = 'none';
		});
	})();
	</script>
	<?php
}

// =============================================================================
// 4. META BOX KAYDETME
// =============================================================================

add_action( 'save_post_oyun_oyuncusu', 'oto_meta_box_save' );

function oto_meta_box_save( $post_id ) {
	if ( ! isset( $_POST['oto_meta_box_nonce'] ) ) return;
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['oto_meta_box_nonce'] ) ), 'oto_meta_box_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_posts' ) ) return;

	$gorsel_id  = isset( $_POST['oto_gorsel_id'] ) ? absint( $_POST['oto_gorsel_id'] ) : 0;
	$gorsel_url = isset( $_POST['oto_gorsel_url'] ) ? esc_url_raw( wp_unslash( $_POST['oto_gorsel_url'] ) ) : '';
	$zorluk_raw = isset( $_POST['oto_zorluk'] ) ? sanitize_text_field( wp_unslash( $_POST['oto_zorluk'] ) ) : 'kolay';
	$zorluk     = in_array( $zorluk_raw, array( 'kolay', 'orta', 'zor' ), true ) ? $zorluk_raw : 'kolay';
	$ipucu_1    = isset( $_POST['oto_ipucu_1'] ) ? sanitize_text_field( wp_unslash( $_POST['oto_ipucu_1'] ) ) : '';
	$ipucu_2    = isset( $_POST['oto_ipucu_2'] ) ? sanitize_text_field( wp_unslash( $_POST['oto_ipucu_2'] ) ) : '';
	$ipucu_3    = isset( $_POST['oto_ipucu_3'] ) ? sanitize_text_field( wp_unslash( $_POST['oto_ipucu_3'] ) ) : '';
	$aktif      = isset( $_POST['oto_aktif'] ) ? '1' : '0';

	update_post_meta( $post_id, '_oyuncu_gorsel_id', $gorsel_id );
	update_post_meta( $post_id, '_oyuncu_gorsel_url', $gorsel_url );
	update_post_meta( $post_id, '_oyuncu_zorluk', $zorluk );
	update_post_meta( $post_id, '_oyuncu_ipucu_1', $ipucu_1 );
	update_post_meta( $post_id, '_oyuncu_ipucu_2', $ipucu_2 );
	update_post_meta( $post_id, '_oyuncu_ipucu_3', $ipucu_3 );
	update_post_meta( $post_id, '_oyuncu_aktif', $aktif );

	// Oyuncu değişti — bugünün set cache'ini temizle
	delete_transient( 'oto_set_' . wp_date( 'Y-m-d' ) );
}

// =============================================================================
// 5. ADMİN GÜNLÜk SET SAYFASI
// =============================================================================

add_action( 'admin_menu', 'oto_admin_menu' );

function oto_admin_menu() {
	add_submenu_page(
		'edit.php?post_type=oyun_oyuncusu',
		'Günlük Set',
		'Günlük Set',
		'edit_posts',
		'oto-gunluk-set',
		'oto_gunluk_set_sayfasi'
	);
	add_submenu_page(
		'edit.php?post_type=oyun_oyuncusu',
		'Nasıl Kullanılır',
		'📖 Nasıl Kullanılır',
		'edit_posts',
		'oto-nasil-kullanilir',
		'oto_nasil_kullanilir_sayfasi'
	);
}

function oto_nasil_kullanilir_sayfasi() {
	if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'Yetkiniz yok.' );
	?>
	<div class="wrap" style="max-width:860px;">
		<h1>📖 Oyuncu Tahmin Oyunu — Nasıl Kullanılır?</h1>

		<div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:24px 28px;margin-top:16px;">

			<h2 style="margin-top:0;border-bottom:2px solid #FF6B00;padding-bottom:8px;color:#FF6B00;">🚀 Kurulum</h2>
			<ol style="line-height:2;">
				<li>Plugini <code>wp-content/plugins/</code> klasörüne yükleyin ve <strong>Eklentiler</strong> sayfasından etkinleştirin.</li>
				<li>Herhangi bir sayfaya <code>[oyuncu_tahmin_oyunu]</code> kısa kodunu ekleyin — oyun orada görünür.</li>
				<li>Aşağıdaki adımları izleyerek oyuncuları ve günlük setleri ayarlayın.</li>
			</ol>

			<h2 style="border-bottom:2px solid #FF6B00;padding-bottom:8px;color:#FF6B00;">👤 Oyuncu Ekleme</h2>
			<ol style="line-height:2;">
				<li><strong>Oyuncular → Yeni Ekle</strong> menüsüne tıklayın.</li>
				<li><strong>Başlık</strong> alanına oyuncunun tam adını yazın (örn. <em>Harry Kane</em>).</li>
				<li><strong>Görsel Seç</strong> butonuyla oyuncunun fotoğrafını medya kütüphanesinden seçin.</li>
				<li><strong>Zorluk</strong> seçin: Kolay / Orta / Zor.</li>
				<li>En fazla 3 <strong>İpucu</strong> girin (örn. "Arjantinli", "Forvet", "Dünya Kupası şampiyonu").</li>
				<li><strong>Havuzda Göster</strong> kutusunu işaretleyin — işaretlenmeyenler günlük sette seçilmez.</li>
				<li><strong>Yayınla</strong> butonuna tıklayın.</li>
			</ol>

			<h2 style="border-bottom:2px solid #FF6B00;padding-bottom:8px;color:#FF6B00;">📅 Günlük Set Yönetimi</h2>
			<p>Her gün <strong>2 Kolay + 2 Orta + 1 Zor</strong> oyuncu otomatik seçilir. İsterseniz manuel olarak değiştirebilirsiniz.</p>
			<ol style="line-height:2;">
				<li><strong>Oyuncular → Günlük Set</strong> menüsüne gidin.</li>
				<li>Düzenlemek istediğiniz günün satırındaki <strong>Düzenle</strong> butonuna tıklayın.</li>
				<li>Her slot için oyuncu seçin veya <em>— Otomatik —</em> bırakın.</li>
				<li><strong>Kaydet</strong> butonuna tıklayın.</li>
			</ol>
			<div style="background:#fff8e1;border-left:4px solid #FF9800;padding:10px 14px;border-radius:4px;margin:12px 0;">
				⚠️ <strong>Geçmiş tarihlerdeki setler düzenlenemez.</strong> En fazla gelecek 8 gün ayarlanabilir.
			</div>

			<h2 style="border-bottom:2px solid #FF6B00;padding-bottom:8px;color:#FF6B00;">🔍 Oyuncu Listeleme, Filtreleme ve Sıralama</h2>
			<ul style="line-height:2;">
				<li><strong>Oyuncular</strong> menüsünde tüm oyuncuları görürsünüz.</li>
				<li>Listenin üstündeki <strong>Tüm Zorluklar</strong> açılır menüsünden Kolay / Orta / Zor filtresi uygulayın.</li>
				<li><strong>Tüm Durumlar</strong> açılır menüsünden Aktif / Pasif filtreleyebilirsiniz.</li>
				<li>Sütun başlıklarına (<strong>Zorluk</strong>, <strong>Durum</strong>) tıklayarak listeyi sıralayabilirsiniz.</li>
			</ul>

			<h2 style="border-bottom:2px solid #FF6B00;padding-bottom:8px;color:#FF6B00;">🎮 Oyun Nasıl Çalışır?</h2>
			<ul style="line-height:2;">
				<li>Her gün 5 farklı oyuncu vardır: sırasıyla 2 Kolay, 2 Orta, 1 Zor.</li>
				<li>Oyuncu görseli başta <strong>bulanık</strong> görünür; her yanlış tahminde biraz netleşir.</li>
				<li>Oyuncunun adını tahmin edin. <span style="background:#4CAF50;color:#fff;padding:1px 6px;border-radius:3px;">yeşil</span> = doğru harf doğru yer, <span style="background:#FF9800;color:#fff;padding:1px 6px;border-radius:3px;">turuncu</span> = doğru harf yanlış yer, <span style="background:#9e9e9e;color:#fff;padding:1px 6px;border-radius:3px;">gri</span> = o harf isimde yok.</li>
				<li>5 hak var. Hakları dolmadan doğru tahmin ederseniz bir sonraki oyuncuya geçersiniz.</li>
				<li>İpucu almak 1 hak harcar.</li>
				<li>5 oyuncunun tamamı doğru bilinirse günlük seri (+1) kazanılır.</li>
			</ul>

			<h2 style="border-bottom:2px solid #FF6B00;padding-bottom:8px;color:#FF6B00;">💡 İpuçları ve Öneriler</h2>
			<ul style="line-height:2;">
				<li>Kolay oyuncularda çok tanınan isimleri, Zor'da daha az bilinen oyuncuları tercih edin.</li>
				<li>Her zorluk seviyesinde <strong>en az 5–10 oyuncu</strong> olması önerilir; aksi hâlde aynı oyuncular tekrar edebilir.</li>
				<li>Pasife alınan oyuncular havuzdan çıkar, silinmez — istediğinizde tekrar aktif edebilirsiniz.</li>
				<li>Kısa kod parametresi: <code>[oyuncu_tahmin gorsel_boyut="500"]</code> (piksel cinsinden genişlik).</li>
			</ul>

		</div>

		<div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:24px 28px;margin-top:20px;">
			<h2 style="margin-top:0;border-bottom:2px solid #1976D2;padding-bottom:8px;color:#1976D2;">📋 Hazır Sayfa İçeriği — Kopyala &amp; Yapıştır</h2>
			<p style="color:#555;">WordPress editöründe <strong>Kod editörü</strong> (sağ üst ⋮ → Kod editörü) görünümüne geçip aşağıdaki içeriği yapıştırın. Slug: <code>futbolcu-tahmin-oyunu</code></p>

			<details style="margin-top:12px;">
				<summary style="cursor:pointer;font-weight:600;font-size:14px;padding:8px 0;">▶ HTML içeriğini göster</summary>
				<textarea readonly rows="55" style="width:100%;font-family:monospace;font-size:12px;margin-top:10px;padding:10px;border:1px solid #ddd;border-radius:4px;background:#f9f9f9;resize:vertical;"><!-- wp:group {"className":"oto-sayfa-hero","style":{"color":{"background":"#1c1c1c"},"spacing":{"padding":{"top":"40px","bottom":"32px","left":"20px","right":"20px"}}}} -->
<div class="wp-block-group oto-sayfa-hero" style="background-color:#1c1c1c;padding-top:40px;padding-right:20px;padding-bottom:32px;padding-left:20px;">
<!-- wp:heading {"level":1,"style":{"color":{"text":"#ffffff"},"typography":{"fontSize":"28px","fontWeight":"700"},"spacing":{"margin":{"bottom":"12px"}}}} -->
<h1 class="wp-block-heading" style="color:#ffffff;font-size:28px;font-weight:700;margin-bottom:12px;">🎯 Günlük Futbolcu Tahmin Oyunu</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"color":{"text":"#cccccc"},"typography":{"fontSize":"15px"}}} -->
<p style="color:#cccccc;font-size:15px;">Her gün 5 farklı futbolcu seni bekliyor. Bulanık görselden tahmin et, harf ipuçlarıyla doğru ismi bul. Serinle her gün geri dön!</p>
<!-- /wp:paragraph -->
<!-- wp:columns {"style":{"spacing":{"blockGap":"16px","margin":{"top":"20px"}}}} -->
<div class="wp-block-columns" style="margin-top:20px;">
<!-- wp:column {"width":"33%"} -->
<div class="wp-block-column" style="flex-basis:33%;">
<!-- wp:paragraph {"style":{"color":{"text":"#FF6B00"},"typography":{"fontSize":"13px","fontWeight":"600"}}} -->
<p style="color:#FF6B00;font-size:13px;font-weight:600;">🔥 5 Hak</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"width":"33%"} -->
<div class="wp-block-column" style="flex-basis:33%;">
<!-- wp:paragraph {"style":{"color":{"text":"#FF6B00"},"typography":{"fontSize":"13px","fontWeight":"600"}}} -->
<p style="color:#FF6B00;font-size:13px;font-weight:600;">📅 Her Gün Yeni</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"width":"33%"} -->
<div class="wp-block-column" style="flex-basis:33%;">
<!-- wp:paragraph {"style":{"color":{"text":"#FF6B00"},"typography":{"fontSize":"13px","fontWeight":"600"}}} -->
<p style="color:#FF6B00;font-size:13px;font-weight:600;">🏆 Seri Kazan</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:shortcode -->
[oyuncu_tahmin gorsel_boyut="420"]
<!-- /wp:shortcode -->

<!-- wp:heading {"level":2,"style":{"spacing":{"margin":{"top":"40px","bottom":"12px"}}}} -->
<h2 class="wp-block-heading" style="margin-top:40px;margin-bottom:12px;">Nasıl Oynanır?</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li>Ekranda bulanık bir futbolcu fotoğrafı göreceksin.</li>
<li>Oyuncunun adını tahmin et — her harf yeşil, turuncu veya gri renkte gösterilir.</li>
<li>Yeşil = doğru harf, doğru konum. Turuncu = harf var ama yanlış konum. Gri = bu harf yok.</li>
<li>Her yanlış tahminde görsel biraz daha netleşir.</li>
<li>5 hak bitmeden doğru tahmin edersen sonraki oyuncuya geçersin.</li>
<li>İpucu almak 1 hak harcar — dikkatli kullan!</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading {"level":2,"style":{"spacing":{"margin":{"top":"36px","bottom":"12px"}}}} -->
<h2 class="wp-block-heading" style="margin-top:36px;margin-bottom:12px;">Sıkça Sorulan Sorular</h2>
<!-- /wp:heading -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Oyun ne zaman yenilenir?</summary>
<!-- wp:paragraph -->
<p>Her gün gece yarısı yeni 5 oyuncu gelir. Önceki günü tamamladıysan seri sayın artar.</p>
<!-- /wp:paragraph -->
</details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Zorluk seviyeleri nelerdir?</summary>
<!-- wp:paragraph -->
<p>Günlük 5 oyuncu şöyle sıralanır: 2 Kolay, 2 Orta, 1 Zor. Kolay oyuncular dünyaca ünlü isimler, Zor oyuncular biraz daha az tanınanlardır.</p>
<!-- /wp:paragraph -->
</details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Seri nasıl hesaplanır?</summary>
<!-- wp:paragraph -->
<p>Bir günün 5 oyuncusunun tamamını doğru tahmin edersen seri +1 artar. Tek bir oyuncu kaçırılsa bile seri sıfırlanır.</p>
<!-- /wp:paragraph -->
</details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>İpucu almak ne kadar hak götürür?</summary>
<!-- wp:paragraph -->
<p>Her ipucu 1 hak harcar. Toplamda 5 hakkın var. İpucu görseli netleştirmez, yalnızca yazılı bir bilgi açar.</p>
<!-- /wp:paragraph -->
</details>
<!-- /wp:details --></textarea>
			</details>

			<h3 style="margin-top:20px;font-size:14px;">RankMath Ayarları (Sayfa düzenlenirken)</h3>
			<table style="border-collapse:collapse;width:100%;font-size:13px;">
				<tr style="background:#f5f5f5;">
					<th style="padding:8px 12px;text-align:left;border:1px solid #ddd;width:200px;">Alan</th>
					<th style="padding:8px 12px;text-align:left;border:1px solid #ddd;">Önerilen Değer</th>
				</tr>
				<tr>
					<td style="padding:8px 12px;border:1px solid #ddd;">Focus Keyword</td>
					<td style="padding:8px 12px;border:1px solid #ddd;font-family:monospace;">futbolcu tahmin oyunu</td>
				</tr>
				<tr style="background:#f9f9f9;">
					<td style="padding:8px 12px;border:1px solid #ddd;">SEO Title</td>
					<td style="padding:8px 12px;border:1px solid #ddd;font-family:monospace;">Günlük Futbolcu Tahmin Oyunu | %sitename%</td>
				</tr>
				<tr>
					<td style="padding:8px 12px;border:1px solid #ddd;">Meta Description</td>
					<td style="padding:8px 12px;border:1px solid #ddd;font-family:monospace;">Her gün 5 farklı futbolcu seni bekliyor. Bulanık görselden tahmin et, harf ipuçlarıyla doğru ismi bul. Ücretsiz oyna!</td>
				</tr>
				<tr style="background:#f9f9f9;">
					<td style="padding:8px 12px;border:1px solid #ddd;">Schema Type</td>
					<td style="padding:8px 12px;border:1px solid #ddd;">Article → <em>Game schema plugin tarafından ayrıca eklenir</em></td>
				</tr>
				<tr>
					<td style="padding:8px 12px;border:1px solid #ddd;">Sayfa Slug</td>
					<td style="padding:8px 12px;border:1px solid #ddd;font-family:monospace;">futbolcu-tahmin-oyunu</td>
				</tr>
				<tr style="background:#f9f9f9;">
					<td style="padding:8px 12px;border:1px solid #ddd;">Canonical</td>
					<td style="padding:8px 12px;border:1px solid #ddd;">RankMath otomatik ayarlar, dokunma</td>
				</tr>
			</table>

		</div>
	</div>
	<?php
}

function oto_bos_besli_dizi(): array {
	return array( null, null, null, null, null );
}

function oto_gunluk_set_dizisini_normalize_et( $set ): array {
	$normalize = array( 0, 0, 0, 0, 0 );

	if ( ! is_array( $set ) ) {
		return $normalize;
	}

	foreach ( OTO_SET_YAPISI as $i => $slot ) {
		$normalize[ $i ] = isset( $set[ $i ] ) ? absint( $set[ $i ] ) : 0;
	}

	return $normalize;
}

function oto_gunluk_override_dizisini_normalize_et( $override ): array {
	$normalize = oto_bos_besli_dizi();

	if ( ! is_array( $override ) ) {
		return $normalize;
	}

	for ( $i = 0; $i < 5; $i++ ) {
		if ( isset( $override[ $i ] ) && '' !== (string) $override[ $i ] && '0' !== (string) $override[ $i ] ) {
			$normalize[ $i ] = absint( $override[ $i ] );
		}
	}

	return $normalize;
}

function oto_tarih_anahtar( string $tarih ): string {
	// Y-m-d → Ymd — wp_options anahtarı için.
	return 'oto_gunluk_set_' . str_replace( '-', '', $tarih );
}

function oto_gunluk_override_verisini_getir( string $tarih = '' ): array {
	// Yeni tarih bazlı anahtardan oku; yoksa eski tek-kayıt yapısına fallback.
	$veri = array(
		'tarih'    => $tarih,
		'set'      => array( 0, 0, 0, 0, 0 ),
		'override' => oto_bos_besli_dizi(),
	);

	if ( '' !== $tarih ) {
		$kayitli = get_option( oto_tarih_anahtar( $tarih ), null );
		if ( is_array( $kayitli ) ) {
			$veri['tarih']    = $tarih;
			$veri['set']      = oto_gunluk_set_dizisini_normalize_et( $kayitli['set'] ?? array() );
			$veri['override'] = oto_gunluk_override_dizisini_normalize_et( $kayitli['override'] ?? array() );
			return $veri;
		}
		// Eski tek-kayıt yapısına fallback
		$eski = get_option( 'oto_gunluk_override', array() );
		if ( is_array( $eski ) && isset( $eski['tarih'] ) && sanitize_text_field( $eski['tarih'] ) === $tarih ) {
			$veri['tarih']    = $tarih;
			$veri['set']      = oto_gunluk_set_dizisini_normalize_et( $eski['set'] ?? array() );
			$veri['override'] = oto_gunluk_override_dizisini_normalize_et( $eski['override'] ?? array() );
		}
		return $veri;
	}

	// tarih boşsa eski yapıdan oku
	$kayitli = get_option( 'oto_gunluk_override', array() );
	if ( ! is_array( $kayitli ) ) return $veri;
	$veri['tarih']    = isset( $kayitli['tarih'] ) ? sanitize_text_field( $kayitli['tarih'] ) : '';
	$veri['set']      = oto_gunluk_set_dizisini_normalize_et( $kayitli['set'] ?? array() );
	$veri['override'] = oto_gunluk_override_dizisini_normalize_et( $kayitli['override'] ?? array() );
	return $veri;
}

function oto_gunluk_override_kaydet( string $tarih, array $set, array $override ): void {
	$data = array(
		'tarih'    => $tarih,
		'set'      => oto_gunluk_set_dizisini_normalize_et( $set ),
		'override' => oto_gunluk_override_dizisini_normalize_et( $override ),
	);
	// Tarih bazlı anahtar
	update_option( oto_tarih_anahtar( $tarih ), $data );
	// Eski tek-kayıt anahtarına bugün için de yaz — geriye dönük uyumluluk
	if ( $tarih === wp_date( 'Y-m-d' ) ) {
		update_option( 'oto_gunluk_override', $data );
	}
}

function oto_gunluk_set_sayfasi() {
	if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'Yetkiniz yok.' );

	$bugun   = wp_date( 'Y-m-d' );
	$mesaj   = '';

	// POST: belirli bir tarih için kaydet
	if (
		isset( $_POST['oto_set_nonce'], $_POST['oto_set_tarih'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['oto_set_nonce'] ) ), 'oto_set_kaydet' )
	) {
		$kayit_tarihi = sanitize_text_field( wp_unslash( $_POST['oto_set_tarih'] ) );
		// Geçmiş tarihe kaydetme
		if ( $kayit_tarihi >= $bugun ) {
			$override = oto_bos_besli_dizi();
			for ( $i = 0; $i < 5; $i++ ) {
				$key = 'slot_' . $i;
				if ( isset( $_POST[ $key ] ) ) {
					$v              = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
					$override[ $i ] = ( '' !== $v && '0' !== $v ) ? absint( $v ) : null;
				}
			}
			$kayitli = oto_gunluk_override_verisini_getir( $kayit_tarihi );
			oto_gunluk_override_kaydet( $kayit_tarihi, $kayitli['set'], $override );
			delete_transient( 'oto_set_' . $kayit_tarihi );
			$mesaj = 'Set kaydedildi: ' . esc_html( $kayit_tarihi );
		} else {
			$mesaj = 'Geçmiş tarihler düzenlenemez.';
		}
	}

	// Listeler — sadece bir kez çek
	$listeler = array(
		'kolay' => oto_zorluk_oyuncularini_getir( 'kolay' ),
		'orta'  => oto_zorluk_oyuncularini_getir( 'orta' ),
		'zor'   => oto_zorluk_oyuncularini_getir( 'zor' ),
	);
	$renkler = array( 'kolay' => '#4CAF50', 'orta' => '#FF9800', 'zor' => '#F44336' );

	// Türkçe ay isimleri
	$aylar = array(
		1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
		5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
		9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
	);

	// Bugün + gelecek 7 gün = 8 gün
	$gunler = array();
	for ( $g = 0; $g < 8; $g++ ) {
		$gunler[] = wp_date( 'Y-m-d', strtotime( "+{$g} days" ) );
	}

	// POST'tan düzenle hangi tarihi açıyor
	$duzenle_tarihi = isset( $_GET['duzenle'] ) ? sanitize_text_field( wp_unslash( $_GET['duzenle'] ) ) : '';
	if ( $duzenle_tarihi && ! in_array( $duzenle_tarihi, $gunler, true ) ) {
		$duzenle_tarihi = '';
	}

	?>
	<div class="wrap">
		<h1>Günlük Set Yönetimi</h1>

		<?php if ( $mesaj ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $mesaj ); ?></p></div>
		<?php endif; ?>

		<p style="color:#666;margin-bottom:12px;">Gelecek 8 gün gösteriliyor. Geçmiş günler düzenlenemez. Boş bırakılan slotlar otomatik doldurulur.</p>

		<table class="widefat striped" style="max-width:700px;">
			<thead>
				<tr>
					<th style="width:180px;">Tarih</th>
					<th style="width:130px;">Durum</th>
					<th>İşlem</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $gunler as $gun ) :
				$kayitli       = oto_gunluk_override_verisini_getir( $gun );
				$override      = $kayitli['override'];
				$manuel_var    = count( array_filter( $override, fn( $v ) => ! empty( $v ) ) ) > 0;
				$tarih_parca   = explode( '-', $gun );
				$gun_no        = (int) $tarih_parca[2];
				$ay_no         = (int) $tarih_parca[1];
				$tarih_goster  = $gun_no . ' ' . $aylar[ $ay_no ];
				$gecmis        = $gun < $bugun;
				$bugun_mu      = $gun === $bugun;
			?>
				<tr style="<?php echo $gecmis ? 'opacity:0.55;' : ''; ?>">
					<td>
						<strong><?php echo esc_html( $tarih_goster ); ?></strong>
						<?php if ( $bugun_mu ) echo ' <span style="background:#FF6B00;color:#fff;font-size:10px;border-radius:10px;padding:1px 7px;">Bugün</span>'; ?>
					</td>
					<td>
						<?php if ( $gecmis ) : ?>
							<span style="color:#999;font-size:12px;">Geçmiş</span>
						<?php elseif ( $manuel_var ) : ?>
							<span style="background:#1976D2;color:#fff;font-size:11px;border-radius:10px;padding:2px 8px;">Manuel ✓</span>
						<?php else : ?>
							<span style="background:#eee;color:#666;font-size:11px;border-radius:10px;padding:2px 8px;">Otomatik</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( ! $gecmis ) : ?>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'oto-gunluk-set', 'post_type' => 'oyun_oyuncusu', 'duzenle' => $gun ), admin_url( 'edit.php' ) ) ); ?>"
							   class="button button-small">Düzenle</a>
						<?php else : ?>
							<span style="color:#bbb;font-size:12px;">—</span>
						<?php endif; ?>
					</td>
				</tr>

				<?php if ( $duzenle_tarihi === $gun ) : ?>
				<tr>
					<td colspan="3" style="background:#f9f9f9;padding:16px 20px;">
						<form method="post">
							<?php wp_nonce_field( 'oto_set_kaydet', 'oto_set_nonce' ); ?>
							<input type="hidden" name="oto_set_tarih" value="<?php echo esc_attr( $gun ); ?>" />
							<strong style="display:block;margin-bottom:10px;"><?php echo esc_html( $tarih_goster ); ?> — Slot Düzenleme</strong>
							<table class="widefat" style="max-width:560px;">
								<thead><tr><th>Sıra</th><th>Zorluk</th><th>Oyuncu</th></tr></thead>
								<tbody>
								<?php foreach ( OTO_SET_YAPISI as $i => $slot ) :
									$z = $slot['zorluk'];
									$r = esc_attr( $renkler[ $z ] );
									$e = esc_html( $slot['etiket'] );
								?>
									<tr>
										<td><?php echo esc_html( $i + 1 ); ?>.</td>
										<td><span style="background:<?php echo $r; ?>;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;"><?php echo $e; ?></span></td>
										<td>
											<select name="slot_<?php echo esc_attr( $i ); ?>" style="min-width:220px;">
												<option value="0">— Otomatik —</option>
												<?php foreach ( $listeler[ $z ] as $oyuncu ) :
													$sel = ( ! empty( $override[ $i ] ) && (int) $override[ $i ] === $oyuncu->ID ) ? 'selected' : '';
												?>
													<option value="<?php echo esc_attr( $oyuncu->ID ); ?>" <?php echo esc_attr( $sel ); ?>><?php echo esc_html( $oyuncu->post_title ); ?></option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
							<p style="margin-top:10px;">
								<input type="submit" class="button button-primary" value="Kaydet" />
								<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'oto-gunluk-set', 'post_type' => 'oyun_oyuncusu' ), admin_url( 'edit.php' ) ) ); ?>"
								   class="button" style="margin-left:6px;">İptal</a>
							</p>
						</form>
					</td>
				</tr>
				<?php endif; ?>

			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

function oto_zorluk_oyuncularini_getir( string $zorluk ): array {
	return get_posts( array(
		'post_type'      => 'oyun_oyuncusu',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'meta_query'     => array(
			'relation' => 'AND',
			array( 'key' => '_oyuncu_zorluk', 'value' => $zorluk, 'compare' => '=' ),
			array(
				'relation' => 'OR',
				array( 'key' => '_oyuncu_aktif', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_oyuncu_aktif', 'value' => '0', 'compare' => '!=' ),
			),
		),
	) );
}

// =============================================================================
// 6. GÜNLÜk SET SEÇİMİ
// =============================================================================

function oto_gunluk_set_getir( string $tarih ): array {
	$kayitli = oto_gunluk_override_verisini_getir( $tarih );
	$cache = get_transient( 'oto_set_' . $tarih );
	
	if ( false !== $cache && is_array( $cache ) && 5 === count( $cache ) ) {
		$cache = oto_gunluk_set_dizisini_normalize_et( $cache );
		oto_gunluk_override_kaydet( $tarih, $cache, $kayitli['override'] );
		return $cache;
	}

	// Eğer transient silindiyse ama wp_options'ta bugünün seti zaten oluşturulmuşsa, diziyi bozmadan geri dön:
	if ( count( array_filter( $kayitli['set'] ) ) === 5 ) {
		set_transient( 'oto_set_' . $tarih, $kayitli['set'], max( 60, strtotime( 'tomorrow midnight' ) - time() ) );
		return $kayitli['set'];
	}

	$override = $kayitli['override'];
	$son_7 = oto_son_7_gun_oyuncu_idleri();
	$set   = oto_bos_besli_dizi();

	foreach ( OTO_SET_YAPISI as $i => $slot ) {
		if ( ! empty( $override[ $i ] ) ) {
			$p = get_post( absint( $override[ $i ] ) );
			if ( $p && 'oyun_oyuncusu' === $p->post_type && 'publish' === $p->post_status ) {
				$set[ $i ] = absint( $override[ $i ] );
				continue;
			}
		}
		$set[ $i ] = oto_otomatik_oyuncu_sec( $slot['zorluk'], $son_7, $set );
	}

	// Kısıt gevşetme — null kalanlar
	foreach ( $set as $i => $id ) {
		if ( null === $id ) {
			$set[ $i ] = oto_otomatik_oyuncu_sec( OTO_SET_YAPISI[ $i ]['zorluk'], array(), $set );
		}
	}

	// Anahtar sıralamasını zorunlu tutarak [kolay, kolay, orta, orta, zor] strüktürünü sabitle:
	$sabit_sira_set = array();
	foreach ( OTO_SET_YAPISI as $i => $slot ) {
		$sabit_sira_set[ $i ] = isset( $set[ $i ] ) ? absint( $set[ $i ] ) : 0;
	}
	$set = $sabit_sira_set;

	$ttl = max( 60, strtotime( 'tomorrow midnight' ) - time() );
	set_transient( 'oto_set_' . $tarih, $set, $ttl );
	oto_gunluk_override_kaydet( $tarih, $set, $override );

	return $set;
}

function oto_otomatik_oyuncu_sec( string $zorluk, array $son_7, array $mevcut ): ?int {
	$adaylar = oto_zorluk_oyuncularini_getir( $zorluk );
	if ( empty( $adaylar ) ) return null;

	$mevcut_ids = array_filter( array_map( 'absint', $mevcut ) );

	$tercihli = array_filter( $adaylar, function( $o ) use ( $son_7, $mevcut_ids ) {
		return ! in_array( $o->ID, $son_7, true ) && ! in_array( $o->ID, $mevcut_ids, true );
	} );

	if ( empty( $tercihli ) ) {
		$tercihli = array_filter( $adaylar, function( $o ) use ( $mevcut_ids ) {
			return ! in_array( $o->ID, $mevcut_ids, true );
		} );
	}

	if ( empty( $tercihli ) ) {
		$tercihli = $adaylar;
	}

	$tercihli = array_values( $tercihli );
	shuffle( $tercihli );
	return $tercihli[0]->ID;
}

function oto_son_7_gun_oyuncu_idleri(): array {
	$ids = array();
	for ( $g = 1; $g <= 7; $g++ ) {
		$t   = wp_date( 'Y-m-d', strtotime( "-{$g} days" ) );
		$set = get_transient( 'oto_set_' . $t );
		if ( is_array( $set ) ) $ids = array_merge( $ids, $set );
	}
	return array_unique( array_filter( $ids ) );
}

// =============================================================================
// 7. COOKİE OKUMA (PHP — sadece /durum endpoint için)
// =============================================================================

function oto_cookie_oku(): ?array {
	$adi = 'oyun_durum_' . wp_date( 'Ymd' );
	if ( ! isset( $_COOKIE[ $adi ] ) ) return null;
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$d = json_decode( wp_unslash( $_COOKIE[ $adi ] ), true );
	if ( ! is_array( $d ) ) {
		return null;
	}

	if ( isset( $d['aktifIndex'] ) && ! isset( $d['aktif_index'] ) ) {
		$d['aktif_index'] = absint( $d['aktifIndex'] );
	}

	if ( isset( $d['gunBitti'] ) && ! isset( $d['gun_bitti'] ) ) {
		$d['gun_bitti'] = (bool) $d['gunBitti'];
	}

	if ( isset( $d['set'] ) ) {
		$d['set'] = oto_gunluk_set_dizisini_normalize_et( $d['set'] );
	}

	return $d;
}

function oto_seri_oku(): int {
	return isset( $_COOKIE['oto_seri'] ) ? absint( $_COOKIE['oto_seri'] ) : 0;
}

// =============================================================================
// 8. TÜRKÇE NORMALİZE
// =============================================================================

function oto_normalize( string $str ): string {
	$str    = mb_strtolower( trim( $str ), 'UTF-8' );
	$tr_map = array(
		'ı' => 'i', 'i' => 'i', 'İ' => 'i', 'I' => 'i',
		'ü' => 'u', 'Ü' => 'u',
		'ş' => 's', 'Ş' => 's',
		'ç' => 'c', 'Ç' => 'c',
		'ğ' => 'g', 'Ğ' => 'g',
		'ö' => 'o', 'Ö' => 'o',
	);
	$str = str_replace( array_keys( $tr_map ), array_values( $tr_map ), $str );
	// Boşluklar Wordle render'ında kutucuk oluşturulmuyor — karşılaştırma boşluksuz yapılır.
	return preg_replace( '/\s+/', '', $str );
}

// =============================================================================
// 9. WORDLE MOTORU
// =============================================================================

function oto_wordle_hesapla( string $tahmin, string $cevap ): array {
	$t       = mb_str_split( $tahmin, 1, 'UTF-8' );
	$c       = mb_str_split( $cevap, 1, 'UTF-8' );
	$n       = count( $t );
	$sonuclar = array_fill( 0, $n, 'gri' );
	$sayac   = array();

	foreach ( $c as $h ) {
		$sayac[ $h ] = ( $sayac[ $h ] ?? 0 ) + 1;
	}

	// Adım 1: Yeşil
	for ( $i = 0; $i < $n; $i++ ) {
		if ( isset( $t[ $i ], $c[ $i ] ) && $t[ $i ] === $c[ $i ] ) {
			$sonuclar[ $i ] = 'yesil';
			$sayac[ $t[ $i ] ]--;
		}
	}

	// Adım 2: Sarı
	for ( $i = 0; $i < $n; $i++ ) {
		if ( 'yesil' === $sonuclar[ $i ] ) continue;
		$h = $t[ $i ];
		if ( isset( $sayac[ $h ] ) && $sayac[ $h ] > 0 ) {
			$sonuclar[ $i ] = 'sari';
			$sayac[ $h ]--;
		}
	}

	return $sonuclar;
}

// =============================================================================
// 10. REST API
// =============================================================================

add_action( 'rest_api_init', 'oto_register_rest_routes' );

function oto_register_rest_routes() {
	register_rest_route( 'oyun/v1', '/durum', array(
		'methods'             => 'GET',
		'callback'            => 'oto_rest_durum',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'oyun/v1', '/tahmin', array(
		'methods'             => 'POST',
		'callback'            => 'oto_rest_tahmin_gonder',
		'permission_callback' => 'oto_rest_permission_check',
		'args'                => array(
			'tahmin'    => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
			'oyuncu_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
		),
	) );

	register_rest_route( 'oyun/v1', '/ipucu', array(
		'methods'             => 'POST',
		'callback'            => 'oto_rest_ipucu_al',
		'permission_callback' => 'oto_rest_permission_check',
		'args'                => array(
			'oyuncu_id'     => array( 'required' => true, 'sanitize_callback' => 'absint' ),
			'acilan_sayisi' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
		),
	) );
}

function oto_rest_permission_check( WP_REST_Request $request ): bool {
	$nonce = $request->get_header( 'X-WP-Nonce' );
	return $nonce && (bool) wp_verify_nonce( $nonce, 'wp_rest' );
}

function oto_rest_durum( WP_REST_Request $request ) {
	$tarih = wp_date( 'Y-m-d' );
	$set   = oto_gunluk_set_getir( $tarih );

	if ( empty( array_filter( $set ) ) ) {
		return new WP_REST_Response(
			array( 'hata' => 'Günlük set için yeterli oyuncu bulunamadı. Admin panelinden oyuncu ekleyin.' ),
			404
		);
	}

	$cookie_data  = oto_cookie_oku();
	$seri         = oto_seri_oku();
	$cookie_set   = isset( $cookie_data['set'] ) ? array_map( 'absint', (array) $cookie_data['set'] ) : array();
	$set_eslesti  = ( $cookie_set === array_map( 'absint', $set ) );

	if ( null === $cookie_data || ! $set_eslesti ) {
		$oyuncular = array();
		foreach ( $set as $idx => $id ) {
			$oyuncular[] = array(
				'id'             => absint( $id ),
				'durum'          => 0 === $idx ? 'devam' : 'bekliyor',
				'hak_kullanilan' => 0,
				'ipucu_acilan'   => array(),
				'tahminler'      => array(),
			);
		}
		$cookie_data = array(
			'set'         => $set,
			'aktif_index' => 0,
			'oyuncular'   => $oyuncular,
			'gun_bitti'   => false,
		);
	}

	$aktif_index  = min( absint( $cookie_data['aktif_index'] ?? 0 ), 4 );
	$aktif_id     = absint( $set[ $aktif_index ] ?? 0 );
	$gorsel_url   = '';
	$ipucu_sayisi = 0;
	$cevap_uzunluk = 0;
	$oyuncu_adi_ham = '';

	// Her oyuncuya: gorsel_url, zorluk, ipucu_sayisi ve tahmin_sonuclari ekle.
	// tahmin_sonuclari: sayfa yenilenince renkli Wordle geçmişi render edilebilsin.
	foreach ( $cookie_data['oyuncular'] as &$oyuncu ) {
		$id = $oyuncu['id'];
		$oyuncu['gorsel_url']   = esc_url( get_post_meta( $id, '_oyuncu_gorsel_url', true ) );
		$oyuncu['zorluk']       = sanitize_text_field( get_post_meta( $id, '_oyuncu_zorluk', true ) );
		$oyuncu['ipucu_sayisi'] = count( array_filter( array(
			get_post_meta( $id, '_oyuncu_ipucu_1', true ),
			get_post_meta( $id, '_oyuncu_ipucu_2', true ),
			get_post_meta( $id, '_oyuncu_ipucu_3', true ),
		) ) );
		// Tahmin sonuçlarını yeniden hesapla — yenileme sonrası renkli render için.
		$cevap_norm = oto_normalize( get_the_title( $id ) );
		$tahmin_sonuclari = array();
		$tahminler = is_array( $oyuncu['tahminler'] ?? null ) ? $oyuncu['tahminler'] : array();
		foreach ( $tahminler as $tahmin ) {
			$t_norm = oto_normalize( sanitize_text_field( $tahmin ) );
			$tahmin_sonuclari[] = oto_wordle_hesapla( $t_norm, $cevap_norm );
		}
		$oyuncu['tahmin_sonuclari'] = $tahmin_sonuclari;
		$oyuncu['cevap_uzunluk'] = mb_strlen( $cevap_norm, 'UTF-8' );
		$oyuncu['oyuncu_adi_ham'] = get_the_title( $id ); // Normalize edilmemiş, boşluklu
		
		if ( in_array( $oyuncu['durum'], array('kazandi', 'kaybetti'), true ) ) {
			$oyuncu['oyuncu_adi'] = get_the_title( $id );
		}
	}
	unset( $oyuncu );

	if ( $aktif_id ) {
		$gorsel_url   = esc_url( get_post_meta( $aktif_id, '_oyuncu_gorsel_url', true ) );
		$ipucu_sayisi = count( array_filter( array(
			get_post_meta( $aktif_id, '_oyuncu_ipucu_1', true ),
			get_post_meta( $aktif_id, '_oyuncu_ipucu_2', true ),
			get_post_meta( $aktif_id, '_oyuncu_ipucu_3', true ),
		) ) );
		$cevap_uzunluk = mb_strlen( oto_normalize( get_the_title( $aktif_id ) ), 'UTF-8' );
		$oyuncu_adi_ham = get_the_title( $aktif_id );
	}

	return new WP_REST_Response( array(
		'set'            => array_map( 'absint', $set ),
		'set_meta'       => array_values( OTO_SET_YAPISI ),
		'aktif_index'    => $aktif_index,
		'oyuncular'      => $cookie_data['oyuncular'] ?? array(),
		'gorsel_url'     => $gorsel_url,
		'ipucu_sayisi'   => $ipucu_sayisi,
		'cevap_uzunluk'  => $cevap_uzunluk,
		'oyuncu_adi_ham' => $oyuncu_adi_ham,
		'gun_bitti'      => (bool) ( $cookie_data['gun_bitti'] ?? false ),
		'seri'           => absint( $seri ),
		'nonce'          => wp_create_nonce( 'wp_rest' ),
	), 200 );
}

function oto_rest_tahmin_gonder( WP_REST_Request $request ) {
	$ip      = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
	$rl_key  = 'oyun_rl_' . md5( $ip );
	$rl_val  = (int) get_transient( $rl_key );
	if ( $rl_val >= 15 ) {
		return new WP_REST_Response( array( 'hata' => 'Çok fazla istek. Lütfen bekleyin.' ), 429 );
	}
	set_transient( $rl_key, $rl_val + 1, 10 );

	$tahmin    = sanitize_text_field( $request->get_param( 'tahmin' ) );
	$oyuncu_id = absint( $request->get_param( 'oyuncu_id' ) );

	if ( empty( $tahmin ) ) {
		return new WP_REST_Response( array( 'hata' => 'Tahmin boş olamaz.' ), 400 );
	}

	$post = get_post( $oyuncu_id );
	if ( ! $post || 'oyun_oyuncusu' !== $post->post_type ) {
		return new WP_REST_Response( array( 'hata' => 'Geçersiz oyuncu.' ), 400 );
	}

	// --- HAK KONTROLÜ (Cookie tabanlı) ---
	$max_hak    = 5; // Shortcode default ile aynı
	$cookie_data = oto_cookie_oku();
	$hak_kullanilan = 0;
	if ( is_array( $cookie_data ) && isset( $cookie_data['oyuncular'] ) ) {
		foreach ( $cookie_data['oyuncular'] as $oyuncu_row ) {
			if ( isset( $oyuncu_row['id'] ) && absint( $oyuncu_row['id'] ) === $oyuncu_id ) {
				$hak_kullanilan = absint( $oyuncu_row['hak_kullanilan'] ?? 0 );
				break;
			}
		}
	}
	if ( $hak_kullanilan >= $max_hak ) {
		return new WP_REST_Response( array(
			'hata'         => 'Bu oyuncu için hakkın kalmadı.',
			'oyuncu_bitti' => true,
			'kazandi'      => false,
			'oyuncu_adi'   => get_the_title( $oyuncu_id ),
		), 200 );
	}

	$cevap    = get_the_title( $oyuncu_id );
	$t_norm   = oto_normalize( $tahmin );
	$c_norm   = oto_normalize( $cevap );
	$dogru    = ( $t_norm === $c_norm );
	$sonuclar = oto_wordle_hesapla( $t_norm, $c_norm );

	$yeni_hak_kullanilan = $hak_kullanilan + 1;
	$hak_bitti = ( ! $dogru && $yeni_hak_kullanilan >= $max_hak );

	$yanit = array(
		'dogru'         => $dogru,
		'sonuclar'      => $sonuclar,
		'oyuncu_bitti'  => ( $dogru || $hak_bitti ),
		'kazandi'       => $dogru,
		'kalan_hak'     => max( 0, $max_hak - $yeni_hak_kullanilan ),
		'cevap_uzunluk' => mb_strlen( $c_norm, 'UTF-8' ),
	);

	if ( $dogru || $hak_bitti ) {
		$yanit['oyuncu_adi'] = $cevap;
		$yanit['gorsel_url'] = esc_url( get_post_meta( $oyuncu_id, '_oyuncu_gorsel_url', true ) );
	}

	return new WP_REST_Response( $yanit, 200 );
}

function oto_rest_ipucu_al( WP_REST_Request $request ) {
	$oyuncu_id     = absint( $request->get_param( 'oyuncu_id' ) );
	$acilan_sayisi = absint( $request->get_param( 'acilan_sayisi' ) );

	$post = get_post( $oyuncu_id );
	if ( ! $post || 'oyun_oyuncusu' !== $post->post_type ) {
		return new WP_REST_Response( array( 'hata' => 'Geçersiz oyuncu.' ), 400 );
	}

	$ipucular = array_values( array_filter( array(
		sanitize_text_field( get_post_meta( $oyuncu_id, '_oyuncu_ipucu_1', true ) ),
		sanitize_text_field( get_post_meta( $oyuncu_id, '_oyuncu_ipucu_2', true ) ),
		sanitize_text_field( get_post_meta( $oyuncu_id, '_oyuncu_ipucu_3', true ) ),
	) ) );

	if ( ! isset( $ipucular[ $acilan_sayisi ] ) ) {
		return new WP_REST_Response( array( 'hata' => 'ipucu_yok' ), 200 );
	}

	return new WP_REST_Response( array(
		'ipucu'       => $ipucular[ $acilan_sayisi ],
		'sonraki_var' => isset( $ipucular[ $acilan_sayisi + 1 ] ),
	), 200 );
}

// =============================================================================
// 11. SHORTCODE
// =============================================================================

add_shortcode( 'oyuncu_tahmin', 'oto_shortcode_render' );

$GLOBALS['oto_shortcode_aktif'] = false;

function oto_shortcode_render( $atts ): string {
	$GLOBALS['oto_shortcode_aktif'] = true;

	$atts = shortcode_atts( array(
		'max_hak'      => 5,
		'gorsel_boyut' => 400,
		'tema'         => 'acik',
	), $atts, 'oyuncu_tahmin' );

	$max_hak      = absint( $atts['max_hak'] );
	$gorsel_boyut = absint( $atts['gorsel_boyut'] );
	$tema_raw     = sanitize_text_field( $atts['tema'] );
	$tema         = in_array( $tema_raw, array( 'acik', 'koyu' ), true ) ? $tema_raw : 'acik';

	if ( $max_hak < 1 || $max_hak > 10 )            $max_hak = 5;
	if ( $gorsel_boyut < 100 || $gorsel_boyut > 800 ) $gorsel_boyut = 400;

	$GLOBALS['oto_max_hak'] = $max_hak;

	$hak_noktalari = '';
	for ( $i = 0; $i < $max_hak; $i++ ) {
		$hak_noktalari .= '<span class="oto-hak-nokta"></span>';
	}

	$logo_url = OTO_PLUGIN_URL . 'assets/logo.png';

	ob_start();
	?>
	<div id="oyuncu-tahmin-oyunu" class="oto-oyun tema-<?php echo esc_attr( $tema ); ?>"
		data-max-hak="<?php echo esc_attr( $max_hak ); ?>"
		data-gorsel-boyut="<?php echo esc_attr( $gorsel_boyut ); ?>">

		<!-- Header -->
		<div class="oto-header">
			<div class="oto-header-logo-kutu">
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="SpurKulis" class="oto-header-logo"
					onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';" />
				<span class="oto-header-logo-fallback" style="display:none;">S</span>
			</div>
			<span class="oto-header-baslik-metin">Futbolcu Tahmin Etme Oyunu</span>
		</div>

		<!-- İlerleme + Hak -->
		<div class="oto-ilerleme" id="oto-ilerleme">
			<span class="oto-ilerleme-metin" id="oto-ilerleme-metin">Oyuncu 1 / 5</span>
			<span class="oto-zorluk-rozet kolay" id="oto-zorluk-rozet">Kolay</span>
			<div class="oto-hak-grubu">
				<div class="oto-hak-noktalari" id="oto-hak-noktalari">
					<?php echo $hak_noktalari; ?>
				</div>
				<span class="oto-hak-text" id="oto-hak-text"><?php echo esc_html( $max_hak ); ?> hak</span>
			</div>
		</div>
		<div class="oto-seri-rozet" id="oto-seri-rozet" style="display:none;"></div>

		<!-- Görsel -->
		<div class="oto-gorsel-wrapper" style="max-width:<?php echo esc_attr( $gorsel_boyut ); ?>px;" id="oto-gorsel-wrapper">
			<div class="oto-gorsel-yukleniyor" id="oto-gorsel-yukleniyor">Yükleniyor...</div>
			<img src="" alt="Oyuncu görseli" class="oto-gorsel" id="oto-gorsel" style="display:none;"
				width="<?php echo esc_attr( $gorsel_boyut ); ?>" />
		</div>
		<!-- İsim Kutucukları (--- ---- yapısı) — görsel altında -->
		<div class="oto-isim-kutulari" id="oto-aktif-tahmin-satiri" style="display:none;"></div>

		<!-- Tahmin Geçmişi -->
		<div class="oto-tahmin-gecmisi" id="oto-tahminler" aria-label="Tahmin geçmişi"></div>

		<!-- Giriş Alanı -->
		<div class="oto-giris-alani" id="oto-giris-alani">
			<input type="text" id="oto-tahmin-input" class="oto-tahmin-input"
				placeholder="Oyuncu adı..."
				autocomplete="off" autocorrect="off" autocapitalize="off"
				spellcheck="false" maxlength="60" />
			<button id="oto-tahmin-btn" class="oto-tahmin-btn" type="button">Tahmin Et</button>
		</div>

		<!-- İpucu Butonu -->
		<div class="oto-ipucu-btn-satiri">
			<button id="oto-ipucu-btn" class="oto-ipucu-btn" type="button">
				İpucu Al <span class="oto-ipucu-uyari">(1 hak gider)</span>
			</button>
		</div>

		<!-- Açılan İpuçları -->
		<div class="oto-ipucu-listesi" id="oto-ipuclari"></div>

		<!-- Mesaj -->
		<div class="oto-mesaj" id="oto-mesaj" aria-live="polite"></div>

	</div>
	<?php
	return ob_get_clean();
}

// =============================================================================
// 12. ENQUEUE
// =============================================================================

add_action( 'wp_footer', 'oto_enqueue_assets' );

function oto_enqueue_assets() {
	if ( ! $GLOBALS['oto_shortcode_aktif'] ) return;

	wp_enqueue_style( 'oyun-css', OTO_PLUGIN_URL . 'assets/oyun.css', array(), OTO_VERSION );
	wp_enqueue_script( 'oyun-js', OTO_PLUGIN_URL . 'assets/oyun.js', array(), OTO_VERSION, true );

	wp_localize_script( 'oyun-js', 'oyunConfig', array(
		'nonce'   => wp_create_nonce( 'wp_rest' ),
		'restUrl' => rest_url( 'oyun/v1/' ),
		'maxHak'  => absint( $GLOBALS['oto_max_hak'] ?? 5 ),
	) );
}

// =============================================================================
// SEO — JSON-LD SCHEMA (FAQPage + Game) — Sadece shortcode sayfasında
// =============================================================================

add_action( 'wp_head', 'oto_seo_schema_enjekte' );

function oto_seo_schema_enjekte() {
	if ( ! $GLOBALS['oto_shortcode_aktif'] ) return;

	$site_adi  = get_bloginfo( 'name' );
	$site_url  = home_url();
	$sayfa_url = get_permalink();

	// --- Game Schema ---
	$game_schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Game',
		'name'        => 'Günlük Futbolcu Tahmin Oyunu',
		'description' => 'Her gün 5 farklı futbolcunun bulanık görselini tahmin et. Harf ipuçlarıyla doğru ismi bul, hakların bitmeden oyuncuyu keşfet.',
		'url'         => $sayfa_url,
		'inLanguage'  => 'tr',
		'genre'       => 'Sports, Puzzle',
		'publisher'   => array(
			'@type' => 'Organization',
			'name'  => $site_adi,
			'url'   => $site_url,
		),
		'offers' => array(
			'@type' => 'Offer',
			'price' => '0',
			'priceCurrency' => 'TRY',
			'availability'  => 'https://schema.org/InStock',
		),
	);

	// --- FAQPage Schema ---
	$faq_schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array(
			array(
				'@type'          => 'Question',
				'name'           => 'Oyun nasıl oynanır?',
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => 'Her gün 5 farklı futbolcu görseli bulanık olarak karşınıza çıkar. Her yanlış tahminde görsel biraz daha netleşir. 5 hak içinde oyuncunun adını tahmin etmeye çalışırsınız.',
				),
			),
			array(
				'@type'          => 'Question',
				'name'           => 'Oyun ne zaman yenilenir?',
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => 'Oyun her gün gece yarısı sıfırlanır ve yeni 5 oyuncu gelir. Seriyi devam ettirmek için her gün girin.',
				),
			),
			array(
				'@type'          => 'Question',
				'name'           => 'İpucu almak ne kadar hak götürür?',
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => 'Her ipucu 1 hak harcar. Toplamda 5 hakkınız vardır. İpucu almak görseli daha net yapmaz, yalnızca yazılı bir bilgi açar.',
				),
			),
			array(
				'@type'          => 'Question',
				'name'           => 'Zorluk seviyeleri nelerdir?',
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => 'Günlük set 2 Kolay, 2 Orta ve 1 Zor oyuncudan oluşur. Kolay oyuncular dünyaca ünlü isimlerdir, Zor oyuncular daha az tanınandır.',
				),
			),
			array(
				'@type'          => 'Question',
				'name'           => 'Seri nedir, nasıl hesaplanır?',
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => 'Bir günün 5 oyuncusunun tamamını doğru tahmin ederseniz seri +1 artar. Tek bir oyuncu kaçırılsa bile o günkü seri sıfırlanır.',
				),
			),
		),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $game_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	echo '<script type="application/ld+json">' . wp_json_encode( $faq_schema,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

add_action( 'admin_enqueue_scripts', 'oto_admin_enqueue' );

function oto_admin_enqueue( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) return;
	$screen = get_current_screen();
	if ( ! $screen || 'oyun_oyuncusu' !== $screen->post_type ) return;
	wp_enqueue_media();
}
