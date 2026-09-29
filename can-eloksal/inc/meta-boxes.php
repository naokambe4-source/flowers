<?php
/**
 * Meta kutuları: hizmet, slayt, sektör, referans, banka, sayfa bölümleri ve SEO.
 * Tüm alanlar _ce_{id} anahtarıyla kaydedilir.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta kutusu tanımları.
 *
 * @param WP_Post|null $post Yazı (şablona göre kutu göstermek için).
 * @return array
 */
function ce_meta_boxes( $post = null ) {
	$template = $post ? get_page_template_slug( $post ) : '';

	$boxes = array(
		'ce_service_details' => array(
			'title'   => 'Hizmet Detayları',
			'screens' => array( 'ce_service' ),
			'fields'  => array(
				array( 'id' => 'summary', 'type' => 'textarea', 'label' => 'Kısa açıklama (kartlarda ve hero\'da)', 'rows' => 2 ),
				array( 'id' => 'tone', 'type' => 'select', 'label' => 'Yüzey tonu (görsel yokken kullanılan doku)', 'options' => ce_tone_choices(), 'default' => 'steel', 'half' => true ),
				array( 'id' => 'hero_image', 'type' => 'image', 'label' => 'Hero görseli (boşsa kapak görseli)', 'half' => true ),
				array( 'id' => 'advantages', 'type' => 'lines', 'label' => 'Avantajlar (her satır bir madde)', 'rows' => 5, 'half' => true ),
				array( 'id' => 'applications', 'type' => 'lines', 'label' => 'Uygulama alanları (her satır bir madde)', 'rows' => 5, 'half' => true ),
				array(
					'id'     => 'specs',
					'type'   => 'repeater',
					'label'  => 'Teknik bilgiler tablosu',
					'add'    => 'Satır ekle',
					'fields' => array(
						array( 'id' => 'label', 'type' => 'text', 'label' => 'Özellik', 'half' => true ),
						array( 'id' => 'value', 'type' => 'text', 'label' => 'Değer', 'half' => true ),
					),
				),
				array( 'id' => 'technical', 'type' => 'editor', 'label' => 'Teknik içerik (opsiyonel, tablonun altında gösterilir)', 'rows' => 6 ),
				array( 'id' => 'gallery', 'type' => 'gallery', 'label' => 'Hizmet galerisi' ),
				array( 'id' => 'cta_title', 'type' => 'text', 'label' => 'Teklif CTA başlığı (boşsa varsayılan)' ),
			),
		),

		'ce_slide_details'   => array(
			'title'   => 'Slayt İçeriği',
			'screens' => array( 'ce_slide' ),
			'fields'  => array(
				array( 'id' => 'eyebrow', 'type' => 'text', 'label' => 'Üst etiket (subtitle)', 'half' => true ),
				array( 'id' => 'align', 'type' => 'select', 'label' => 'Metin konumu', 'options' => array( 'left' => 'Sol', 'center' => 'Orta' ), 'default' => 'left', 'quarter' => true ),
				array( 'id' => 'overlay', 'type' => 'number', 'label' => 'Karartma (%)', 'default' => 55, 'min' => 0, 'max' => 90, 'quarter' => true ),
				array( 'id' => 'description', 'type' => 'textarea', 'label' => 'Açıklama', 'rows' => 3 ),
				array( 'id' => 'tags', 'type' => 'lines', 'label' => 'Etiketler (her satır bir etiket, ör. Savunma Sanayi)', 'rows' => 3 ),
				array( 'id' => 'mobile_image', 'type' => 'image', 'label' => 'Mobil görsel (dikey, opsiyonel)', 'desc' => 'Masaüstü görseli sağdaki "Kapak görseli" kutusundan eklenir.', 'half' => true ),
				array( 'id' => 'video', 'type' => 'url', 'label' => 'Arka plan videosu (MP4 adresi, opsiyonel)', 'desc' => 'Ortam Kütüphanesi\'ne yüklediğiniz MP4 dosyasının adresini yapıştırın. Hareket azaltma tercihindeki ziyaretçilere görsel gösterilir.', 'half' => true ),
				array( 'id' => 'btn1_label', 'type' => 'text', 'label' => 'Birincil buton metni', 'half' => true ),
				array( 'id' => 'btn1_url', 'type' => 'url', 'label' => 'Birincil buton bağlantısı', 'half' => true ),
				array( 'id' => 'btn2_label', 'type' => 'text', 'label' => 'İkincil buton metni', 'half' => true ),
				array( 'id' => 'btn2_url', 'type' => 'url', 'label' => 'İkincil buton bağlantısı', 'half' => true ),
			),
		),

		'ce_sector_details'  => array(
			'title'   => 'Sektör Detayları',
			'screens' => array( 'ce_sector' ),
			'fields'  => array(
				array( 'id' => 'number', 'type' => 'text', 'label' => 'Numara (ör. 01)', 'quarter' => true ),
				array( 'id' => 'link', 'type' => 'url', 'label' => 'Bağlantı (opsiyonel)', 'half' => true ),
				array( 'id' => 'tone', 'type' => 'select', 'label' => 'Görsel yokken doku', 'options' => ce_tone_choices(), 'default' => 'graphite', 'quarter' => true ),
			),
			'desc'    => 'Kısa açıklama için "Özet" kutusunu, görsel için "Kapak görseli" kutusunu kullanın.',
		),

		'ce_reference_details' => array(
			'title'   => 'Referans',
			'screens' => array( 'ce_reference' ),
			'fields'  => array(
				array( 'id' => 'website', 'type' => 'url', 'label' => 'Web sitesi (opsiyonel)' ),
			),
			'desc'    => 'Logo, sağdaki "Logo" kutusundan eklenir. Logosu olmayan referanslar sitede gösterilmez.',
		),

		'ce_bank_details'    => array(
			'title'   => 'Hesap Bilgileri',
			'screens' => array( 'ce_bank' ),
			'fields'  => array(
				array( 'id' => 'holder', 'type' => 'text', 'label' => 'Hesap sahibi (ünvan)', 'half' => true ),
				array( 'id' => 'currency', 'type' => 'select', 'label' => 'Para birimi', 'options' => array( 'TRY' => 'TL', 'USD' => 'USD', 'EUR' => 'EUR' ), 'default' => 'TRY', 'half' => true ),
				array( 'id' => 'branch', 'type' => 'text', 'label' => 'Şube', 'third' => true ),
				array( 'id' => 'branch_code', 'type' => 'text', 'label' => 'Şube no', 'third' => true ),
				array( 'id' => 'account_no', 'type' => 'text', 'label' => 'Hesap no', 'third' => true ),
				array( 'id' => 'iban', 'type' => 'text', 'label' => 'IBAN', 'placeholder' => 'TR00 0000 0000 0000 0000 0000 00' ),
				array( 'id' => 'color', 'type' => 'select', 'label' => 'Kart vurgusu', 'options' => array( 'dark' => 'Koyu', 'cyan' => 'Turkuaz', 'steel' => 'Metalik' ), 'default' => 'dark', 'half' => true ),
			),
		),

		'ce_page_hero'       => array(
			'title'   => 'Sayfa Hero',
			'screens' => array( 'page' ),
			'fields'  => array(
				array( 'id' => 'hero_title', 'type' => 'text', 'label' => 'Hero başlığı (boşsa sayfa başlığı)', 'half' => true ),
				array( 'id' => 'hero_eyebrow', 'type' => 'text', 'label' => 'Üst etiket', 'half' => true ),
				array( 'id' => 'hero_image', 'type' => 'image', 'label' => 'Hero arka plan görseli (boşsa kapak görseli)', 'half' => true ),
				array( 'id' => 'hero_subtitle', 'type' => 'textarea', 'label' => 'Alt başlık', 'rows' => 2 ),
			),
			'desc'    => 'Sayfa şablonunu değiştirdiğinizde şablona özel bölüm kutuları için sayfayı kaydedip yenileyin.',
		),
	);

	if ( 'page-templates/about.php' === $template ) {
		$boxes['ce_page_about'] = array(
			'title'   => 'Hakkımızda — Bölümler',
			'screens' => array( 'page' ),
			'fields'  => array(
				array( 'id' => 'intro_title', 'type' => 'textarea', 'label' => 'Giriş başlığı (satır başı = yeni satır)', 'rows' => 2, 'default' => "HER YÜZEYDE\nÖZENLİ İŞÇİLİK." ),
				array( 'id' => 'intro_image', 'type' => 'image', 'label' => 'Giriş görseli (personel + üretim hattı)', 'half' => true ),
				array( 'id' => 'intro_badge', 'type' => 'text', 'label' => 'Görsel etiketi', 'half' => true ),
				array(
					'id'     => 'blocks',
					'type'   => 'repeater',
					'label'  => 'İçerik blokları (Üretim yaklaşımı, Teknoloji, Kalite, Tesis…)',
					'add'    => 'Blok ekle',
					'fields' => array(
						array( 'id' => 'eyebrow', 'type' => 'text', 'label' => 'Üst etiket', 'half' => true ),
						array( 'id' => 'title', 'type' => 'text', 'label' => 'Başlık', 'half' => true ),
						array( 'id' => 'text', 'type' => 'textarea', 'label' => 'Metin', 'rows' => 4 ),
						array( 'id' => 'icon', 'type' => 'icon', 'label' => 'İkon', 'half' => true ),
						array( 'id' => 'image', 'type' => 'image', 'label' => 'Görsel (opsiyonel)', 'half' => true ),
					),
				),
				array( 'id' => 'show_sectors', 'type' => 'toggle', 'label' => 'Sektörler bölümünü göster', 'default' => 1 ),
				array( 'id' => 'facility_title', 'type' => 'text', 'label' => 'Tesis galerisi başlığı', 'half' => true ),
				array( 'id' => 'facility', 'type' => 'gallery', 'label' => 'Tesis görselleri' ),
				array( 'id' => 'cta_title', 'type' => 'text', 'label' => 'CTA başlığı', 'half' => true ),
				array( 'id' => 'cta_text', 'type' => 'text', 'label' => 'CTA metni', 'half' => true ),
			),
		);
	}

	if ( 'page-templates/quality.php' === $template ) {
		$boxes['ce_page_quality'] = array(
			'title'   => 'Kalite Politikası — Bölümler',
			'screens' => array( 'page' ),
			'fields'  => array(
				array( 'id' => 'quality_image', 'type' => 'image', 'label' => 'Yan görsel', 'half' => true ),
				array( 'id' => 'principles_title', 'type' => 'text', 'label' => 'İlkeler başlığı', 'half' => true ),
				array(
					'id'     => 'principles',
					'type'   => 'repeater',
					'label'  => 'Kalite ilkeleri',
					'add'    => 'İlke ekle',
					'fields' => array(
						array( 'id' => 'icon', 'type' => 'icon', 'label' => 'İkon', 'half' => true ),
						array( 'id' => 'title', 'type' => 'text', 'label' => 'Başlık', 'half' => true ),
						array( 'id' => 'text', 'type' => 'textarea', 'label' => 'Metin', 'rows' => 3 ),
					),
				),
				array( 'id' => 'steps_title', 'type' => 'text', 'label' => 'Kontrol adımları başlığı' ),
				array(
					'id'     => 'steps',
					'type'   => 'repeater',
					'label'  => 'Kontrol adımları',
					'add'    => 'Adım ekle',
					'fields' => array(
						array( 'id' => 'title', 'type' => 'text', 'label' => 'Başlık' ),
						array( 'id' => 'text', 'type' => 'textarea', 'label' => 'Metin', 'rows' => 3 ),
					),
				),
			),
		);
	}

	if ( 'page-templates/contact.php' === $template ) {
		$boxes['ce_page_contact'] = array(
			'title'   => 'İletişim — Form',
			'screens' => array( 'page' ),
			'fields'  => array(
				array( 'id' => 'form_title', 'type' => 'text', 'label' => 'Form başlığı', 'half' => true ),
				array( 'id' => 'form_text', 'type' => 'text', 'label' => 'Form açıklaması', 'half' => true ),
				array( 'id' => 'subjects', 'type' => 'lines', 'label' => 'Konu seçenekleri (boşsa serbest metin)', 'rows' => 4 ),
			),
		);
	}

	if ( 'page-templates/quote.php' === $template ) {
		$boxes['ce_page_quote'] = array(
			'title'   => 'Teklif Al — Yan panel',
			'screens' => array( 'page' ),
			'fields'  => array(
				array( 'id' => 'aside_title', 'type' => 'text', 'label' => 'Yan panel başlığı' ),
				array( 'id' => 'aside_steps', 'type' => 'lines', 'label' => 'Süreç adımları (her satır bir adım)', 'rows' => 4 ),
				array( 'id' => 'aside_note', 'type' => 'textarea', 'label' => 'Not', 'rows' => 2 ),
			),
		);
	}

	$boxes['ce_seo'] = array(
		'title'   => 'SEO',
		'screens' => array( 'page', 'post', 'ce_service' ),
		'fields'  => array(
			array( 'id' => 'seo_title', 'type' => 'text', 'label' => 'SEO başlığı (boşsa sayfa başlığı)' ),
			array( 'id' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta açıklama (150–160 karakter önerilir)', 'rows' => 2 ),
			array( 'id' => 'seo_canonical', 'type' => 'url', 'label' => 'Canonical URL (boşsa otomatik)' ),
			array( 'id' => 'seo_noindex', 'type' => 'toggle', 'label' => 'Arama motorlarında gösterme (noindex — sitemap\'ten de çıkarılır)' ),
			array( 'id' => 'seo_nofollow', 'type' => 'toggle', 'label' => 'Bağlantıları takip etme (nofollow)' ),
			array( 'id' => 'og_title', 'type' => 'text', 'label' => 'Open Graph başlığı', 'half' => true ),
			array( 'id' => 'og_image', 'type' => 'image', 'label' => 'Open Graph görseli (1200×630)', 'half' => true ),
			array( 'id' => 'og_description', 'type' => 'textarea', 'label' => 'Open Graph açıklaması', 'rows' => 2 ),
			array( 'id' => 'schema_json', 'type' => 'code', 'label' => 'Ek JSON-LD şeması (opsiyonel, geçerli JSON)', 'rows' => 4, 'json' => true ),
		),
	);

	return $boxes;
}

add_action( 'add_meta_boxes', 'ce_add_meta_boxes', 10, 2 );
/**
 * Kutuları ekler.
 *
 * @param string  $post_type Tür.
 * @param WP_Post $post      Yazı.
 */
function ce_add_meta_boxes( $post_type, $post ) {
	foreach ( ce_meta_boxes( $post ) as $id => $box ) {
		if ( ! in_array( $post_type, $box['screens'], true ) ) {
			continue;
		}
		add_meta_box( $id, $box['title'], 'ce_render_meta_box', $post_type, 'normal', 'ce_seo' === $id ? 'low' : 'high', array( 'box' => $box, 'id' => $id ) );
	}

	if ( 'ce_slide' === $post_type && $post->ID ) {
		add_meta_box( 'ce_slide_preview', 'Önizleme', 'ce_render_slide_preview_box', 'ce_slide', 'side', 'high' );
	}
}

/**
 * Kutu içeriği.
 *
 * @param WP_Post $post Yazı.
 * @param array   $args Argümanlar.
 */
function ce_render_meta_box( $post, $args ) {
	$box = $args['args']['box'];
	$id  = $args['args']['id'];
	wp_nonce_field( 'ce_save_' . $id, $id . '_nonce' );
	echo '<div class="ce-fields">';
	if ( ! empty( $box['desc'] ) ) {
		echo '<p class="ce-box-desc">' . esc_html( $box['desc'] ) . '</p>';
	}
	foreach ( $box['fields'] as $field ) {
		$raw   = get_post_meta( $post->ID, '_ce_' . $field['id'], true );
		$value = ( '' === $raw && ! metadata_exists( 'post', $post->ID, '_ce_' . $field['id'] ) ) ? ( $field['default'] ?? '' ) : $raw;
		ce_render_field( $field, $value, 'ce_meta[' . $field['id'] . ']', 'ce-meta-' . $field['id'] );
	}
	echo '</div>';
}

/**
 * Slayt önizleme kutusu.
 *
 * @param WP_Post $post Yazı.
 */
function ce_render_slide_preview_box( $post ) {
	$url = wp_nonce_url( add_query_arg( 'ce_preview_slide', $post->ID, home_url( '/' ) ), 'ce_preview_slide_' . $post->ID );
	echo '<p>Slaytı ana sayfada, taslak olsa bile yalnızca size görünecek şekilde önizleyin. Önce değişiklikleri kaydedin.</p>';
	echo '<a class="button button-primary" target="_blank" rel="noopener" href="' . esc_url( $url ) . '">Ana sayfada önizle</a>';
}

add_action( 'save_post', 'ce_save_meta_boxes', 10, 2 );
/**
 * Meta alanlarını kaydeder.
 *
 * @param int     $post_id Yazı.
 * @param WP_Post $post    Yazı nesnesi.
 */
function ce_save_meta_boxes( $post_id, $post ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) || empty( $_POST['ce_meta'] ) || ! is_array( $_POST['ce_meta'] ) ) {
		return;
	}
	$input = wp_unslash( $_POST['ce_meta'] ); // phpcs:ignore -- her alan aşağıda şemaya göre temizlenir.

	foreach ( ce_meta_boxes( $post ) as $id => $box ) {
		if ( ! in_array( $post->post_type, $box['screens'], true ) ) {
			continue;
		}
		$nonce = isset( $_POST[ $id . '_nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $id . '_nonce' ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ce_save_' . $id ) ) {
			continue;
		}
		foreach ( $box['fields'] as $field ) {
			if ( 'heading' === $field['type'] ) {
				continue;
			}
			$raw = $input[ $field['id'] ] ?? ( 'toggle' === $field['type'] ? 0 : '' );
			if ( ! empty( $field['json'] ) ) {
				$value = ce_sanitize_json( (string) $raw );
			} else {
				$value = ce_sanitize_field( $field, $raw, get_post_meta( $post_id, '_ce_' . $field['id'], true ) );
			}
			update_post_meta( $post_id, '_ce_' . $field['id'], $value );
		}
	}
}

