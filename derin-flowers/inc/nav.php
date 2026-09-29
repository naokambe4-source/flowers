<?php
/**
 * Menü ikonları, menü yedeği ve kategori çekmecesi.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menü öğesine ikon alanı ekler (Görünüm → Menüler).
 *
 * @param int $item_id Menü öğesi.
 */
function df_menu_item_icon_field( $item_id ) {
	$value = get_post_meta( $item_id, '_df_icon', true );
	?>
	<p class="field-df-icon description description-wide">
		<label for="edit-menu-item-df-icon-<?php echo esc_attr( $item_id ); ?>">
			İkon (Derin Flowers)<br>
			<select id="edit-menu-item-df-icon-<?php echo esc_attr( $item_id ); ?>" class="widefat" name="df_menu_icon[<?php echo esc_attr( $item_id ); ?>]">
				<option value="">Otomatik (kategori ikonu) / yok</option>
				<?php foreach ( df_icon_choices( true ) as $key => $label ) : ?>
					<?php
					if ( '' === $key ) {
						continue;
					}
					?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
				<option value="none" <?php selected( $value, 'none' ); ?>>İkon gösterme</option>
			</select>
		</label>
	</p>
	<?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'df_menu_item_icon_field' );

/**
 * Menü ikonu kaydı.
 *
 * @param int $menu_id Menü.
 * @param int $item_id Öğe.
 */
