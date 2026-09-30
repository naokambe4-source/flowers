<?php
/**
 * Menüler: ana menü (mega menü destekli), yedek menüler, footer.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Varsayılan ana menü öğeleri (menü atanmamışsa).
 *
 * @return array
 */
function cr_default_menu_items() {
	$cat = function ( $slug, $fallback ) {
		$t = get_category_by_slug( $slug );
		return $t ? get_category_link( $t ) : home_url( $fallback );
	};
	return array(
		array( 'title' => 'Ana Sayfa', 'url' => home_url( '/' ) ),
		array( 'title' => 'Cilt Yapısı', 'url' => $cat( 'cilt-yapisi', '/kategori/cilt-yapisi/' ) ),
		array( 'title' => 'Cilt Problemleri', 'url' => $cat( 'cilt-problemleri', '/kategori/cilt-problemleri/' ), 'mega' => true ),
		array( 'title' => 'Cilt Bakım Rutini', 'url' => $cat( 'cilt-bakim-rutini', '/kategori/cilt-bakim-rutini/' ) ),
		array( 'title' => 'İçerikler', 'url' => get_post_type_archive_link( 'icerik' ) ),
		array( 'title' => 'Ürün Rehberi', 'url' => get_post_type_archive_link( 'urun_rehberi' ) ),
	);
}

/**
 * Menü öğesi mega menü mü (Cilt Problemleri)?
 *
 * @param string $url Adres.
 * @return bool
 */
function cr_is_mega_url( $url ) {
	return cr_opt( 'mega_problems' ) && false !== strpos( (string) $url, 'cilt-problemleri' );
}

/**
 * Mega menü paneli: cilt sorunları görselli.
 *
 * @return string
 */
function cr_mega_panel() {
	static $html = null;
	if ( null !== $html ) {
		return $html;
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'cilt_sorunu',
			'hide_empty' => false,
			'parent'     => 0,
			'number'     => 8,
		)
	);
	$items = array();
	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			$items[] = array(
				'title' => $t->name,
				'text'  => $t->description,
				'image' => cr_term_image( $t ),
				'url'   => get_term_link( $t ),
			);
		}
	} else {
		$items = (array) cr_opt( 'problems_items' );
	}
	if ( ! $items ) {
		$html = '';
		return $html;
	}
	ob_start();
	?>
	<div class="cr-mega" role="group" aria-label="Cilt problemleri">
		<div class="cr-mega__inner">
			<div class="cr-mega__intro">
				<span class="cr-eyebrow">Cilt problemleri</span>
				<p class="cr-mega__title">Cildin verdiği sinyalleri anlamaya başla.</p>
				<?php
				$pc = get_category_by_slug( 'cilt-problemleri' );
				if ( $pc ) :
					?>
					<a class="cr-link" href="<?php echo esc_url( get_category_link( $pc ) ); ?>">Tüm rehberler <?php echo cr_icon( 'arrow-right', 16 ); // phpcs:ignore ?></a>
				<?php endif; ?>
			</div>
			<ul class="cr-mega__grid">
				<?php foreach ( array_slice( $items, 0, 8 ) as $it ) : ?>
					<li>
						<a href="<?php echo esc_url( cr_url( $it['url'] ) ); ?>" class="cr-mega__item">
							<span class="cr-mega__img"><?php echo cr_img( $it['image'], 'cr-thumb', array( 'alt' => '' ) ); // phpcs:ignore ?></span>
							<span>
								<strong><?php echo esc_html( $it['title'] ); ?></strong>
								<?php if ( ! empty( $it['text'] ) ) : ?>
									<small><?php echo esc_html( wp_trim_words( $it['text'], 7, '…' ) ); ?></small>
								<?php endif; ?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php
	$html = ob_get_clean();
	return $html;
}

/**
 * Ana menü yürüteci.
 */
class CR_Walker extends Walker_Nav_Menu {

