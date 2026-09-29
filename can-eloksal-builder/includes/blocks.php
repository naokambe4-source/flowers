<?php
/**
 * Blok tanımları, kaydı ve sunucu tarafı çizimi.
 *
 * Tüm bloklar dinamiktir: içerik PHP'de temanın bileşenleriyle çizilir, bu yüzden tema
 * güncellendiğinde bloklar da otomatik güncellenir. Boş bırakılan alanlar Tema Ayarları'ndaki
 * değerleri kullanır.
 *
 * @package CanEloksalBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Terim seçenekleri.
 *
 * @param string $taxonomy Taksonomi.
 * @return array<string,string>
 */
function ceb_term_options( $taxonomy ) {
	$out   = array( '' => 'Tümü' );
	$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			$out[ $t->slug ] = $t->name;
		}
	}
	return $out;
}

/**
 * Blok tanımları.
 *
 * Alan tipleri: text, textarea, lines, select, toggle, number, image, url.
 *
 * @return array
 */
function ceb_blocks() {
	static $blocks = null;
	if ( null !== $blocks ) {
		return $blocks;
	}
	$items_help = 'Her satır bir kart: Başlık | Açıklama | ikon (ikon: shield, layers, palette, gauge, target, settings, cpu, ruler, sparkle, badge-check, factory, refresh, users, clock, check). Boş bırakılırsa Tema Ayarları kullanılır.';
	$tone       = array( '' => 'Varsayılan' ) + ce_tone_choices();

	$blocks = array(
		'slider'       => array(
			'title'       => 'Hero Slider',
			'icon'        => 'slides',
			'description' => 'Ana sayfa hero slider. Slaytlar "Hero / Slider" menüsünden yönetilir.',
			'fields'      => array(),
			'part'        => 'home/hero',
		),
		'page-hero'    => array(
			'title'       => 'Sayfa Başlığı (Hero)',
			'icon'        => 'cover-image',
			'description' => 'Koyu zeminli sayfa başlığı: breadcrumb, üst etiket, başlık, alt başlık, görsel ve butonlar.',
			'fields'      => array(
				'title'      => array( 'text', 'Başlık', '' ),
				'eyebrow'    => array( 'text', 'Üst etiket', '' ),
				'subtitle'   => array( 'textarea', 'Alt başlık', '' ),
				'image'      => array( 'image', 'Arka plan görseli', 0 ),
				'tone'       => array( 'select', 'Görsel yokken renk tonu', 'steel', $tone ),
				'size'       => array( 'select', 'Yükseklik', 'lg', array( 'md' => 'Orta', 'lg' => 'Büyük' ) ),
				'btn1_label' => array( 'text', 'Buton 1 metni', '' ),
				'btn1_url'   => array( 'url', 'Buton 1 bağlantısı', '' ),
				'btn2_label' => array( 'text', 'Buton 2 metni', '' ),
				'btn2_url'   => array( 'url', 'Buton 2 bağlantısı', '' ),
			),
		),
		'trust'        => array(
			'title'       => 'Güven Şeridi',
			'icon'        => 'shield',
			'description' => 'İkonlu kısa yetkinlik maddeleri (koyu şerit).',
			'fields'      => array(
				'trust_items' => array( 'lines', 'Maddeler', '', null, $items_help ),
			),
			'part'        => 'home/trust',
		),
		'services'     => array(
			'title'       => 'Hizmetler',
			'icon'        => 'admin-generic',
			'description' => 'Fotoğraf odaklı hizmet kartları (editoryal veya eşit ızgara).',
			'fields'      => array(
				'services_eyebrow' => array( 'text', 'Üst etiket', '' ),
				'services_title'   => array( 'text', 'Başlık', '' ),
				'services_text'    => array( 'textarea', 'Açıklama', '' ),
				'services_count'   => array( 'number', 'Hizmet sayısı', 9, array( 1, 24 ) ),
				'category'         => array( 'select', 'Kategori', '', 'ce_service_cat' ),
				'layout'           => array( 'select', 'Düzen', 'editorial', array( 'editorial' => 'Editoryal (büyük/küçük kartlar)', 'grid' => 'Eşit ızgara (3 sütun)' ) ),
			),
			'part'        => 'home/services',
		),
		'about'        => array(
			'title'       => 'Görsel + Metin',
			'icon'        => 'align-pull-left',
			'description' => 'İki kolonlu editoryal bölüm: büyük görsel, başlık, paragraflar ve buton.',
			'fields'      => array(
				'about_eyebrow' => array( 'text', 'Üst etiket', '' ),
				'about_title'   => array( 'textarea', 'Başlık (satır başı = yeni satır)', '' ),
				'about_text'    => array( 'textarea', 'Metin (boş satır = yeni paragraf)', '' ),
				'about_image'   => array( 'image', 'Ana görsel', 0 ),
				'about_image_2' => array( 'image', 'Küçük görsel', 0 ),
				'about_badge'   => array( 'text', 'Görsel etiketi', '' ),
				'about_button'  => array( 'text', 'Buton metni', '' ),
				'about_link'    => array( 'url', 'Buton bağlantısı', '' ),
			),
			'part'        => 'home/about',
		),
		'features'     => array(
			'title'       => 'Özellik Kartları (Bento)',
			'icon'        => 'screenoptions',
			'description' => 'İkonlu kartlar; ilk kart vurgulu. "Neden Can Eloksal" bölümü.',
			'fields'      => array(
				'why_eyebrow' => array( 'text', 'Üst etiket', '' ),
				'why_title'   => array( 'text', 'Başlık', '' ),
				'why_items'   => array( 'lines', 'Kartlar', '', null, $items_help ),
			),
			'part'        => 'home/why',
		),
		'sectors'      => array(
			'title'       => 'Sektörler',
			'icon'        => 'building',
			'description' => 'Genişleyen sektör panelleri. Kartlar "Sektörler" menüsünden yönetilir.',
			'fields'      => array(
				'sectors_eyebrow' => array( 'text', 'Üst etiket', '' ),
				'sectors_title'   => array( 'text', 'Başlık', '' ),
			),
			'part'        => 'home/sectors',
		),
		'process'      => array(
			'title'       => 'Proses / Zaman Çizelgesi',
			'icon'        => 'editor-ol',
			'description' => 'Numaralı adımlar (masaüstünde yatay, mobilde dikey).',
			'fields'      => array(
				'process_eyebrow' => array( 'text', 'Üst etiket', '' ),
				'process_title'   => array( 'text', 'Başlık', '' ),
				'process_steps'   => array( 'lines', 'Adımlar', '', null, 'Her satır bir adım: Başlık | Açıklama. Boş bırakılırsa Tema Ayarları kullanılır.' ),
			),
			'part'        => 'home/process',
		),
		'gallery'      => array(
			'title'       => 'Galeri',
			'icon'        => 'format-gallery',
			'description' => 'Masonry galeri + lightbox. Görseller "Galeri" menüsünden yönetilir.',
			'fields'      => array(
				'gallery_eyebrow' => array( 'text', 'Üst etiket', '' ),
				'gallery_title'   => array( 'text', 'Başlık', '' ),
				'gallery_count'   => array( 'number', 'Görsel sayısı', 8, array( 2, 60 ) ),
				'category'        => array( 'select', 'Kategori', '', 'ce_gallery_cat' ),
				'filter'          => array( 'toggle', 'Kategori filtresini göster', false ),
			),
			'part'        => 'home/gallery',
		),
		'cta'          => array(
			'title'       => 'Tam Genişlik CTA',
			'icon'        => 'megaphone',
			'description' => 'Görselli büyük çağrı alanı: başlık, açıklama, buton ve telefon.',
			'fields'      => array(
				'cta_title'  => array( 'textarea', 'Başlık', '' ),
				'cta_text'   => array( 'textarea', 'Açıklama', '' ),
				'cta_image'  => array( 'image', 'Arka plan görseli', 0 ),
				'cta_button' => array( 'text', 'Buton metni', '' ),
				'cta_link'   => array( 'url', 'Buton bağlantısı', '' ),
			),
			'part'        => 'home/cta',
		),
		'band'         => array(
			'title'       => 'CTA Bandı',
			'icon'        => 'button',
			'description' => 'Kompakt koyu çağrı bandı.',
			'fields'      => array(
				'title'  => array( 'text', 'Başlık', 'Parçalarınız için doğru yüzey çözümünü birlikte belirleyelim.' ),
				'text'   => array( 'text', 'Açıklama', '' ),
				'button' => array( 'text', 'Buton metni', 'Teklif Al' ),
				'url'    => array( 'url', 'Buton bağlantısı', '/teklif-al/' ),
			),
		),
		'posts'        => array(
			'title'       => 'Blog Yazıları',
			'icon'        => 'admin-post',
			'description' => 'Son yazılar (Teknik Bilgi Merkezi).',
			'fields'      => array(
				'blog_eyebrow' => array( 'text', 'Üst etiket', '' ),
				'blog_title'   => array( 'text', 'Başlık', '' ),
				'blog_count'   => array( 'number', 'Yazı sayısı', 3, array( 1, 12 ) ),
				'category'     => array( 'select', 'Kategori', '', 'category' ),
			),
			'part'        => 'home/blog',
		),
		'references'   => array(
			'title'       => 'Referans Logoları',
			'icon'        => 'awards',
			'description' => 'Logo kayan şerit. Yalnızca logosu olan referanslar gösterilir.',
			'fields'      => array(
				'refs_title' => array( 'text', 'Başlık', '' ),
			),
			'part'        => 'home/references',
		),
		'contact'      => array(
			'title'       => 'İletişim Kartları',
			'icon'        => 'phone',
			'description' => 'Telefon, WhatsApp, e-posta ve adres kartları + butonlar.',
			'fields'      => array(
				'contact_title' => array( 'text', 'Başlık', '' ),
				'contact_text'  => array( 'textarea', 'Açıklama', '' ),
			),
			'part'        => 'home/contact',
		),
		'map'          => array(
			'title'       => 'Harita',
			'icon'        => 'location-alt',
			'description' => 'Tema Ayarları\'ndaki Google Maps konumu (tam genişlik).',
			'fields'      => array(),
		),
		'contact-form' => array(
			'title'       => 'İletişim Formu',
			'icon'        => 'email',
			'description' => 'Veritabanına kaydedilen, SMTP bildirimli iletişim formu.',
			'fields'      => array(
				'title'    => array( 'text', 'Form başlığı', 'Bize yazın' ),
				'text'     => array( 'text', 'Açıklama', '' ),
				'subjects' => array( 'lines', 'Konu seçenekleri', '', null, 'Her satır bir konu. Boşsa serbest metin alanı gösterilir.' ),
			),
		),
		'quote-form'   => array(
			'title'       => 'Teklif Formu',
			'icon'        => 'clipboard',
			'description' => 'Dosya yüklemeli teklif formu (PDF, JPG, PNG, WEBP).',
			'fields'      => array(),
		),
		'banks'        => array(
			'title'       => 'Banka Hesapları',
			'icon'        => 'bank',
			'description' => 'IBAN kopyalama butonlu banka kartları.',
			'fields'      => array(),
		),
		'heading'      => array(
			'title'       => 'Bölüm Başlığı',
			'icon'        => 'heading',
			'description' => 'Üst etiket + büyük başlık + açıklama + bağlantı.',
			'fields'      => array(
				'eyebrow'    => array( 'text', 'Üst etiket', '' ),
				'title'      => array( 'textarea', 'Başlık', 'Bölüm başlığı' ),
				'text'       => array( 'textarea', 'Açıklama', '' ),
				'link'       => array( 'url', 'Bağlantı', '' ),
				'link_label' => array( 'text', 'Bağlantı metni', '' ),
				'align'      => array( 'select', 'Hizalama', 'left', array( 'left' => 'Sol', 'center' => 'Orta', 'split' => 'İki kolon' ) ),
			),
		),
		'section'      => array(
			'title'       => 'Bölüm (Kapsayıcı)',
			'icon'        => 'align-wide',
			'description' => 'İçine istediğiniz blokları (paragraf, görsel, sütunlar, butonlar…) koyabileceğiniz tema stilinde bölüm.',
			'inner'       => true,
			'fields'      => array(
				'bg'      => array( 'select', 'Zemin', 'light', array( 'light' => 'Beyaz', 'tint' => 'Açık gri', 'dark' => 'Koyu' ) ),
				'width'   => array( 'select', 'Genişlik', 'normal', array( 'narrow' => 'Dar (metin)', 'normal' => 'Normal', 'full' => 'Tam genişlik' ) ),
				'padding' => array( 'select', 'Dikey boşluk', 'normal', array( 'normal' => 'Normal', 'compact' => 'Az', 'none' => 'Yok' ) ),
				'image'   => array( 'image', 'Arka plan görseli (opsiyonel)', 0 ),
				'overlay' => array( 'number', 'Görsel karartma (%)', 60, array( 0, 90 ) ),
			),
		),
	);

	// Seçim listelerini ve öznitelikleri tamamla.
	foreach ( $blocks as $slug => &$b ) {
		foreach ( $b['fields'] as $key => &$f ) {
			if ( 'select' === $f[0] && is_string( $f[3] ?? null ) ) {
				$f[3] = ceb_term_options( $f[3] );
			}
		}
		unset( $f );
	}
	unset( $b );
	return $blocks;
}

