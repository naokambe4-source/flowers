<?php
/**
 * Canlı Editör: sayfayı ön yüzde açıp öğelere tıklayarak metin, görsel, bağlantı, stil
 * (renk, boşluk, yazı, kenarlık, gölge…) ve bölüm sırası düzenleme.
 *
 * Mimari:
 * - Kabuk (shell): admin.php?page=ceb-live — sol panel + cihaz seçici + sayfayı gösteren iframe.
 * - Çerçeve (frame): sayfanın kendisi ?ceb_frame=1 ile, yalnızca düzenleme yetkisi olan
 *   kullanıcıya; tema bu modda metinlerin kaynağını data-ce-* nitelikleriyle işaretler.
 * - Kayıt: metinler gerçek kaynağına (Tema Ayarları, sayfa/hizmet alanları, bloklar) yazılır;
 *   sayfa HTML'i dondurulmaz, dinamik içerik (formlar, listeler) çalışmaya devam eder.
 * - Stiller: "bu sayfa" veya "tüm site" kapsamında, masaüstü/tablet/mobil ayrı; ön yüzde
 *   yalnızca ilgili sayfanın ve genel kuralların CSS'i basılır.
 *
 * @package CanEloksalBuilder
 */

defined( 'ABSPATH' ) || exit;

require __DIR__ . '/save.php';

const CEB_LIVE_OPTION = 'ceb_live_styles';

/**
 * Çerçeve (editör önizleme) modunda mıyız?
 *
 * @return bool
 */
function ceb_is_frame() {
	static $on = null;
	if ( null === $on ) {
		$on = ! is_admin() && isset( $_GET['ceb_frame'] ) && is_user_logged_in() && current_user_can( 'edit_posts' ); // phpcs:ignore
	}
	return $on;
}

/**
 * Geçerli sayfanın bağlam anahtarı (sayfaya özel stiller bu anahtarla saklanır).
 *
 * @return string
 */
function ceb_context() {
	if ( is_front_page() ) {
		return 'front';
	}
	if ( is_home() ) {
		return 'blog';
	}
	if ( is_singular() ) {
		return 'post-' . get_queried_object_id();
	}
	if ( is_post_type_archive() ) {
		$pt = get_query_var( 'post_type' );
		return 'archive-' . sanitize_key( is_array( $pt ) ? reset( $pt ) : $pt );
	}
	if ( is_category() || is_tag() || is_tax() ) {
		return 'term-' . get_queried_object_id();
	}
	if ( is_search() ) {
		return 'search';
	}
	if ( is_404() ) {
		return '404';
	}
	return 'other';
}

/**
 * Bağlamın okunabilir adı.
 *
 * @param string $ctx Bağlam.
 * @return string
 */
function ceb_context_label( $ctx ) {
	if ( 'global' === $ctx ) {
		return 'Tüm site';
	}
	if ( 'front' === $ctx ) {
		return 'Ana sayfa';
	}
	if ( 'blog' === $ctx ) {
		return 'Blog';
	}
	if ( 0 === strpos( $ctx, 'post-' ) ) {
		$title = get_the_title( (int) substr( $ctx, 5 ) );
		return $title ? $title : $ctx;
	}
	if ( 0 === strpos( $ctx, 'term-' ) ) {
		$term = get_term( (int) substr( $ctx, 5 ) );
		return $term && ! is_wp_error( $term ) ? $term->name : $ctx;
	}
	$map = array( 'archive-ce_service' => 'Hizmetler', 'search' => 'Arama', '404' => '404 sayfası' );
	return $map[ $ctx ] ?? $ctx;
}

/* -------------------------------------------------------------------------
 * Ön yüz: kayıtlı canlı stiller
 * ---------------------------------------------------------------------- */

/**
 * Kayıtlı stil ağacı: [bağlam][cihaz][seçici][özellik] = değer.
 *
 * @return array
 */
function ceb_live_styles() {
	$s = get_option( CEB_LIVE_OPTION, array() );
	return is_array( $s ) ? $s : array();
}

/**
 * Stil kurallarını CSS'e çevirir (değerler kayıt sırasında temizlenmiştir).
 *
 * @param array $devices [cihaz][seçici][özellik] = değer.
 * @return string
 */
