<?php
/**
 * WooCommerce genel düzenlemeleri, ürün kartı ve ürün sorguları.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

// Varsayılan WooCommerce stilleri yerine temanın kendi mağaza stilleri (assets/css/shop.css) kullanılır.
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

// Kenar çubuğu ve varsayılan sarmalayıcılar kullanılmaz; şablonlar tam genişlik.
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

/**
 * Sayfa başına ürün.
 *
 * @return int
 */
function df_loop_per_page() {
	// Ziyaretçinin seçtiği "Göster: 20 / 40 / 60".
	if ( isset( $_GET['per_page'] ) && in_array( (int) $_GET['per_page'], df_per_page_choices(), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return (int) $_GET['per_page']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	if ( is_product_taxonomy() ) {
		$n = absint( get_term_meta( get_queried_object_id(), 'df_per_page', true ) );
		if ( $n ) {
			return min( 100, $n );
		}
	}
	return absint( df_opt( 'shop_per_page', 20 ) );
}
add_filter( 'loop_shop_per_page', 'df_loop_per_page', 20 );

/**
 * Sütun sayısı.
 *
 * @return int
 */
function df_loop_columns() {
	return absint( df_opt( 'shop_columns', 4 ) );
}
add_filter( 'loop_shop_columns', 'df_loop_columns' );

/**
 * Breadcrumb görünümü.
 *
 * @param array $args Argümanlar.
 * @return array
 */
function df_breadcrumb_defaults( $args ) {
	$args['delimiter']   = '<span class="df-crumbs__sep" aria-hidden="true">/</span>';
	$args['wrap_before'] = '<nav class="df-crumbs" aria-label="Sayfa konumu">';
	$args['wrap_after']  = '</nav>';
	$args['home']        = 'Ana Sayfa';
	return $args;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'df_breadcrumb_defaults' );

/**
 * İndirim etiketi: yüzde.
 *
 * @param string     $html    HTML.
 * @param WP_Post    $post    Yazı.
 * @param WC_Product $product Ürün.
 * @return string
 */
function df_sale_flash( $html, $post, $product ) {
	$pct = df_sale_percent( $product );
	return '<span class="df-badge df-badge--sale">' . ( $pct ? '%' . (int) $pct . ' İndirim' : 'İndirim' ) . '</span>';
}
add_filter( 'woocommerce_sale_flash', 'df_sale_flash', 10, 3 );

/**
 * İndirim yüzdesi.
 *
 * @param WC_Product $product Ürün.
 * @return int
 */
function df_sale_percent( $product ) {
	if ( ! $product || ! $product->is_on_sale() ) {
		return 0;
	}
	if ( $product->is_type( 'variable' ) ) {
		$max = 0;
		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( $child && $child->is_on_sale() && (float) $child->get_regular_price() > 0 ) {
				$max = max( $max, ( (float) $child->get_regular_price() - (float) $child->get_sale_price() ) / (float) $child->get_regular_price() * 100 );
			}
		}
		return (int) round( $max );
	}
	$regular = (float) $product->get_regular_price();
	$sale    = (float) $product->get_sale_price();
	return $regular > 0 ? (int) round( ( $regular - $sale ) / $regular * 100 ) : 0;
}

/**
 * Liste "Sepete ekle" bağlantısı: hızlı ekleme kapalıysa ürün detayına yönlendirir.
 * (Bloklar ve eklentiler tarafından üretilen listeler de bu kurala uyar.)
 *
 * @param string     $html    HTML.
 * @param WC_Product $product Ürün.
 * @return string
 */
function df_loop_add_to_cart_link( $html, $product ) {
	if ( df_quick_add_allowed( $product ) ) {
		return $html;
	}
	return sprintf(
		'<a href="%s" class="button df-btn df-btn--outline df-view-product">%s</a>',
		esc_url( $product->get_permalink() ),
		esc_html( df_opt( 'card_btn_text', 'İncele' ) )
	);
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'df_loop_add_to_cart_link', 20, 2 );

/**
 * Hızlı sepete ekleme bu ürün için uygun mu?
 *
 * @param WC_Product $product Ürün.
 * @return bool
 */
function df_quick_add_allowed( $product ) {
	return df_opt( 'quick_add' ) && $product && $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() && $product->supports( 'ajax_add_to_cart' );
}

/**
 * Ürün kartı. Tüm listelerde (ana sayfa, mağaza, favoriler, benzer ürünler) aynı tasarım.
 *
 * @param WC_Product|int $product Ürün.
 * @param array          $args    Argümanlar.
 */
function df_product_card( $product, $args = array() ) {
	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
	if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
		return;
	}
	$args = wp_parse_args(
		$args,
		array(
			'tag'     => 'div',
			'loading' => 'lazy',
			'variant' => '',
		)
	);
	get_template_part(
		'template-parts/product/card',
		null,
		array(
			'product' => $product,
			'tag'     => $args['tag'],
			'loading' => $args['loading'],
			'variant' => $args['variant'],
		)
	);
}