function df_menu_item_icon_save( $menu_id, $item_id ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- menü kaydı çekirdek nonce ile korunur.
	if ( isset( $_POST['df_menu_icon'][ $item_id ] ) ) {
		$icon = sanitize_key( wp_unslash( $_POST['df_menu_icon'][ $item_id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $icon ) {
			update_post_meta( $item_id, '_df_icon', $icon );
		} else {
			delete_post_meta( $item_id, '_df_icon' );
		}
	}
}
add_action( 'wp_update_nav_menu_item', 'df_menu_item_icon_save', 10, 2 );

/**
 * Menü öğesinin ikonu.
 *
 * @param WP_Post $item Menü öğesi.
 * @return string
 */
function df_menu_item_icon_name( $item ) {
	$icon = get_post_meta( $item->ID, '_df_icon', true );
	if ( 'none' === $icon ) {
		return '';
	}
	if ( ! $icon && 'taxonomy' === $item->type && 'product_cat' === $item->object ) {
		$icon = get_term_meta( (int) $item->object_id, 'df_icon', true );
	}
	return (string) $icon;
}

/**
 * Menü başlığına ikon ekler (ana menü + mobil çekmece).
 *
 * @param string   $title Başlık.
 * @param WP_Post  $item  Öğe.
 * @param stdClass $args  Argümanlar.
 * @param int      $depth Derinlik.
 * @return string
 */
function df_menu_item_title( $title, $item, $args, $depth ) {
	if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location || ! df_opt( 'nav_icons' ) ) {
		return $title;
	}
	$icon = df_menu_item_icon_name( $item );
	$html = $icon ? df_icon( $icon, array( 'size' => $depth ? 18 : 17, 'class' => 'df-nav__icon' ) ) : '';
	return $html . '<span class="df-nav__label">' . $title . '</span>';
}
add_filter( 'nav_menu_item_title', 'df_menu_item_title', 10, 4 );

/**
 * Alt menüsü olan öğelere ok ekler.
 *
 * @param string   $output Çıktı.
 * @param WP_Post  $item   Öğe.
 * @param int      $depth  Derinlik.
 * @param stdClass $args   Argümanlar.
 * @return string
 */
function df_menu_item_caret( $output, $item, $depth, $args ) {
	if ( ! empty( $args->theme_location ) && 'primary' === $args->theme_location && in_array( 'menu-item-has-children', (array) $item->classes, true ) && 0 === $depth ) {
		$output .= '<button type="button" class="df-nav__caret" aria-expanded="false" aria-label="Alt menüyü aç">' . df_icon( 'chevron-down', array( 'size' => 14 ) ) . '</button>';
	}
	return $output;
}
add_filter( 'walker_nav_menu_start_el', 'df_menu_item_caret', 10, 4 );

/**
 * Menü atanmamışsa: üst ürün kategorileri + blog + iletişim.
 *
 * @param array $args Argümanlar.
 */
function df_menu_fallback( $args = array() ) {
	$items = array();
	if ( df_wc() ) {
		$items[] = array(
			'url'   => wc_get_page_permalink( 'shop' ),
			'label' => 'Tüm Ürünler',
			'icon'  => 'grid',
		);
		foreach ( df_top_product_cats( 9 ) as $term ) {
			$items[] = array(
				'url'   => get_term_link( $term ),
				'label' => $term->name,
				'icon'  => get_term_meta( $term->term_id, 'df_icon', true ),
			);
		}
	}
	$blog = (int) get_option( 'page_for_posts' );
	if ( $blog ) {
		$items[] = array(
			'url'   => get_permalink( $blog ),
			'label' => 'Blog',
			'icon'  => '',
		);
	}
	$contact = get_page_by_path( 'iletisim' );
	if ( $contact ) {
		$items[] = array(
			'url'   => get_permalink( $contact ),
			'label' => 'İletişim',
			'icon'  => '',
		);
	}
	$class = isset( $args['menu_class'] ) ? $args['menu_class'] : 'df-nav__list';
	echo '<ul class="' . esc_attr( $class ) . '">';
	foreach ( $items as $item ) {
		$icon = ( df_opt( 'nav_icons' ) && $item['icon'] ) ? df_icon( $item['icon'], array( 'size' => 17, 'class' => 'df-nav__icon' ) ) : '';
		printf(
			'<li class="menu-item"><a href="%s">%s<span class="df-nav__label">%s</span></a></li>',
			esc_url( $item['url'] ),
			$icon, // phpcs:ignore
			esc_html( $item['label'] )
		);
	}
	echo '</ul>';
}

/**
 * Üst seviye ürün kategorileri (Kategorisiz hariç).
 *
 * @param int $limit Sayı.
 * @return WP_Term[]
 */
function df_top_product_cats( $limit = 12 ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => false,
			'number'     => $limit + 1,
			'orderby'    => 'menu_order', // WooCommerce sürükle-bırak sırası.
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	return array_slice( $terms, 0, $limit );
}

/**
 * Kategori çekmecesi (header solundaki ikonlu kategori menüsü).
 */
function df_category_drawer_list() {
	$terms = df_top_product_cats( 24 );
	if ( ! $terms ) {
		echo '<p class="df-drawer__empty">Henüz ürün kategorisi yok.</p>';
		return;
	}
	echo '<ul class="df-catlist">';
	foreach ( $terms as $term ) {
		$icon     = get_term_meta( $term->term_id, 'df_icon', true );
		$children = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $term->term_id,
				'hide_empty' => false,
			)
		);
		$has      = ! is_wp_error( $children ) && $children;
		echo '<li class="df-catlist__item' . ( $has ? ' has-children' : '' ) . '">';
		printf(
			'<a class="df-catlist__link" href="%s"><span class="df-catlist__icon">%s</span><span class="df-catlist__name">%s</span>%s</a>',
			esc_url( get_term_link( $term ) ),
			df_icon( $icon ? $icon : 'bouquet', array( 'size' => 22 ) ), // phpcs:ignore
			esc_html( $term->name ),
			$term->count ? '<span class="df-catlist__count">' . (int) $term->count . '</span>' : ''
		);
		if ( $has ) {
			echo '<button type="button" class="df-catlist__toggle" aria-expanded="false" aria-label="' . esc_attr( $term->name ) . ' alt kategorileri">' . df_icon( 'chevron-down', array( 'size' => 16 ) ) . '</button>'; // phpcs:ignore
			echo '<ul class="df-catlist__sub">';
			foreach ( $children as $child ) {
				printf( '<li><a href="%s">%s</a></li>', esc_url( get_term_link( $child ) ), esc_html( $child->name ) );
			}
			echo '</ul>';
		}
		echo '</li>';
	}
	echo '</ul>';
}
