<?php
/**
 * Görünüm → Özelleştir → "Can Eloksal Tasarım": site geneli renkler, fontlar, yazı ölçeği,
 * köşe yuvarlaklığı, içerik genişliği, buton stili ve header davranışı (canlı önizlemeli).
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tasarım ayarları: anahtar => [etiket, varsayılan, tür].
 *
 * @return array
 */
function ce_design_settings() {
	return array(
		'ce_color_accent'   => array( 'Vurgu rengi (turkuaz)', '#00AFC1', 'color' ),
		'ce_color_accent2'  => array( 'İkincil vurgu', '#19C4D2', 'color' ),
		'ce_color_primary'  => array( 'Ana koyu renk', '#101820', 'color' ),
		'ce_color_dark'     => array( 'En koyu zemin', '#0A1118', 'color' ),
		'ce_color_bg_alt'   => array( 'Açık bölüm zemini', '#F4F7F8', 'color' ),
		'ce_color_text'     => array( 'Metin rengi', '#17212B', 'color' ),
		'ce_color_metal'    => array( 'Metalik gri', '#87939D', 'color' ),
	);
}

/**
 * Font seçenekleri.
 *
 * @return array<string,array{0:string,1:string}>
 */
function ce_font_choices() {
	return array(
		'manrope' => array( 'Manrope', "'Manrope', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif" ),
		'inter'   => array( 'Inter', "'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif" ),
		'system'  => array( 'Sistem fontu', "system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif" ),
		'serif'   => array( 'Serif (Georgia)', "Georgia, 'Times New Roman', serif" ),
	);
}

add_action( 'customize_register', 'ce_customize_register' );
/**
 * Özelleştirici kayıtları.
 *
 * @param WP_Customize_Manager $wp Yönetici.
 */