/**
 * Ürünün ana kategorisi (Kategorisiz hariç).
 *
 * @param WC_Product $product Ürün.
 * @return WP_Term|null
 */
function df_product_primary_cat( $product ) {
	$ids = $product->get_category_ids();
	if ( ! $ids && $product->get_parent_id() ) {
		$parent = wc_get_product( $product->get_parent_id() );
		$ids    = $parent ? $parent->get_category_ids() : array();
	}
	$default = (int) get_option( 'default_product_cat' );
	foreach ( $ids as $id ) {
		if ( (int) $id === $default ) {
			continue;
		}
		$term = get_term( $id, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			return $term;
		}
	}
	return null;
}

/**
 * Ürün yeni mi?
 *
 * @param WC_Product $product Ürün.
 * @return bool
 */
function df_product_is_new( $product ) {
	$days = absint( df_opt( 'badge_new_days', 14 ) );
	if ( ! $days || ! $product->get_date_created() ) {
		return false;
	}
	return ( time() - $product->get_date_created()->getTimestamp() ) < $days * DAY_IN_SECONDS;
}

/**
 * Ana sayfa ürün sorgusu.
 *
 * @param string $source  manual|featured|category|newest|bestsellers|onsale|rating.
 * @param array  $args    ids, cat, limit, exclude.
 * @return WC_Product[]
 */
