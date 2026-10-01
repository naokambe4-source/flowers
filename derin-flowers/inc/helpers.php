<?php
/**
 * Yardımcı fonksiyonlar: seçenekler, görseller, bağlantılar, bölüm başlıkları.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Şemadan varsayılan değerler.
 *
 * @return array
 */
function df_defaults() {
	static $defaults = null;
	if ( null !== $defaults ) {
		return $defaults;
	}
	$defaults = array();
	foreach ( df_options_schema() as $tab ) {
		foreach ( $tab['groups'] as $group ) {
			foreach ( $group['fields'] as $field ) {
				if ( empty( $field['id'] ) ) {
					continue;
				}
				if ( 'sections' === $field['type'] ) {
					$defaults[ $field['id'] ] = df_default_sections();
					continue;
				}
				$defaults[ $field['id'] ] = isset( $field['default'] ) ? $field['default'] : ( in_array( $field['type'], array( 'repeater', 'products', 'checkboxes' ), true ) ? array() : '' );
			}
		}
	}
	return $defaults;
}

/**
 * Varsayılan bölüm listesi.
 *
 * @return array
 */
function df_default_sections() {
	$out = array();
	foreach ( array_keys( df_home_section_labels() ) as $id ) {
		$out[] = array(
			'id' => $id,
			'on' => 1,
		);
	}
	return $out;
}

/**
 * Tüm seçenekler (varsayılanlarla birleştirilmiş).
 *
 * @return array
 */
function df_options() {
	global $df_options_cache;
	if ( null === $df_options_cache ) {
		// Tasarım Stüdyosu önizlemesi: yayınlanmamış taslak yalnızca yöneticinin önizleme penceresinde görünür.
		if ( ! did_action( 'init' ) && df_preview_requested() ) {
			$saved = get_option( DF_OPTION, array() );
			return array_merge( df_defaults(), is_array( $saved ) ? $saved : array() );
		}
		$saved = df_preview_mode() ? get_option( DF_OPTION_DRAFT, null ) : null;
		if ( ! is_array( $saved ) ) {
			$saved = get_option( DF_OPTION, array() );
		}
		$df_options_cache = array_merge( df_defaults(), is_array( $saved ) ? $saved : array() );
	}
	return $df_options_cache;
}

/**
 * İstek önizleme parametresi taşıyor mu?
 *
 * @return bool
 */
function df_preview_requested() {
	return ! is_admin() && isset( $_GET['df_preview'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Tasarım Stüdyosu önizleme penceresi mi? (taslak ayarlar gösterilir)
 *
 * @return bool
 */
function df_preview_mode() {
	return df_preview_requested() && did_action( 'init' ) && current_user_can( 'edit_theme_options' );
}

/**
 * Tek seçenek.
 *
 * @param string $key     Anahtar.
 * @param mixed  $default Varsayılan.
 * @return mixed
 */
function df_opt( $key, $default = null ) {
	$opts = df_options();
	if ( array_key_exists( $key, $opts ) ) {
		return $opts[ $key ];
	}
	return $default;
}

/**
 * Seçenek önbelleğini temizler.
 */
function df_flush_options_cache() {
	global $df_options_cache;
	$df_options_cache = null;
}
add_action( 'add_option_' . DF_OPTION, 'df_flush_options_cache' );
add_action( 'update_option_' . DF_OPTION, 'df_flush_options_cache' );

/**
 * Bağlantı çözümleyici: "/magaza/" gibi göreli adresleri site adresine çevirir.
 *
 * @param string $url URL.
 * @return string
 */
function df_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
		return home_url( $url );
	}
	if ( 0 === strpos( $url, '#' ) || preg_match( '#^(https?:|mailto:|tel:|//)#i', $url ) ) {
		return $url;
	}
	return home_url( '/' . ltrim( $url, '/' ) );
}

/**
 * Metindeki {cutoff}, {year} gibi değişkenleri doldurur.
 *
 * @param string $text Metin.
 * @return string
 */
function df_vars( $text ) {
	return strtr(
		(string) $text,
		array(
			'{cutoff}' => str_replace( ':', '.', (string) df_opt( 'df_cutoff', '16:00' ) ),
			'{year}'   => wp_date( 'Y' ),
			'{site}'   => get_bloginfo( 'name' ),
		)
	);
}

/**
 * Satır satır metni diziye çevirir. "a | b | c" satırlarını parçalar.
 *
 * @param string $text  Metin.
 * @param bool   $split "|" ile bölünsün mü.
 * @return array
 */
function df_lines( $text, $split = false ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$out[] = $split ? array_map( 'trim', explode( '|', $line ) ) : $line;
	}
	return $out;
}