function ce_customize_register( $wp ) {
	$wp->add_panel( 'ce_design', array( 'title' => 'Can Eloksal Tasarım', 'priority' => 20, 'capability' => 'edit_theme_options' ) );

	// Renkler.
	$wp->add_section( 'ce_colors', array( 'title' => 'Renkler', 'panel' => 'ce_design' ) );
	foreach ( ce_design_settings() as $key => $cfg ) {
		$wp->add_setting( $key, array( 'default' => $cfg[1], 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'postMessage' ) );
		$wp->add_control( new WP_Customize_Color_Control( $wp, $key, array( 'label' => $cfg[0], 'section' => 'ce_colors' ) ) );
	}

	// Tipografi.
	$wp->add_section( 'ce_typo', array( 'title' => 'Tipografi', 'panel' => 'ce_design' ) );
	$fonts = wp_list_pluck( ce_font_choices(), 0 );
	$wp->add_setting( 'ce_font_head', array( 'default' => 'manrope', 'sanitize_callback' => 'ce_sanitize_font', 'transport' => 'postMessage' ) );
	$wp->add_control( 'ce_font_head', array( 'label' => 'Başlık fontu', 'section' => 'ce_typo', 'type' => 'select', 'choices' => $fonts ) );
	$wp->add_setting( 'ce_font_body', array( 'default' => 'inter', 'sanitize_callback' => 'ce_sanitize_font', 'transport' => 'postMessage' ) );
	$wp->add_control( 'ce_font_body', array( 'label' => 'Metin fontu', 'section' => 'ce_typo', 'type' => 'select', 'choices' => $fonts ) );
	$wp->add_setting( 'ce_font_scale', array( 'default' => 100, 'sanitize_callback' => 'absint', 'transport' => 'postMessage' ) );
	$wp->add_control( 'ce_font_scale', array( 'label' => 'Yazı ölçeği (%)', 'section' => 'ce_typo', 'type' => 'range', 'input_attrs' => array( 'min' => 85, 'max' => 120, 'step' => 1 ) ) );

	// Düzen.
	$wp->add_section( 'ce_layout', array( 'title' => 'Düzen & Butonlar', 'panel' => 'ce_design' ) );
	$wp->add_setting( 'ce_container', array( 'default' => 1320, 'sanitize_callback' => 'absint', 'transport' => 'postMessage' ) );
	$wp->add_control( 'ce_container', array( 'label' => 'İçerik genişliği (px)', 'section' => 'ce_layout', 'type' => 'range', 'input_attrs' => array( 'min' => 1080, 'max' => 1680, 'step' => 20 ) ) );
	$wp->add_setting( 'ce_radius', array( 'default' => 18, 'sanitize_callback' => 'absint', 'transport' => 'postMessage' ) );
	$wp->add_control( 'ce_radius', array( 'label' => 'Kart köşe yuvarlaklığı (px)', 'section' => 'ce_layout', 'type' => 'range', 'input_attrs' => array( 'min' => 0, 'max' => 36, 'step' => 1 ) ) );
	$wp->add_setting( 'ce_btn_shape', array( 'default' => 'pill', 'sanitize_callback' => 'ce_sanitize_btn_shape', 'transport' => 'postMessage' ) );
	$wp->add_control( 'ce_btn_shape', array( 'label' => 'Buton şekli', 'section' => 'ce_layout', 'type' => 'radio', 'choices' => array( 'pill' => 'Hap (tam yuvarlak)', 'rounded' => 'Yuvarlatılmış', 'square' => 'Köşeli' ) ) );
	$wp->add_setting( 'ce_section_space', array( 'default' => 100, 'sanitize_callback' => 'absint', 'transport' => 'postMessage' ) );
	$wp->add_control( 'ce_section_space', array( 'label' => 'Bölüm boşlukları (%)', 'section' => 'ce_layout', 'type' => 'range', 'input_attrs' => array( 'min' => 60, 'max' => 140, 'step' => 5 ) ) );
	$wp->add_setting( 'ce_header_hide', array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
	$wp->add_control( 'ce_header_hide', array( 'label' => 'Aşağı kaydırırken header\'ı gizle', 'section' => 'ce_layout', 'type' => 'checkbox' ) );
	$wp->add_setting( 'ce_reveal', array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
	$wp->add_control( 'ce_reveal', array( 'label' => 'Kaydırma animasyonları', 'section' => 'ce_layout', 'type' => 'checkbox' ) );

	// Hızlı bağlantılar: tema paneli.
	$wp->add_section( 'ce_links', array( 'title' => 'İçerik ve metinler', 'panel' => 'ce_design', 'description' => 'Metinler, iletişim bilgileri, ana sayfa bölümleri ve SEO için <a href="' . esc_url( admin_url( 'admin.php?page=ce-settings' ) ) . '" target="_blank">Can Eloksal → Tema Ayarları</a> sayfasını kullanın. Sayfaları bloklarla düzenlemek için Can Eloksal Builder eklentisini etkinleştirin.' ) );
	$wp->add_setting( 'ce_links_dummy', array( 'sanitize_callback' => '__return_empty_string' ) );
	$wp->add_control( 'ce_links_dummy', array( 'section' => 'ce_links', 'type' => 'hidden' ) );
}

/**
 * Font doğrulama.
 *
 * @param string $value Değer.
 * @return string
 */
function ce_sanitize_font( $value ) {
	return array_key_exists( $value, ce_font_choices() ) ? $value : 'manrope';
}

/**
 * Buton şekli doğrulama.
 *
 * @param string $value Değer.
 * @return string
 */
function ce_sanitize_btn_shape( $value ) {
	return in_array( $value, array( 'pill', 'rounded', 'square' ), true ) ? $value : 'pill';
}

/**
 * Hex rengi koyulaştırır.
 *
 * @param string $hex    Renk.
 * @param float  $amount 0..1.
 * @return string
 */
function ce_shade( $hex, $amount ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return '#007C89';
	}
	$out = '#';
	foreach ( str_split( $hex, 2 ) as $c ) {
		$out .= str_pad( dechex( (int) round( hexdec( $c ) * ( 1 - $amount ) ) ), 2, '0', STR_PAD_LEFT );
	}
	return $out;
}

/**
 * Özelleştirici değerlerinden CSS değişkenleri.
 *
 * @return string
 */
function ce_design_css() {
	$vars = array();
	$map  = array(
		'ce_color_accent'  => '--c-accent',
		'ce_color_accent2' => '--c-accent-2',
		'ce_color_primary' => '--c-primary',
		'ce_color_dark'    => '--c-dark',
		'ce_color_bg_alt'  => '--c-bg-alt',
		'ce_color_text'    => '--c-text',
		'ce_color_metal'   => '--c-metal',
	);
	foreach ( ce_design_settings() as $key => $cfg ) {
		$val = get_theme_mod( $key, $cfg[1] );
		if ( $val && strtolower( $val ) !== strtolower( $cfg[1] ) ) {
			$vars[] = $map[ $key ] . ':' . $val;
			if ( 'ce_color_accent' === $key ) {
				$vars[] = '--c-accent-ink:' . ce_shade( $val, .3 );
			}
		}
	}
	$fonts = ce_font_choices();
	$head  = get_theme_mod( 'ce_font_head', 'manrope' );
	$body  = get_theme_mod( 'ce_font_body', 'inter' );
	if ( 'manrope' !== $head && isset( $fonts[ $head ] ) ) {
		$vars[] = '--f-head:' . $fonts[ $head ][1];
	}
	if ( 'inter' !== $body && isset( $fonts[ $body ] ) ) {
		$vars[] = '--f-body:' . $fonts[ $body ][1];
	}
	$container = absint( get_theme_mod( 'ce_container', 1320 ) );
	if ( 1320 !== $container ) {
		$vars[] = '--container:' . $container . 'px';
	}
	$radius = absint( get_theme_mod( 'ce_radius', 18 ) );
	if ( 18 !== $radius ) {
		$vars[] = '--radius:' . $radius . 'px;--radius-lg:' . round( $radius * 1.55 ) . 'px;--radius-sm:' . round( $radius * .55 ) . 'px';
	}
	$space = absint( get_theme_mod( 'ce_section_space', 100 ) );
	if ( 100 !== $space ) {
		$f      = $space / 100;
		$vars[] = '--section:clamp(' . round( 72 * $f ) . 'px,' . round( 9 * $f, 2 ) . 'vw,' . round( 136 * $f ) . 'px)';
	}
	$css = $vars ? ':root{' . implode( ';', $vars ) . '}' : '';

	$scale = absint( get_theme_mod( 'ce_font_scale', 100 ) );
	if ( 100 !== $scale ) {
		$css .= 'html{font-size:' . $scale . '%}';
	}
	$shape = get_theme_mod( 'ce_btn_shape', 'pill' );
	if ( 'rounded' === $shape ) {
		$css .= '.ce-btn,.ce-filter__btn,.ce-searchform__input,.ce-searchform__btn{border-radius:12px}';
	} elseif ( 'square' === $shape ) {
		$css .= '.ce-btn,.ce-filter__btn,.ce-searchform__input,.ce-searchform__btn{border-radius:2px}';
	}
	if ( ! get_theme_mod( 'ce_reveal', true ) ) {
		$css .= '.ce-js [data-reveal]{opacity:1!important;transform:none!important}';
	}
	return $css;
}

add_action( 'wp_enqueue_scripts', 'ce_design_inline_css', 20 );
/**
 * CSS'i ana stil dosyasının ardından ekler.
 */
function ce_design_inline_css() {
	$css = ce_design_css();
	if ( $css ) {
		wp_add_inline_style( 'ce-main', $css );
	}
}

add_action( 'customize_preview_init', 'ce_customize_preview_js' );
/**
 * Canlı önizleme betiği.
 */
function ce_customize_preview_js() {
	wp_enqueue_script( 'ce-customize-preview', CE_URI . '/assets/js/customize-preview.js', array( 'customize-preview' ), CE_VERSION, true );
	wp_localize_script( 'ce-customize-preview', 'CEFonts', wp_list_pluck( ce_font_choices(), 1 ) );
}
