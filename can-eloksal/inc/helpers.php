<?php
/**
 * Genel yardımcı fonksiyonlar.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tema ayarını döndürür (varsayılanlarla birleştirilmiş).
 *
 * @param string $key     Anahtar.
 * @param mixed  $default Varsayılan (şemadaki değeri ezmek için).
 * @return mixed
 */
function ce_opt( $key, $default = null ) {
	static $options = null;
	if ( null === $options || ! empty( $GLOBALS['ce_opt_reset'] ) ) {
		$saved                  = get_option( CE_OPTION, array() );
		$options                = wp_parse_args( is_array( $saved ) ? $saved : array(), ce_option_defaults() );
		$GLOBALS['ce_opt_reset'] = false;
	}
	if ( array_key_exists( $key, $options ) && '' !== $options[ $key ] && null !== $options[ $key ] ) {
		return $options[ $key ];
	}
	return null !== $default ? $default : ( $options[ $key ] ?? '' );
}

/**
 * Bölüm ayarı: blok/şablon argümanı verilmişse onu, yoksa Tema Ayarları değerini döndürür.
 *
 * @param array  $args    Argümanlar (get_template_part).
 * @param string $key     Anahtar (Tema Ayarları anahtarıyla aynı).
 * @param mixed  $default Varsayılan.
 * @return mixed
 */
function ce_hopt( $args, $key, $default = null ) {
	if ( is_array( $args ) && array_key_exists( $key, $args ) && '' !== $args[ $key ] && null !== $args[ $key ] && array() !== $args[ $key ] ) {
		return $args[ $key ];
	}
	return ce_opt( $key, $default );
}

/**
 * Aynı bölüm bir sayfada birden fazla kullanıldığında benzersiz id üretir.
 *
 * @param array  $args Argümanlar.
 * @param string $base Temel id.
 * @return string
 */
function ce_uid( $args, $base ) {
	return empty( $args['uid'] ) ? $base : $base . '-' . sanitize_key( $args['uid'] );
}

/**
 * Canlı editör modu mu? (Can Eloksal Builder eklentisi bu filtreyi açar.)
 *
 * @return bool
 */
function ce_live() {
	static $on = null;
	if ( null === $on ) {
		$on = (bool) apply_filters( 'ce_live_frame', false );
	}
	return $on;
}

/**
 * Canlı editör için metin kaynağı işareti (yalnızca editör modunda çıktı verir).
 *
 * @param string $source Kaynak (opt:anahtar, meta:ID:anahtar, post:ID:title…).
 * @param string $format text|nl|pipe|paras|lines.
 * @return string
 */
function ce_ed( $source, $format = 'text' ) {
	if ( ! $source || ! ce_live() ) {
		return '';
	}
	return ' data-ce-edit="' . esc_attr( $source ) . '"' . ( 'text' !== $format ? ' data-ce-format="' . esc_attr( $format ) . '"' : '' );
}

/**
 * Canlı editör için görsel kaynağı işareti.
 *
 * @param string $source Kaynak.
 * @return string
 */
function ce_edimg( $source ) {
	return $source && ce_live() ? ' data-ce-img="' . esc_attr( $source ) . '"' : '';
}

/**
 * Canlı editör için bağlantı kaynağı işareti.
 *
 * @param string $source Kaynak.
 * @return string
 */
function ce_edlink( $source ) {
	return $source && ce_live() ? ' data-ce-link="' . esc_attr( $source ) . '"' : '';
}

/**
 * Bölüm alanının kaynağı: blok içinden çizildiyse blok özniteliği, değilse Tema Ayarları.
 *
 * @param array  $args Argümanlar.
 * @param string $key  Anahtar.
 * @return string
 */
function ce_src( $args, $key ) {
	if ( is_array( $args ) && ! empty( $args['__src'] ) ) {
		return 'none' === $args['__src'] ? '' : $args['__src'] . ':' . $key;
	}
	return 'opt:' . $key;
}

/**
 * Tema meta alanı (_ce_ önekli).
 *
 * @param int    $post_id Yazı ID.
 * @param string $key     Anahtar.
 * @param mixed  $default Varsayılan.
 * @return mixed
 */
function ce_meta( $post_id, $key, $default = '' ) {
	$value = get_post_meta( $post_id, '_ce_' . $key, true );
	if ( '' === $value || null === $value || ( is_array( $value ) && ! $value ) ) {
		return $default;
	}
	return $value;
}