/**
 * Çok satırlı metni <br> ile güvenli HTML'e çevirir.
 *
 * @param string $text Metin.
 * @return string
 */
function df_nl2br( $text ) {
	// Satır sonu karakteri bırakılmaz: canlı düzenleyicide (pre-wrap) çift satır oluşmasın.
	return str_replace( array( "\r\n", "\r", "\n" ), '<br>', esc_html( trim( (string) $text ) ) );
}

/**
 * Görsel URL'si.
 *
 * @param int    $id   Ek ID.
 * @param string $size Boyut.
 * @return string
 */
function df_img_url( $id, $size = 'full' ) {
	$id = absint( $id );
	if ( ! $id ) {
		return '';
	}
	$src = wp_get_attachment_image_url( $id, $size );
	return $src ? $src : '';
}

/**
 * Görsel etiketi ya da zarif yer tutucu.
 *
 * @param int    $id    Ek ID.
 * @param string $size  Boyut.
 * @param array  $attr  Nitelikler.
 * @param string $label Görsel yoksa yöneticiye gösterilecek ipucu.
 * @return string
 */
function df_image( $id, $size = 'large', $attr = array(), $label = '' ) {
	$id = absint( $id );
	if ( $id && wp_attachment_is_image( $id ) ) {
		$attr = wp_parse_args(
			$attr,
			array(
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);
		return wp_get_attachment_image( $id, $size, false, $attr );
	}
	return df_placeholder( $label );
}

/**
 * Görsel eklenmemiş alanlar için tonlu yer tutucu (aynı görseli tekrar etmek yerine).
 *
 * @param string $label Etiket.
 * @return string
 */
function df_placeholder( $label = '' ) {
	$hint = '';
	if ( $label && current_user_can( 'edit_theme_options' ) ) {
		$hint = '<span class="df-ph__hint">' . df_icon( 'edit', array( 'size' => 14 ) ) . esc_html( $label ) . '</span>';
	}
	return '<span class="df-ph" aria-hidden="true">' . df_icon( 'bouquet', array( 'size' => 42, 'class' => 'df-ph__icon' ) ) . $hint . '</span>';
}

/**
 * Bölüm başlığı.
 *
 * @param array $args eyebrow, title, sub, align, link, link_text, tag.
 */
function df_section_head( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'eyebrow'   => '',
			'title'     => '',
			'sub'       => '',
			'align'     => 'center',
			'link'      => '',
			'link_text' => '',
			'tag'       => 'h2',
			'id'        => '',
			'keys'      => array(),
		)
	);
	$k = wp_parse_args( (array) $args['keys'], array( 'eyebrow' => '', 'title' => '', 'sub' => '' ) );
	if ( ! $args['title'] && ! $args['eyebrow'] ) {
		return;
	}
	$tag = in_array( $args['tag'], array( 'h1', 'h2', 'h3' ), true ) ? $args['tag'] : 'h2';
	?>
	<header class="df-head df-head--<?php echo esc_attr( $args['align'] ); ?>">
		<div class="df-head__text">
			<?php if ( $args['eyebrow'] ) : ?>
				<p class="df-eyebrow"<?php echo $k['eyebrow'] ? df_e( $k['eyebrow'] ) : ''; // phpcs:ignore ?>><?php echo esc_html( $args['eyebrow'] ); ?></p>
			<?php endif; ?>
			<?php if ( $args['title'] ) : ?>
				<<?php echo $tag; // phpcs:ignore ?> class="df-head__title"<?php echo $args['id'] ? ' id="' . esc_attr( $args['id'] ) . '"' : ''; ?><?php echo $k['title'] ? df_e( $k['title'] ) : ''; // phpcs:ignore ?>><?php echo esc_html( $args['title'] ); ?></<?php echo $tag; // phpcs:ignore ?>>
			<?php endif; ?>
			<?php if ( $args['sub'] ) : ?>
				<p class="df-head__sub"<?php echo $k['sub'] ? df_e( $k['sub'] ) : ''; // phpcs:ignore ?>><?php echo esc_html( $args['sub'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $args['link'] && $args['link_text'] ) : ?>
			<a class="df-link-arrow" href="<?php echo esc_url( df_url( $args['link'] ) ); ?>"><?php echo esc_html( $args['link_text'] ); ?><?php df_the_icon( 'arrow-right', array( 'size' => 18 ) ); ?></a>
		<?php endif; ?>
	</header>
	<?php
}