/**
 * JSON-LD girdisini doğrular; geçersizse boş döner.
 *
 * @param string $raw Ham.
 * @return string
 */
function ce_sanitize_json( $raw ) {
	$raw = trim( $raw );
	if ( '' === $raw ) {
		return '';
	}
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		return '';
	}
	return (string) wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}

add_action( 'admin_enqueue_scripts', 'ce_admin_assets' );
/**
 * Yönetim paneli dosyaları.
 *
 * @param string $hook Sayfa.
 */
function ce_admin_assets( $hook ) {
	$ver = CE_VERSION . '.' . filemtime( CE_DIR . '/assets/admin/admin.js' );
	wp_enqueue_style( 'ce-admin', CE_URI . '/assets/admin/admin.css', array(), CE_VERSION . '.' . filemtime( CE_DIR . '/assets/admin/admin.css' ) );
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || false !== strpos( $hook, 'ce-' ) ) {
		wp_enqueue_media();
	}
	wp_enqueue_script( 'ce-admin', CE_URI . '/assets/admin/admin.js', array( 'jquery', 'jquery-ui-sortable' ), $ver, true );
	wp_localize_script(
		'ce-admin',
		'CEAdmin',
		array(
			'ajax'    => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'ce_admin' ),
			'confirm' => 'Bu kayıt kalıcı olarak silinecek. Emin misiniz?',
			'trash'   => 'Bu kayıt çöp kutusuna taşınacak. Emin misiniz?',
			'choose'  => 'Görsel seç',
			'use'     => 'Kullan',
			'icons'   => ce_icon_paths(),
		)
	);
}