/**
 * Çok satırlı metni diziye çevirir.
 *
 * @param string|array $text Metin.
 * @return string[]
 */
function ce_lines( $text ) {
	if ( is_array( $text ) ) {
		return array_values( array_filter( array_map( 'trim', $text ) ) );
	}
	$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
	return array_values( array_filter( array_map( 'trim', $lines ), 'strlen' ) );
}

/**
 * Satır sonlarını <br> yapar (güvenli).
 *
 * @param string $text Metin.
 * @return string
 */
function ce_nl2br( $text ) {
	return implode( '<br>', array_map( 'esc_html', ce_lines( $text ) ) );
}

/**
 * Boş satırlarla ayrılmış metni paragraflara çevirir (güvenli).
 *
 * @param string $text Metin.
 * @return string
 */
function ce_paragraphs( $text ) {
	$parts = preg_split( '/\n\s*\n/', str_replace( "\r", '', (string) $text ) );
	$out   = '';
	foreach ( $parts as $p ) {
		$p = trim( $p );
		if ( '' !== $p ) {
			$out .= '<p>' . nl2br( esc_html( $p ) ) . '</p>';
		}
	}
	return $out;
}

/**
 * Göreli veya tam URL'yi site adresine göre çözer.
 *
 * @param string $url URL.
 * @return string
 */
function ce_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
		return home_url( $url );
	}
	if ( 0 === strpos( $url, '#' ) || preg_match( '#^(https?:|mailto:|tel:)#i', $url ) ) {
		return $url;
	}
	return home_url( '/' . ltrim( $url, '/' ) );
}

/**
 * Telefon numarasını tel: bağlantısına çevirir.
 *
 * @param string $phone Telefon.
 * @return string
 */
function ce_tel( $phone = null ) {
	$phone = null === $phone ? ce_opt( 'phone' ) : $phone;
	return 'tel:' . preg_replace( '/[^0-9+]/', '', (string) $phone );
}

/**
 * WhatsApp bağlantısı.
 *
 * @param string $message Hazır mesaj.
 * @return string
 */
function ce_whatsapp_url( $message = null ) {
	$number = preg_replace( '/\D/', '', (string) ce_opt( 'whatsapp' ) );
	if ( '' === $number ) {
		return '';
	}
	$message = null === $message ? ce_opt( 'whatsapp_message' ) : $message;
	return 'https://wa.me/' . $number . ( $message ? '?text=' . rawurlencode( $message ) : '' );
}

/**
 * Görsel HTML (lazy, boyut, srcset WordPress tarafından).
 *
 * @param int    $id    Ek ID.
 * @param string $size  Boyut.
 * @param array  $attr  Nitelikler.
 * @return string
 */
function ce_img( $id, $size = 'large', $attr = array() ) {
	$id = absint( $id );
	if ( ! $id || ! wp_attachment_is_image( $id ) ) {
		return '';
	}
	$attr = wp_parse_args( $attr, array( 'loading' => 'lazy', 'decoding' => 'async' ) );
	if ( isset( $attr['loading'] ) && 'eager' === $attr['loading'] ) {
		$attr['fetchpriority'] = 'high';
	}
	if ( empty( $attr['alt'] ) ) {
		$alt = get_post_meta( $id, '_wp_attachment_image_alt', true );
		if ( '' === $alt && ! empty( $attr['fallback_alt'] ) ) {
			$attr['alt'] = $attr['fallback_alt'];
		}
	}
	unset( $attr['fallback_alt'] );
	return wp_get_attachment_image( $id, $size, false, $attr );
}

/**
 * Görsel yoksa kullanılan metal yüzey dokusu (sahte fotoğraf üretmez, markaya uygun zemin).
 *
 * @param string $tone  Ton.
 * @param string $label Erişilebilir etiket.
 * @param string $class Ek sınıf.
 * @return string
 */