function ceb_compile_css( $devices ) {
	$media = array(
		'desktop' => '',
		'tablet'  => '@media (max-width: 1023px)',
		'mobile'  => '@media (max-width: 639px)',
	);
	$css = '';
	foreach ( $media as $device => $query ) {
		if ( empty( $devices[ $device ] ) || ! is_array( $devices[ $device ] ) ) {
			continue;
		}
		$block = '';
		foreach ( $devices[ $device ] as $selector => $props ) {
			$decl = array();
			foreach ( (array) $props as $prop => $value ) {
				if ( 'background-image' === $prop && preg_match( '/^att:(\d+)$/', (string) $value, $m ) ) {
					$url = wp_get_attachment_image_url( (int) $m[1], 'ce-hero' );
					if ( ! $url ) {
						continue;
					}
					$value = 'url("' . esc_url_raw( $url ) . '")';
				}
				$decl[] = $prop . ':' . $value . ' !important';
			}
			if ( $decl ) {
				$block .= $selector . '{' . implode( ';', $decl ) . '}';
			}
		}
		if ( $block ) {
			$css .= $query ? $query . '{' . $block . '}' : $block;
		}
	}
	return $css;
}

add_action( 'wp_head', 'ceb_print_live_css', 100 );
/**
 * Genel + bu sayfaya ait canlı stilleri basar (tema CSS'inden sonra).
 */
function ceb_print_live_css() {
	$styles = ceb_live_styles();
	$css    = ceb_compile_css( $styles['global'] ?? array() ) . ceb_compile_css( $styles[ ceb_context() ] ?? array() );
	if ( $css ) {
		echo '<style id="ceb-live-css">' . wp_strip_all_tags( $css ) . '</style>' . "\n"; // phpcs:ignore -- kayıtta doğrulanmış CSS.
	}
}

/* -------------------------------------------------------------------------
 * Çerçeve modu
 * ---------------------------------------------------------------------- */

add_action( 'init', 'ceb_frame_bootstrap', 1 );
/**
 * Çerçeve modunda gerekli kancalar.
 */
function ceb_frame_bootstrap() {
	if ( ! ceb_is_frame() ) {
		return;
	}
	add_filter( 'ce_live_frame', '__return_true' );
	add_filter( 'show_admin_bar', '__return_false' );
	add_filter( 'body_class', static fn( $c ) => array_merge( $c, array( 'ceb-frame' ) ) );
	add_filter( 'the_content', 'ceb_reset_block_counter', 8 );
	add_filter( 'render_block_data', 'ceb_tag_block_data', 10, 3 );
	add_filter( 'render_block', 'ceb_mark_block_html', 10, 2 );
	add_action( 'wp_enqueue_scripts', 'ceb_frame_assets', 50 );
	add_action(
		'template_redirect',
		static function () {
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			if ( ! defined( 'LSCACHE_NO_CACHE' ) ) {
				define( 'LSCACHE_NO_CACHE', true );
			}
			nocache_headers();
		},
		0
	);
	// Kaydedilmiş stiller çerçevede kabuk tarafından yönetilir.
	remove_action( 'wp_head', 'ceb_print_live_css', 100 );
}

/**
 * İçerik çizilmeden önce blok sayacını sıfırlar.
 *
 * @param string $content İçerik.
 * @return string
 */
function ceb_reset_block_counter( $content ) {
	$GLOBALS['ceb_block_counter'] = array();
	return $content;
}

/**
 * Ana içeriğin üst düzey bloklarına kalıcı anahtar ekler (k0, k1…; boşluk blokları sayılmaz).
 *
 * @param array         $block  Blok.
 * @param array         $source Kaynak.
 * @param WP_Block|null $parent Üst blok.
 * @return array
 */
function ceb_tag_block_data( $block, $source, $parent = null ) {
	unset( $source );
	if ( $parent || ! in_the_loop() ) {
		return $block;
	}
	if ( null === $block['blockName'] && '' === trim( (string) $block['innerHTML'] ) ) {
		return $block;
	}
	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return $block;
	}
	$GLOBALS['ceb_block_counter'][ $post_id ] = ( $GLOBALS['ceb_block_counter'][ $post_id ] ?? -1 ) + 1;
	$block['attrs']['__ceb_key']  = 'k' . $GLOBALS['ceb_block_counter'][ $post_id ];
	$block['attrs']['__ceb_post'] = $post_id;
	return $block;
}

/**
 * Üst düzey blokların ilk HTML etiketine editör niteliklerini ekler (yapı değişmez).
 *
 * @param string $html  Çıktı.
 * @param array  $block Blok.
 * @return string
 */