/**
 * Buton.
 *
 * @param string $text    Metin.
 * @param string $url     Bağlantı.
 * @param string $variant solid|outline|light|text.
 * @return string
 */
function df_button( $text, $url, $variant = 'solid' ) {
	if ( ! $text || ! $url ) {
		return '';
	}
	return sprintf(
		'<a class="df-btn df-btn--%1$s" href="%2$s"><span>%3$s</span></a>',
		esc_attr( $variant ),
		esc_url( df_url( $url ) ),
		esc_html( $text )
	);
}

/**
 * Ana sayfa bölümleri: kayıtlı sıra + sonradan eklenen bölümler varsayılan yerlerinde.
 *
 * @return array<int, array{id:string,on:bool}>
 */
function df_all_sections_ordered() {
	$labels   = df_home_section_labels();
	$sections = df_opt( 'home_sections', df_default_sections() );
	$order    = array();
	$on       = array();
	if ( is_array( $sections ) ) {
		foreach ( $sections as $s ) {
			if ( empty( $s['id'] ) || ! isset( $labels[ $s['id'] ] ) || in_array( $s['id'], $order, true ) ) {
				continue;
			}
			$order[]        = $s['id'];
			$on[ $s['id'] ] = ! empty( $s['on'] );
		}
	}
	// Temaya sonradan eklenen bölümler, varsayılan sıradaki bir önceki bölümün arkasına açık olarak eklenir.
	$defaults = array_keys( $labels );
	foreach ( $defaults as $i => $id ) {
		if ( in_array( $id, $order, true ) ) {
			continue;
		}
		$pos = 0;
		for ( $j = $i - 1; $j >= 0; $j-- ) {
			$k = array_search( $defaults[ $j ], $order, true );
			if ( false !== $k ) {
				$pos = $k + 1;
				break;
			}
		}
		array_splice( $order, $pos, 0, array( $id ) );
		$on[ $id ] = true;
	}
	$out = array();
	foreach ( $order as $id ) {
		$out[] = array(
			'id' => $id,
			'on' => (bool) $on[ $id ],
		);
	}
	return $out;
}

/**
 * Aktif ana sayfa bölümleri (sıralı).
 *
 * @return string[]
 */
function df_active_sections() {
	$out = array();
	foreach ( df_all_sections_ordered() as $s ) {
		if ( $s['on'] ) {
			$out[] = $s['id'];
		}
	}
	return apply_filters( 'df_active_sections', $out );
}

/**
 * Canlı düzenleme modu açık mı? (?df_live=1, yalnızca tema yöneticileri)
 *
 * @return bool
 */
function df_live() {
	static $live = null;
	if ( null === $live ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- yalnızca görünüm modu.
		$live = ! is_admin() && ( isset( $_GET['df_live'] ) || isset( $_GET['df_preview'] ) ) && current_user_can( 'edit_theme_options' );
	}
	return $live;
}

/**
 * Canlı düzenleyicide yazı alanı işareti. Örn: df_e( 'hero_slides.0.title' ).
 *
 * @param string $key Seçenek yolu.
 * @return string
 */