function ce_material( $tone = 'steel', $label = '', $class = '' ) {
	$tone = array_key_exists( $tone, ce_tone_choices() ) ? $tone : 'steel';
	$hint = '';
	if ( current_user_can( 'edit_posts' ) ) {
		$hint = '<span class="ce-material__hint">' . ce_icon( 'image', 14 ) . ' Görsel ekleyin</span>';
	}
	return sprintf(
		'<span class="ce-material ce-material--%1$s %2$s" %3$s><span class="ce-material__sheen"></span>%4$s</span>',
		esc_attr( $tone ),
		esc_attr( $class ),
		$label ? 'role="img" aria-label="' . esc_attr( $label ) . '"' : 'aria-hidden="true"',
		$hint
	);
}

/**
 * Yazının görseli ya da metal doku.
 *
 * @param int    $post_id Yazı.
 * @param string $size    Boyut.
 * @param string $tone    Ton.
 * @param array  $attr    Nitelikler.
 * @return string
 */
function ce_post_visual( $post_id, $size = 'ce-card', $tone = 'steel', $attr = array() ) {
	$thumb = get_post_thumbnail_id( $post_id );
	if ( $thumb ) {
		$attr['fallback_alt'] = get_the_title( $post_id );
		$html                 = ce_img( $thumb, $size, $attr );
		if ( $html ) {
			return $html;
		}
	}
	return ce_material( $tone, get_the_title( $post_id ) );
}

/**
 * Hizmetin tonu.
 *
 * @param int $post_id Hizmet.
 * @return string
 */
function ce_service_tone( $post_id ) {
	return (string) ce_meta( $post_id, 'tone', 'steel' );
}

/**
 * Blog yazısının tonu (yazıdaki ilk kategoriye göre deterministik).
 *
 * @param int $post_id Yazı.
 * @return string
 */
function ce_post_tone( $post_id ) {
	$tones = array( 'natural', 'blue', 'graphite', 'champagne', 'steel', 'black' );
	return $tones[ absint( $post_id ) % count( $tones ) ];
}

/**
 * Sayfa şablonuna göre ilk yayınlanmış sayfanın URL'si.
 *
 * @param string $template Şablon dosyası (page-templates/xxx.php).
 * @param string $fallback Yedek yol.
 * @return string
 */
function ce_template_url( $template, $fallback = '/' ) {
	$page_id = ce_template_page_id( $template );
	return $page_id ? get_permalink( $page_id ) : home_url( $fallback );
}

/**
 * Sayfa şablonuna göre sayfa ID'si (önbellekli).
 *
 * @param string $template Şablon.
 * @return int
 */
function ce_template_page_id( $template ) {
	static $cache = array();
	if ( isset( $cache[ $template ] ) ) {
		return $cache[ $template ];
	}
	$pages              = get_posts(
		array(
			'post_type'              => 'page',
			'post_status'            => 'publish',
			'numberposts'            => 1,
			'meta_key'               => '_wp_page_template',
			'meta_value'             => 'page-templates/' . $template . '.php',
			'fields'                 => 'ids',
			'update_post_term_cache' => false,
		)
	);
	$cache[ $template ] = $pages ? (int) $pages[0] : 0;
	return $cache[ $template ];
}

/**
 * Teklif sayfası URL'si.
 *
 * @return string
 */
function ce_quote_url() {
	return ce_template_url( 'quote', '/teklif-al/' );
}

/**
 * Hizmetler arşiv URL'si.
 *
 * @return string
 */
function ce_services_url() {
	$link = get_post_type_archive_link( 'ce_service' );
	return $link ? $link : home_url( '/hizmetler/' );
}

/**
 * Bölüm başlığı bileşeni.
 *
 * @param array $args eyebrow, title, text, link, link_label, align, tag, dark.
 */
