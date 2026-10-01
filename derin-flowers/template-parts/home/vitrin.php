<?php
/**
 * Ana sayfa — Vitrin Koleksiyonu: seçilen kategoriden ürünler; ilk satırdan sonra
 * üç tanıtım bannerı, ardından kalan ürünler. Kartlarda "İncele" düğmesi.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

if ( ! df_wc() ) {
	return;
}
$cat      = absint( df_opt( 'vit_cat' ) );
$limit    = max( 4, min( 24, (int) df_opt( 'vit_count', 12 ) ) );
$products = df_query_products( $cat ? 'category' : 'newest', array( 'cat' => $cat, 'limit' => $limit ) );
if ( ! $products ) {
	return;
}
$cols   = max( 3, min( 5, (int) df_opt( 'vit_cols', 4 ) ) );
$promos = array();
foreach ( df_opt( 'vit_promos_on', 1 ) ? (array) df_opt( 'vit_promos', array() ) : array() as $i => $row ) {
	if ( is_array( $row ) && ( ! empty( $row['image'] ) || df_live() ) && ! empty( $row['title'] ) ) {
		$row['_i'] = $i;
		$promos[]  = $row;
	}
}
$term    = $cat ? get_term( $cat, 'product_cat' ) : null;
$all_url = ( $term && ! is_wp_error( $term ) ) ? get_term_link( $term ) : wc_get_page_permalink( 'shop' );
$first   = array_slice( $products, 0, $cols );
$rest    = array_slice( $products, $cols );
?>
<section data-df-sec="vitrin" class="df-section df-vitrin df-vitrin--r<?php echo esc_attr( sanitize_key( df_opt( 'vit_ratio', '1-1' ) ) ); ?> df-vitrin--c<?php echo (int) df_opt( 'vit_cols', 4 ); ?>" aria-labelledby="df-vit-title">
	<div class="df-container">
		<header class="df-vitrin__head">
			<h2 id="df-vit-title"<?php echo df_e( 'vit_title' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'vit_title', 'Vitrin Koleksiyonu' ) ); ?></h2>
			<a class="df-link" href="<?php echo esc_url( $all_url ); ?>"><span<?php echo df_e( 'vit_link' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'vit_link', 'Tüm ürünleri gör' ) ); ?></span><?php df_the_icon( 'arrow-right', array( 'size' => 14 ) ); ?></a>
		</header>
		<div class="df-vitrin__grid">
			<?php foreach ( $first as $p ) : ?>
				<?php df_product_card( $p, array( 'variant' => 'vitrin' ) ); ?>
			<?php endforeach; ?>
		</div>
		<?php if ( $promos ) : ?>
			<div class="df-vitrin__promos df-vitrin__promos--<?php echo count( $promos ); ?>">
				<?php foreach ( $promos as $row ) : ?>
					<?php $k = 'vit_promos.' . $row['_i']; ?>
					<a class="df-promo" href="<?php echo esc_url( ! empty( $row['url'] ) ? df_url( $row['url'] ) : $all_url ); ?>">
						<span class="df-promo__media"<?php echo df_i( $k . '.image' ); // phpcs:ignore ?>><?php echo df_image( isset( $row['image'] ) ? $row['image'] : 0, 'large', array( 'sizes' => '(max-width: 900px) 100vw, 34vw', 'alt' => '' ), 'Banner görseli' ); // phpcs:ignore ?></span>
						<span class="df-promo__body">
							<span class="df-promo__title"<?php echo df_e( $k . '.title' ); // phpcs:ignore ?>><?php echo df_nl2br( $row['title'] ); // phpcs:ignore ?></span>
							<span class="df-promo__btn"><?php echo esc_html( ! empty( $row['btn'] ) ? $row['btn'] : 'Keşfet' ); ?><?php df_the_icon( 'arrow-right', array( 'size' => 14 ) ); ?></span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( $rest ) : ?>
			<div class="df-vitrin__grid">
				<?php foreach ( $rest as $p ) : ?>
					<?php df_product_card( $p, array( 'variant' => 'vitrin' ) ); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