function df_e( $key ) {
	if ( ! df_live() ) {
		return '';
	}
	$def  = df_field_def( $key );
	$type = $def ? $def['type'] : 'text';
	return ' data-df-edit="' . esc_attr( $key ) . '" data-df-type="' . esc_attr( $type ) . '"';
}

/**
 * Seçenek yolundan (ör. "hero_slides.0.title") şema alan tanımını bulur.
 *
 * @param string $path Yol.
 * @return array|null
 */
function df_field_def( $path ) {
	static $map = null;
	if ( null === $map ) {
		$map = array();
		foreach ( df_options_schema() as $tab ) {
			foreach ( $tab['groups'] as $group ) {
				foreach ( $group['fields'] as $field ) {
					if ( ! empty( $field['id'] ) ) {
						$map[ $field['id'] ] = $field;
					}
				}
			}
		}
	}
	$parts = explode( '.', (string) $path );
	if ( empty( $map[ $parts[0] ] ) ) {
		return null;
	}
	$field = $map[ $parts[0] ];
	if ( 1 === count( $parts ) ) {
		return 'repeater' === $field['type'] ? null : $field;
	}
	if ( 'repeater' !== $field['type'] || 3 !== count( $parts ) || ! ctype_digit( $parts[1] ) ) {
		return null;
	}
	foreach ( $field['fields'] as $sub ) {
		if ( $sub['id'] === $parts[2] ) {
			return $sub;
		}
	}
	return null;
}

/**
 * Canlı düzenleyicide görsel alanı işareti.
 *
 * @param string $key Seçenek yolu (ek ID tutan alan).
 * @return string
 */
function df_i( $key ) {
	return df_live() ? ' data-df-img="' . esc_attr( $key ) . '"' : '';
}

/**
 * Canlı düzenleyicide bölüm sarmalayıcısı işareti.
 *
 * @param string $id Bölüm.
 * @return string
 */
function df_s( $id ) {
	return df_live() ? ' data-df-section="' . esc_attr( $id ) . '"' : '';
}

/**
 * Telefon numarasını tel: bağlantısına çevirir.
 *
 * @param string $phone Telefon.
 * @return string
 */
function df_tel( $phone ) {
	$digits = preg_replace( '/[^0-9+]/', '', (string) $phone );
	return 'tel:' . $digits;
}

/**
 * WhatsApp bağlantısı.
 *
 * @param string $text Mesaj.
 * @return string
 */
function df_whatsapp_url( $text = '' ) {
	$num = preg_replace( '/\D/', '', (string) df_opt( 'contact_whatsapp' ) );
	if ( ! $num ) {
		return '';
	}
	return 'https://wa.me/' . $num . ( $text ? '?text=' . rawurlencode( $text ) : '' );
}

/**
 * Sosyal medya bağlantıları.
 *
 * @return array<string,string>
 */
function df_social_links() {
	$out = array();
	foreach ( array( 'instagram', 'facebook', 'pinterest', 'youtube', 'tiktok' ) as $net ) {
		$url = df_opt( 'social_' . $net );
		if ( $url ) {
			$out[ $net ] = $url;
		}
	}
	return $out;
}

/**
 * Sayfa ID'sinden bağlantı; sayfa yoksa slug ile arar.
 *
 * @param string $opt_key Seçenek anahtarı.
 * @param string $slug    Yedek slug.
 * @return string
 */
function df_page_url( $opt_key, $slug ) {
	$id = absint( df_opt( $opt_key ) );
	if ( $id && get_post_status( $id ) === 'publish' ) {
		return get_permalink( $id );
	}
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
}

/**
 * Renk tonu: hex rengi açar/koyulaştırır.
 *
 * @param string $hex    Renk.
 * @param float  $amount -1..1.
 * @return string
 */
function df_shade( $hex, $amount ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return '#' . $hex;
	}
	$rgb = array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
	foreach ( $rgb as &$c ) {
		$c = $amount < 0 ? $c * ( 1 + $amount ) : $c + ( 255 - $c ) * $amount;
		$c = max( 0, min( 255, (int) round( $c ) ) );
	}
	return sprintf( '#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2] );
}

/**
 * Hex → "r,g,b".
 *
 * @param string $hex Renk.
 * @return string
 */