function ce_section_head( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'eyebrow'    => '',
			'title'      => '',
			'text'       => '',
			'link'       => '',
			'link_label' => '',
			'align'      => 'split',
			'tag'        => 'h2',
			'id'         => '',
			'src'        => array(),
		)
	);
	$src = (array) $args['src'];
	$tag = in_array( $args['tag'], array( 'h1', 'h2', 'h3' ), true ) ? $args['tag'] : 'h2';
	echo '<header class="ce-head ce-head--' . esc_attr( $args['align'] ) . '" data-reveal>';
	echo '<div class="ce-head__main">';
	if ( $args['eyebrow'] ) {
		echo '<p class="ce-eyebrow"' . ce_ed( $src['eyebrow'] ?? '' ) . '>' . esc_html( $args['eyebrow'] ) . '</p>'; // phpcs:ignore
	}
	if ( $args['title'] ) {
		printf( '<%1$s class="ce-head__title"%3$s%4$s>%2$s</%1$s>', esc_html( $tag ), ce_nl2br( $args['title'] ), $args['id'] ? ' id="' . esc_attr( $args['id'] ) . '"' : '', ce_ed( $src['title'] ?? '', 'nl' ) ); // phpcs:ignore
	}
	echo '</div>';
	if ( $args['text'] || $args['link'] ) {
		echo '<div class="ce-head__side">';
		if ( $args['text'] ) {
			echo '<p class="ce-head__text"' . ce_ed( $src['text'] ?? '' ) . '>' . esc_html( $args['text'] ) . '</p>'; // phpcs:ignore
		}
		if ( $args['link'] ) {
			echo '<a class="ce-link-arrow" href="' . esc_url( $args['link'] ) . '">' . esc_html( $args['link_label'] ) . ce_icon( 'arrow-right', 18 ) . '</a>';
		}
		echo '</div>';
	}
	echo '</header>';
}

/**
 * Buton bileşeni.
 *
 * @param string $label   Metin.
 * @param string $url     Bağlantı.
 * @param string $variant primary|ghost|light|dark.
 * @param string $icon    İkon.
 * @return string
 */
function ce_button( $label, $url, $variant = 'primary', $icon = 'arrow-right', $label_src = '', $link_src = '' ) {
	if ( '' === trim( (string) $label ) || '' === trim( (string) $url ) ) {
		return '';
	}
	return sprintf(
		'<a class="ce-btn ce-btn--%3$s" href="%1$s"%6$s><span%5$s>%2$s</span>%4$s</a>',
		esc_url( ce_url( $url ) ),
		esc_html( $label ),
		esc_attr( $variant ),
		$icon ? ce_icon( $icon, 18, 'ce-btn__icon' ) : '',
		ce_ed( $label_src ),
		ce_edlink( $link_src )
	);
}

/**
 * Sosyal medya bağlantıları.
 *
 * @return array<string,string> ikon => url
 */
function ce_socials() {
	$out = array();
	foreach ( array( 'instagram', 'linkedin', 'facebook', 'youtube' ) as $key ) {
		$url = trim( (string) ce_opt( $key ) );
		if ( $url ) {
			$out[ $key ] = $url;
		}
	}
	return $out;
}

/**
 * Google Maps iframe'ini güvenli biçimde döndürür.
 *
 * @return string
 */
function ce_map_embed() {
	$code = (string) ce_opt( 'maps_embed' );
	if ( ! preg_match( '/src=["\']([^"\']+)["\']/i', $code, $m ) ) {
		return '';
	}
	$src = html_entity_decode( $m[1] );
	if ( ! preg_match( '#^https://(www\.)?google\.[a-z.]+/maps/embed#i', $src ) ) {
		return '';
	}
	return sprintf(
		'<iframe src="%s" title="%s" width="600" height="450" style="border:0" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>',
		esc_url( $src ),
		esc_attr( ce_opt( 'company_name' ) . ' konum haritası' )
	);
}

/**
 * Logo çıktısı.
 *
 * @param string $context header|footer|drawer.
 */