	/**
	 * Alt liste başlangıcı.
	 *
	 * @param string   $output Çıktı.
	 * @param int      $depth  Derinlik.
	 * @param stdClass $args   Argümanlar.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '<div class="cr-dropdown"><ul class="cr-dropdown__list">';
	}

	/**
	 * Alt liste sonu.
	 *
	 * @param string   $output Çıktı.
	 * @param int      $depth  Derinlik.
	 * @param stdClass $args   Argümanlar.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul></div>';
	}

	/**
	 * Öğe.
	 *
	 * @param string   $output Çıktı.
	 * @param WP_Post  $item   Öğe.
	 * @param int      $depth  Derinlik.
	 * @param stdClass $args   Argümanlar.
	 * @param int      $id     Kimlik.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes  = empty( $item->classes ) ? array() : (array) $item->classes;
		$has_sub  = in_array( 'menu-item-has-children', $classes, true );
		$mega     = 0 === $depth && ! $has_sub && cr_is_mega_url( $item->url );
		$current  = array_intersect( $classes, array( 'current-menu-item', 'current-menu-ancestor', 'current-menu-parent', 'current-category-ancestor' ) );
		$li_class = 'cr-nav__item' . ( ( $has_sub || $mega ) ? ' has-panel' : '' ) . ( $current ? ' is-current' : '' ) . ( $mega ? ' has-mega' : '' );

		$output .= '<li class="' . esc_attr( $li_class ) . '">';
		$atts    = array(
			'href'         => $item->url,
			'class'        => 0 === $depth ? 'cr-nav__link' : 'cr-dropdown__link',
			'target'       => $item->target,
			'rel'          => $item->xfn,
			'aria-current' => $current && in_array( 'current-menu-item', $classes, true ) ? 'page' : '',
		);
		$attr    = '';
		foreach ( $atts as $k => $v ) {
			if ( '' !== (string) $v ) {
				$attr .= ' ' . $k . '="' . ( 'href' === $k ? esc_url( $v ) : esc_attr( $v ) ) . '"';
			}
		}
		$title   = apply_filters( 'the_title', $item->title, $item->ID );
		$output .= '<a' . $attr . '>' . esc_html( $title ) . '</a>';
		if ( 0 === $depth && ( $has_sub || $mega ) ) {
			$output .= '<button class="cr-nav__toggle" type="button" aria-expanded="false" aria-label="' . esc_attr( $title . ' alt menüsü' ) . '">' . cr_icon( 'chevron-down', 16 ) . '</button>';
		}
		if ( $mega ) {
			$output .= cr_mega_panel();
		}
	}
}

/**
 * Ana menüyü yazdırır.
 *
 * @param string $context desktop|drawer.
 */
function cr_primary_menu( $context = 'desktop' ) {
	$class = 'desktop' === $context ? 'cr-nav__list' : 'cr-drawer__list';
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => $class,
				'depth'          => 2,
				'walker'         => 'desktop' === $context ? new CR_Walker() : '',
				'fallback_cb'    => false,
			)
		);
		return;
	}
	$req = home_url( add_query_arg( array() ) );
	echo '<ul class="' . esc_attr( $class ) . '">';
	foreach ( cr_default_menu_items() as $it ) {
		$is_cur = untrailingslashit( $it['url'] ) === untrailingslashit( $req );
		$mega   = 'desktop' === $context && ! empty( $it['mega'] ) && cr_opt( 'mega_problems' );
		echo '<li class="cr-nav__item' . ( $mega ? ' has-panel has-mega' : '' ) . ( $is_cur ? ' is-current' : '' ) . '">';
		echo '<a class="' . ( 'desktop' === $context ? 'cr-nav__link' : '' ) . '" href="' . esc_url( $it['url'] ) . '"' . ( $is_cur ? ' aria-current="page"' : '' ) . '>' . esc_html( $it['title'] ) . '</a>';
		if ( $mega ) {
			echo '<button class="cr-nav__toggle" type="button" aria-expanded="false" aria-label="' . esc_attr( $it['title'] . ' alt menüsü' ) . '">' . cr_icon( 'chevron-down', 16 ) . '</button>'; // phpcs:ignore
			echo cr_mega_panel(); // phpcs:ignore
		}
		echo '</li>';
	}
	echo '</ul>';
}

/**
 * Footer kolonu menüsü (yedekli).
 *
 * @param string $location Konum.
 */
function cr_footer_menu( $location ) {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => 'cr-footer__list',
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		return;
	}
	$page = function ( $slug, $title ) {
		$p = get_page_by_path( $slug );
		return array( 'title' => $title, 'url' => $p ? get_permalink( $p ) : home_url( '/' . $slug . '/' ) );
	};
	$cat = function ( $slug, $title ) {
		$t = get_category_by_slug( $slug );
		return array( 'title' => $title, 'url' => $t ? get_category_link( $t ) : home_url( '/kategori/' . $slug . '/' ) );
	};
	$sets = array(
		'footer_1' => array(
			$cat( 'cilt-yapisi', 'Cilt Yapısı' ),
			$cat( 'cilt-problemleri', 'Cilt Problemleri' ),
			$cat( 'cilt-bakim-rutini', 'Rutinler' ),
			array( 'title' => 'İçerik Sözlüğü', 'url' => get_post_type_archive_link( 'icerik' ) ),
		),
		'footer_2' => array(
			$page( 'hakkimizda', 'Hakkımızda' ),
			$page( 'iletisim', 'İletişim' ),
			$page( 'yayin-ilkeleri', 'Yayın İlkeleri' ),
		),
		'footer_3' => array(
			$page( 'gizlilik', 'Gizlilik' ),
			$page( 'cerez-politikasi', 'Çerez Politikası' ),
			$page( 'kullanim-kosullari', 'Kullanım Koşulları' ),
		),
	);
	echo '<ul class="cr-footer__list">';
	foreach ( $sets[ $location ] as $it ) {
		echo '<li><a href="' . esc_url( $it['url'] ) . '">' . esc_html( $it['title'] ) . '</a></li>';
	}
	echo '</ul>';
}