function df_rgb( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return '250,247,242';
	}
	return hexdec( substr( $hex, 0, 2 ) ) . ',' . hexdec( substr( $hex, 2, 2 ) ) . ',' . hexdec( substr( $hex, 4, 2 ) );
}

/**
 * Google Fonts adresi.
 *
 * @return string
 */
function df_fonts_url() {
	$heading = df_opt( 'font_heading', 'Cormorant Garamond' );
	$body    = df_opt( 'font_body', 'Jost' );
	$families = array();
	$map      = array(
		'Cormorant Garamond' => 'ital,wght@0,400;0,500;0,600;0,700;1,400;1,500',
		'Playfair Display'   => 'ital,wght@0,400;0,500;0,600;0,700;1,400',
		'Bodoni Moda'        => 'ital,opsz,wght@0,6..96,400;0,6..96,500;0,6..96,700;1,6..96,400',
		'Marcellus'          => 'wght@400',
		'Gilda Display'      => 'wght@400',
		'Lora'               => 'ital,wght@0,400;0,500;0,700;1,400',
		'Jost'               => 'wght@300;400;500;600;700;800',
		'Manrope'            => 'wght@300;400;500;600;700;800',
		'DM Sans'            => 'opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700;9..40,800',
		'Inter'              => 'wght@300;400;500;600;700;800',
		'Montserrat'         => 'wght@300;400;500;600;700;800',
		'Nunito Sans'        => 'wght@300;400;600;700;800',
		'Great Vibes'        => 'wght@400',
		'Allura'             => 'wght@400',
		'Parisienne'         => 'wght@400',
		'Pinyon Script'      => 'wght@400',
	);
	$script = df_opt( 'font_script', 'Great Vibes' );
	foreach ( array_unique( array_filter( array( $heading, $body, $script ) ) ) as $font ) {
		if ( isset( $map[ $font ] ) ) {
			$families[] = 'family=' . str_replace( ' ', '+', $font ) . ':' . $map[ $font ];
		}
	}
	if ( ! $families ) {
		return '';
	}
	return 'https://fonts.googleapis.com/css2?' . implode( '&', $families ) . '&display=swap';
}

/**
 * Seçeneklerden CSS değişkenleri.
 *
 * @return string
 */
function df_css_variables() {
	$o     = df_options();
	$vars  = array(
		'--df-bg'           => $o['color_bg'],
		'--df-ivory'        => $o['color_ivory'],
		'--df-ivory-rgb'    => df_rgb( $o['color_ivory'] ),
		'--df-sand'         => $o['color_sand'],
		'--df-text'         => $o['color_text'],
		'--df-text-rgb'     => df_rgb( $o['color_text'] ),
		'--df-muted'        => $o['color_muted'],
		'--df-line'         => $o['color_line'],
		'--df-accent'       => $o['color_accent'],
		'--df-accent-dark'  => $o['color_accent_dark'],
		'--df-accent-soft'  => df_shade( $o['color_accent'], 0.86 ),
		'--df-rose'         => $o['color_rose'],
		'--df-dark'         => $o['color_dark'],
		'--df-sale'         => $o['color_sale'],
		'--df-success'      => $o['color_success'],
		'--df-topbar'       => $o['topbar_bg'],
		'--df-topbar-fg'    => $o['topbar_fg'] ? $o['topbar_fg'] : $o['color_text'],
		'--df-header'       => $o['header_bg'],
		'--df-footer'       => $o['footer_bg'],
		'--df-font-heading' => '"' . $o['font_heading'] . '", "Times New Roman", serif',
		'--df-font-body'    => '"' . $o['font_body'] . '", system-ui, -apple-system, "Segoe UI", sans-serif',
		'--df-font-script'  => '"' . ( $o['font_script'] ? $o['font_script'] : 'Great Vibes' ) . '", "Brush Script MT", cursive',
		'--df-scale'        => (float) $o['font_scale'],
		'--df-container'    => absint( $o['container_width'] ) . 'px',
		'--df-radius-btn'   => absint( $o['btn_radius'] ) . 'px',
		'--df-radius'       => absint( $o['card_radius'] ) . 'px',
		'--df-space'        => absint( $o['section_space'] ) . 'px',
		'--df-logo-w'       => absint( $o['logo_width'] ) . 'px',
		'--df-logo-w-m'     => absint( $o['logo_width_mobile'] ) . 'px',
	);
	$css = ':root{';
	foreach ( $vars as $k => $v ) {
		$css .= $k . ':' . wp_strip_all_tags( (string) $v ) . ';';
	}
	$css .= '}' . df_section_design_css( $o );
	if ( ! empty( $o['custom_css'] ) ) {
		$css .= "\n/* Özel CSS */\n" . str_ireplace( '</style', '', wp_strip_all_tags( (string) $o['custom_css'] ) );
	}
	return $css;
}