function ceb_mark_block_html( $html, $block ) {
	if ( empty( $block['attrs']['__ceb_key'] ) || '' === trim( $html ) ) {
		return $html;
	}
	$post = (int) $block['attrs']['__ceb_post'];
	$key  = $block['attrs']['__ceb_key'];
	$name = (string) $block['blockName'];
	$attr = ' data-ce-block="' . esc_attr( $post . ':' . $key ) . '" data-ce-bname="' . esc_attr( $name ) . '"';
	if ( in_array( $name, ceb_text_block_names(), true ) && current_user_can( 'edit_post', $post ) ) {
		$attr .= ' data-ce-edit="' . esc_attr( 'cblock:' . $post . ':' . $key ) . '" data-ce-format="html"';
	}
	if ( 'core/image' === $name ) {
		$attr .= ' data-ce-img="' . esc_attr( 'cimg:' . $post . ':' . $key ) . '"';
	}
	return preg_replace( '/^(\s*<[a-zA-Z0-9]+)/', '$1' . $attr, $html, 1 );
}

/**
 * Metni doğrudan düzenlenebilen çekirdek bloklar.
 *
 * @return string[]
 */
function ceb_text_block_names() {
	return array( 'core/paragraph', 'core/heading', 'core/list', 'core/quote' );
}

/**
 * Çerçeve dosyaları.
 */
function ceb_frame_assets() {
	$styles = ceb_live_styles();
	$ctx    = ceb_context();
	wp_enqueue_style( 'ceb-frame', CEB_URL . 'assets/live/frame.css', array(), CEB_VERSION . '.' . filemtime( CEB_DIR . 'assets/live/frame.css' ) );
	wp_enqueue_script( 'ceb-frame', CEB_URL . 'assets/live/frame.js', array(), CEB_VERSION . '.' . filemtime( CEB_DIR . 'assets/live/frame.js' ), true );
	wp_localize_script(
		'ceb-frame',
		'CEBFrameData',
		array(
			'context'      => $ctx,
			'contextLabel' => ceb_context_label( $ctx ),
			'postId'       => is_singular() ? get_queried_object_id() : 0,
			'editLink'     => is_singular() ? (string) get_edit_post_link( get_queried_object_id(), 'raw' ) : '',
			'saved'        => array(
				'global' => $styles['global'] ?? new stdClass(),
				'page'   => $styles[ $ctx ] ?? new stdClass(),
			),
			'title'        => wp_get_document_title(),
			'attachments'  => ceb_style_attachments( array( $styles['global'] ?? array(), $styles[ $ctx ] ?? array() ) ),
		)
	);
}

/**
 * Kayıtlı stillerde arka plan olarak kullanılan görsellerin URL'leri.
 *
 * @param array $trees Stil ağaçları.
 * @return array<int,string>
 */
function ceb_style_attachments( $trees ) {
	$out = array();
	array_walk_recursive(
		$trees,
		static function ( $value ) use ( &$out ) {
			if ( is_string( $value ) && preg_match( '/^att:(\d+)$/', $value, $m ) ) {
				$url = wp_get_attachment_image_url( (int) $m[1], 'ce-hero' );
				if ( $url ) {
					$out[ (int) $m[1] ] = $url;
				}
			}
		}
	);
	return (object) $out;
}

/* -------------------------------------------------------------------------
 * Giriş noktaları
 * ---------------------------------------------------------------------- */

/**
 * Canlı editör URL'si.
 *
 * @param string $url Düzenlenecek sayfa.
 * @return string
 */
function ceb_live_url( $url ) {
	return add_query_arg( array( 'page' => 'ceb-live', 'url' => rawurlencode( $url ) ), admin_url( 'admin.php' ) );
}

add_action( 'admin_bar_menu', 'ceb_live_admin_bar', 80 );
/**
 * Ön yüzde "Canlı Düzenle" düğmesi.
 *
 * @param WP_Admin_Bar $bar Çubuk.
 */
function ceb_live_admin_bar( $bar ) {
	if ( is_admin() || ! current_user_can( 'edit_posts' ) || ceb_is_frame() ) {
		return;
	}
	$scheme = is_ssl() ? 'https://' : 'http://';
	$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
	$uri    = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$bar->add_node(
		array(
			'id'    => 'ceb-live',
			'title' => '<span class="ab-icon dashicons dashicons-edit-page" aria-hidden="true"></span><span class="ab-label">Canlı Düzenle</span>',
			'href'  => ceb_live_url( $scheme . $host . $uri ),
			'meta'  => array( 'class' => 'ceb-live-node' ),
		)
	);
}