function df_query_products( $source, $args = array() ) {
	$args  = wp_parse_args(
		$args,
		array(
			'ids'     => array(),
			'cat'     => 0,
			'limit'   => 4,
			'exclude' => array(),
		)
	);
	$query = array(
		'status'     => 'publish',
		'limit'      => (int) $args['limit'],
		'visibility' => 'catalog',
		'exclude'    => array_map( 'absint', (array) $args['exclude'] ),
	);

	switch ( $source ) {
		case 'manual':
			$ids = array_values( array_diff( array_map( 'absint', (array) $args['ids'] ), $query['exclude'] ) );
			if ( ! $ids ) {
				return df_query_products( 'newest', $args );
			}
			$query['include'] = $ids;
			$query['orderby'] = 'include';
			unset( $query['exclude'] );
			break;
		case 'featured':
			$query['featured'] = true;
			break;
		case 'category':
			$term = $args['cat'] ? get_term( (int) $args['cat'], 'product_cat' ) : null;
			if ( $term && ! is_wp_error( $term ) ) {
				$query['category'] = array( $term->slug );
			}
			break;
		case 'bestsellers':
			$query['orderby']  = 'meta_value_num';
			$query['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
			$query['order']    = 'DESC';
			break;
		case 'onsale':
			$sale             = array_diff( wc_get_product_ids_on_sale(), $query['exclude'] );
			$query['include'] = $sale ? array_values( $sale ) : array( 0 );
			break;
		case 'rating':
			$query['orderby']  = 'meta_value_num';
			$query['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery
			$query['order']    = 'DESC';
			break;
		default:
			$query['orderby'] = 'date';
			$query['order']   = 'DESC';
	}

	$products = wc_get_products( apply_filters( 'df_query_products_args', $query, $source, $args ) );

	// Öne çıkan / kategori ürünleri satırı doldurmuyorsa en yenilerle tamamla (5'li satırda boşluk kalmasın).
	if ( in_array( $source, array( 'featured', 'category' ), true ) && count( $products ) < (int) $args['limit'] ) {
		$have  = array_map(
			function ( $p ) {
				return $p->get_id();
			},
			$products
		);
		$extra = df_query_products(
			'newest',
			array_merge(
				$args,
				array(
					'limit'   => (int) $args['limit'] - count( $products ),
					'exclude' => array_merge( (array) $args['exclude'], $have ),
				)
			)
		);
		$products = array_merge( $products, $extra );
	}
	return $products;
}

/**
 * Sepet sayfası: teslimat bilgilendirmesi.
 */
function df_cart_delivery_notice() {
	if ( ! df_opt( 'df_checkout_on' ) ) {
		return;
	}
	echo '<div class="df-cart-note">' . df_icon( 'truck', array( 'size' => 22 ) ) . '<p>Teslimat tarihi, saat aralığı, alıcı bilgileri ve çiçek notunuzu <strong>bir sonraki adımda</strong> seçeceksiniz. Teslimat ücreti bölgenize göre otomatik hesaplanır.</p></div>'; // phpcs:ignore
}
add_action( 'woocommerce_before_cart_table', 'df_cart_delivery_notice' );

/**
 * Sepet: çapraz satış sayısı.
 *
 * @return int
 */
function df_cross_sells_total() {
	return 4;
}
add_filter( 'woocommerce_cross_sells_total', 'df_cross_sells_total' );
add_filter(
	'woocommerce_cross_sells_columns',
	function () {
		return 4;
	}
);

/**
 * Sayfalama okları.
 *
 * @param array $args Argümanlar.
 * @return array
 */
function df_pagination_args( $args ) {
	$args['prev_text'] = df_icon( 'arrow-left', array( 'size' => 18 ) );
	$args['next_text'] = df_icon( 'arrow-right', array( 'size' => 18 ) );
	return $args;
}
add_filter( 'woocommerce_pagination_args', 'df_pagination_args' );

/**
 * Mini sepet çekmecesi (footer'da).
 */
function df_mini_cart_drawer() {
	if ( is_cart() || is_checkout() ) {
		return;
	}
	?>
	<div class="df-drawer df-drawer--right df-cart-drawer" id="df-cart-drawer" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="df-cart-drawer-title">
		<div class="df-drawer__panel">
			<div class="df-drawer__head">
				<h2 class="df-drawer__title" id="df-cart-drawer-title">Sepetim <span class="df-drawer-count">(<?php echo (int) ( WC()->cart ? WC()->cart->get_cart_contents_count() : 0 ); ?>)</span></h2>
				<button type="button" class="df-drawer__close" data-df-close aria-label="Kapat"><?php df_the_icon( 'close' ); ?></button>
			</div>
			<div class="df-drawer__body">
				<div class="widget_shopping_cart_content"><?php woocommerce_mini_cart(); ?></div>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'df_mini_cart_drawer', 8 );

/**
 * Mini sepet düğmeleri: "Sepeti görüntüle" ve "Ödeme" metinleri.
 */
remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );
remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_proceed_to_checkout', 20 );
add_action(
	'woocommerce_widget_shopping_cart_buttons',
	function () {
		echo '<a href="' . esc_url( wc_get_cart_url() ) . '" class="df-btn df-btn--outline wc-forward">Sepete Git</a>';
		echo '<a href="' . esc_url( wc_get_checkout_url() ) . '" class="df-btn df-btn--solid checkout wc-forward">Siparişi Tamamla</a>';
	}
);

/**
 * Kategori sayfası: kapak ve alt kategoriler için şablon bilgisi.
 *
 * @return array
 */
function df_archive_header_data() {
	$data = array(
		'title'    => woocommerce_page_title( false ),
		'desc'     => '',
		'subtitle' => '',
		'image'    => 0,
		'image_m'  => 0,
		'pos'      => '',
		'height'   => '',
		'overlay'  => false,
		'desc_top' => false,
		'promo'    => array( 'text' => '', 'btn' => '', 'url' => '' ),
		'children' => array(),
	);
	if ( is_product_taxonomy() ) {
		$term             = get_queried_object();
		$data['desc']     = term_description( $term );
		$data['subtitle'] = get_term_meta( $term->term_id, 'df_subtitle', true );
		$data['image']    = absint( get_term_meta( $term->term_id, 'df_banner', true ) );
		$data['image_m']  = absint( get_term_meta( $term->term_id, 'df_banner_mobile', true ) );
		$data['pos']      = (string) get_term_meta( $term->term_id, 'df_banner_pos', true );
		$data['height']   = (string) get_term_meta( $term->term_id, 'df_banner_h', true );
		$data['overlay']  = 'over' === get_term_meta( $term->term_id, 'df_banner_text', true );
		$data['desc_top'] = 'top' === get_term_meta( $term->term_id, 'df_desc_pos', true );
		$data['desc']     = df_md_lite( (string) $term->description );
		$h1               = trim( (string) get_term_meta( $term->term_id, 'df_h1', true ) );
		if ( '' !== $h1 ) {
			$data['title'] = $h1;
		}
		$data['promo']    = array(
			'text' => (string) get_term_meta( $term->term_id, 'df_promo_text', true ),
			'btn'  => (string) get_term_meta( $term->term_id, 'df_promo_btn', true ),
			'url'  => (string) get_term_meta( $term->term_id, 'df_promo_url', true ),
		);
		if ( 'product_cat' === $term->taxonomy ) {
			$children = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'parent'     => $term->term_id,
					'hide_empty' => true,
				)
			);
			if ( ! $children && $term->parent ) {
				$children = get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'parent'     => $term->parent,
						'hide_empty' => true,
					)
				);
			}
			$data['children'] = is_wp_error( $children ) ? array() : $children;
		}
	} elseif ( is_shop() ) {
		$shop_id      = wc_get_page_id( 'shop' );
		$data['desc'] = $shop_id > 0 ? get_post_field( 'post_excerpt', $shop_id ) : '';
		$top          = df_top_product_cats( 12 );
		$data['children'] = $top;
	}
	return $data;
}

