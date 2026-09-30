<?php
/**
 * Arşiv: rehberler, kategori, cilt sorunu, cilt tipi, ürün türü, ürün rehberi, etiket, yazar.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();

$cr_obj    = get_queried_object();
$cr_is_tax = is_category() || is_tax() || is_tag();
$cr_title  = '';
$cr_hero   = '';
$cr_desc   = '';
$cr_img    = '';
$cr_eyebrow = '';
$cr_chips  = array();

if ( $cr_is_tax ) {
	$cr_title   = $cr_obj->name;
	$cr_hero    = get_term_meta( $cr_obj->term_id, 'cr_hero_title', true );
	$cr_desc    = term_description( $cr_obj );
	$cr_img     = cr_term_image( $cr_obj );
	$tax_labels = array(
		'category'     => 'Rehber kategorisi',
		'cilt_sorunu'  => 'Cilt problemi',
		'cilt_tipi'    => 'Cilt tipi',
		'urun_turu'    => 'Ürün türü',
		'icerik_grubu' => 'İçerik grubu',
		'post_tag'     => 'Etiket',
	);
	$cr_eyebrow = isset( $tax_labels[ $cr_obj->taxonomy ] ) ? $tax_labels[ $cr_obj->taxonomy ] : '';
	// Alt gezinme çipleri.
	$chip_tax  = $cr_obj->taxonomy;
	$chip_args = array( 'taxonomy' => $chip_tax, 'hide_empty' => false, 'parent' => $cr_obj->term_id );
	if ( 'category' === $cr_obj->taxonomy && 'cilt-problemleri' === $cr_obj->slug ) {
		$chip_args = array( 'taxonomy' => 'cilt_sorunu', 'hide_empty' => false, 'parent' => 0 );
	} elseif ( 'cilt_sorunu' === $cr_obj->taxonomy || 'urun_turu' === $cr_obj->taxonomy ) {
		$chip_args = array( 'taxonomy' => $cr_obj->taxonomy, 'hide_empty' => false, 'parent' => 0 );
	}
	$cr_chips = get_terms( $chip_args );
} elseif ( is_post_type_archive( 'urun_rehberi' ) ) {
	$cr_title   = cr_opt( 'products_archive_title' );
	$cr_desc    = cr_opt( 'products_archive_text' );
	$cr_eyebrow = 'Bağımsız ürün incelemeleri';
	$cr_chips   = get_terms( array( 'taxonomy' => 'urun_turu', 'hide_empty' => false, 'parent' => 0 ) );
} elseif ( is_author() ) {
	$cr_title   = get_the_author_meta( 'display_name', get_queried_object_id() );
	$cr_desc    = get_the_author_meta( 'description', get_queried_object_id() );
	$cr_eyebrow = get_user_meta( get_queried_object_id(), 'cr_job', true ) ? get_user_meta( get_queried_object_id(), 'cr_job', true ) : 'Yazar';
} elseif ( is_home() ) {
	$cr_title   = cr_opt( 'blog_title' );
	$cr_desc    = cr_opt( 'blog_text' );
	$cr_eyebrow = 'Tüm rehberler';
	$cr_chips   = get_categories( array( 'hide_empty' => true, 'parent' => 0, 'exclude' => array( (int) get_option( 'default_category' ) ) ) );
} else {
	$cr_title = wp_strip_all_tags( get_the_archive_title() );
	$cr_desc  = get_the_archive_description();
}
$cr_order = isset( $_GET['siralama'] ) ? sanitize_key( wp_unslash( $_GET['siralama'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
?>
<section class="cr-archive-hero<?php echo $cr_img ? ' has-image' : ''; ?>">
	<div class="cr-container">
		<?php cr_breadcrumb_html(); ?>
		<div class="cr-archive-hero__grid">
			<div class="cr-archive-hero__copy">
				<?php if ( $cr_eyebrow ) : ?>
					<p class="cr-eyebrow"><?php echo esc_html( $cr_eyebrow ); ?></p>
				<?php endif; ?>
				<h1 class="cr-archive-hero__title"><?php echo esc_html( $cr_title ); ?></h1>
				<?php if ( $cr_hero ) : ?>
					<p class="cr-archive-hero__sub"><?php echo esc_html( $cr_hero ); ?></p>
				<?php endif; ?>
				<?php if ( $cr_desc ) : ?>
					<div class="cr-archive-hero__desc"><?php echo wp_kses_post( wpautop( $cr_desc ) ); ?></div>
				<?php endif; ?>
			</div>
			<?php if ( $cr_img ) : ?>
				<div class="cr-archive-hero__media"><?php echo cr_img( $cr_img, 'cr-card', array( 'alt' => $cr_title, 'loading' => 'eager', 'fetchpriority' => 'high' ) ); // phpcs:ignore ?></div>
			<?php endif; ?>
		</div>
		<?php if ( $cr_chips && ! is_wp_error( $cr_chips ) ) : ?>
			<nav class="cr-subnav" aria-label="Alt başlıklar">
				<?php foreach ( $cr_chips as $c ) : ?>
					<?php $active = $cr_is_tax && $c->term_id === $cr_obj->term_id; ?>
					<a class="cr-subnav__item<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $c ) ); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>>
						<?php $ci = cr_term_image( $c ); ?>
						<?php if ( $ci ) : ?>
							<span class="cr-subnav__img"><?php echo cr_img( $ci, 'cr-thumb', array( 'alt' => '' ) ); // phpcs:ignore ?></span>
						<?php endif; ?>
						<span><?php echo esc_html( $c->name ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</div>
</section>

<section class="cr-section cr-section--tight cr-archive">
	<div class="cr-container">
		<div class="cr-toolbar">
			<form class="cr-toolbar__search" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
				<?php echo cr_icon( 'search', 20 ); // phpcs:ignore ?>
				<label class="screen-reader-text" for="cr-arch-q">Bu bölümde ara</label>
				<input id="cr-arch-q" type="search" name="s" placeholder="<?php echo esc_attr( $cr_title ? $cr_title . ' içinde ara…' : 'Ara…' ); ?>" data-live-filter>
				<?php if ( is_category() ) : ?>
					<input type="hidden" name="cat" value="<?php echo esc_attr( $cr_obj->term_id ); ?>">
				<?php elseif ( is_post_type_archive( 'urun_rehberi' ) || ( is_tax( 'urun_turu' ) ) ) : ?>
					<input type="hidden" name="tur" value="urun_rehberi">
				<?php endif; ?>
			</form>
			<div class="cr-toolbar__sort" role="group" aria-label="Sıralama">
				<a class="cr-filter<?php echo 'populer' !== $cr_order ? ' is-active' : ''; ?>" href="<?php echo esc_url( remove_query_arg( array( 'siralama', 'paged' ) ) ); ?>">En yeni</a>
				<a class="cr-filter<?php echo 'populer' === $cr_order ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'siralama', 'populer', remove_query_arg( 'paged' ) ) ); ?>">En çok okunan</a>
			</div>
		</div>

		<?php if ( have_posts() ) : ?>
			<div class="cr-archive__grid" data-filter-grid>
				<?php
				$i       = 0;
				$pattern = array( 'large', 'tall', 'tall' );
				while ( have_posts() ) :
					the_post();
					$variant = ( ! is_paged() && $i < 3 ) ? $pattern[ $i ] : 'plain';
					get_template_part( 'template-parts/cards/card', null, array( 'variant' => $variant, 'heading' => 'h2', 'eager' => $i < 1 ) );
					$i++;
				endwhile;
				?>
			</div>
			<p class="cr-empty" data-filter-empty hidden>Bu sayfada eşleşen içerik yok. Enter'a basarak tüm sitede ara.</p>
			<?php
			the_posts_pagination(
				array(
					'mid_size'           => 1,
					'prev_text'          => cr_icon( 'arrow-left', 18 ) . '<span class="screen-reader-text">Önceki</span>',
					'next_text'          => '<span class="screen-reader-text">Sonraki</span>' . cr_icon( 'arrow-right', 18 ),
					'class'              => 'cr-pagination',
					'before_page_number' => '<span class="screen-reader-text">Sayfa </span>',
				)
			);
			?>
		<?php else : ?>
			<div class="cr-empty-state">
				<?php echo cr_icon( 'leaf', 40 ); // phpcs:ignore ?>
				<p>Bu başlıkta henüz içerik yok. Yakında burada olacak.</p>
				<a class="cr-btn cr-btn--ghost" href="<?php echo esc_url( cr_url( cr_opt( 'bn_explore_url' ) ) ); ?>">Tüm rehberler</a>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
