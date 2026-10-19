<?php
/**
 * Mağaza ve kategori sayfaları (tam genişlik).
 *
 * @package DerinFlowers
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$df_data = df_archive_header_data();

do_action( 'woocommerce_before_main_content' );
?>
<?php
$df_desc_h = $df_data['desc'] && ! is_paged() ? '<div class="df-catdesc__body">' . wp_kses_post( $df_data['desc'] ) . '</div>' : '';
?>
<?php
$df_term = is_product_taxonomy() ? get_queried_object() : null;
$df_img  = $df_data['image'];
if ( ! $df_img && $df_term instanceof WP_Term ) {
	// Kapak yoksa: kategori görseli, o da yoksa kategorideki ilk ürünün görseli.
	$df_img = absint( get_term_meta( $df_term->term_id, 'thumbnail_id', true ) );
	if ( ! $df_img ) {
		$df_first = wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'category' => array( $df_term->slug ), 'orderby' => 'menu_order', 'order' => 'ASC', 'return' => 'ids' ) );
		$df_img   = $df_first ? (int) get_post_thumbnail_id( $df_first[0] ) : 0;
	}
}
$df_o    = $df_img ? df_img_original( $df_img ) : null;
$df_om   = $df_data['image_m'] ? df_img_original( $df_data['image_m'] ) : null;
?>
<header class="df-cathero<?php echo $df_o ? ' has-image' : ''; ?>" style="--df-ch:<?php echo esc_attr( $df_term instanceof WP_Term ? df_cat_hero_color( $df_term ) : df_cat_hero_color( (object) array( 'term_id' => 0, 'slug' => '', 'name' => '' ), false ) ); ?>">
	<div class="df-cathero__glow" aria-hidden="true"></div>
	<?php if ( $df_o ) : ?>
		<picture class="df-cathero__media">
			<?php if ( $df_om ) : ?>
				<source media="(max-width: 700px)" srcset="<?php echo esc_url( $df_om[0] ); ?>">
			<?php endif; ?>
			<img src="<?php echo esc_url( $df_o[0] ); ?>" width="<?php echo (int) $df_o[1]; ?>" height="<?php echo (int) $df_o[2]; ?>" alt="<?php echo esc_attr( $df_data['title'] ); ?>" loading="eager" fetchpriority="high" decoding="async" style="object-position:<?php echo esc_attr( df_cat_hero_pos( $df_data['pos'] ) ); ?>">
		</picture>
	<?php endif; ?>
	<div class="df-container df-cathero__inner">
		<div class="df-cathero__text">
			<div class="screen-reader-text"><?php woocommerce_breadcrumb(); ?></div>
			<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
				<?php $df_tl = mb_strlen( $df_data['title'] ); ?>
				<h1 class="df-cathero__title df-cathero__title--<?php echo esc_attr( $df_tl <= 10 ? 's' : ( $df_tl <= 16 ? 'm' : ( $df_tl <= 24 ? 'l' : 'xl' ) ) ); ?>"><?php echo esc_html( $df_data['title'] ); ?></h1>
			<?php endif; ?>
			<?php $df_sub = trim( (string) df_opt( 'cat_hero_sub', '' ) ); ?>
			<?php if ( $df_sub ) : ?>
				<p class="df-cathero__sub"><?php echo esc_html( $df_sub ); ?></p>
			<?php endif; ?>
			<?php if ( $df_desc_h && $df_data['desc_top'] ) : ?>
				<div class="df-cathero__desc"><?php echo $df_desc_h; // phpcs:ignore ?></div>
			<?php endif; ?>
		</div>
	</div>
</header>
<?php if ( '' !== $df_data['promo']['text'] && ! is_paged() ) : ?>
	<div class="df-catpromo">
		<div class="df-container df-catpromo__inner">
			<p><?php echo esc_html( $df_data['promo']['text'] ); ?></p>
			<?php if ( $df_data['promo']['btn'] && $df_data['promo']['url'] ) : ?>
				<a class="df-catpromo__btn" href="<?php echo esc_url( $df_data['promo']['url'] ); ?>"><?php echo esc_html( $df_data['promo']['btn'] ); ?></a>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>

<?php if ( $df_data['children'] ) : ?>
	<nav class="df-chips" aria-label="Kategoriler">
		<div class="df-container">
			<ul class="df-chips__list df-scroller">
				<?php $df_current = get_queried_object_id(); ?>
				<?php foreach ( $df_data['children'] as $df_term ) : ?>
					<?php $df_icon = get_term_meta( $df_term->term_id, 'df_icon', true ); ?>
					<li>
						<a class="df-chip<?php echo (int) $df_current === (int) $df_term->term_id ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $df_term ) ); ?>">
							<?php echo $df_icon ? df_icon( $df_icon, array( 'size' => 18 ) ) : ''; // phpcs:ignore ?>
							<span><?php echo esc_html( $df_term->name ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</nav>
<?php endif; ?>

<div class="df-container df-shop" id="df-urunler">
	<?php if ( woocommerce_product_loop() ) : ?>
		<?php
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		do_action( 'woocommerce_before_shop_loop' );
		?>
		<div class="df-shop__toolbar">
			<p class="df-shop__count"><?php echo esc_html( number_format_i18n( (int) wc_get_loop_prop( 'total' ) ) . ' ürün' ); ?></p>
			<div class="df-shop__actions">
				<?php df_shop_filter_button(); ?>
				<?php woocommerce_catalog_ordering(); ?>
			</div>
		</div>
		<?php df_shop_filters(); ?>
		<?php
		woocommerce_product_loop_start();
		if ( wc_get_loop_prop( 'total' ) ) {
			while ( have_posts() ) {
				the_post();
				do_action( 'woocommerce_shop_loop' );
				wc_get_template_part( 'content', 'product' );
			}
		}
		woocommerce_product_loop_end();
		do_action( 'woocommerce_after_shop_loop' );
		?>
	<?php else : ?>
		<?php df_shop_filters(); ?>
		<?php do_action( 'woocommerce_no_products_found' ); ?>
	<?php endif; ?>
</div>
<?php if ( $df_desc_h && ! $df_data['desc_top'] ) : ?>
	<section class="df-catdesc" aria-label="<?php echo esc_attr( $df_data['title'] ); ?> hakkında">
		<div class="df-container df-container--narrow">
			<div class="df-catdesc__wrap" data-df-catdesc><?php echo $df_desc_h; // phpcs:ignore ?></div>
			<button type="button" class="df-catdesc__more" data-df-catdesc-more hidden>Devamını oku</button>
		</div>
	</section>
	<script>
	( function () {
		var w = document.querySelector( '[data-df-catdesc]' ), b = document.querySelector( '[data-df-catdesc-more]' );
		if ( ! w || ! b ) { return; }
		if ( w.scrollHeight > w.clientHeight + 20 ) {
			b.hidden = false;
			b.addEventListener( 'click', function () {
				var open = w.classList.toggle( 'is-open' );
				b.textContent = open ? 'Daha az göster' : 'Devamını oku';
			} );
		} else {
			w.classList.add( 'is-open' );
		}
	}() );
	</script>
<?php endif; ?>
<?php
do_action( 'woocommerce_after_main_content' );
get_footer( 'shop' );
