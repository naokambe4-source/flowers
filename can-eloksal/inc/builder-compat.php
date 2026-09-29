<?php
/**
 * Sayfa oluşturucu uyumluluğu: Can Eloksal Builder blokları ve Elementor.
 *
 * - İçeriği Builder bloklarıyla (ce/…) veya Elementor ile hazırlanan sayfalar tam genişlikte,
 *   tema hero'su ve dar metin kutusu olmadan çizilir.
 * - Ana sayfa içeriği bloklarla/Elementor ile hazırlanmışsa tema bölümleri yerine o içerik gösterilir.
 * - Elementor Pro Theme Builder konumları (header, footer, single, archive) desteklenir.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Yazı Elementor ile mi düzenlendi?
 *
 * @param int $post_id Yazı.
 * @return bool
 */
function ce_is_elementor( $post_id ) {
	return $post_id && did_action( 'elementor/loaded' ) && 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true );
}

/**
 * İçerik sayfa oluşturucuyla mı hazırlandı? (Builder bloğu veya Elementor)
 *
 * @param int $post_id Yazı.
 * @return bool
 */
function ce_is_builder_content( $post_id ) {
	if ( ! $post_id ) {
		return false;
	}
	if ( ce_is_elementor( $post_id ) ) {
		return true;
	}
	return false !== strpos( (string) get_post_field( 'post_content', $post_id ), '<!-- wp:ce/' );
}

/**
 * İçerik koyu bir hero bloğu ile mi başlıyor? (şeffaf header için)
 *
 * @param int $post_id Yazı.
 * @return bool
 */
function ce_builder_starts_with_hero( $post_id ) {
	if ( ce_is_elementor( $post_id ) ) {
		return (bool) apply_filters( 'ce_elementor_transparent_header', false, $post_id );
	}
	$content = ltrim( (string) get_post_field( 'post_content', $post_id ) );
	return (bool) preg_match( '#^<!-- wp:ce/(page-hero|slider|cta)\b#', $content );
}

/**
 * Boş tuval / builder görünümü: tam genişlik içerik.
 */
function ce_render_canvas() {
	while ( have_posts() ) {
		the_post();
		echo '<div class="ce-canvas">';
		the_content();
		echo '</div>';
	}
}

add_filter( 'body_class', 'ce_builder_body_class' );
/**
 * Header'ın açık zeminde okunabilmesi için sınıf.
 *
 * @param array $classes Sınıflar.
 * @return array
 */
function ce_builder_body_class( $classes ) {
	$id = is_singular() ? get_queried_object_id() : ( is_front_page() ? (int) get_option( 'page_on_front' ) : 0 );
	$is_canvas = $id && ( ce_is_builder_content( $id ) || 'page-templates/canvas.php' === get_page_template_slug( $id ) );
	if ( $is_canvas ) {
		$classes[] = 'ce-builder-page';
		if ( ! ce_builder_starts_with_hero( $id ) ) {
			$classes[] = 'ce-solid-header';
		}
	}
	if ( get_theme_mod( 'ce_header_hide', true ) ) {
		$classes[] = 'ce-header-autohide';
	}
	return $classes;
}

add_action( 'elementor/theme/register_locations', 'ce_elementor_locations' );
/**
 * Elementor Pro Theme Builder konumları.
 *
 * @param \ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $manager Yönetici.
 */
function ce_elementor_locations( $manager ) {
	$manager->register_all_core_location();
}

/**
 * Elementor konumu çizildiyse true.
 *
 * @param string $location header|footer|single|archive.
 * @return bool
 */
function ce_elementor_location( $location ) {
	return function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( $location );
}

add_action( 'after_setup_theme', 'ce_builder_setup', 20 );
/**
 * Editörde ön yüz stilleri (blok önizlemeleri birebir görünsün diye; WordPress seçicileri editöre kapsüller).
 */
function ce_builder_setup() {
	add_editor_style( array( 'assets/css/main.css', 'assets/css/editor.css' ) );
	// Elementor: tema stili ve genişlikleri kullanılsın.
	add_theme_support( 'elementor' );
}

add_action( 'elementor/editor/after_enqueue_styles', 'ce_elementor_editor_fonts' );
add_action( 'elementor/frontend/after_enqueue_styles', 'ce_elementor_editor_fonts' );
/**
 * Elementor içinde tema fontları.
 */
function ce_elementor_editor_fonts() {
	wp_register_style( 'ce-el-fonts', false, array(), CE_VERSION );
	wp_enqueue_style( 'ce-el-fonts' );
	wp_add_inline_style( 'ce-el-fonts', ce_font_face_css() );
}

/**
 * Ana sayfa bölümünün düzenleme kısayolu (yalnızca yetkili ve giriş yapmış kullanıcıya).
 *
 * @param string $key Bölüm anahtarı.
 */
function ce_section_edit_link( $key ) {
	if ( ! is_user_logged_in() || is_customize_preview() || ce_live() ) {
		return;
	}
	$map = array(
		'hero'       => array( 'edit.php?post_type=ce_slide', 'edit_posts', 'Slaytları düzenle' ),
		'sectors'    => array( 'edit.php?post_type=ce_sector', 'edit_posts', 'Sektörleri düzenle' ),
		'gallery'    => array( 'edit.php?post_type=ce_gallery', 'edit_posts', 'Galeriyi düzenle' ),
		'references' => array( 'edit.php?post_type=ce_reference', 'edit_posts', 'Referansları düzenle' ),
		'blog'       => array( 'edit.php', 'edit_posts', 'Yazıları düzenle' ),
	);
	$link = $map[ $key ] ?? array( 'admin.php?page=ce-settings&tab=home', 'ce_manage_settings', 'Bölümü düzenle' );
	if ( ! current_user_can( $link[1] ) ) {
		return;
	}
	printf(
		'<a class="ce-edit-link" href="%1$s">%2$s<span>%3$s</span></a>',
		esc_url( admin_url( $link[0] ) ),
		ce_icon( 'settings', 14 ), // phpcs:ignore
		esc_html( $link[2] )
	);
}
