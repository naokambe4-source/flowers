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
$df_bg   = $df_data['image'] ? df_img_url( $df_data['image'], 'df-banner' ) : '';
$df_bgm  = $df_data['image_m'] ? df_img_url( $df_data['image_m'], 'large' ) : '';
$df_sty  = $df_bg ? '--df-shophead:url(' . esc_url( $df_bg ) . ');' . ( $df_bgm ? '--df-shophead-m:url(' . esc_url( $df_bgm ) . ');' : '' ) . ( $df_data['pos'] ? '--df-shophead-pos:' . esc_attr( $df_data['pos'] ) . ';' : '' ) : '';

do_action( 'woocommerce_before_main_content' );
?>
<header class="df-shophead<?php echo $df_bg ? ' has-image' : ''; ?><?php echo $df_bgm ? ' has-mobile' : ''; ?>"<?php echo $df_sty ? ' style="' . $df_sty . '"' : ''; // phpcs:ignore ?>>
	<div class="df-container df-shophead__inner">
		<?php woocommerce_breadcrumb(); ?>
		<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
			<h1 class="df-shophead__title"><?php echo esc_html( $df_data['title'] ); ?></h1>
		<?php endif; ?>
		<?php if ( $df_data['subtitle'] ) : ?>
			<p class="df-shophead__sub"><?php echo esc_html( $df_data['subtitle'] ); ?></p>
		<?php endif; ?>
		<?php if ( $df_data['desc'] && ! is_paged() ) : ?>
			<div class="df-shophead__desc"><?php echo wp_kses_post( wpautop( $df_data['desc'] ) ); ?></div>
		<?php endif; ?>
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

<div class="df-container df-shop">
	<?php if ( woocommerce_product_loop() ) : ?>
		<?php
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		do_action( 'woocommerce_before_shop_loop' );
		?>
		<div class="df-shop__toolbar">
			<?php woocommerce_result_count(); ?>
			<?php woocommerce_catalog_ordering(); ?>
		</div>
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
		<?php do_action( 'woocommerce_no_products_found' ); ?>
	<?php endif; ?>
</div>
<?php
do_action( 'woocommerce_after_main_content' );
get_footer( 'shop' );