add_filter( 'page_row_actions', 'ceb_live_row_action', 10, 2 );
add_filter( 'post_row_actions', 'ceb_live_row_action', 10, 2 );
/**
 * Listelerde "Canlı Düzenle" bağlantısı.
 *
 * @param array   $actions İşlemler.
 * @param WP_Post $post    Kayıt.
 * @return array
 */
function ceb_live_row_action( $actions, $post ) {
	if ( in_array( $post->post_type, array( 'page', 'post', 'ce_service' ), true ) && current_user_can( 'edit_post', $post->ID ) ) {
		$link = get_permalink( $post );
		if ( $link ) {
			$actions['ceb_live'] = '<a href="' . esc_url( ceb_live_url( $link ) ) . '" style="font-weight:600">Canlı Düzenle</a>';
		}
	}
	return $actions;
}

add_action( 'admin_menu', 'ceb_live_menu', 21 );
/**
 * Kabuk sayfası (menüde "Canlı Editör").
 */
function ceb_live_menu() {
	add_submenu_page( 'ce-dashboard', 'Canlı Editör', 'Canlı Editör', 'edit_posts', 'ceb-live', 'ceb_render_live_shell' );
}

add_action( 'admin_enqueue_scripts', 'ceb_live_shell_assets' );
/**
 * Kabuk dosyaları.
 *
 * @param string $hook Sayfa.
 */
function ceb_live_shell_assets( $hook ) {
	if ( false === strpos( $hook, 'ceb-live' ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'ceb-shell', CEB_URL . 'assets/live/shell.css', array( 'dashicons' ), CEB_VERSION . '.' . filemtime( CEB_DIR . 'assets/live/shell.css' ) );
	wp_enqueue_script( 'ceb-shell', CEB_URL . 'assets/live/shell.js', array(), CEB_VERSION . '.' . filemtime( CEB_DIR . 'assets/live/shell.js' ), true );

	$pages = array();
	foreach ( get_posts( array( 'post_type' => array( 'page', 'ce_service', 'post' ), 'post_status' => array( 'publish', 'draft', 'private' ), 'numberposts' => 300, 'orderby' => array( 'post_type' => 'ASC', 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) as $p ) {
		if ( current_user_can( 'edit_post', $p->ID ) ) {
			$type    = get_post_type_object( $p->post_type );
			$pages[] = array( 'url' => get_permalink( $p ), 'title' => $p->post_title, 'group' => $type ? $type->labels->name : $p->post_type );
		}
	}
	$archive = get_post_type_archive_link( 'ce_service' );
	if ( $archive ) {
		array_unshift( $pages, array( 'url' => $archive, 'title' => 'Hizmetler (liste)', 'group' => 'Arşivler' ) );
	}
	array_unshift( $pages, array( 'url' => home_url( '/' ), 'title' => 'Ana Sayfa', 'group' => 'Site' ) );

	$url = isset( $_GET['url'] ) ? esc_url_raw( rawurldecode( wp_unslash( $_GET['url'] ) ) ) : home_url( '/' ); // phpcs:ignore
	if ( wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
		$url = home_url( '/' );
	}

	wp_localize_script(
		'ceb-shell',
		'CEBShellData',
		array(
			'ajax'        => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'ceb_live' ),
			'url'         => $url,
			'home'        => home_url( '/' ),
			'exit'        => $url,
			'pages'       => $pages,
			'canStyle'    => current_user_can( 'edit_theme_options' ),
			'canSettings' => current_user_can( 'ce_manage_settings' ),
			'fonts'       => function_exists( 'ce_font_choices' ) ? wp_list_pluck( ce_font_choices(), 1 ) : array(),
			'fontLabels'  => function_exists( 'ce_font_choices' ) ? wp_list_pluck( ce_font_choices(), 0 ) : array(),
			'palette'     => array( '#00AFC1', '#19C4D2', '#101820', '#0A1118', '#17212B', '#87939D', '#DCE3E7', '#F4F7F8', '#FFFFFF' ),
		)
	);
}

/**
 * Kabuk ekranı (arayüz shell.js tarafından kurulur).
 */
function ceb_render_live_shell() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	echo '<div id="ceb-live-app" class="ceb-live" aria-live="polite"><noscript>Canlı editör için JavaScript gereklidir.</noscript></div>';
}

add_filter( 'admin_body_class', static fn( $c ) => ( isset( $_GET['page'] ) && 'ceb-live' === $_GET['page'] ) ? $c . ' ceb-live-screen' : $c ); // phpcs:ignore