/**
 * Bölüm tasarımı (Tasarım Stüdyosu → Bölüm tasarımı) CSS'i.
 *
 * @param array $o Seçenekler.
 * @return string
 */
function df_section_design_css( $o ) {
	$space  = array(
		'none' => 0,
		's'    => 40,
		'm'    => 80,
		'l'    => 140,
		'xl'   => 200,
	);
	$css    = '';
	$mobile = '';
	foreach ( array_keys( df_home_section_labels() ) as $id ) {
		$d = isset( $o[ 'sd_' . $id ] ) && is_array( $o[ 'sd_' . $id ] ) ? $o[ 'sd_' . $id ] : array();
		if ( ! $d ) {
			continue;
		}
		$sel  = '[data-df-sec="' . $id . '"]';
		$rule = '';
		if ( ! empty( $d['bg'] ) ) {
			$rule .= 'background:' . $d['bg'] . ';';
		}
		if ( ! empty( $d['color'] ) ) {
			$rule .= 'color:' . $d['color'] . ';--df-text:' . $d['color'] . ';--df-muted:' . $d['color'] . ';';
		}
		foreach ( array( 'pt' => 'padding-top', 'pb' => 'padding-bottom' ) as $k => $prop ) {
			if ( ! empty( $d[ $k ] ) && isset( $space[ $d[ $k ] ] ) ) {
				$rule   .= $prop . ':' . $space[ $d[ $k ] ] . 'px!important;';
				$mobile .= $sel . '{' . $prop . ':' . round( $space[ $d[ $k ] ] * 0.6 ) . 'px!important}';
			}
		}
		if ( ! empty( $d['align'] ) ) {
			$rule .= 'text-align:' . $d['align'] . ';';
			$flex  = array(
				'left'   => 'flex-start',
				'center' => 'center',
				'right'  => 'flex-end',
			);
			$css  .= $sel . ' .df-head{flex-direction:column;align-items:' . $flex[ $d['align'] ] . ';text-align:' . $d['align'] . '}';
		}
		if ( ! empty( $d['title'] ) ) {
			$css .= $sel . ' h2{font-size:' . (int) $d['title'] . 'px}';
		}
		if ( $rule ) {
			$css .= $sel . '{' . $rule . '}';
		}
		if ( ! empty( $d['hide_m'] ) ) {
			$mobile .= $sel . '{display:none!important}';
		}
		if ( ! empty( $d['hide_d'] ) ) {
			$css .= '@media(min-width:761px){' . $sel . '{display:none!important}}';
		}
	}
	return $css . ( $mobile ? '@media(max-width:760px){' . $mobile . '}' : '' );
}

/**
 * Logo çıktısı.
 *
 * @param string $context header|footer|drawer.
 */