/**
 * Blok öznitelikleri (register_block_type için).
 *
 * @param array $fields Alanlar.
 * @return array
 */
function ceb_attributes( $fields ) {
	$attrs = array();
	foreach ( $fields as $key => $f ) {
		$type = in_array( $f[0], array( 'number', 'image' ), true ) ? 'number' : ( 'toggle' === $f[0] ? 'boolean' : 'string' );
		$attrs[ $key ] = array( 'type' => $type, 'default' => $f[2] );
	}
	return $attrs;
}

add_action( 'init', 'ceb_register_blocks', 20 );
/**
 * Blokları kaydeder.
 */
function ceb_register_blocks() {
	wp_register_script(
		'ceb-editor',
		CEB_URL . 'assets/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-data' ),
		CEB_VERSION . '.' . filemtime( CEB_DIR . 'assets/editor.js' ),
		true
	);
	wp_register_style( 'ceb-editor', CEB_URL . 'assets/editor.css', array(), CEB_VERSION . '.' . filemtime( CEB_DIR . 'assets/editor.css' ) );

	foreach ( ceb_blocks() as $slug => $b ) {
		register_block_type(
			'ce/' . $slug,
			array(
				'api_version'     => 3,
				'title'           => $b['title'],
				'category'        => 'can-eloksal',
				'icon'            => $b['icon'],
				'description'     => $b['description'],
				'attributes'      => ceb_attributes( $b['fields'] ),
				'supports'        => array( 'html' => false, 'align' => false, 'customClassName' => true ),
				'editor_script'   => 'ceb-editor',
				'editor_style'    => 'ceb-editor',
				'render_callback' => static function ( $attributes, $content = '' ) use ( $slug ) {
					return ceb_render( $slug, (array) $attributes, (string) $content );
				},
			)
		);
	}
}