/**
 * Kategori açıklamasındaki basit biçimleri HTML'e çevir: "## Başlık", "### Alt başlık",
 * **kalın**, "- madde", boş satırla paragraf. Zaten HTML ise dokunmaz.
 *
 * @param string $text Metin.
 * @return string
 */
function df_md_lite( $text ) {
	$text = trim( str_replace( "\r", '', (string) $text ) );
	if ( '' === $text ) {
		return '';
	}
	if ( preg_match( '/<(p|h[1-6]|ul|ol|div|br)\b/i', $text ) && ! preg_match( '/^#{2,4}\s/m', $text ) ) {
		return wp_kses_post( $text );
	}
	$html  = '';
	$list  = array();
	$para  = array();
	$flush = function () use ( &$html, &$list, &$para ) {
		if ( $para ) {
			$html .= '<p>' . implode( '<br>', $para ) . "</p>\n";
			$para  = array();
		}
		if ( $list ) {
			$html .= "<ul>\n<li>" . implode( "</li>\n<li>", $list ) . "</li>\n</ul>\n";
			$list  = array();
		}
	};
	$inline = function ( $s ) {
		$s = esc_html( $s );
		$s = preg_replace( '/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $s );
		return preg_replace( '/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/u', '<em>$1</em>', $s );
	};
	foreach ( explode( "\n", $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			$flush();
			continue;
		}
		if ( preg_match( '/^(#{2,4})\s*(.+?)\s*#*\s*$/u', $line, $m ) ) {
			$flush();
			$tag   = 2 === strlen( $m[1] ) ? 'h2' : 'h3';
			$html .= '<' . $tag . '>' . $inline( rtrim( $m[2], ' .' ) ) . '</' . $tag . ">\n";
			continue;
		}
		if ( preg_match( '/^[-*•]\s+(.+)$/u', $line, $m ) ) {
			if ( $para ) {
				$html .= '<p>' . implode( '<br>', $para ) . "</p>\n";
				$para  = array();
			}
			$list[] = $inline( $m[1] );
			continue;
		}
		if ( $list ) {
			$flush();
		}
		$para[] = $inline( $line );
	}
	$flush();
	return wp_kses_post( $html );
}

/**
 * "Göster" seçenekleri (varsayılan sayı dahil).
 *
 * @return int[]
 */
function df_per_page_choices() {
	$base = absint( df_opt( 'shop_per_page', 20 ) );
	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		$cat  = absint( get_term_meta( get_queried_object_id(), 'df_per_page', true ) );
		$base = $cat ? $cat : $base;
	}
	$list = array_unique( array( $base, 20, 40, 60 ) );
	sort( $list );
	return array_values( array_filter( $list ) );
}