function df_logo( $context = 'header' ) {
	$img_id = absint( 'footer' === $context && df_opt( 'footer_logo' ) ? df_opt( 'footer_logo' ) : df_opt( 'logo_image' ) );
	$tag    = 'div'; // Ana sayfada H1 hero başlığıdır, logo div olarak kalır.
	echo '<' . $tag . ' class="df-logo df-logo--' . esc_attr( $context ) . '">';
	echo '<a href="' . esc_url( home_url( '/' ) ) . '" rel="home" aria-label="' . esc_attr( get_bloginfo( 'name' ) ) . '">';
	if ( $img_id ) {
		echo wp_get_attachment_image(
			$img_id,
			'medium_large',
			false,
			array(
				'class'   => 'df-logo__img',
				'loading' => 'eager',
				'alt'     => get_bloginfo( 'name' ),
			)
		);
	} else {
		if ( df_opt( 'logo_icon' ) ) {
			echo '<span class="df-logo__icon" aria-hidden="true">' . df_icon( 'flower', array( 'size' => 30 ) ) . '</span>'; // phpcs:ignore
		}
		echo '<span class="df-logo__text">' . esc_html( df_opt( 'logo_text', get_bloginfo( 'name' ) ) ) . '</span>';
		if ( df_opt( 'logo_tagline' ) ) {
			echo '<span class="df-logo__tag">' . esc_html( df_opt( 'logo_tagline' ) ) . '</span>';
		}
	}
	echo '</a></' . $tag . '>'; // phpcs:ignore
}

/**
 * WooCommerce aktif mi?
 *
 * @return bool
 */
function df_wc() {
	return class_exists( 'WooCommerce' );
}

/**
 * Dil bağlantıları (üst bant). Polylang / WPML varsa onları kullanır; yoksa
 * Google Çeviri ile sayfanın çevrilmiş halini açar.
 *
 * @return array<int, array{code:string,url:string,current:bool}>
 */
function df_lang_links() {
	if ( ! df_opt( 'lang_on' ) ) {
		return array();
	}
	$out = array();
	if ( function_exists( 'pll_the_languages' ) ) {
		foreach ( (array) pll_the_languages( array( 'raw' => 1, 'hide_if_empty' => 0 ) ) as $l ) {
			$out[] = array(
				'code'    => strtoupper( $l['slug'] ),
				'url'     => $l['url'],
				'current' => ! empty( $l['current_lang'] ),
			);
		}
		return $out;
	}
	$langs = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
	if ( is_array( $langs ) && $langs ) {
		foreach ( $langs as $l ) {
			$out[] = array(
				'code'    => strtoupper( $l['language_code'] ),
				'url'     => $l['url'],
				'current' => ! empty( $l['active'] ),
			);
		}
		return $out;
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$here = home_url( $uri );
	$base = strtolower( (string) df_opt( 'lang_base', 'tr' ) );
	foreach ( array_filter( array_map( 'trim', explode( ',', (string) df_opt( 'lang_codes', 'TR, EN, RU, AR, DE, FR' ) ) ) ) as $code ) {
		$c     = strtolower( preg_replace( '/[^A-Za-z-]/', '', $code ) );
		$out[] = array(
			'code'    => strtoupper( $c ),
			'url'     => $c === $base ? $here : 'https://translate.google.com/translate?sl=' . rawurlencode( $base ) . '&tl=' . rawurlencode( $c ) . '&u=' . rawurlencode( $here ),
			'current' => $c === $base,
		);
	}
	return $out;
}

/**
 * Ürün aramasında ürün kodu (SKU) ile de eşleş.
 *
 * @param string   $search SQL.
 * @param WP_Query $q      Sorgu.
 * @return string
 */
function df_search_by_sku( $search, $q ) {
	global $wpdb;
	if ( is_admin() || ! $q->is_main_query() || ! $q->is_search() || ! $search || ! df_wc() ) {
		return $search;
	}
	$term = trim( (string) $q->get( 's' ) );
	if ( strlen( $term ) < 2 ) {
		return $search;
	}
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_sku' WHERE m.meta_value LIKE %s AND p.post_type IN ('product','product_variation')", '%' . $wpdb->esc_like( $term ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	if ( ! $ids ) {
		return $search;
	}
	$parents = array();
	foreach ( $ids as $id ) {
		$parent    = wp_get_post_parent_id( $id );
		$parents[] = (int) ( $parent ? $parent : $id );
	}
	$in = implode( ',', array_unique( $parents ) );
	return preg_replace( '/^\s*AND\s*\(/', " AND ( {$wpdb->posts}.ID IN ({$in}) OR (", $search, 1 ) . ')';
}
add_filter( 'posts_search', 'df_search_by_sku', 20, 2 );