add_filter( 'block_categories_all', 'ceb_block_category' );
/**
 * "Can Eloksal" blok kategorisi (listenin başında).
 *
 * @param array $categories Kategoriler.
 * @return array
 */
function ceb_block_category( $categories ) {
	array_unshift( $categories, array( 'slug' => 'can-eloksal', 'title' => 'Can Eloksal', 'icon' => null ) );
	return $categories;
}

add_action( 'enqueue_block_editor_assets', 'ceb_editor_data' );
/**
 * Editöre blok tanımlarını aktarır.
 */
function ceb_editor_data() {
	$data = array();
	foreach ( ceb_blocks() as $slug => $b ) {
		$fields = array();
		foreach ( $b['fields'] as $key => $f ) {
			$fields[] = array(
				'key'     => $key,
				'type'    => $f[0],
				'label'   => $f[1],
				'options' => ( 'select' === $f[0] && is_array( $f[3] ?? null ) ) ? $f[3] : null,
				'range'   => ( 'number' === $f[0] && is_array( $f[3] ?? null ) ) ? $f[3] : null,
				'help'    => $f[4] ?? '',
			);
		}
		$data[ 'ce/' . $slug ] = array(
			'title'       => $b['title'],
			'icon'        => $b['icon'],
			'description' => $b['description'],
			'inner'       => ! empty( $b['inner'] ),
			'fields'      => $fields,
		);
	}
	wp_add_inline_script( 'ceb-editor', 'window.CEBlocks = ' . wp_json_encode( $data ) . ';', 'before' );
}