/**
 * Fiyat aralıkları ("0-1000, 1000-2000, 3000+").
 *
 * @return array<int, array{min:float|null,max:float|null,label:string}>
 */
function df_price_ranges() {
	$out = array();
	foreach ( array_filter( array_map( 'trim', explode( ',', (string) df_opt( 'price_ranges', '0-1000, 1000-2000, 2000-3000, 3000+' ) ) ) ) as $r ) {
		if ( preg_match( '/^(\d+)\s*\+$/', $r, $m ) ) {
			$out[] = array( 'min' => (float) $m[1], 'max' => null, 'label' => wp_strip_all_tags( wc_price( $m[1], array( 'decimals' => 0 ) ) ) . ' ve üzeri' );
		} elseif ( preg_match( '/^(\d+)\s*-\s*(\d+)$/', $r, $m ) ) {
			$out[] = array( 'min' => (float) $m[1], 'max' => (float) $m[2], 'label' => ( (float) $m[1] > 0 ? number_format( (float) $m[1], 0, ',', '.' ) . ' – ' : '' ) . wp_strip_all_tags( wc_price( $m[2], array( 'decimals' => 0 ) ) ) . ( (float) $m[1] > 0 ? '' : ' altı' ) );
		}
	}
	return $out;
}

/**
 * Mağaza / kategori araç çubuğu: fiyat filtresi ve sayfada gösterilecek ürün sayısı.
 */
function df_shop_filters() {
	if ( ! df_opt( 'shop_price_filter', 1 ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$min    = isset( $_GET['min_price'] ) ? (float) $_GET['min_price'] : null;
	$max    = isset( $_GET['max_price'] ) ? (float) $_GET['max_price'] : null;
	$per    = isset( $_GET['per_page'] ) ? (int) $_GET['per_page'] : 0;
	// phpcs:enable
	$base   = remove_query_arg( array( 'min_price', 'max_price', 'paged', 'product-page' ) );
	$base   = preg_replace( '#/page/\d+/?#', '/', $base );
	$ranges = df_price_ranges();
	echo '<div class="df-filters">';
	if ( $ranges ) {
		echo '<div class="df-filters__price" role="group" aria-label="Fiyat aralığı"><span class="df-filters__label">Fiyat:</span>';
		echo '<a class="df-fchip' . ( null === $min && null === $max ? ' is-on' : '' ) . '" href="' . esc_url( $base ) . '">Tümü</a>';
		foreach ( $ranges as $r ) {
			$on   = $min === $r['min'] && $max === $r['max'];
			$args = array( 'min_price' => (int) $r['min'] );
			if ( null !== $r['max'] ) {
				$args['max_price'] = (int) $r['max'];
			}
			echo '<a class="df-fchip' . ( $on ? ' is-on' : '' ) . '" href="' . esc_url( add_query_arg( $args, $base ) ) . '" rel="nofollow">' . esc_html( $r['label'] ) . '</a>';
		}
		echo '</div>';
	}
	$choices = df_per_page_choices();
	if ( count( $choices ) > 1 ) {
		$cur = $per ? $per : (int) df_loop_per_page();
		echo '<div class="df-filters__per"><span class="df-filters__label">Göster:</span>';
		foreach ( $choices as $c ) {
			echo '<a class="df-fchip' . ( $c === $cur ? ' is-on' : '' ) . '" href="' . esc_url( add_query_arg( 'per_page', $c, remove_query_arg( array( 'paged' ), preg_replace( '#/page/\d+/?#', '/', remove_query_arg( 'per_page' ) ) ) ) ) . '" rel="nofollow">' . (int) $c . '</a>';
		}
		echo '</div>';
	}
	echo '</div>';
}