function ce_logo( $context = 'header' ) {
	$name   = ce_opt( 'site_name', get_bloginfo( 'name' ) );
	$light  = absint( ce_opt( 'logo_light' ) );
	$dark   = absint( ce_opt( 'logo_dark' ) );
	$mobile = absint( ce_opt( 'logo_mobile' ) );
	$height = absint( ce_opt( 'logo_height', 40 ) );
	$attr   = array(
		'loading'       => 'eager',
		'alt'           => $name,
		'style'         => 'height:' . $height . 'px;width:auto',
		'fetchpriority' => 'low',
	);

	echo '<a class="ce-logo ce-logo--' . esc_attr( $context ) . '" href="' . esc_url( home_url( '/' ) ) . '" rel="home" aria-label="' . esc_attr( $name . ' — Ana Sayfa' ) . '">';
	if ( $light || $dark ) {
		$light = $light ? $light : $dark;
		$dark  = $dark ? $dark : $light;
		if ( 'header' === $context ) {
			echo wp_get_attachment_image( $light, 'medium', false, $attr + array( 'class' => 'ce-logo__img ce-logo__img--light' . ( $mobile ? ' ce-hide-sm' : '' ) ) ); // phpcs:ignore
			echo wp_get_attachment_image( $dark, 'medium', false, $attr + array( 'class' => 'ce-logo__img ce-logo__img--dark' . ( $mobile ? ' ce-hide-sm' : '' ) ) ); // phpcs:ignore
			if ( $mobile ) {
				echo wp_get_attachment_image( $mobile, 'medium', false, $attr + array( 'class' => 'ce-logo__img ce-logo__img--mobile' ) ); // phpcs:ignore
			}
		} elseif ( 'drawer' === $context ) {
			echo wp_get_attachment_image( $dark, 'medium', false, $attr + array( 'class' => 'ce-logo__img' ) ); // phpcs:ignore
		} else {
			echo wp_get_attachment_image( $light, 'medium', false, $attr + array( 'class' => 'ce-logo__img', 'loading' => 'lazy' ) ); // phpcs:ignore
		}
	} else {
		$parts = preg_split( '/\s+/', trim( $name ), 2 );
		echo '<span class="ce-logo__mark" aria-hidden="true"><svg viewBox="0 0 40 40" width="38" height="38"><rect x="1" y="1" width="38" height="38" rx="10" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".35"/><path d="M27 13.5a9 9 0 1 0 0 13" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><circle cx="27.5" cy="20" r="2.4" fill="#00AFC1"/></svg></span>';
		echo '<span class="ce-logo__text"><strong>' . esc_html( mb_strtoupper( $parts[0] ) ) . '</strong>';
		if ( isset( $parts[1] ) ) {
			echo ' <span>' . esc_html( mb_strtoupper( $parts[1] ) ) . '</span>';
		}
		$tagline = ce_opt( 'logo_tagline' );
		if ( $tagline ) {
			echo '<small>' . esc_html( $tagline ) . '</small>';
		}
		echo '</span>';
	}
	echo '</a>';
}

/**
 * İstemci IP adresi (yalnızca REMOTE_ADDR — sahte başlıklara güvenilmez).
 *
 * @return string
 */
function ce_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	/**
	 * Proxy/CDN arkasında gerçek IP için filtre (ör. Cloudflare CF-Connecting-IP).
	 */
	$ip = apply_filters( 'ce_client_ip', $ip );
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Tarih biçimi (Türkçe).
 *
 * @param int|string $post Yazı.
 * @return string
 */
function ce_date( $post = null ) {
	return get_the_date( 'j F Y', $post );
}

/**
 * Okuma süresi (dk).
 *
 * @param int $post_id Yazı.
 * @return int
 */
function ce_reading_time( $post_id ) {
	$words = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) );
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Bir sayfanın hero alt başlığı (meta) veya özeti.
 *
 * @param int $post_id Yazı.
 * @return string
 */
function ce_page_subtitle( $post_id ) {
	$sub = ce_meta( $post_id, 'hero_subtitle' );
	if ( $sub ) {
		return $sub;
	}
	return has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : '';
}

/**
 * Sayfa hero başlığı (meta veya sayfa başlığı).
 *
 * @param int $post_id Yazı.
 * @return string
 */
function ce_page_title( $post_id ) {
	$title = ce_meta( $post_id, 'hero_title' );
	return $title ? $title : get_the_title( $post_id );
}

/**
 * Sayfa hero'sunun canlı editör kaynakları.
 *
 * @param int $post_id Sayfa.
 * @return array
 */
function ce_page_hero_src( $post_id ) {
	return array(
		'title'    => 'meta:' . $post_id . ':hero_title',
		'subtitle' => 'meta:' . $post_id . ':hero_subtitle',
		'eyebrow'  => 'meta:' . $post_id . ':hero_eyebrow',
		'image'    => 'meta:' . $post_id . ':hero_image',
	);
}

/**
 * Yasal sayfa bağlantısı.
 *
 * @param string $key   kvkk_page|privacy_page|cookie_page.
 * @param string $label Metin.
 * @return string
 */
function ce_legal_link( $key, $label ) {
	$id = absint( ce_opt( $key ) );
	if ( ! $id || 'publish' !== get_post_status( $id ) ) {
		return esc_html( $label );
	}
	return '<a href="' . esc_url( get_permalink( $id ) ) . '" target="_blank" rel="noopener">' . esc_html( $label ) . '</a>';
}