/**
 * "Başlık | Açıklama | ikon" satırlarını diziye çevirir.
 *
 * @param string $text  Metin.
 * @param bool   $icons İkon sütunu var mı.
 * @return array
 */
function ceb_parse_items( $text, $icons = true ) {
	$out = array();
	foreach ( ce_lines( $text ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		$row   = array( 'title' => $parts[0], 'text' => $parts[1] ?? '' );
		if ( $icons ) {
			$row['icon'] = isset( $parts[2] ) && array_key_exists( $parts[2], ce_icon_choices() ) ? $parts[2] : 'check';
		}
		$out[] = $row;
	}
	return $out;
}

/**
 * Blok çizimi.
 *
 * @param string $slug    Blok.
 * @param array  $a       Öznitelikler.
 * @param string $content İç içerik (kapsayıcı blok).
 * @return string
 */
function ceb_render( $slug, $a, $content ) {
	static $n = 0;
	++$n;
	$blocks = ceb_blocks();
	$def    = $blocks[ $slug ] ?? null;
	if ( ! $def ) {
		return '';
	}
	$args        = $a;
	$args['uid'] = 'b' . $n;

	// Satır alanlarını tema biçimine çevir.
	if ( ! empty( $a['trust_items'] ) ) {
		$args['trust_items'] = ceb_parse_items( $a['trust_items'] );
	}
	if ( ! empty( $a['why_items'] ) ) {
		$args['why_items'] = ceb_parse_items( $a['why_items'] );
	}
	if ( ! empty( $a['process_steps'] ) ) {
		$args['process_steps'] = ceb_parse_items( $a['process_steps'], false );
	}

	ob_start();
	switch ( $slug ) {
		case 'page-hero':
			$meta = ce_button( $a['btn1_label'] ?? '', $a['btn1_url'] ?? '', 'primary', 'arrow-right' ) . ce_button( $a['btn2_label'] ?? '', $a['btn2_url'] ?? '', 'ghost-light', 'arrow-up-right' );
			get_template_part(
				'template-parts/components/page-hero',
				null,
				array(
					'title'    => $a['title'] ? $a['title'] : get_the_title(),
					'subtitle' => $a['subtitle'] ?? '',
					'eyebrow'  => $a['eyebrow'] ?? '',
					'image'    => absint( $a['image'] ?? 0 ),
					'tone'     => $a['tone'] ? $a['tone'] : 'steel',
					'size'     => $a['size'] ?? 'lg',
					'meta'     => $meta,
				)
			);
			break;

		case 'band':
			get_template_part( 'template-parts/components/cta-band', null, $a );
			break;

		case 'map':
			$map = ce_map_embed();
			if ( $map ) {
				echo '<section class="ce-map" aria-label="Konum haritası"><div class="ce-map__frame">' . $map . '</div>'; // phpcs:ignore -- doğrulanmış iframe.
				if ( ce_opt( 'maps_link' ) ) {
					echo '<a class="ce-btn ce-btn--dark ce-map__btn" href="' . esc_url( ce_opt( 'maps_link' ) ) . '" target="_blank" rel="noopener">' . ce_icon( 'map-pin', 18, 'ce-btn__icon' ) . '<span>Yol tarifi al</span></a>'; // phpcs:ignore
				}
				echo '</section>';
			}
			break;

		case 'contact-form':
			echo '<section class="ce-section"><div class="ce-container ce-container--narrow">';
			get_template_part( 'template-parts/components/form-contact', null, array( 'title' => $a['title'] ?? '', 'text' => $a['text'] ?? '', 'subjects' => ce_lines( $a['subjects'] ?? '' ) ) );
			echo '</div></section>';
			break;

		case 'quote-form':
			echo '<section class="ce-section"><div class="ce-container ce-container--narrow">';
			get_template_part( 'template-parts/components/form-quote' );
			echo '</div></section>';
			break;

		case 'banks':
			echo '<section class="ce-section"><div class="ce-container">';
			get_template_part( 'template-parts/components/banks' );
			echo '</div></section>';
			break;

		case 'heading':
			echo '<div class="ce-container ce-block-heading">';
			ce_section_head(
				array(
					'eyebrow'    => $a['eyebrow'] ?? '',
					'title'      => $a['title'] ?? '',
					'text'       => $a['text'] ?? '',
					'link'       => ! empty( $a['link'] ) ? ce_url( $a['link'] ) : '',
					'link_label' => $a['link_label'] ?? '',
					'align'      => $a['align'] ?? 'left',
				)
			);
			echo '</div>';
			break;

		case 'section':
			$bg      = in_array( $a['bg'] ?? 'light', array( 'light', 'tint', 'dark' ), true ) ? $a['bg'] : 'light';
			$width   = $a['width'] ?? 'normal';
			$padding = $a['padding'] ?? 'normal';
			$img     = absint( $a['image'] ?? 0 );
			$classes = array( 'ce-section', 'ce-block-section', 'ce-block-section--' . $bg );
			if ( 'tint' === $bg ) {
				$classes[] = 'ce-section--tint';
			} elseif ( 'dark' === $bg ) {
				$classes[] = 'ce-section--dark';
			}
			if ( 'compact' === $padding ) {
				$classes[] = 'ce-section--compact';
			}
			$style = '';
			if ( 'none' === $padding ) {
				$style .= 'padding-block:0;';
			}
			if ( $img ) {
				$url = wp_get_attachment_image_url( $img, 'ce-hero' );
				if ( $url ) {
					$classes[] = 'has-bg-image ce-section--dark';
					$style    .= 'background-image:url(' . esc_url( $url ) . ');--ce-overlay:' . ( max( 0, min( 90, (int) ( $a['overlay'] ?? 60 ) ) ) / 100 ) . ';';
				}
			}
			$container = 'full' === $width ? '' : ( 'narrow' === $width ? 'ce-container ce-container--narrow ce-prose-wrap' : 'ce-container' );
			printf(
				'<section class="%1$s"%2$s><div class="%3$s">%4$s</div></section>',
				esc_attr( implode( ' ', $classes ) . ( ! empty( $a['className'] ) ? ' ' . implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $a['className'] ) ) ) : '' ) ),
				$style ? ' style="' . esc_attr( $style ) . '"' : '',
				esc_attr( $container ),
				$content // phpcs:ignore -- iç blokların WordPress tarafından üretilmiş çıktısı.
			);
			break;

		default:
			if ( ! empty( $def['part'] ) ) {
				get_template_part( 'template-parts/' . $def['part'], null, $args );
			}
	}
	$html = (string) ob_get_clean();

	// Editör önizlemesinde boş bölümler için açıklayıcı yer tutucu.
	if ( '' === trim( $html ) && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$html = '<div class="ceb-empty">' . esc_html( $def['title'] ) . ': gösterilecek içerik yok. ' . esc_html( $def['description'] ) . '</div>';
	}
	// Ek CSS sınıfı (Gelişmiş → Ek CSS sınıfları) dış sarmalayıcıya eklenir.
	if ( ! empty( $a['className'] ) && 'section' !== $slug && '' !== trim( $html ) ) {
		$html = '<div class="' . esc_attr( implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $a['className'] ) ) ) ) . '">' . $html . '</div>';
	}
	return $html;
}
